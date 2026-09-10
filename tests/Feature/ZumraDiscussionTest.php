<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Comments\ContextCommentService;
use App\Models\ContextComment;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraDiscussionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_read_discussion_without_side_effects(): void
    {
        $group = $this->group('ZUMRA Discussion');
        $this->activateMember($group, 'IDN-A');
        $this->signIn('IDN-A');

        $beforeComments = ContextComment::query()->count();
        $beforeMemberships = ZumraGroupMembership::query()->count();

        $this->get(route('zumra.groups.discussion', $group))
            ->assertOk()
            ->assertSee('La conversation de '.$group->name)
            ->assertSee('Aucun message pour le moment.')
            ->assertSee('Publier dans la discussion');

        self::assertSame($beforeComments, ContextComment::query()->count());
        self::assertSame($beforeMemberships, ZumraGroupMembership::query()->count());
    }

    public function test_outsider_cannot_read_or_publish_in_discussion(): void
    {
        $group = $this->group('ZUMRA Privée aux membres');
        $this->activateMember($group, 'IDN-A');
        $this->signIn('IDN-OUTSIDER');

        $this->get(route('zumra.groups.discussion', $group))->assertForbidden();
        $this->post(route('zumra.groups.discussion.store', $group), [
            'purpose' => 'QUESTION',
            'body' => 'Cette tentative ne doit pas entrer dans le canal.',
        ])->assertForbidden();

        self::assertSame(0, ContextComment::query()->count());
    }

    public function test_discussion_is_isolated_and_author_is_session_identity(): void
    {
        $groupA = $this->group('ZUMRA Alpha');
        $groupB = $this->group('ZUMRA Bêta');
        $this->activateMember($groupA, 'IDN-A');
        $this->activateMember($groupB, 'IDN-A');
        $this->signIn('IDN-A');

        $this->post(route('zumra.groups.discussion.store', $groupA), [
            'purpose' => 'COORDINATION',
            'body' => 'Préparons la prochaine séance de travail Alpha.',
            'author_core_reference' => 'IDN-SPOOFED',
        ])->assertRedirect(route('zumra.groups.discussion', $groupA));

        $this->assertDatabaseHas('dg_context_comments', [
            'context_type' => ContextComment::CONTEXT_ZUMRA_ACTIVITY,
            'context_reference' => $groupA->public_reference,
            'author_core_reference' => 'IDN-A',
            'purpose' => 'COORDINATION',
            'body' => 'Préparons la prochaine séance de travail Alpha.',
        ]);
        $this->assertDatabaseMissing('dg_context_comments', ['author_core_reference' => 'IDN-SPOOFED']);

        $this->get(route('zumra.groups.discussion', $groupA))
            ->assertOk()
            ->assertSee('Préparons la prochaine séance de travail Alpha.');
        $this->get(route('zumra.groups.discussion', $groupB))
            ->assertOk()
            ->assertDontSee('Préparons la prochaine séance de travail Alpha.');
    }

    public function test_suspended_zumra_hides_discussion_and_legacy_activity_thread(): void
    {
        $group = $this->group('ZUMRA active');
        $this->activateMember($group, 'IDN-A');
        app(ContextCommentService::class)->addZumraActivity(
            $group,
            'IDN-A',
            'COORDINATION',
            'Prochaine action : préparer la séance de travail.'
        );

        $group->update(['state' => ZumraGroup::STATE_SUSPENDED, 'suspended_at' => now()]);
        $this->signIn('IDN-A');

        $this->get(route('comments.zumra-activity', $group))->assertNotFound();
        $this->get(route('zumra.groups.discussion', $group))->assertNotFound();
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

    private function activateMember(ZumraGroup $group, string $reference): ZumraGroupMembership
    {
        return ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $reference,
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => $reference,
            'joined_at' => now(),
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
                'libelle' => 'Membre GAMAD',
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
