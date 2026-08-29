<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_projects_real_events_without_creating_social_objects(): void
    {
        $need = $this->need('Besoin public', Need::VISIBILITY_PUBLIC);
        $this->needEvent($need, 'NEED_PUBLISHED');

        $project = $this->project('Projet public', Project::VISIBILITY_PUBLIC);
        $this->projectEvent($project, 'PROJECT_IN_PROGRESS');

        $group = $this->group('ZUMRA utile');
        $this->groupEvent($group, 'GROUP_PROPOSED');

        $feed = app(ActivityFeedService::class)->paginate('IDN-VIEWER');

        self::assertCount(3, $feed->items());
        self::assertFalse(Schema::hasTable('dg_posts'));
        self::assertFalse(Schema::hasTable('dg_likes'));
        self::assertFalse(Schema::hasTable('dg_comments'));
        self::assertFalse(Schema::hasTable('dg_messages'));
    }

    public function test_private_need_and_project_never_leak_to_an_outsider(): void
    {
        $privateNeed = $this->need('Besoin privé', Need::VISIBILITY_PRIVATE);
        $publicNeed = $this->need('Besoin visible', Need::VISIBILITY_PUBLIC);
        $this->needEvent($privateNeed, 'NEED_PUBLISHED');
        $this->needEvent($publicNeed, 'NEED_PUBLISHED');

        $privateProject = $this->project('Projet privé', Project::VISIBILITY_PRIVATE);
        $publicProject = $this->project('Projet visible', Project::VISIBILITY_PUBLIC);
        $this->projectEvent($privateProject, 'PROJECT_PROPOSED');
        $this->projectEvent($publicProject, 'PROJECT_PROPOSED');

        $items = app(ActivityFeedService::class)->paginate('IDN-OUTSIDER')->getCollection();

        self::assertFalse($items->contains(fn (array $item): bool => $item['title'] === 'Besoin privé'));
        self::assertFalse($items->contains(fn (array $item): bool => $item['title'] === 'Projet privé'));
        self::assertTrue($items->contains(fn (array $item): bool => $item['title'] === 'Besoin visible'));
        self::assertTrue($items->contains(fn (array $item): bool => $item['title'] === 'Projet visible'));
    }

    public function test_feed_keeps_only_latest_useful_event_per_object(): void
    {
        $need = $this->need('Besoin avec étapes', Need::VISIBILITY_PUBLIC);
        $this->needEvent($need, 'NEED_PUBLISHED', now()->subHour());
        $need->update(['status' => Need::STATUS_IN_PROGRESS]);
        $this->needEvent($need, 'NEED_STARTED', now());

        $items = app(ActivityFeedService::class)->paginate('IDN-VIEWER', 'NEEDS')->getCollection();

        self::assertCount(1, $items);
        self::assertSame('NEED_STARTED', $items->first()['event']);
    }

    public function test_actionable_activity_is_prioritized_without_popularity_score(): void
    {
        $resolved = $this->need('Résolu très récent', Need::VISIBILITY_PUBLIC);
        $resolved->update(['status' => Need::STATUS_RESOLVED]);
        $this->needEvent($resolved, 'NEED_RESOLVED', now());

        $open = $this->need('Ouvert à activer', Need::VISIBILITY_PUBLIC);
        $this->needEvent($open, 'NEED_PUBLISHED', now()->subDay());

        $items = app(ActivityFeedService::class)->paginate('IDN-VIEWER', 'NEEDS')->getCollection();

        self::assertSame('Ouvert à activer', $items->first()['title']);
        self::assertArrayNotHasKey('likes', $items->first());
        self::assertArrayNotHasKey('followers', $items->first());
        self::assertArrayNotHasKey('engagement', $items->first());
    }

    public function test_zumra_feed_excludes_sensitive_events_and_suspended_groups(): void
    {
        $visible = $this->group('ZUMRA visible');
        $this->groupEvent($visible, 'MEMBERSHIP_REQUESTED');
        $this->groupEvent($visible, 'MEMBERSHIP_APPROVED');

        $suspended = $this->group('ZUMRA suspendue', ZumraGroup::STATE_SUSPENDED);
        $this->groupEvent($suspended, 'GROUP_PROPOSED');

        $items = app(ActivityFeedService::class)->paginate('IDN-VIEWER', 'ZUMRA')->getCollection();

        self::assertCount(1, $items);
        self::assertSame('ZUMRA visible', $items->first()['title']);
        self::assertSame('MEMBERSHIP_APPROVED', $items->first()['event']);
    }

    public function test_member_space_never_presents_a_strangers_project_as_personally_relevant(): void
    {
        // UIUX-002 — décision « corriger les faux Pour vous » : un projet sans relation
        // personnelle réelle avec ce membre (ni porteur, ni équipe) ne doit plus jamais être
        // présenté sur Mon espace comme personnellement destiné à lui — ni comme priorité
        // (déjà corrigé par UIUX-001), ni dans les sections « Pour vous »/« Cette semaine ».
        $project = $this->project('Projet dans Mon espace', Project::VISIBILITY_PUBLIC);
        $this->projectEvent($project, 'PROJECT_IN_PROGRESS');
        $this->signIn('IDN-VIEWER');

        $this->get('/espace')
            ->assertOk()
            ->assertDontSee('Projet dans Mon espace')
            ->assertSee('Aujourd’hui — une seule chose compte');
    }

    private function need(string $title, string $visibility): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER',
            'author_core_reference' => 'IDN-OWNER',
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour rendre cette activité utile dans le réseau.',
            'category' => 'SKILL',
            'capability_label' => 'Gestion de projet',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => $visibility,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => 'IDN-OWNER',
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

    private function project(string $name, string $visibility): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER',
            'initiator_core_reference' => 'IDN-OWNER',
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
            'visibility' => $visibility,
            'status' => Project::STATUS_IN_PROGRESS,
            'maturity' => 'ACTIVITY',
            'decided_by_core_reference' => 'IDN-OWNER',
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

    private function group(string $name, string $state = ZumraGroup::STATE_ACTIVE): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes pour apprendre, transmettre et produire une action utile ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => $state,
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
}
