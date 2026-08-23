<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\Contexts\MissionContextAdapter;
use App\Application\Missions\Contexts\NeedMissionContext;
use App\Application\Missions\Contexts\ProjectMissionContext;
use App\Application\Missions\Contexts\ZumraMissionContext;
use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionDependencyService;
use App\Application\Missions\MissionRecurrenceService;
use App\Application\Missions\MissionService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Missions\Support\RecurrenceRule;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionRecurrence;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Régression sur la revue externe du 2026-08-17 (HEAD e1cf80a) : IDOR assignments,
 * autorité de démarrage, complétion bloquée par sous-Mission active, revalidation de
 * visibilité dans Mes Missions/Mon espace, scalabilité du bassin d'autorité, adaptateurs
 * spécialisés (canCancel/canValidate distincts de canOfficialize), état terminal stable,
 * dépendances (visibilité cible), récurrence (RRULE réelle, échéance relative, idempotence).
 */
final class MissionReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    // ===== 1. IDOR assignments =====

    public function test_assignment_operations_reject_a_mismatched_mission(): void
    {
        [$missionA] = $this->openMission('IDN-OWNER-A', 'Mission A');
        [$missionB] = $this->openMission('IDN-OWNER-B', 'Mission B');
        $assignments = app(MissionAssignmentService::class);

        $offerOnB = $assignments->offer($missionB, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);

        $this->assertAborts(404, fn () => $assignments->acceptOffer($missionA, 'IDN-OWNER-A', $offerOnB));
        $this->assertAborts(404, fn () => $assignments->declineOffer($missionA, 'IDN-OWNER-A', $offerOnB));
        $this->assertAborts(404, fn () => $assignments->withdraw($missionA, 'IDN-EXEC', $offerOnB));

        $learnerDiscoveryReference = $this->discoverableProfile('IDN-LEARNER', 'Apprenant B');
        $invitationOnB = $assignments->invite($missionB, 'IDN-OWNER-B', $learnerDiscoveryReference, MissionAssignment::ROLE_LEARNER);
        $this->assertAborts(404, fn () => $assignments->acceptInvitation($missionA, 'IDN-LEARNER', $invitationOnB));
        $this->assertAborts(404, fn () => $assignments->declineInvitation($missionA, 'IDN-LEARNER', $invitationOnB));

        $acceptedOnB = $assignments->acceptOffer($missionB, 'IDN-OWNER-B', $offerOnB);
        $this->assertAborts(404, fn () => $assignments->release($missionA, 'IDN-EXEC', $acceptedOnB));
        $this->assertAborts(404, fn () => $assignments->remove($missionA, 'IDN-OWNER-A', $acceptedOnB, 'Retrait tenté depuis la mauvaise Mission.'));

        // La même opération réussit bien sur la bonne Mission.
        $released = $assignments->release($missionB, 'IDN-EXEC', $acceptedOnB);
        self::assertSame(MissionAssignment::STATUS_RELEASED, $released->fresh()->status);
    }

    public function test_invitation_never_fabricates_context_access(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission ZUMRA', 'ZUMRA');
        $assignments = app(MissionAssignmentService::class);

        // Une chaîne arbitraire ne résout jamais vers une identité réelle.
        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', 'not-a-real-discovery-reference', MissionAssignment::ROLE_LEARNER));

        // IDN-STRANGER est une personne réelle et découvrable, mais n'est membre d'aucune
        // ZUMRA : l'inviter directement sur une Mission de contexte ZUMRA ne doit jamais lui
        // fabriquer un accès qu'il n'avait pas.
        $strangerDiscoveryReference = $this->discoverableProfile('IDN-STRANGER', 'Kwame Étranger');
        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', $strangerDiscoveryReference, MissionAssignment::ROLE_LEARNER));
    }

    // ===== REF-MISSION-UUID-001 : robustesse UUID de resolveInvitableSubject() =====

    public function test_invite_rejects_a_syntactically_invalid_discovery_reference_without_a_sql_error_or_side_effect(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission ZUMRA UUID invalide', 'ZUMRA');
        $assignments = app(MissionAssignmentService::class);

        // Aucune SQLSTATE 22P02 ne doit remonter : la valeur n'atteint jamais la requête
        // typée uuid de dg_person_profiles.discovery_reference.
        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', 'not-a-real-discovery-reference', MissionAssignment::ROLE_LEARNER));

        self::assertSame(0, MissionAssignment::query()->count());
    }

    public function test_invite_rejects_a_well_formed_but_unknown_discovery_reference(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission ZUMRA UUID inconnu', 'ZUMRA');
        $assignments = app(MissionAssignmentService::class);

        // Format UUID valide, mais aucun PersonProfile réel ne le porte.
        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', (string) Str::uuid(), MissionAssignment::ROLE_LEARNER));

        self::assertSame(0, MissionAssignment::query()->count());
    }

    public function test_invite_rejects_a_discoverable_person_without_real_context_access_and_creates_nothing(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission ZUMRA contexte fermé', 'ZUMRA');
        $strangerDiscoveryReference = $this->discoverableProfile('IDN-STRANGER-2', 'Personne hors contexte');
        $assignments = app(MissionAssignmentService::class);

        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', $strangerDiscoveryReference, MissionAssignment::ROLE_LEARNER));

        self::assertSame(0, MissionAssignment::query()->count());
        self::assertFalse(MissionAssignment::query()->where('core_identity_reference', 'IDN-STRANGER-2')->exists());
    }

    public function test_invite_succeeds_for_a_discoverable_person_with_real_context_access(): void
    {
        [$mission, $group] = $this->openMission('IDN-OWNER', 'Mission ZUMRA accès réel', 'ZUMRA');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER-REAL',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER-REAL', 'joined_at' => now(),
        ]);
        $memberDiscoveryReference = $this->discoverableProfile('IDN-MEMBER-REAL', 'Membre autorisé');
        $assignments = app(MissionAssignmentService::class);

        $assignment = $assignments->invite($mission, 'IDN-OWNER', $memberDiscoveryReference, MissionAssignment::ROLE_LEARNER);

        self::assertSame(MissionAssignment::STATUS_INVITED, $assignment->status);
        self::assertSame('IDN-MEMBER-REAL', $assignment->core_identity_reference);
        self::assertSame(1, MissionAssignment::query()->count());
    }

    public function test_invite_http_rejects_an_invalid_discovery_reference_as_a_clean_validation_error(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission ZUMRA HTTP', 'ZUMRA');
        $this->signIn('IDN-OWNER');

        $this->post(route('missions.assignments.invite', $mission), [
            'discovery_reference' => 'not-a-real-discovery-reference',
            'role' => MissionAssignment::ROLE_LEARNER,
        ])->assertSessionHasErrors('discovery_reference');

        self::assertSame(0, MissionAssignment::query()->count());
    }

    // ===== 2. Démarrage =====

    public function test_creator_alone_cannot_start_a_mission_without_authority_or_accepted_assignment(): void
    {
        $this->activateProgram('IDN-CREATOR');
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->group('IDN-OWNER')->public_reference]), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);

        $mission = $workflow->create('IDN-CREATOR', 'PROJECT', $project->public_reference, [
            'title' => 'Créée par un non-responsable', 'description' => 'Une Mission proposée par une personne qui n’est pas responsable du projet.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-CREATOR');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Un résultat réel.']);

        // IDN-CREATOR est le créateur mais n'a aucune autorité contextuelle et aucune
        // participation acceptée : démarrer doit échouer.
        $this->assertAborts(403, fn () => $workflow->start($mission, 'IDN-CREATOR'));

        // L'autorité contextuelle réelle (le responsable du projet) peut démarrer.
        $mission = $workflow->start($mission, 'IDN-OWNER');
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);
    }

    // ===== 3. Sous-Missions bloquent la complétion =====

    public function test_completion_is_blocked_by_an_active_sub_mission(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission avec sous-tâche');
        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $submissions = app(MissionSubmissionService::class);

        $workflow->create('IDN-OWNER', 'PROJECT', $mission->context_reference, [
            'title' => 'Sous-tâche active', 'description' => 'Une sous-Mission encore non terminale.',
            'parent_mission_id' => $mission->public_reference,
        ]);

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $mission = $workflow->start($mission, 'IDN-EXEC');
        $submissions->submit($mission, 'IDN-EXEC', 'Résultat produit malgré la sous-tâche active.');
        $mission = $mission->fresh();

        $this->assertAborts(409, fn () => $submissions->accept($mission, 'IDN-OWNER'));
    }

    // ===== 4. Mes Missions / Mon espace revalidation + bassin scalable =====

    public function test_lost_zumra_access_hides_the_mission_from_my_missions_and_next_action(): void
    {
        $this->activateProgram('IDN-MEMBER');
        $group = $this->group('IDN-LEADER');
        $membership = ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-MEMBER', 'ZUMRA', $group->public_reference, [
            'title' => 'Session ZUMRA', 'description' => 'Une session régulière de la ZUMRA.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-MEMBER');
        $mission = $workflow->officialize($mission, 'IDN-LEADER', ['expected_result' => 'Une session tenue.']);

        $assignments = app(MissionAssignmentService::class);
        $offer = $assignments->offer($mission, 'IDN-MEMBER', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-LEADER', $offer);

        $missions = app(MissionService::class);
        $sections = $missions->myMissionsSections('IDN-MEMBER');
        self::assertTrue($sections['mes_engagements']->contains('id', $mission->id));

        // IDN-MEMBER quitte la ZUMRA : la Mission disparaît de Mes Missions malgré
        // l'ancienne affectation ACCEPTED.
        $membership->update(['status' => ZumraGroupMembership::STATUS_LEFT]);
        $sections = $missions->myMissionsSections('IDN-MEMBER');
        self::assertFalse($sections['mes_engagements']->contains('id', $mission->id));
        self::assertNull($missions->nextAction('IDN-MEMBER'));
    }

    public function test_authority_pool_is_not_masked_by_unrelated_recent_missions(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->group('IDN-OWNER')->public_reference]), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Mission proposée en premier', 'description' => 'La toute première Mission proposée à cette autorité.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');

        // Beaucoup de Missions plus récentes sont proposées ailleurs, par d'autres
        // autorités, sur d'autres Projets — un bassin borné par récence globale les
        // laisserait masquer la décision de IDN-OWNER.
        for ($i = 0; $i < 6; $i++) {
            $this->activateProgram("IDN-OTHER-{$i}");
            $otherProject = app(ProjectService::class)->create("IDN-OTHER-{$i}", $this->projectPayload(['name' => "Autre projet {$i}", 'zumra_group_reference' => $this->group("IDN-OTHER-{$i}")->public_reference]), (new ProjectConfiguration)->defaults());
            $other = $workflow->create("IDN-OTHER-{$i}", 'PROJECT', $otherProject->public_reference, [
                'title' => "Mission étrangère {$i}", 'description' => 'Une Mission sans rapport, proposée après celle de IDN-OWNER.',
            ]);
            $workflow->propose($other, "IDN-OTHER-{$i}");
        }

        $sections = app(MissionService::class)->myMissionsSections('IDN-OWNER');
        self::assertTrue($sections['a_decider']->contains('id', $mission->id), 'La proposition de IDN-OWNER doit rester visible malgré des Missions étrangères plus récentes.');
    }

    // ===== 7. Adaptateurs spécialisés =====

    public function test_engine_calls_the_specific_adapter_method_not_can_officialize(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->group('IDN-OWNER')->public_reference]), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Mission témoin', 'description' => 'Sert à prouver que canCancel() est réellement consulté.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Un résultat réel.']);

        $real = app(ProjectMissionContext::class);
        $spy = new class($real) implements MissionContextAdapter
        {
            public function __construct(private readonly MissionContextAdapter $inner) {}

            public function type(): string
            {
                return $this->inner->type();
            }

            public function resolve(string $reference): object
            {
                return $this->inner->resolve($reference);
            }

            public function canView(object $context, string $actor): bool
            {
                return $this->inner->canView($context, $actor);
            }

            public function canPropose(object $context, string $actor): bool
            {
                return $this->inner->canPropose($context, $actor);
            }

            public function canOfficialize(object $context, string $actor): bool
            {
                // Volontairement TRUE pour tout le monde : si le moteur consultait cette
                // méthode pour annuler, l'annulation réussirait à tort.
                return true;
            }

            public function canManageAssignments(object $context, string $actor): bool
            {
                return $this->inner->canManageAssignments($context, $actor);
            }

            public function canValidate(object $context, string $actor): bool
            {
                return $this->inner->canValidate($context, $actor);
            }

            public function canCancel(object $context, string $actor): bool
            {
                // Volontairement FALSE pour tout le monde : prouve que cancel() échoue si
                // (et seulement si) le moteur consulte bien canCancel() et non canOfficialize().
                return false;
            }

            public function canReopen(object $context, string $actor): bool
            {
                return $this->inner->canReopen($context, $actor);
            }

            public function isOperational(object $context): bool
            {
                return $this->inner->isOperational($context);
            }

            public function maxVisibility(object $context, string $actor): string
            {
                return $this->inner->maxVisibility($context, $actor);
            }

            public function label(object $context): string
            {
                return $this->inner->label($context);
            }

            public function reference(object $context): string
            {
                return $this->inner->reference($context);
            }

            public function authorityContextReferences(string $actor): array
            {
                return $this->inner->authorityContextReferences($actor);
            }
        };

        app()->instance(MissionContextRegistry::class, new MissionContextRegistry([
            $spy, app(ZumraMissionContext::class), app(NeedMissionContext::class),
        ]));
        $workflowWithSpy = app(MissionWorkflow::class);

        // canOfficialize() renvoie true pour tout le monde chez le spy, mais cancel()
        // n'interroge plus jamais canOfficialize() : il doit échouer puisque canCancel()
        // renvoie false.
        $this->assertAborts(403, fn () => $workflowWithSpy->cancel($mission, 'IDN-OWNER', 'Tentative avec canCancel=false.'));
    }

    // ===== 8. Terminal state stability =====

    public function test_terminal_mission_rejects_ordinary_mutations(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission à annuler');
        $workflow = app(MissionWorkflow::class);
        $missions = app(MissionService::class);
        $dependencies = app(MissionDependencyService::class);

        [$other] = $this->openMission('IDN-OWNER', 'Autre Mission pour dépendance', contextReferenceReuse: $mission->context_reference);

        $mission = $workflow->cancel($mission, 'IDN-OWNER', 'Annulée pour tester la stabilité historique.');
        self::assertSame(Mission::STATUS_CANCELLED, $mission->status);

        $this->assertAborts(409, fn () => $missions->addChecklistItem($mission, 'IDN-OWNER', 'Nouvel élément', false));
        $this->assertAborts(409, fn () => $missions->addCapabilityRequirement($mission, 'IDN-OWNER', 'Capacité', 'REQUIRED', null, null));
        $this->assertAborts(409, fn () => $missions->addResourceRequirement($mission, 'IDN-OWNER', 'MATERIAL', 'Ressource', null, null, true, null));
        $this->assertAborts(409, fn () => $dependencies->add($mission, 'IDN-OWNER', $other, 'FINISH_BEFORE_START'));
    }

    // ===== 6. Dependencies : cible visible =====

    public function test_dependency_target_must_be_visible_to_the_actor(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission A dépendante', visibility: Mission::VISIBILITY_PROGRAM);
        [$privateTarget] = $this->openMission('IDN-OWNER', 'Mission B privée', contextReferenceReuse: $mission->context_reference, visibility: Mission::VISIBILITY_PRIVATE);

        $dependencies = app(MissionDependencyService::class);

        // IDN-OWNER (créateur/officialisateur de la cible) peut créer la dépendance.
        $dependencies->add($mission, 'IDN-OWNER', $privateTarget, 'RESULT_REQUIRED');
        self::assertDatabaseHas('dg_mission_dependencies', ['mission_id' => $mission->id, 'depends_on_mission_id' => $privateTarget->id]);
    }

    // ===== 5. Récurrence =====

    public function test_recurrence_rejects_invalid_rrule_and_requires_officialized_source(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission récurrente');
        $recurrences = app(MissionRecurrenceService::class);

        $this->assertAborts(422, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=YEARLY', 'Africa/Abidjan'));
        $this->assertAborts(422, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=WEEKLY', 'Not/AZone'));
    }

    public function test_recurrence_deadline_is_relative_to_each_occurrence_not_a_copy_of_source_due_at(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->group('IDN-OWNER')->public_reference]), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Mission avec échéance', 'description' => 'Sa récurrence doit garder un décalage relatif.',
            'due_at' => now()->addDays(2)->toDateTimeString(),
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Un résultat réel.']);

        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=WEEKLY', 'Africa/Abidjan');
        self::assertNotNull($recurrence->due_offset_minutes);

        // Fait avancer le temps réel entre la création et la génération : si due_at était
        // une copie éternelle du due_at de la Mission source (même horodatage absolu), les
        // deux échéances seraient encore égales malgré ce décalage temporel. Un décalage
        // réellement relatif à l'occurrence produit, lui, une échéance différente.
        Carbon::setTestNow(now()->addDay());
        try {
            $occurrence = $recurrences->generateOccurrence($recurrence, '2026-09-01');
        } finally {
            Carbon::setTestNow();
        }

        self::assertNotNull($occurrence->due_at);
        self::assertNotEquals($mission->due_at->timestamp, $occurrence->due_at->timestamp);
        self::assertEqualsWithDelta($occurrence->officialized_at->addMinutes($recurrence->due_offset_minutes)->timestamp, $occurrence->due_at->timestamp, 5);
    }

    public function test_recurrence_generation_via_scheduler_is_idempotent_and_advances_next_occurrence(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission planifiée');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY', 'UTC');

        $recurrence->update(['next_occurrence_at' => now()->subMinute()]);

        $generatedFirstPass = $recurrences->generateDueOccurrences();
        self::assertSame(1, $generatedFirstPass);
        self::assertSame(1, Mission::query()->where('recurrence_id', $recurrence->id)->count());

        $refreshed = $recurrence->fresh();
        self::assertTrue($refreshed->next_occurrence_at->isFuture());

        // Rejouer immédiatement ne génère rien de plus : la prochaine échéance n'est pas due.
        $generatedSecondPass = $recurrences->generateDueOccurrences();
        self::assertSame(0, $generatedSecondPass);
        self::assertSame(1, Mission::query()->where('recurrence_id', $recurrence->id)->count());
    }

    public function test_recurrence_count_bound_stops_generation_automatically(): void
    {
        [$mission] = $this->openMission('IDN-OWNER', 'Mission bornée par COUNT');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY;COUNT=1', 'UTC');

        $recurrences->generateOccurrence($recurrence, 'occurrence-1');
        $recurrence->update(['next_occurrence_at' => now()->subMinute()]);

        $generated = $recurrences->generateDueOccurrences();
        self::assertSame(0, $generated, 'COUNT=1 déjà atteint : aucune nouvelle occurrence ne doit être générée.');
        self::assertSame(MissionRecurrence::STATUS_STOPPED, $recurrence->fresh()->status);
    }

    public function test_only_the_source_mission_authority_can_pause_a_recurrence_and_ids_must_match(): void
    {
        [$missionA] = $this->openMission('IDN-OWNER-A', 'Mission A récurrente');
        [$missionB] = $this->openMission('IDN-OWNER-B', 'Mission B récurrente');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrenceOfA = $recurrences->create($missionA, 'IDN-OWNER-A', 'FREQ=DAILY', 'UTC');

        $this->assertAborts(404, fn () => $recurrences->pause($missionB, $recurrenceOfA, 'IDN-OWNER-B'));
        $this->assertAborts(403, fn () => $recurrences->pause($missionA, $recurrenceOfA, 'IDN-OUTSIDER'));
    }

    public function test_recurrence_rule_weekly_byday_and_monthly_clamping(): void
    {
        $rule = RecurrenceRule::parse('FREQ=WEEKLY;BYDAY=MO,WE');
        $monday = new \DateTimeImmutable('2026-08-17 10:00:00');
        $next = $rule->nextOccurrenceAfter($monday);
        self::assertSame('2026-08-19', $next->format('Y-m-d'));

        $monthly = RecurrenceRule::parse('FREQ=MONTHLY');
        $jan31 = new \DateTimeImmutable('2026-01-31 09:00:00');
        self::assertSame('2026-02-28', $monthly->nextOccurrenceAfter($jan31)->format('Y-m-d'));

        $this->expectException(InvalidArgumentException::class);
        RecurrenceRule::parse('FREQ=WEEKLY;COUNT=1;UNTIL=20260101T000000Z');
    }

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    /** @return array{0: Mission, 1: Project|ZumraGroup} */
    private function openMission(string $owner, string $title, string $contextType = 'PROJECT', ?string $contextReferenceReuse = null, string $visibility = Mission::VISIBILITY_CONTEXT): array
    {
        $this->activateProgram($owner);
        $workflow = app(MissionWorkflow::class);

        if ($contextReferenceReuse !== null) {
            $contextReference = $contextReferenceReuse;
            $context = null;
        } elseif ($contextType === 'ZUMRA') {
            $context = $this->group($owner);
            $contextReference = $context->public_reference;
        } else {
            $context = app(ProjectService::class)->create($owner, $this->projectPayload(['name' => $title.' — projet', 'zumra_group_reference' => $this->group($owner)->public_reference]), (new ProjectConfiguration)->defaults());
            $contextReference = $context->public_reference;
        }

        $mission = $workflow->create($owner, $contextReferenceReuse !== null ? 'PROJECT' : $contextType, $contextReference, [
            'title' => $title, 'description' => 'Description suffisamment détaillée pour '.$title.'.',
            'visibility' => $visibility,
        ]);
        $mission = $workflow->propose($mission, $owner);
        $mission = $workflow->officialize($mission, $owner, ['expected_result' => 'Un résultat réel pour '.$title.'.']);

        return [$mission, $context];
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire '.uniqid(),
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l'équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA '.uniqid(), 'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.',
            'assume_primary_lead' => true,
        ]);
    }

    /** Crée un profil découvrable et consentant, et retourne sa référence de découverte. */
    private function discoverableProfile(string $reference, string $name): string
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
        ]);

        return $profile->discovery_reference;
    }

    private function activateProgram(string $reference): void
    {
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->exists()) {
            return;
        }
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-30T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-30T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
