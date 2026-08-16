<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Messaging\MessagingService;
use App\Models\MessageConversation;
use App\Models\MessageEntry;
use App\Models\MessageParticipant;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_conversation_starts_from_a_discoverable_person_and_messages_are_append_only(): void
    {
        $profile = $this->profile('IDN-B', 'Awa Visible');
        $service = app(MessagingService::class);
        $conversation = $service->startDirect('IDN-A', $profile->discovery_reference);

        $service->send($conversation, 'IDN-A', 'Bonjour Awa, pouvons-nous coordonner cette action ?');
        $service->send($conversation, 'IDN-B', 'Oui, précisons les prochaines étapes.');

        self::assertSame(MessageConversation::KIND_DIRECT, $conversation->kind);
        self::assertSame(2, MessageParticipant::query()->where('conversation_id', $conversation->id)->count());
        self::assertSame(2, MessageEntry::query()->where('conversation_id', $conversation->id)->count());
        self::assertFalse(Schema::hasTable('dg_messages'));
        self::assertFalse(Schema::hasTable('dg_comments'));
        self::assertFalse(Schema::hasTable('dg_shares'));
        self::assertFalse(Schema::hasTable('dg_followers'));
    }

    public function test_outsider_cannot_read_or_send_to_a_private_conversation(): void
    {
        $profile = $this->profile('IDN-B', 'Awa Visible');
        $service = app(MessagingService::class);
        $conversation = $service->startDirect('IDN-A', $profile->discovery_reference);

        self::assertFalse($service->canAccess($conversation, 'IDN-C'));

        try {
            $service->send($conversation, 'IDN-C', 'Intrusion');
            self::fail('Un non-participant ne doit pas pouvoir écrire.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_need_conversation_requires_current_visibility_and_links_the_real_owner(): void
    {
        $service = app(MessagingService::class);
        $public = $this->need('Besoin public', Need::VISIBILITY_PUBLIC, 'IDN-OWNER');
        $conversation = $service->openNeed('IDN-VIEWER', $public);

        self::assertTrue(MessageParticipant::query()->where('conversation_id', $conversation->id)->where('core_identity_reference', 'IDN-OWNER')->exists());
        self::assertTrue($service->canAccess($conversation, 'IDN-VIEWER'));

        $private = $this->need('Besoin privé', Need::VISIBILITY_PRIVATE, 'IDN-OWNER');
        try {
            $service->openNeed('IDN-VIEWER', $private);
            self::fail('Un besoin privé ne doit pas ouvrir de conversation à un tiers.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_zumra_conversation_tracks_active_membership_instead_of_permanent_access(): void
    {
        $group = $this->group('ZUMRA Coordination', 'IDN-A');
        $this->membership($group, 'IDN-B', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(MessagingService::class);

        $first = $service->openZumra('IDN-A', $group);
        self::assertTrue($service->canAccess($first, 'IDN-B'));

        $this->membership($group, 'IDN-C', ZumraGroupMembership::STATUS_ACTIVE);
        $same = $service->openZumra('IDN-C', $group);
        self::assertSame($first->id, $same->id);
        self::assertTrue(MessageParticipant::query()->where('conversation_id', $first->id)->where('core_identity_reference', 'IDN-C')->exists());

        ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', 'IDN-B')->update(['status' => ZumraGroupMembership::STATUS_LEFT, 'left_at' => now()]);
        self::assertFalse($service->canAccess($first, 'IDN-B'));
    }

    public function test_invitation_conversation_does_not_accept_the_invitation(): void
    {
        $group = $this->group('ZUMRA Invitation', 'IDN-LEADER');
        $invitation = $this->membership($group, 'IDN-INVITED', ZumraGroupMembership::STATUS_INVITED);
        $service = app(MessagingService::class);

        $conversation = $service->openInvitation('IDN-INVITED', $group);
        $service->send($conversation, 'IDN-INVITED', 'Je souhaite comprendre l’objectif avant de répondre.');

        self::assertSame(ZumraGroupMembership::STATUS_INVITED, $invitation->refresh()->status);
        self::assertTrue($service->canAccess($conversation, 'IDN-LEADER'));
    }

    public function test_project_coordination_adds_only_a_message_participant(): void
    {
        $project = $this->project('Projet ouvert', Project::VISIBILITY_PUBLIC, 'IDN-OWNER');
        $profile = $this->profile('IDN-B', 'Binta Projet');
        $service = app(MessagingService::class);
        $conversation = $service->openProject('IDN-OWNER', $project);

        $service->addProjectParticipant($conversation, 'IDN-OWNER', $profile->discovery_reference);

        self::assertTrue(MessageParticipant::query()->where('conversation_id', $conversation->id)->where('core_identity_reference', 'IDN-B')->exists());
        self::assertSame('IDN-OWNER', $project->refresh()->owner_reference);
        self::assertSame(Project::STATUS_IN_PROGRESS, $project->status);
        self::assertFalse(Schema::hasTable('dg_project_memberships'));
    }

    public function test_dg_afrique_conversation_uses_currently_provisioned_administrators(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN', 'granted_by_core_reference' => 'IDN-ADMIN']);
        $service = app(MessagingService::class);
        $conversation = $service->openSupport('IDN-USER');

        self::assertTrue($service->canAccess($conversation, 'IDN-USER'));
        self::assertTrue($service->canAccess($conversation, 'IDN-ADMIN'));

        PortalAdministrator::query()->whereKey('IDN-ADMIN')->delete();
        self::assertFalse($service->canAccess($conversation, 'IDN-ADMIN'));
    }

    public function test_member_routes_open_inbox_direct_thread_and_space_link(): void
    {
        $profile = $this->profile('IDN-AWA', 'Awa Messagerie');
        $this->signIn('IDN-VIEWER');

        $this->get('/messages')->assertOk()->assertSee('Vos conversations d’action');
        $this->post('/messages/personnes/'.$profile->discovery_reference)->assertRedirect();

        $conversation = MessageConversation::query()->sole();
        $this->get(route('messages.show', $conversation))->assertOk()
            ->assertSee('Awa Messagerie')
            ->assertSee('Conversation privée de coordination');
        $this->get('/espace')->assertOk()->assertSee('Messages');
    }

    private function profile(string $identity, string $name): PersonProfile
    {
        return PersonProfile::query()->create([
            'core_identity_reference' => $identity,
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_bio' => 'Profil utile et volontaire.',
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
            'country_code' => 'CI',
            'city' => 'Abidjan',
            'participation_mode' => 'HYBRIDE',
        ]);
    }

    private function need(string $title, string $visibility, string $owner): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $owner,
            'author_core_reference' => $owner,
            'title' => $title,
            'context' => 'Un contexte réel et suffisamment précis pour organiser une coordination utile.',
            'category' => 'SKILL',
            'capability_label' => 'Coordination',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => $visibility,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $owner,
            'published_at' => now(),
        ]);
    }

    private function group(string $name, string $leader): ZumraGroup
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Coordonner des personnes autour d’une action utile et concrète.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => $leader,
            'active_member_count' => 1,
        ]);
        $this->membership($group, $leader, ZumraGroupMembership::STATUS_ACTIVE);
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id,
            'role' => 'PRIMARY_LEAD',
            'core_identity_reference' => $leader,
            'status' => ZumraGroupRole::STATUS_ACCEPTED,
            'proposed_by_core_reference' => $leader,
            'proposed_at' => now(),
            'accepted_at' => now(),
        ]);

        return $group;
    }

    private function membership(ZumraGroup $group, string $identity, string $status): ZumraGroupMembership
    {
        return ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $identity,
            'status' => $status,
            'entry_mode' => $status === ZumraGroupMembership::STATUS_INVITED ? 'INVITATION' : 'FOUNDER',
            'initiated_by_core_reference' => $group->proposer_core_reference,
            'invited_at' => $status === ZumraGroupMembership::STATUS_INVITED ? now() : null,
            'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function project(string $name, string $visibility, string $owner): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => $owner,
            'initiator_core_reference' => $owner,
            'name' => $name,
            'summary' => 'Projet concret de coordination de capacités.',
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
            'decided_by_core_reference' => $owner,
            'started_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
