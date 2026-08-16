<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Sharing\ContextShareService;
use App\Models\ContextShare;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ContextShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_need_share_to_person_references_source_without_duplicating_it(): void
    {
        $need = $this->need('Besoin à transmettre', Need::VISIBILITY_PUBLIC);
        $target = $this->discoverableProfile('IDN-TARGET', 'Awa Démo');

        $share = app(ContextShareService::class)->shareNeed(
            $need,
            'IDN-OWNER',
            ContextShare::TARGET_PERSON,
            $target->discovery_reference,
            'Ce besoin correspond à une capacité que vous avez rendue visible.',
        );

        self::assertTrue($share->wasRecentlyCreated);
        self::assertSame(ContextShare::SOURCE_NEED, $share->source_type);
        self::assertSame($need->public_reference, $share->source_reference);
        self::assertSame('IDN-TARGET', $share->target_reference);
        self::assertSame(1, Need::query()->count());
        self::assertSame(1, ContextShare::query()->count());
        self::assertTrue(Schema::hasTable('dg_context_shares'));
        self::assertFalse(Schema::hasTable('dg_posts'));
    }

    public function test_private_source_cannot_be_shared_to_person_without_access(): void
    {
        $need = $this->need('Besoin privé', Need::VISIBILITY_PRIVATE);
        $target = $this->discoverableProfile('IDN-TARGET', 'Awa Démo');

        try {
            app(ContextShareService::class)->shareNeed(
                $need,
                'IDN-OWNER',
                ContextShare::TARGET_PERSON,
                $target->discovery_reference,
                'Tentative de transmission privée.',
            );
            self::fail('Le partage privé aurait dû être refusé.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->getStatusCode());
        }

        self::assertSame(0, ContextShare::query()->count());
    }

    public function test_received_share_disappears_when_source_visibility_is_withdrawn(): void
    {
        $need = $this->need('Besoin visible puis privé', Need::VISIBILITY_PUBLIC);
        $target = $this->discoverableProfile('IDN-TARGET', 'Awa Démo');
        $service = app(ContextShareService::class);

        $service->shareNeed(
            $need,
            'IDN-OWNER',
            ContextShare::TARGET_PERSON,
            $target->discovery_reference,
            'À regarder tant que ce besoin reste visible.',
        );

        self::assertCount(1, $service->personalInbox('IDN-TARGET')['shares']);

        $need->update(['visibility' => Need::VISIBILITY_PRIVATE]);

        self::assertCount(0, $service->personalInbox('IDN-TARGET')['shares']);
        self::assertSame(1, ContextShare::query()->count(), 'La trace historique reste conservée.');
    }

    public function test_group_share_requires_active_membership_and_rechecks_source_access(): void
    {
        $need = $this->need('Besoin pour une équipe', Need::VISIBILITY_PUBLIC);
        $group = $this->group('ZUMRA Action');
        $this->activeMembership($group, 'IDN-SHARER');
        $this->activeMembership($group, 'IDN-MEMBER');
        $service = app(ContextShareService::class);

        $service->shareNeed(
            $need,
            'IDN-SHARER',
            ContextShare::TARGET_ZUMRA,
            $group->public_reference,
            'Ce besoin peut mobiliser une capacité collective de notre ZUMRA.',
        );

        self::assertCount(1, $service->groupInbox($group, 'IDN-MEMBER')['shares']);

        $need->update(['visibility' => Need::VISIBILITY_PRIVATE]);

        self::assertCount(0, $service->groupInbox($group, 'IDN-MEMBER')['shares']);

        try {
            $service->groupInbox($group, 'IDN-OUTSIDER');
            self::fail('Un non-membre ne doit pas lire les partages de la ZUMRA.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_suspended_zumra_hides_its_share_space(): void
    {
        $group = $this->group('ZUMRA suspendue');
        $this->activeMembership($group, 'IDN-MEMBER');
        $group->update(['state' => ZumraGroup::STATE_SUSPENDED, 'suspended_at' => now()]);

        try {
            app(ContextShareService::class)->groupInbox($group, 'IDN-MEMBER');
            self::fail('Une ZUMRA suspendue ne doit pas exposer son espace de partages.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_same_source_sharer_and_target_is_not_duplicated(): void
    {
        $project = $this->project('Projet non dupliqué', Project::VISIBILITY_PUBLIC);
        $target = $this->discoverableProfile('IDN-TARGET', 'Moussa Démo');
        $service = app(ContextShareService::class);

        $first = $service->shareProject(
            $project,
            'IDN-OWNER',
            ContextShare::TARGET_PERSON,
            $target->discovery_reference,
            'Premier contexte conservé.',
        );
        $second = $service->shareProject(
            $project,
            'IDN-OWNER',
            ContextShare::TARGET_PERSON,
            $target->discovery_reference,
            'Ce second texte ne doit pas réécrire silencieusement la trace.',
        );

        self::assertTrue($first->is($second));
        self::assertSame(1, ContextShare::query()->count());
        self::assertSame('Premier contexte conservé.', ContextShare::query()->firstOrFail()->context_note);
        self::assertSame(1, Project::query()->count());
    }

    public function test_member_routes_render_contextual_sharing_without_popularity_mechanics(): void
    {
        $need = $this->need('Besoin partageable par route', Need::VISIBILITY_PUBLIC);
        $target = $this->discoverableProfile('IDN-TARGET', 'Awa Visible');
        $group = $this->group('ZUMRA Route');
        $this->activeMembership($group, 'IDN-OWNER');
        $this->signIn('IDN-OWNER');

        $this->get(route('shares.need', $need))
            ->assertOk()
            ->assertSee('Partager utilement')
            ->assertSee('Awa Visible')
            ->assertSee('ZUMRA Route')
            ->assertSee('Le partage ne donne aucun droit supplémentaire')
            ->assertDontSee('J’aime')
            ->assertDontSee('Score d’engagement')
            ->assertDontSee('Reposter');

        $this->post(route('shares.need.store', $need), [
            'target_type' => ContextShare::TARGET_PERSON,
            'target_reference' => $target->discovery_reference,
            'context_note' => 'Ce besoin peut correspondre à ce que vous cherchez à faire.',
        ])->assertRedirect(route('shares.need', $need));

        $this->signIn('IDN-TARGET');
        $this->get(route('shares.index'))
            ->assertOk()
            ->assertSee('Partages reçus')
            ->assertSee('Besoin partageable par route')
            ->assertSee('Ce besoin peut correspondre à ce que vous cherchez à faire.')
            ->assertSee('Membre DG Afrique')
            ->assertDontSee('IDN-OWNER')
            ->assertDontSee('IDN-TARGET');
    }

    public function test_private_sharer_reference_is_never_rendered_and_archived_source_is_hidden(): void
    {
        $project = $this->project('Projet partagé puis archivé', Project::VISIBILITY_PUBLIC);
        $target = $this->discoverableProfile('IDN-TARGET', 'Destinataire Visible');
        PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-HIDDEN-SHARER',
            'discovery_display_name' => 'Nom privé à ne pas afficher',
            'orientation_consent' => false,
            'discovery_consent' => false,
        ]);
        $service = app(ContextShareService::class);

        $service->shareProject(
            $project,
            'IDN-HIDDEN-SHARER',
            ContextShare::TARGET_PERSON,
            $target->discovery_reference,
            'Transmission utile sans exposer l’identité privée de l’émetteur.',
        );

        $before = $service->personalInbox('IDN-TARGET')['shares'];
        self::assertCount(1, $before);
        self::assertSame('Membre DG Afrique', $before->first()['sharer_label']);
        self::assertStringNotContainsString('IDN-HIDDEN-SHARER', json_encode($before->all(), JSON_THROW_ON_ERROR));

        $project->update(['status' => Project::STATUS_ARCHIVED, 'archived_at' => now()]);

        self::assertCount(0, $service->personalInbox('IDN-TARGET')['shares']);
        self::assertSame(1, ContextShare::query()->count());
    }

    private function need(string $title, string $visibility): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => 'IDN-OWNER',
            'author_core_reference' => 'IDN-OWNER',
            'title' => $title,
            'context' => 'Un contexte réel et suffisamment précis pour permettre une circulation utile sans dupliquer le besoin.',
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
            'summary' => 'Un projet réel transmis par référence, sans créer de copie sociale autonome.',
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

    private function discoverableProfile(string $reference, string $name): PersonProfile
    {
        return PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
        ]);
    }

    private function group(string $name): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes autour d’une action utile et coordonnée.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-OWNER',
            'active_member_count' => 2,
        ]);
    }

    private function activeMembership(ZumraGroup $group, string $reference): ZumraGroupMembership
    {
        return ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $reference,
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'INVITATION',
            'initiated_by_core_reference' => 'IDN-OWNER',
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
