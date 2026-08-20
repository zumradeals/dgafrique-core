<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CAP-055 — couvre la couche de pertinence personnelle ajoutée à ActivityFeedService :
 * une relation métier réelle (porteur, équipe, mission assignée, ZUMRA active) fait
 * remonter une activité et l'explique, sans jamais fabriquer de relation ni produire de
 * score de valeur humaine. Les invariants déjà couverts par ActivityFeedTest (visibilité,
 * déduplication, absence de mécanique de popularité, pagination) ne sont pas dupliqués ici.
 */
final class ActivityFeedRelevanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_need_the_actor_authored_outranks_a_higher_priority_stranger_need(): void
    {
        $mine = $this->need('IDN-ACTOR', 'Mon besoin en action');
        $this->needEvent($mine, 'NEED_STARTED', now()->subDay());

        $stranger = $this->need('IDN-STRANGER', 'Besoin ouvert d’un tiers');
        $this->needEvent($stranger, 'NEED_PUBLISHED', now());

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertSame('Mon besoin en action', $items->first()['title'], 'La pertinence personnelle dépasse la priorité métier brute (300 > 260 normalement).');
        self::assertSame('Besoin que vous avez publié.', $items->first()['relevance_reason']);
        self::assertNull($items->last()['relevance_reason']);
    }

    public function test_a_project_the_actor_is_an_active_team_member_of_carries_a_relevance_reason(): void
    {
        $mine = $this->project('IDN-OWNER', 'Projet équipe');
        $this->projectEvent($mine, 'PROJECT_ADOPTED', now()->subDay());
        ProjectTeamMember::query()->create([
            'project_id' => $mine->id,
            'core_identity_reference' => 'IDN-ACTOR',
            'status' => ProjectTeamMember::STATUS_ACTIVE,
            'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST,
            'initiated_by_core_reference' => 'IDN-ACTOR',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $other = $this->project('IDN-OWNER', 'Projet sans lien');
        $this->projectEvent($other, 'PROJECT_PROPOSED', now());

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertSame('Projet équipe', $items->first()['title']);
        self::assertSame('Projet auquel vous participez.', $items->first()['relevance_reason']);
        self::assertNull($items->last()['relevance_reason']);
    }

    public function test_a_project_the_actor_owns_is_labelled_distinctly_from_mere_participation(): void
    {
        $owned = $this->project('IDN-ACTOR', 'Projet que je porte');
        $this->projectEvent($owned, 'PROJECT_PROPOSED', now());

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertSame('Projet que vous portez.', $items->first()['relevance_reason']);
    }

    public function test_a_mission_the_actor_is_assigned_to_carries_a_relevance_reason(): void
    {
        [$mine] = $this->openMission('IDN-OWNER');
        MissionAssignment::query()->create([
            'mission_id' => $mine->id,
            'core_identity_reference' => 'IDN-ACTOR',
            'role' => MissionAssignment::ROLE_EXECUTOR,
            'status' => MissionAssignment::STATUS_ACCEPTED,
            'initiated_by_core_reference' => 'IDN-OWNER',
            'accepted_by_core_reference' => 'IDN-OWNER',
            'accepted_at' => now(),
        ]);

        [$other] = $this->openMission('IDN-OWNER', ['title' => 'Mission sans lien']);

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        $mineItem = $items->first(fn (array $item): bool => $item['action_url'] === route('missions.show', $mine));
        self::assertNotNull($mineItem);
        self::assertSame('Mission qui vous concerne.', $mineItem['relevance_reason']);

        $otherItem = $items->first(fn (array $item): bool => $item['action_url'] === route('missions.show', $other));
        self::assertNotNull($otherItem);
        self::assertNull($otherItem['relevance_reason']);
    }

    public function test_zumra_activity_from_an_active_membership_carries_a_relevance_reason(): void
    {
        $mine = $this->group('ZUMRA active');
        $this->groupEvent($mine, 'GROUP_PROPOSED');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $mine->id,
            'core_identity_reference' => 'IDN-ACTOR',
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-ACTOR',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $other = $this->group('ZUMRA sans lien');
        $this->groupEvent($other, 'GROUP_PROPOSED');

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertSame('ZUMRA active', $items->first()['title']);
        self::assertSame('Activité de votre ZUMRA.', $items->first()['relevance_reason']);
        self::assertNull($items->last()['relevance_reason']);
    }

    public function test_relevance_is_isolated_between_identities(): void
    {
        $project = $this->project('IDN-ACTOR', 'Projet privé au porteur');
        $this->projectEvent($project, 'PROJECT_PROPOSED');

        $mine = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();
        $stranger = app(ActivityFeedService::class)->paginate('IDN-STRANGER')->getCollection();

        self::assertSame('Projet que vous portez.', $mine->first()['relevance_reason']);
        self::assertNull($stranger->first()['relevance_reason'], 'Une identité sans relation réelle ne reçoit jamais de raison fabriquée.');
    }

    public function test_a_non_personal_activity_still_appears_in_the_feed(): void
    {
        $project = $this->project('IDN-OWNER', 'Projet public sans lien avec le lecteur');
        $this->projectEvent($project, 'PROJECT_PROPOSED');

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertCount(1, $items, 'La pertinence personnelle priorise, elle ne filtre jamais une activité métier réelle.');
        self::assertNull($items->first()['relevance_reason']);
    }

    public function test_relevance_order_is_deterministic_across_repeated_calls(): void
    {
        $mine = $this->project('IDN-OWNER', 'Projet à moi');
        $this->projectEvent($mine, 'PROJECT_ADOPTED', now()->subHours(2));
        ProjectTeamMember::query()->create([
            'project_id' => $mine->id, 'core_identity_reference' => 'IDN-ACTOR',
            'status' => ProjectTeamMember::STATUS_ACTIVE, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST,
            'initiated_by_core_reference' => 'IDN-ACTOR', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        $other = $this->project('IDN-OWNER', 'Projet neutre plus récent');
        $this->projectEvent($other, 'PROJECT_PROPOSED', now());

        $engine = app(ActivityFeedService::class);
        $first = $engine->paginate('IDN-ACTOR')->getCollection()->pluck('title')->all();
        $second = $engine->paginate('IDN-ACTOR')->getCollection()->pluck('title')->all();

        self::assertSame($first, $second);
        self::assertSame('Projet à moi', $first[0]);
    }

    public function test_no_business_mutation_happens_while_reading_the_feed(): void
    {
        $project = $this->project('IDN-ACTOR', 'Projet non muté');
        $this->projectEvent($project, 'PROJECT_PROPOSED');

        $projectsBefore = Project::query()->count();
        $eventsBefore = ProjectEvent::query()->count();
        $teamBefore = ProjectTeamMember::query()->count();

        app(ActivityFeedService::class)->paginate('IDN-ACTOR');

        self::assertSame($projectsBefore, Project::query()->count());
        self::assertSame($eventsBefore, ProjectEvent::query()->count());
        self::assertSame($teamBefore, ProjectTeamMember::query()->count());
    }

    public function test_relevance_reason_is_always_a_real_sentence_never_a_numeric_score(): void
    {
        $project = $this->project('IDN-ACTOR', 'Projet observé');
        $this->projectEvent($project, 'PROJECT_PROPOSED');

        $items = app(ActivityFeedService::class)->paginate('IDN-ACTOR')->getCollection();

        self::assertIsString($items->first()['relevance_reason']);
        self::assertArrayNotHasKey('relevance_score', $items->first());
        self::assertContains(
            $items->first()['relevance_reason'],
            ['Besoin que vous avez publié.', 'Projet que vous portez.', 'Projet auquel vous participez.', 'Mission qui vous concerne.', 'Activité de votre ZUMRA.', 'Transmission que vous avez proposée.', 'Preuve que vous avez soumise.'],
        );
    }

    public function test_the_relevance_reason_renders_on_the_real_feed_page(): void
    {
        $project = $this->project('IDN-ACTOR', 'Projet visible dans le Fil');
        $this->projectEvent($project, 'PROJECT_PROPOSED');
        $this->signIn('IDN-ACTOR');

        $this->get('/activite')
            ->assertOk()
            ->assertSee('Projet visible dans le Fil')
            ->assertSee('Projet que vous portez.');
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference,
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference,
                'type' => 'personne',
                'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF',
                'source' => 'CORE',
                'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', [
            'identifier' => $reference,
            'secret' => 'secret',
        ])->assertRedirect('/espace');
    }

    private function need(string $author, string $title): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $author,
            'author_core_reference' => $author,
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour rendre cette activité utile dans le réseau.',
            'category' => 'SKILL',
            'capability_label' => 'Gestion de projet',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $author,
            'published_at' => now(),
        ]);
    }

    private function needEvent(Need $need, string $event, $occurredAt = null): void
    {
        NeedEvent::query()->create([
            'need_id' => $need->id,
            'event' => $event,
            'actor_core_reference' => $need->author_core_reference,
            'from_status' => null,
            'to_status' => $need->status,
            'context' => [],
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    private function project(string $owner, string $name): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => $owner,
            'initiator_core_reference' => $owner,
            'source_need_id' => null,
            'name' => $name,
            'summary' => 'Un projet concret qui relie des capacités à une action utile.',
            'problem' => 'Un problème réel à résoudre.',
            'proposed_solution' => 'Une solution progressive et testable.',
            'beneficiaries' => 'Communauté locale',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'objectives' => ['Agir'],
            'required_capabilities' => ['Coordination'],
            'required_resources' => ['Temps'],
            'risks' => ['Disponibilité'],
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => Project::STATUS_IN_PROGRESS,
            'maturity' => 'ACTIVITY',
            'decided_by_core_reference' => $owner,
            'started_at' => now(),
        ]);
    }

    private function projectEvent(Project $project, string $event, $occurredAt = null): void
    {
        ProjectEvent::query()->create([
            'project_id' => $project->id,
            'event' => $event,
            'actor_core_reference' => $project->initiator_core_reference,
            'context' => [],
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    private function group(string $name): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes pour apprendre, transmettre et produire une action utile ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-OWNER',
            'active_member_count' => 3,
        ]);
    }

    private function groupEvent(ZumraGroup $group, string $event): void
    {
        ZumraGroupEvent::query()->create([
            'zumra_group_id' => $group->id,
            'event' => $event,
            'actor_core_reference' => 'IDN-OWNER',
            'context' => [],
            'occurred_at' => now(),
        ]);
    }

    /** @return array{0: Mission, 1: Project} */
    private function openMission(string $owner, array $missionOverrides = []): array
    {
        $this->activateProgram($owner);
        $project = app(ProjectService::class)->create($owner, $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $workflow = app(MissionWorkflow::class);

        $mission = $workflow->create($owner, 'PROJECT', $project->public_reference, array_replace([
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
            'visibility' => Mission::VISIBILITY_PUBLIC,
        ], $missionOverrides));
        $mission = $workflow->propose($mission, $owner);
        $mission = $workflow->officialize($mission, $owner, ['expected_result' => 'Une session pilote réalisée.']);

        return [$mission, $project];
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire '.Str::random(6),
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
