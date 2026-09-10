<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PersonProfile;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_sees_only_active_members_and_accepted_roles(): void
    {
        $group = $this->group();
        $this->member($group, 'IDN-A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->member($group, 'IDN-B', ZumraGroupMembership::STATUS_ACTIVE);
        $this->member($group, 'IDN-LEFT', ZumraGroupMembership::STATUS_LEFT);
        $this->profile('IDN-B', 'Awa Koné', true);
        $this->profile('IDN-LEFT', 'Ancien Membre', true);
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id, 'role' => 'FINANCE_LEAD', 'core_identity_reference' => 'IDN-B',
            'status' => ZumraGroupRole::STATUS_ACCEPTED, 'proposed_by_core_reference' => 'IDN-A', 'accepted_at' => now(),
        ]);
        $this->signIn('IDN-A');

        $this->get(route('zumra.groups.members', $group))
            ->assertOk()
            ->assertSee('Les personnes qui font vivre '.$group->name)
            ->assertSee('Awa Koné')
            ->assertSee('Responsable financier')
            ->assertSeeInOrder(['2', 'membres actifs'])
            ->assertDontSee('Ancien Membre');
    }

    public function test_non_discoverable_member_is_not_personally_exposed(): void
    {
        $group = $this->group();
        $this->member($group, 'IDN-A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->member($group, 'IDN-PRIVATE', ZumraGroupMembership::STATUS_ACTIVE);
        $this->profile('IDN-PRIVATE', 'Nom Privé', false);
        $this->signIn('IDN-A');

        $this->get(route('zumra.groups.members', $group))
            ->assertOk()
            ->assertSee('Membre de la ZUMRA')
            ->assertSee('Profil non publié dans Découvrir')
            ->assertDontSee('Nom Privé');
    }

    public function test_outsider_cannot_open_members_space(): void
    {
        $group = $this->group();
        $this->member($group, 'IDN-A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->signIn('IDN-OUTSIDER');

        $this->get(route('zumra.groups.members', $group))->assertNotFound();
    }

    public function test_discoverable_member_reuses_existing_gamad_profile_route(): void
    {
        $group = $this->group();
        $this->member($group, 'IDN-A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->member($group, 'IDN-B', ZumraGroupMembership::STATUS_ACTIVE);
        $profile = $this->profile('IDN-B', 'Awa Koné', true);
        $this->signIn('IDN-A');

        $this->get(route('zumra.groups.members', $group))
            ->assertOk()
            ->assertSee(route('people.show', $profile->discovery_reference), false);
    }

    private function group(): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(), 'name' => 'GAMAD Technology',
            'slug' => 'gamad-technology-'.Str::lower(Str::random(5)), 'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes autour de compétences technologiques utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect et transmission. ', 5),
            'state' => ZumraGroup::STATE_ACTIVE, 'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-A', 'active_member_count' => 2,
        ]);
    }

    private function member(ZumraGroup $group, string $reference, string $status): void
    {
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => $reference, 'status' => $status,
            'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $reference, 'joined_at' => now(),
        ]);
    }

    private function profile(string $reference, string $name, bool $discoverable): PersonProfile
    {
        return PersonProfile::query()->create([
            'core_identity_reference' => $reference, 'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name, 'discovery_bio' => 'Profil GAMAD existant.',
            'discovery_consent' => $discoverable, 'discovery_consented_at' => $discoverable ? now() : null,
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-31T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre GAMAD', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-31T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
