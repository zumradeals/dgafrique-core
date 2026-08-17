<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionNeedLinkService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionBlocker;
use App\Models\MissionSubmission;
use App\Models\Need;
use App\Models\PersonProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-069 §21 : transitions invalides, SUBMITTED != COMPLETED, participation
 * (multi-exécutants, consentement, dernier exécutant), blockers multiples, expression
 * en Besoin avec confirmation explicite, absence de Besoin automatique.
 */
final class MissionParticipationBlockerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_transitions_are_rejected(): void
    {
        [$mission, ] = $this->openMission();
        $workflow = app(MissionWorkflow::class);

        // Une Mission OPEN ne peut pas être officialisée à nouveau (déjà passée cet état).
        $this->assertAborts(409, fn () => $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'x']));
        // On ne peut pas démarrer une Mission qui n'est pas encore OPEN/officialisée depuis DRAFT.
        $draft = $workflow->create('IDN-OWNER', 'PROJECT', $mission->context_reference, [
            'title' => 'Brouillon', 'description' => 'Une Mission encore en brouillon.',
        ]);
        $this->assertAborts(409, fn () => $workflow->start($draft, 'IDN-OWNER'));
    }

    public function test_submitted_is_never_completed_without_explicit_authority_decision(): void
    {
        [$mission, $assignments] = $this->openMission();
        $submissions = app(MissionSubmissionService::class);
        $workflow = app(MissionWorkflow::class);

        $a = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $a);
        $mission = $workflow->start($mission, 'IDN-EXEC');

        $submissions->submit($mission, 'IDN-EXEC', 'Résultat produit et documenté.');
        $mission = $mission->fresh();
        self::assertSame(Mission::STATUS_SUBMITTED, $mission->status);
        self::assertNotSame(Mission::STATUS_COMPLETED, $mission->status);

        // Un exécutant ne peut pas transformer sa propre soumission en résultat validé.
        $this->assertAborts(403, fn () => $submissions->accept($mission, 'IDN-EXEC'));
        self::assertSame(Mission::STATUS_SUBMITTED, $mission->fresh()->status);

        // Correction demandée : retour explicite en IN_PROGRESS, jamais un passage direct à COMPLETED.
        $mission = $submissions->requestCorrection($mission, 'IDN-OWNER', 'Merci de préciser les résultats chiffrés.');
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);
        self::assertSame(MissionSubmission::DECISION_CHANGES_REQUESTED, MissionSubmission::query()->where('mission_id', $mission->id)->latest('version')->first()->decision);

        $submissions->submit($mission, 'IDN-EXEC', 'Résultat corrigé avec chiffres.');
        $mission = $mission->fresh();
        self::assertSame(2, MissionSubmission::query()->where('mission_id', $mission->id)->count());

        $mission = $submissions->accept($mission, 'IDN-OWNER', 'Validé.');
        self::assertSame(Mission::STATUS_COMPLETED, $mission->status);
    }

    public function test_multi_executor_and_no_silent_consent(): void
    {
        [$mission, $assignments] = $this->openMission();

        $offer1 = $assignments->offer($mission, 'IDN-EXEC-1', MissionAssignment::ROLE_EXECUTOR);
        $offer2 = $assignments->offer($mission, 'IDN-EXEC-2', MissionAssignment::ROLE_CO_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer1);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer2);

        self::assertSame(2, MissionAssignment::query()->where('mission_id', $mission->id)->where('status', MissionAssignment::STATUS_ACCEPTED)->count());

        // Une invitation reste INVITED tant que la personne n'a pas explicitement répondu.
        $discoveryReference = $this->discoverableProfile('IDN-EXEC-3', 'Exécutant trois');
        $invitation = $assignments->invite($mission, 'IDN-OWNER', $discoveryReference, MissionAssignment::ROLE_LEARNER);
        self::assertSame(MissionAssignment::STATUS_INVITED, $invitation->status);
        self::assertSame(MissionAssignment::STATUS_INVITED, $invitation->fresh()->status);

        // Seule la personne invitée peut accepter ou décliner elle-même — pas l'autorité à sa place.
        $this->assertAborts(403, fn () => $assignments->acceptInvitation($mission, 'IDN-OWNER', $invitation));
        $accepted = $assignments->acceptInvitation($mission, 'IDN-EXEC-3', $invitation);
        self::assertSame(MissionAssignment::STATUS_ACCEPTED, $accepted->status);
    }

    public function test_last_executor_withdrawal_blocks_mission_without_creating_a_need(): void
    {
        [$mission, $assignments] = $this->openMission();
        $workflow = app(MissionWorkflow::class);

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignment = $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $mission = $workflow->start($mission, 'IDN-EXEC');

        self::assertSame(0, Need::query()->count());
        $assignments->release($mission, 'IDN-EXEC', $assignment, 'Indisponible pour la suite.');

        $mission = $mission->fresh();
        self::assertSame(Mission::STATUS_BLOCKED, $mission->status);
        self::assertSame(1, $mission->blockers()->where('type', MissionBlocker::TYPE_PERSON_UNAVAILABLE)->whereNull('resolved_at')->count());
        self::assertSame(0, Need::query()->count(), 'Le retrait du dernier exécutant ne crée jamais un Besoin automatiquement.');
    }

    public function test_open_mission_stays_open_when_offer_is_withdrawn(): void
    {
        [$mission, $assignments] = $this->openMission();
        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->withdraw($mission, 'IDN-EXEC', $offer);

        self::assertSame(Mission::STATUS_OPEN, $mission->fresh()->status);
    }

    public function test_multiple_simultaneous_blockers_all_require_resolution(): void
    {
        [$mission, $assignments] = $this->openMission();
        $workflow = app(MissionWorkflow::class);
        $blockers = app(MissionBlockerService::class);

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $mission = $workflow->start($mission, 'IDN-EXEC');

        $mission = $blockers->block($mission, 'IDN-EXEC', 'MISSING_RESOURCE', 'Aucun budget disponible.');
        $blockers->reportAdditional($mission, 'IDN-EXEC', 'MISSING_DECISION', 'Attente d’une décision du responsable.');
        self::assertSame(Mission::STATUS_BLOCKED, $mission->fresh()->status);
        self::assertSame(2, $mission->blockers()->whereNull('resolved_at')->count());

        $first = $mission->blockers()->where('type', 'MISSING_RESOURCE')->first();
        $mission = $blockers->resolve($mission, 'IDN-OWNER', $first, 'Budget débloqué.');
        // Un blocker actif subsiste : la Mission reste BLOCKED tant que tous ne sont pas résolus.
        self::assertSame(Mission::STATUS_BLOCKED, $mission->status);

        $second = $mission->blockers()->where('type', 'MISSING_DECISION')->first();
        $mission = $blockers->resolve($mission, 'IDN-OWNER', $second, 'Décision prise.');
        self::assertSame(Mission::STATUS_IN_PROGRESS, $mission->status);
    }

    public function test_blocker_expressed_as_need_requires_confirmation_and_never_auto_unblocks(): void
    {
        [$mission, $assignments] = $this->openMission();
        $workflow = app(MissionWorkflow::class);
        $blockers = app(MissionBlockerService::class);
        $links = app(MissionNeedLinkService::class);

        $offer = $assignments->offer($mission, 'IDN-EXEC', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);
        $mission = $workflow->start($mission, 'IDN-EXEC');
        $mission = $blockers->block($mission, 'IDN-EXEC', 'MISSING_RESOURCE', 'Aucun matériel disponible.');
        $blocker = $mission->blockers()->first();

        self::assertSame(0, Need::query()->count());
        $need = $links->expressBlockerAsNeed($mission, $blocker, 'IDN-OWNER', $this->needPayload(), (new NeedConfiguration)->defaults());
        self::assertSame(1, Need::query()->count());
        self::assertDatabaseHas('dg_mission_need_links', ['mission_id' => $mission->id, 'blocker_id' => $blocker->id, 'need_id' => $need->id]);

        // Résoudre le Need lié ne débloque jamais automatiquement la Mission.
        $need->update(['status' => Need::STATUS_RESOLVED]);
        self::assertSame(Mission::STATUS_BLOCKED, $mission->fresh()->status);
        self::assertNull($blocker->fresh()->resolved_at);
    }

    public function test_coordinator_role_never_grants_institutional_authority(): void
    {
        [$mission, $assignments] = $this->openMission();
        $workflow = app(MissionWorkflow::class);

        $offer = $assignments->offer($mission, 'IDN-COORD', MissionAssignment::ROLE_COORDINATOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $offer);

        // Un coordinateur accepté n'a jamais reçu l'autorité contextuelle du projet/ZUMRA/besoin.
        $this->assertAborts(403, fn () => $workflow->cancel($mission, 'IDN-COORD', 'Annulation par le coordinateur.'));
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

    /** @return array{0: Mission, 1: MissionAssignmentService} */
    private function openMission(): array
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une session pilote réalisée.']);

        return [$mission, app(MissionAssignmentService::class)];
    }

    private function needPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Need::OWNER_PERSON, 'group_reference' => null,
            'title' => 'Trouver du matériel de projection',
            'context' => 'Le blocage rencontré sur la Mission nécessite un matériel que personne ne peut fournir actuellement.',
            'category' => 'MATERIAL', 'capability_label' => null, 'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC,
        ], $overrides);
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
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = \App\Models\ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => \App\Models\ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        \App\Models\ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => \App\Models\ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }
}
