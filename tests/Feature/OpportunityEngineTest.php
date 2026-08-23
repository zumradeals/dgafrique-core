<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Opportunities\OpportunityEngine;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CAP-064 — couvre le moteur d'opportunités comme projection pure des objets métier
 * déjà existants (Mission, CapabilityStatement, PersonProfile) : consentement,
 * disponibilité, visibilité réelle des Missions (intersection MissionVisibilityService),
 * explicabilité, absence de mutation, déterminisme, limite et isolation par identité.
 */
final class OpportunityEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_opportunity_without_a_profile(): void
    {
        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-GHOST'));
    }

    public function test_no_opportunity_without_orientation_consent(): void
    {
        $this->profile('IDN-ACTOR', ['orientation_consent' => false, 'orientation_consented_at' => null]);
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    public function test_no_opportunity_when_availability_is_paused(): void
    {
        $this->profile('IDN-ACTOR', ['availability_status' => PersonProfile::AVAILABILITY_PAUSED]);
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    public function test_no_capability_without_matching_consent(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité', ['matching_consent' => false]);
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    /**
     * CAP-010 : la visibilité DISCOVERABLE d'une CapabilityStatement gouverne sa
     * découvrabilité PAR AUTRUI (ex. quand la personne est proposée comme candidate à
     * quelqu'un d'autre, comme dans MissionMatchingEngine), jamais l'usage qu'une
     * personne fait de ses propres capacités consenties pour trouver ses propres
     * opportunités — exactement comme PersonRecommendationEngine ne filtre jamais les
     * statements du viewer par visibility, uniquement par matching_consent. Exiger
     * DISCOVERABLE ici exclurait à tort les capacités privées mais consenties.
     */
    public function test_a_private_but_matching_consented_capability_still_powers_the_actors_own_opportunities(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité', ['visibility' => CapabilityStatement::VISIBILITY_PRIVATE]);
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertCount(1, $results);
        self::assertSame($mission->public_reference, $results[0]['reference']);
    }

    public function test_a_mission_not_open_is_excluded(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER', officialize: false);
        $this->requirement($mission, 'Comptabilité');

        self::assertNotSame(Mission::STATUS_OPEN, $mission->fresh()->status);
        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    /**
     * Une Mission PROGRAM n'est visible que pour une identité ayant une adhésion
     * ZumraProgramMembership active (MissionVisibilityService::hasActiveProgramMembership) :
     * une capacité compatible ne suffit jamais à contourner cette autorisation réelle.
     */
    public function test_a_mission_not_actually_viewable_by_the_identity_is_excluded(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER', missionOverrides: ['visibility' => Mission::VISIBILITY_PROGRAM]);
        $this->requirement($mission, 'Comptabilité');

        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    public function test_a_compatible_capability_produces_a_mission_opportunity(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertCount(1, $results);
        self::assertSame('MISSION', $results[0]['type']);
        self::assertSame($mission->public_reference, $results[0]['reference']);
        self::assertSame('OPEN_MISSION_CONTEXT', $results[0]['action']['kind']);
        self::assertSame('PROJECT', $results[0]['action']['context_type']);
    }

    /**
     * UIUX-008 — audit Phase A : une Mission à laquelle la personne a déjà une relation
     * active (offerte, invitée ou acceptée — App\Models\MissionAssignment::CURRENT_STATUSES)
     * ne doit plus jamais se présenter comme « ce que vous pourriez rejoindre ».
     */
    public function test_a_mission_the_actor_is_already_assigned_to_is_excluded(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $assignments = app(MissionAssignmentService::class);
        $assignment = $assignments->offer($mission, 'IDN-ACTOR', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-OWNER', $assignment);

        self::assertSame([], app(OpportunityEngine::class)->forIdentity('IDN-ACTOR'));
    }

    /**
     * UIUX-008 — un ancien engagement résolu (retiré par la personne elle-même) ne doit
     * jamais continuer d'exclure la Mission : elle redevient une possibilité réelle.
     */
    public function test_a_mission_with_only_a_withdrawn_assignment_stays_a_real_opportunity(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $assignments = app(MissionAssignmentService::class);
        $assignment = $assignments->offer($mission, 'IDN-ACTOR', MissionAssignment::ROLE_EXECUTOR);
        $assignments->withdraw($mission, 'IDN-ACTOR', $assignment);

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertCount(1, $results);
        self::assertSame($mission->public_reference, $results[0]['reference']);
    }

    public function test_explainable_reasons_are_present(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertNotEmpty($results[0]['reasons']);
        self::assertStringContainsString('Comptabilité', $results[0]['reasons'][0]);
        self::assertStringContainsString('correspond à cette mission', $results[0]['reasons'][0]);
    }

    public function test_no_business_mutation_happens_during_the_projection(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');

        $missionsBefore = Mission::query()->count();
        $updatedAtBefore = $mission->fresh()->updated_at;

        app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertSame($missionsBefore, Mission::query()->count());
        self::assertSame(0, MissionAssignment::query()->count());
        self::assertEquals($updatedAtBefore, $mission->fresh()->updated_at);
        self::assertSame(Mission::STATUS_OPEN, $mission->fresh()->status);
    }

    public function test_result_order_is_deterministic(): void
    {
        $this->profile('IDN-ACTOR', ['participation_mode' => 'HYBRID', 'city' => 'Abidjan']);
        $this->capability('IDN-ACTOR', 'Comptabilité');

        [$moreReasons] = $this->openMission('IDN-OWNER', missionOverrides: [
            'title' => 'Appui comptable de quartier',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
        ]);
        $this->requirement($moreReasons, 'Comptabilité');

        [$fewerReasons] = $this->openMission('IDN-OWNER', missionOverrides: [
            'title' => 'Zéro autre raison',
        ]);
        $this->requirement($fewerReasons, 'Comptabilité');

        $engine = app(OpportunityEngine::class);
        $first = $engine->forIdentity('IDN-ACTOR');
        $second = $engine->forIdentity('IDN-ACTOR');

        self::assertSame($first, $second, 'Le même état métier doit toujours produire le même ordre.');
        self::assertCount(2, $first);
        self::assertSame($moreReasons->public_reference, $first[0]['reference']);
        self::assertGreaterThan($first[1]['priority'], $first[0]['priority']);
    }

    public function test_result_count_is_bounded(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');

        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        for ($i = 0; $i < 25; $i++) {
            [$mission] = $this->openMission('IDN-OWNER', project: $project, missionOverrides: ['title' => 'Mission comptable '.$i]);
            $this->requirement($mission, 'Comptabilité');
        }

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertCount(20, $results, 'La projection ne dépasse jamais sa limite documentée (MAX_RESULTS).');
    }

    public function test_absence_of_problematic_duplicates(): void
    {
        $this->profile('IDN-ACTOR');
        $this->capability('IDN-ACTOR', 'Comptabilité');
        $this->capability('IDN-ACTOR', 'Gestion budgétaire');
        [$mission] = $this->openMission('IDN-OWNER');
        $this->requirement($mission, 'Comptabilité');
        $this->requirement($mission, 'Gestion budgétaire');

        $results = app(OpportunityEngine::class)->forIdentity('IDN-ACTOR');

        self::assertCount(1, $results, 'Une Mission correspondant à plusieurs capacités reste une seule opportunité.');
        self::assertSame($results[0]['reasons'], array_values(array_unique($results[0]['reasons'])));
    }

    public function test_isolation_between_identities(): void
    {
        $this->profile('IDN-A');
        $this->capability('IDN-A', 'Comptabilité');
        $this->profile('IDN-B');
        $this->capability('IDN-B', 'Plomberie');

        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        [$missionA] = $this->openMission('IDN-OWNER', project: $project, missionOverrides: ['title' => 'Mission Comptabilité']);
        $this->requirement($missionA, 'Comptabilité');
        [$missionB] = $this->openMission('IDN-OWNER', project: $project, missionOverrides: ['title' => 'Mission Plomberie']);
        $this->requirement($missionB, 'Plomberie');

        $resultsA = app(OpportunityEngine::class)->forIdentity('IDN-A');
        $resultsB = app(OpportunityEngine::class)->forIdentity('IDN-B');

        self::assertCount(1, $resultsA);
        self::assertSame($missionA->public_reference, $resultsA[0]['reference']);
        self::assertCount(1, $resultsB);
        self::assertSame($missionB->public_reference, $resultsB[0]['reference']);
    }

    private function profile(string $identity, array $overrides = []): PersonProfile
    {
        return PersonProfile::query()->create(array_replace([
            'core_identity_reference' => $identity,
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => 'Membre '.$identity,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
            'availability_status' => PersonProfile::AVAILABILITY_LIMITED,
            'participation_mode' => 'HYBRID',
            'city' => 'Abidjan',
        ], $overrides));
    }

    private function capability(string $identity, string $label, array $overrides = []): CapabilityStatement
    {
        return CapabilityStatement::query()->create(array_replace([
            'core_identity_reference' => $identity,
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => true,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
        ], $overrides));
    }

    private function requirement(Mission $mission, string $label): void
    {
        $mission->capabilityRequirements()->create([
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'requirement_level' => 'REQUIRED',
            'quantity' => 1,
        ]);
    }

    /** @return array{0: Mission, 1: Project} */
    private function openMission(
        string $owner,
        ?Project $project = null,
        array $missionOverrides = [],
        bool $officialize = true,
    ): array {
        if ($project === null) {
            $this->activateProgram($owner);
            $project = app(ProjectService::class)->create($owner, $this->projectPayload(), (new ProjectConfiguration)->defaults());
        }

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create($owner, 'PROJECT', $project->public_reference, array_replace([
            'title' => 'Mission '.Str::random(8),
            'description' => 'Une mission concrète pour les tests du moteur d’opportunités.',
            'visibility' => Mission::VISIBILITY_PUBLIC,
        ], $missionOverrides));
        $mission = $workflow->propose($mission, $owner);
        if ($officialize) {
            $mission = $workflow->officialize($mission, $owner, ['expected_result' => 'Résultat attendu de test.']);
        }

        return [$mission, $project];
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
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists()) {
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
}
