<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Application\Comments\ContextCommentService;
use App\Models\ContextComment;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ContextCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_need_comments_are_short_contextual_and_append_only(): void
    {
        $need = $this->need('Besoin commenté', Need::VISIBILITY_PUBLIC);
        $service = app(ContextCommentService::class);

        $first = $service->addNeed($need, 'IDN-A', 'QUESTION', 'Qui peut préciser la disponibilité nécessaire ?');
        $second = $service->addNeed($need, 'IDN-B', 'RESOURCE', 'Une ressource utile est disponible auprès du réseau local.');
        $thread = $service->needThread($need, 'IDN-A');

        self::assertCount(2, $thread['comments']);
        self::assertSame($first->public_reference, $thread['comments'][0]['public_reference']);
        self::assertSame($second->public_reference, $thread['comments'][1]['public_reference']);
        self::assertSame('Question', $thread['comments'][0]['purpose_label']);
        self::assertSame('Ressource', $thread['comments'][1]['purpose_label']);
        self::assertTrue(Schema::hasTable('dg_context_comments'));
        self::assertFalse(Schema::hasTable('dg_posts'));
        self::assertFalse(Schema::hasTable('dg_comments'));
        self::assertFalse(Schema::hasTable('dg_comment_replies'));
        self::assertFalse(Schema::hasTable('dg_likes'));
        self::assertFalse(Schema::hasTable('dg_shares'));
    }

    public function test_private_need_comments_never_leak_to_an_outsider(): void
    {
        $need = $this->need('Besoin privé', Need::VISIBILITY_PRIVATE);
        app(ContextCommentService::class)->addNeed($need, 'IDN-OWNER', 'PRECISION', 'Cette précision reste dans le contexte privé.');
        $this->signIn('IDN-OUTSIDER');

        $this->get(route('comments.need', $need))->assertNotFound();
        $this->post(route('comments.need.store', $need), [
            'purpose' => 'QUESTION',
            'body' => 'Puis-je intervenir sur ce besoin ?',
        ])->assertNotFound();

        self::assertSame(1, ContextComment::query()->count());
    }

    public function test_project_comment_never_creates_project_membership_or_changes_project(): void
    {
        $project = $this->project('Projet ouvert', Project::VISIBILITY_PUBLIC);
        $before = $project->only(['status', 'owner_type', 'owner_reference', 'visibility']);

        app(ContextCommentService::class)->addProject(
            $project,
            'IDN-VIEWER',
            'ADVICE',
            'Je conseille de tester cette étape avec un petit groupe avant généralisation.'
        );

        self::assertSame($before, $project->fresh()->only(array_keys($before)));
        self::assertFalse(Schema::hasTable('dg_project_memberships'));
        self::assertSame(1, ContextComment::query()->where('context_type', ContextComment::CONTEXT_PROJECT)->count());
    }

    public function test_archived_context_is_read_only_for_authorized_identity_and_hidden_from_outsider(): void
    {
        $need = $this->need('Besoin archivé', Need::VISIBILITY_PUBLIC);
        app(ContextCommentService::class)->addNeed($need, 'IDN-OWNER', 'COORDINATION', 'Dernière action avant archivage.');
        $need->update(['status' => Need::STATUS_ARCHIVED, 'archived_at' => now()]);

        $ownerThread = app(ContextCommentService::class)->needThread($need, 'IDN-OWNER');
        self::assertFalse($ownerThread['context']['can_comment']);
        self::assertCount(1, $ownerThread['comments']);

        $this->signIn('IDN-OWNER');
        $this->post(route('comments.need.store', $need), [
            'purpose' => 'PRECISION',
            'body' => 'Tentative après archivage.',
        ])->assertStatus(409);

        $this->signIn('IDN-OUTSIDER');
        $this->get(route('comments.need', $need))->assertNotFound();
    }

    public function test_suspended_zumra_hides_its_activity_comment_thread(): void
    {
        $group = $this->group('ZUMRA active');
        app(ContextCommentService::class)->addZumraActivity(
            $group,
            'IDN-A',
            'COORDINATION',
            'Prochaine action : préparer la séance de travail.'
        );

        self::assertCount(1, app(ContextCommentService::class)->zumraActivityThread($group, 'IDN-B')['comments']);

        $group->update(['state' => ZumraGroup::STATE_SUSPENDED, 'suspended_at' => now()]);
        $this->signIn('IDN-B');
        $this->get(route('comments.zumra-activity', $group))->assertNotFound();
    }

    public function test_private_identity_reference_is_never_rendered_as_comment_author(): void
    {
        $need = $this->need('Besoin public', Need::VISIBILITY_PUBLIC);
        PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-HIDDEN-AUTHOR',
            'discovery_display_name' => 'Nom qui ne doit pas sortir',
            'discovery_consent' => false,
            'orientation_consent' => false,
        ]);
        app(ContextCommentService::class)->addNeed(
            $need,
            'IDN-HIDDEN-AUTHOR',
            'QUESTION',
            'Une question utile et contextuelle.'
        );
        $this->signIn('IDN-VIEWER');

        $this->get(route('comments.need', $need))
            ->assertOk()
            ->assertSee('Membre DG Afrique')
            ->assertDontSee('IDN-HIDDEN-AUTHOR')
            ->assertDontSee('Nom qui ne doit pas sortir');
    }

    public function test_activity_feed_points_to_the_same_context_without_popularity_ranking(): void
    {
        $need = $this->need('Besoin du fil', Need::VISIBILITY_PUBLIC);
        NeedEvent::query()->create([
            'need_id' => $need->id,
            'event' => 'NEED_PUBLISHED',
            'actor_core_reference' => 'IDN-OWNER',
            'from_status' => null,
            'to_status' => Need::STATUS_OPEN,
            'context' => [],
            'occurred_at' => now(),
        ]);
        app(ContextCommentService::class)->addNeed($need, 'IDN-A', 'PRECISION', 'Une précision ne doit pas influencer le classement.');

        $item = app(ActivityFeedService::class)->paginate('IDN-VIEWER', 'NEEDS')->getCollection()->first();

        self::assertSame(route('comments.need', $need), $item['comment_url']);
        self::assertArrayNotHasKey('comment_count', $item);
        self::assertArrayNotHasKey('engagement', $item);
        self::assertArrayNotHasKey('likes', $item);
    }

    public function test_member_routes_render_actionable_context_without_social_mechanics(): void
    {
        $need = $this->need('Besoin route commentaire', Need::VISIBILITY_PUBLIC);
        $this->signIn('IDN-VIEWER');

        $this->get(route('comments.need', $need))
            ->assertOk()
            ->assertSee('Ajouter quelque chose d’utile')
            ->assertSee('Question')
            ->assertSee('Précision')
            ->assertSee('Conseil')
            ->assertSee('Ressource')
            ->assertSee('Coordination')
            ->assertDontSee('J’aime')
            ->assertDontSee('Répondre')
            ->assertDontSee('Partager');

        $this->post(route('comments.need.store', $need), [
            'purpose' => 'QUESTION',
            'body' => 'Qui peut aider à préciser la prochaine étape ?',
        ])->assertRedirect(route('comments.need', $need));

        $this->get(route('comments.need', $need))
            ->assertOk()
            ->assertSee('Qui peut aider à préciser la prochaine étape ?')
            ->assertSee('Vous');
    }

    private function need(string $title, string $visibility): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER',
            'author_core_reference' => 'IDN-OWNER',
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour permettre une contribution utile et orientée vers l’action.',
            'category' => 'SKILL',
            'capability_label' => 'Coordination',
            'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'visibility' => $visibility,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => 'IDN-OWNER',
            'published_at' => now(),
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
            'summary' => 'Un projet réel qui nécessite des contributions courtes et contextualisées.',
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

    private function group(string $name): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes autour d’une activité utile et coordonnée.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-OWNER',
            'active_member_count' => 3,
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
