<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zumra\ZumraGroupConfiguration;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_active_program_member_can_propose_a_group(): void
    {
        $this->signIn('IDN-PENDING');
        $this->get('/zumra/groupes/proposer')->assertNotFound();

        $this->programMember('IDN-ACTIVE');
        session()->flush();
        $this->signIn('IDN-ACTIVE');
        $this->get('/zumra/groupes/proposer')->assertOk()->assertSee('Proposer une nouvelle ZUMRA');
    }

    public function test_creation_opens_a_real_constitution_with_five_distinct_seats(): void
    {
        $this->programMember('IDN-FOUNDER');
        $this->signIn('IDN-FOUNDER');

        $this->post('/zumra/groupes', $this->groupPayload())->assertRedirect();

        $group = ZumraGroup::query()->sole();
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->state);
        self::assertSame(1, $group->active_member_count);
        self::assertSame(5, $group->roles()->count());
        self::assertSame(1, $group->roles()->where('status', ZumraGroupRole::STATUS_ACCEPTED)->count());
        self::assertSame(4, $group->roles()->where('status', ZumraGroupRole::STATUS_VACANT)->count());
        self::assertSame(1, $group->memberships()->where('status', ZumraGroupMembership::STATUS_ACTIVE)->count());
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'GROUP_PROPOSED')->count());

        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Cinq responsabilités, cinq personnes')
            ->assertSee('1/5 responsabilités acceptées', false);
    }

    public function test_a_join_request_never_creates_membership_before_leader_approval(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-APPLICANT');
        session()->flush();
        $this->signIn('IDN-APPLICANT');
        $this->post(route('zumra.groups.request', $group), ['motivation' => 'Je souhaite apprendre et participer au projet.'])->assertRedirect();

        $request = ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-APPLICANT')->sole();
        self::assertSame(ZumraGroupMembership::STATUS_REQUESTED, $request->status);
        self::assertSame(1, $group->refresh()->active_member_count);

        session()->flush();
        $this->signIn('IDN-LEADER');
        $this->post(route('zumra.groups.requests.approve', [$group, $request->id]))->assertRedirect();
        self::assertSame(ZumraGroupMembership::STATUS_ACTIVE, $request->refresh()->status);
        self::assertSame(2, $group->refresh()->active_member_count);
    }

    public function test_an_invitation_requires_acceptance_and_is_limited_to_a_real_active_member(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-INVITED');
        $profile = PersonProfile::query()->create(['core_identity_reference' => 'IDN-INVITED', 'discovery_reference' => (string) Str::uuid(), 'discovery_display_name' => 'Membre invité', 'discovery_consent' => true, 'discovery_consented_at' => now()]);
        $this->signIn('IDN-LEADER');
        $this->post(route('zumra.groups.invite', $group), ['person_reference' => $profile->discovery_reference])->assertRedirect();

        $invitation = ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-INVITED')->sole();
        self::assertSame(ZumraGroupMembership::STATUS_INVITED, $invitation->status);
        self::assertSame(1, $group->refresh()->active_member_count);

        session()->flush();
        $this->signIn('IDN-INVITED');
        $this->post(route('zumra.groups.invitation.accept', $group))->assertRedirect();
        self::assertSame(ZumraGroupMembership::STATUS_ACTIVE, $invitation->refresh()->status);
        self::assertSame(2, $group->refresh()->active_member_count);
    }

    public function test_a_member_can_leave_freely_but_an_accepted_responsible_must_transfer_first(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-MEMBER');
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now()]);
        $group->update(['active_member_count' => 2]);

        session()->flush();
        $this->signIn('IDN-MEMBER');
        $this->post(route('zumra.groups.leave', $group))->assertRedirect(route('zumra.groups.index'));
        self::assertSame(ZumraGroupMembership::STATUS_LEFT, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);

        session()->flush();
        $this->signIn('IDN-LEADER');
        $this->post(route('zumra.groups.leave', $group))->assertStatus(409);
    }

    public function test_an_administrator_configures_operational_thresholds_without_changing_invariants(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');
        $this->put('/administration/groupes-zumra', [
            'directory_title' => 'Les équipes en action',
            'directory_intro' => 'Une présentation administrable des équipes.',
            'creation_title' => 'Créer une équipe utile',
            'established_member_threshold' => 75,
            'max_simultaneous_founder_roles' => 4,
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(ZumraGroupConfiguration::KEY);
        self::assertSame(75, $setting->value['established_member_threshold']);
        self::assertSame(4, $setting->value['max_simultaneous_founder_roles']);
        self::assertFalse($setting->value['auto_validation_enabled']);
        self::assertSame('IDN-ADMIN', $setting->updated_by_core_reference);
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);
        $this->signIn($leader);
        $this->post('/zumra/groupes', $this->groupPayload())->assertRedirect();

        return ZumraGroup::query()->latest()->firstOrFail();
    }

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier numérique solidaire '.Str::random(5),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables. Les admissions sont approuvées, le départ reste libre et toute exclusion doit être motivée.',
            'assume_primary_lead' => '1',
        ];
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
