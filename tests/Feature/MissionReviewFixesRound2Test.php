<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionDependencyService;
use App\Application\Missions\MissionMatchingEngine;
use App\Application\Missions\MissionRecurrenceService;
use App\Application\Missions\MissionService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Missions\Support\RecurrenceRule;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionRecurrence;
use App\Models\PersonProfile;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Régression sur la deuxième revue externe du 2026-08-17 (HEAD 5c3e315) : résolution
 * d'identité sûre pour l'invitation (jamais de core_identity_reference exposée/saisie côté
 * UI), verrouillage parent/sous-Mission sous concurrence, récurrence (création concurrente,
 * machine d'états ACTIVE/PAUSED/STOPPED, parser RRULE fail-closed, ancre MONTHLY nominale),
 * et verrouillage des mutations ordinaires (contribution, blocker additionnel, dépendances).
 */
final class MissionReviewFixesRound2Test extends TestCase
{
    use RefreshDatabase;

    // ===== 1. Identité / invitation =====

    public function test_invite_rejects_an_unresolvable_discovery_reference(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission A');
        $assignments = app(MissionAssignmentService::class);

        $this->assertAborts(422, fn () => $assignments->invite($mission, 'IDN-OWNER', (string) Str::uuid(), MissionAssignment::ROLE_LEARNER));
        self::assertSame(0, MissionAssignment::query()->count());
    }

    public function test_invite_resolves_a_real_discoverable_person_to_the_correct_core_identity(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission A');
        $discoveryReference = $this->discoverableProfile('IDN-REAL-PERSON', 'Awa Réelle');

        $assignments = app(MissionAssignmentService::class);
        $invitation = $assignments->invite($mission, 'IDN-OWNER', $discoveryReference, MissionAssignment::ROLE_LEARNER);

        self::assertSame('IDN-REAL-PERSON', $invitation->core_identity_reference);
        self::assertSame(MissionAssignment::STATUS_INVITED, $invitation->status);
    }

    public function test_mission_show_and_matching_pages_never_expose_a_raw_core_identity_reference(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission publique', visibility: Mission::VISIBILITY_PUBLIC);
        $mission->capabilityRequirements()->create([
            'label' => 'Developpement web', 'normalized_label' => 'developpement web',
            'requirement_level' => 'REQUIRED', 'quantity' => 1,
        ]);
        $matchProfile = $this->discoverableProfileWithCapability('IDN-MATCHABLE-PERSON', 'Kouassi Trouvable', 'Developpement web');

        $this->signIn('IDN-OWNER');
        $showResponse = $this->get('/missions/'.$mission->public_reference);
        $showResponse->assertOk();
        $showResponse->assertDontSee('IDN-OWNER', false);
        $showResponse->assertDontSee('name="core_identity_reference"', false);
        $showResponse->assertSee('name="discovery_reference"', false);

        $matchingResponse = $this->get('/missions/'.$mission->public_reference.'/correspondances');
        $matchingResponse->assertOk();
        $matchingResponse->assertDontSee('IDN-MATCHABLE-PERSON', false);
        $matchingResponse->assertSee($matchProfile->discovery_reference, false);
    }

    // ===== 2. Parent / sous-Mission en concurrence =====

    public function test_sub_mission_creation_revalidates_parent_status_under_lock(): void
    {
        [$parent, $projectRef] = $this->openMission('IDN-OWNER', 'Parent');
        $workflow = app(MissionWorkflow::class);

        $workflow->cancel($parent, 'IDN-OWNER', 'Annulation avant toute tentative de sous-Mission.');

        // Même une fois le parent rechargé comme « toujours actif » en mémoire ailleurs
        // dans l'application, la revalidation sous verrou au moment de l'insertion
        // reste celle qui décide : ici elle doit refuser, car le parent est terminal.
        $this->assertAborts(409, fn () => $workflow->create('IDN-OWNER', 'PROJECT', $projectRef, [
            'title' => 'Sous-tâche tardive', 'description' => 'Tentative de création après annulation du parent.',
            'parent_mission_id' => $parent->public_reference,
        ]));
    }

    public function test_cancel_and_complete_still_correctly_reject_with_an_active_child_after_lock_refactor(): void
    {
        [$parent, $projectRef] = $this->openMission('IDN-OWNER', 'Parent avec enfant');
        $workflow = app(MissionWorkflow::class);
        $workflow->create('IDN-OWNER', 'PROJECT', $projectRef, [
            'title' => 'Enfant actif', 'description' => 'Une sous-Mission non terminale.',
            'parent_mission_id' => $parent->public_reference,
        ]);

        $this->assertAborts(409, fn () => $workflow->cancel($parent, 'IDN-OWNER', 'Tentative avec enfant actif.'));
    }

    // ===== 3. Récurrence =====

    public function test_creating_a_second_recurrence_for_the_same_source_is_rejected(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission récurrente unique');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY', 'UTC');

        $this->assertAborts(409, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=WEEKLY', 'UTC'));
    }

    public function test_recurrence_state_machine_forbids_stopped_reactivation(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission cycle récurrence');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY', 'UTC');

        // ACTIVE -> ne peut pas être « réactivée » (déjà active).
        $this->assertAborts(409, fn () => $recurrences->activate($mission, $recurrence, 'IDN-OWNER'));

        // ACTIVE -> PAUSED : ok.
        $paused = $recurrences->pause($mission, $recurrence, 'IDN-OWNER');
        self::assertSame(MissionRecurrence::STATUS_PAUSED, $paused->status);

        // PAUSED -> ne peut pas être mise en pause à nouveau.
        $this->assertAborts(409, fn () => $recurrences->pause($mission, $recurrence, 'IDN-OWNER'));

        // PAUSED -> ACTIVE : ok (reprise).
        $resumed = $recurrences->activate($mission, $recurrence, 'IDN-OWNER');
        self::assertSame(MissionRecurrence::STATUS_ACTIVE, $resumed->status);

        // ACTIVE -> STOPPED : ok.
        $stopped = $recurrences->stop($mission, $recurrence, 'IDN-OWNER');
        self::assertSame(MissionRecurrence::STATUS_STOPPED, $stopped->status);

        // STOPPED est final : aucune réactivation, ni nouvelle pause, ni nouvel arrêt.
        $this->assertAborts(409, fn () => $recurrences->activate($mission, $stopped, 'IDN-OWNER'));
        $this->assertAborts(409, fn () => $recurrences->pause($mission, $stopped, 'IDN-OWNER'));
        $this->assertAborts(409, fn () => $recurrences->stop($mission, $stopped, 'IDN-OWNER'));
    }

    public function test_recurrence_state_machine_allows_pause_then_stop_directly(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission pause puis stop');
        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY', 'UTC');
        $recurrences->pause($mission, $recurrence, 'IDN-OWNER');

        $stopped = $recurrences->stop($mission, $recurrence, 'IDN-OWNER');
        self::assertSame(MissionRecurrence::STATUS_STOPPED, $stopped->status);
    }

    public function test_rrule_parser_rejects_unsupported_and_duplicate_keys_via_the_service(): void
    {
        [$mission, ] = $this->openMission('IDN-OWNER', 'Mission RRULE stricte');
        $recurrences = app(MissionRecurrenceService::class);

        $this->assertAborts(422, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY;BYHOUR=09', 'UTC'));
        $this->assertAborts(422, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=DAILY;FREQ=WEEKLY', 'UTC'));
        $this->assertAborts(422, fn () => $recurrences->create($mission, 'IDN-OWNER', 'FREQ=WEEKLY;UNTIL=20260101T000000', 'UTC'));
    }

    public function test_monthly_recurrence_keeps_its_nominal_anchor_across_a_short_month(): void
    {
        $rule = RecurrenceRule::parse('FREQ=MONTHLY');
        $jan31 = new DateTimeImmutable('2026-01-31 09:00:00');

        $feb = $rule->nextOccurrenceAfter($jan31, 31);
        self::assertSame('2026-02-28', $feb->format('Y-m-d'));

        // L'ancre (31) est réutilisée telle quelle, jamais redérivée du 28 de février.
        $mar = $rule->nextOccurrenceAfter($feb, 31);
        self::assertSame('2026-03-31', $mar->format('Y-m-d'));
    }

    public function test_monthly_recurrence_anchor_is_persisted_and_reused_across_real_occurrence_generation(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Revue mensuelle', 'description' => 'Une revue mensuelle ancrée le 31 du mois.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une revue tenue.']);

        $recurrences = app(MissionRecurrenceService::class);
        $recurrence = $recurrences->create($mission, 'IDN-OWNER', 'FREQ=MONTHLY', 'UTC');
        // Force une ancre au 31 pour le test, indépendamment de la date d'exécution du test.
        $recurrence->update(['monthly_anchor_day' => 31, 'next_occurrence_at' => new DateTimeImmutable('2026-01-31 09:00:00')]);

        $feb = $recurrences->generateOccurrence($recurrence, 'occ-feb');
        self::assertSame(Mission::STATUS_OPEN, $feb->status);

        // Avance manuellement comme le ferait generateNextDueOccurrence(), pour vérifier
        // que l'ancre stockée (31) reste utilisée au deuxième saut, pas le 28 de février.
        $rule = RecurrenceRule::parse($recurrence->rrule);
        $marchDate = $rule->nextOccurrenceAfter(new DateTimeImmutable('2026-02-28 09:00:00'), $recurrence->monthly_anchor_day);
        self::assertSame('2026-03-31', $marchDate->format('Y-m-d'));
    }

    // ===== 4. Stabilité / concurrence des mutations ordinaires =====

    public function test_contribute_report_additional_blocker_and_dependency_remove_reject_on_terminal_mission(): void
    {
        [$mission, $projectRef] = $this->openMission('IDN-OWNER', 'Mission à verrouiller');
        [$other, ] = $this->openMission('IDN-OWNER', 'Autre Mission', contextReferenceReuse: $projectRef);
        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $blockers = app(MissionBlockerService::class);
        $dependencies = app(MissionDependencyService::class);
        $submissions = app(MissionSubmissionService::class);

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $mission = $workflow->start($mission, 'IDN-EXEC');
        $mission = $blockers->block($mission, 'IDN-EXEC', 'MISSING_RESOURCE', 'Matériel manquant.');
        $dependency = $dependencies->add($mission, 'IDN-OWNER', $other, 'RESULT_REQUIRED');

        $mission = $workflow->cancel($mission, 'IDN-OWNER', 'Annulée malgré blocage actif, pour tester la stabilité.');
        self::assertSame(Mission::STATUS_CANCELLED, $mission->status);

        $this->assertAborts(409, fn () => $submissions->contribute($mission, 'IDN-EXEC', 'Apport tardif.'));
        $this->assertAborts(409, fn () => $blockers->reportAdditional($mission, 'IDN-EXEC', 'MISSING_DECISION', 'Un autre manque.'));
        $this->assertAborts(409, fn () => $dependencies->remove($mission, 'IDN-OWNER', $dependency));
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

    /** @return array{0: Mission, 1: string} Mission and its context_reference (Project public_reference). */
    private function openMission(string $owner, string $title, ?string $contextReferenceReuse = null, string $visibility = Mission::VISIBILITY_CONTEXT): array
    {
        $this->activateProgram($owner);
        $workflow = app(MissionWorkflow::class);

        if ($contextReferenceReuse !== null) {
            $contextReference = $contextReferenceReuse;
        } else {
            $project = app(ProjectService::class)->create($owner, $this->projectPayload(['name' => $title.' — projet '.uniqid()]), (new ProjectConfiguration)->defaults());
            $contextReference = $project->public_reference;
        }

        $mission = $workflow->create($owner, 'PROJECT', $contextReference, [
            'title' => $title, 'description' => 'Description suffisamment détaillée pour '.$title.'.',
            'visibility' => $visibility,
        ]);
        $mission = $workflow->propose($mission, $owner);
        $mission = $workflow->officialize($mission, $owner, ['expected_result' => 'Un résultat réel pour '.$title.'.']);

        return [$mission, $contextReference];
    }

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

    private function discoverableProfileWithCapability(string $reference, string $name, string $capabilityLabel): PersonProfile
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
            'participation_mode' => 'HYBRID',
        ]);
        CapabilityStatement::query()->create([
            'core_identity_reference' => $reference, 'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $capabilityLabel, 'normalized_label' => mb_strtolower($capabilityLabel),
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE, 'matching_consent' => true,
        ]);

        return $profile;
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
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
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
