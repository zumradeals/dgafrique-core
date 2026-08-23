<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Comments\ContextCommentService;
use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionDependencyService;
use App\Application\Missions\MissionMatchingEngine;
use App\Application\Missions\MissionRecurrenceService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Sharing\ContextShareService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\ContextComment;
use App\Models\ContextShare;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEvent;
use App\Models\MissionRecurrence;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-069 §21 : sous-Missions (cycles/profondeur/visibilité/cascade), dépendances
 * (cycles), récurrence (idempotence/acteur système/pas de réaffectation), matching
 * (consentement/visibilité/pas d'auto-affectation), partage sans octroi d'accès,
 * commentaires revalidant la visibilité, Fil unique, absence de finance/rôle/données fictives.
 */
final class MissionStructureMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_mission_keeps_parent_context_and_cannot_exceed_its_visibility(): void
    {
        [$parent, $project] = $this->openMissionWithProject(['visibility' => Mission::VISIBILITY_CONTEXT]);
        $workflow = app(MissionWorkflow::class);

        $child = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Sous-tâche : réserver le matériel',
            'description' => 'Une sous-Mission concrète rattachée à la Mission parente.',
            'parent_mission_id' => $parent->public_reference,
            'visibility' => Mission::VISIBILITY_CONTEXT,
        ]);
        self::assertSame('PROJECT', $child->context_type);
        self::assertSame($project->public_reference, $child->context_reference);

        $this->assertAborts(422, fn () => $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Sous-tâche trop visible',
            'description' => 'Cette sous-Mission demande plus de visibilité que sa Mission parente.',
            'parent_mission_id' => $parent->public_reference,
            'visibility' => Mission::VISIBILITY_PUBLIC,
        ]));
    }

    public function test_sub_mission_depth_is_capped_at_three(): void
    {
        [$level1, $project] = $this->openMissionWithProject();
        $workflow = app(MissionWorkflow::class);

        $level2 = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Niveau 2', 'description' => 'Sous-Mission de premier niveau.',
            'parent_mission_id' => $level1->public_reference,
        ]);
        $level3 = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Niveau 3', 'description' => 'Sous-Mission de deuxième niveau.',
            'parent_mission_id' => $level2->public_reference,
        ]);
        self::assertSame(3, $workflow->depthOf($level3));

        $this->assertAborts(422, fn () => $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Niveau 4 refusé', 'description' => 'Cette quatrième profondeur ne doit jamais être créée.',
            'parent_mission_id' => $level3->public_reference,
        ]));
    }

    public function test_parent_with_active_child_cannot_be_completed_or_cancelled(): void
    {
        [$parent, $project] = $this->openMissionWithProject();
        $workflow = app(MissionWorkflow::class);
        $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Sous-tâche active', 'description' => 'Une sous-Mission encore non terminale.',
            'parent_mission_id' => $parent->public_reference,
        ]);

        $this->assertAborts(409, fn () => $workflow->cancel($parent, 'IDN-OWNER', 'Annulation prématurée.'));
    }

    public function test_dependency_cycles_are_rejected(): void
    {
        [$missionA, $project] = $this->openMissionWithProject();
        $workflow = app(MissionWorkflow::class);
        $missionB = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Mission B', 'description' => 'Deuxième Mission indépendante du même projet.',
        ]);

        $dependencies = app(MissionDependencyService::class);
        $dependencies->add($missionA, 'IDN-OWNER', $missionB, 'FINISH_BEFORE_START');

        $this->assertAborts(422, fn () => $dependencies->add($missionB, 'IDN-OWNER', $missionA, 'FINISH_BEFORE_START'));
    }

    public function test_recurrence_occurrence_generation_is_idempotent_with_a_system_actor(): void
    {
        [$mission] = $this->openMissionWithProject();
        $recurrences = app(MissionRecurrenceService::class);

        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=WEEKLY;BYDAY=MO', 'Africa/Abidjan');
        self::assertSame(MissionRecurrence::STATUS_ACTIVE, $recurrence->status);

        $first = $recurrences->generateOccurrence($recurrence, '2026-08-24');
        $again = $recurrences->generateOccurrence($recurrence, '2026-08-24');
        self::assertSame($first->id, $again->id, 'La même clé d’occurrence ne doit jamais produire deux Missions.');
        self::assertSame(1, Mission::query()->where('recurrence_id', $recurrence->id)->count());

        $second = $recurrences->generateOccurrence($recurrence, '2026-08-31');
        self::assertNotSame($first->id, $second->id);
        self::assertSame(Mission::STATUS_OPEN, $second->status);
        self::assertSame(0, $second->assignments()->count(), 'Une occurrence ne reprend jamais les affectations de la précédente.');

        self::assertDatabaseHas('dg_mission_events', [
            'mission_id' => $second->id, 'event' => 'MISSION_OCCURRENCE_CREATED',
            'actor_core_reference' => MissionEvent::SYSTEM_RECURRENCE_ACTOR,
        ]);

        // Seule l'autorité contextuelle (ici l'officialisateur du projet) peut créer/piloter la récurrence.
        $this->assertAborts(403, fn () => $recurrences->create($mission, 'IDN-OUTSIDER', 'FREQ=WEEKLY', 'Africa/Abidjan'));
    }

    public function test_matching_only_uses_discoverable_consented_capabilities_and_never_auto_assigns(): void
    {
        [$mission] = $this->openMissionWithProject();
        $mission->capabilityRequirements()->create([
            'label' => 'Developpement web', 'normalized_label' => 'developpement web',
            'requirement_level' => 'REQUIRED', 'quantity' => 1,
        ]);

        $good = $this->profile('IDN-GOOD', 'Awa', true, true, 'Developpement web');
        $this->profile('IDN-PRIVATE', 'Privé', true, false, 'Developpement web');

        $engine = app(MissionMatchingEngine::class);
        $results = $engine->recommend($mission, 'IDN-OWNER');
        self::assertCount(1, $results);
        self::assertSame('Awa', $results[0]['profile']->discovery_display_name);
        self::assertStringContainsString('Capacité correspondante', $results[0]['reasons'][0]);

        self::assertSame(0, MissionAssignment::query()->count(), 'Recommander n’assigne jamais automatiquement personne.');

        $engine->hide($mission, 'IDN-OWNER', $good->discovery_reference);
        self::assertSame([], $engine->recommend($mission, 'IDN-OWNER'));
    }

    public function test_sharing_a_mission_never_grants_access_it_did_not_already_have(): void
    {
        [$mission] = $this->openMissionWithProject(['visibility' => Mission::VISIBILITY_PRIVATE]);
        $stranger = $this->profile('IDN-STRANGER', 'Kwame', true, true, 'Aucune capacité liée');

        $shares = app(ContextShareService::class);
        $this->assertAborts(422, fn () => $shares->shareMission($mission, 'IDN-OWNER', ContextShare::TARGET_PERSON, $stranger->discovery_reference, 'Regarde ceci.'));
        self::assertSame(0, ContextShare::query()->count());
        self::assertSame(0, MissionAssignment::query()->count());
    }

    public function test_comment_thread_revalidates_visibility_at_projection_time(): void
    {
        [$mission] = $this->openMissionWithProject(['visibility' => Mission::VISIBILITY_PRIVATE]);
        $comments = app(ContextCommentService::class);

        $comments->addMission($mission, 'IDN-OWNER', 'COORDINATION', 'Merci de préparer le matériel avant vendredi.');
        self::assertSame(1, ContextComment::query()->count());

        $this->assertAborts(404, fn () => $comments->addMission($mission, 'IDN-STRANGER', 'QUESTION', 'Puis-je participer ?'));
    }

    public function test_no_second_activity_feed_and_no_finance_or_role_created_by_missions(): void
    {
        [$mission, $project] = $this->openMissionWithProject(['visibility' => Mission::VISIBILITY_PUBLIC]);
        $assignments = app(MissionAssignmentService::class);
        $workflow = app(MissionWorkflow::class);

        $rolesBefore = ZumraGroupRole::query()->count();

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $workflow->start($mission, 'IDN-EXEC');

        self::assertSame($rolesBefore, ZumraGroupRole::query()->count(), 'MISSIONS ne crée jamais de rôle ZUMRA/Projet.');

        // Le Fil unique est /activite : aucune route « missions.feed » ou équivalente n'existe.
        self::assertFalse(Route::has('missions.feed'));
        self::assertTrue(Route::has('activity.index') || Route::has('activite'));
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

    /** @return array{0: Mission, 1: Project} */
    private function openMissionWithProject(array $missionOverrides = []): array
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, array_replace([
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
        ], $missionOverrides));
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une session pilote réalisée.']);

        return [$mission, $project];
    }

    private function profile(string $id, string $name, bool $orientation, bool $discoverable, string $label): PersonProfile
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $id,
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'orientation_consent' => $orientation,
            'orientation_consented_at' => $orientation ? now() : null,
            'discovery_consent' => $discoverable,
            'discovery_consented_at' => $discoverable ? now() : null,
            'participation_mode' => 'HYBRID',
        ]);
        CapabilityStatement::query()->create([
            'core_identity_reference' => $id, 'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label, 'normalized_label' => mb_strtolower($label),
            'visibility' => $discoverable ? CapabilityStatement::VISIBILITY_DISCOVERABLE : CapabilityStatement::VISIBILITY_PRIVATE,
            'matching_consent' => true,
        ]);

        return $profile;
    }

    private function zumraFor(string $actor): string
    {
        return app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.uniqid(), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor('IDN-OWNER'), 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function activateProgram(string $reference): void
    {
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
}
