<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_sees_only_activity_from_current_zumra(): void
    {
        $group = $this->group('ZUMRA Alpha');
        $other = $this->group('ZUMRA Beta');
        $this->member($group, 'IDN-A');
        $this->member($other, 'IDN-B');

        ZumraGroupEvent::query()->create([
            'zumra_group_id' => $group->id,
            'event' => 'MEMBERSHIP_APPROVED',
            'actor_core_reference' => 'IDN-A',
            'context' => [],
            'occurred_at' => now(),
        ]);
        ZumraGroupEvent::query()->create([
            'zumra_group_id' => $other->id,
            'event' => 'GROUP_VALIDATED',
            'actor_core_reference' => 'IDN-B',
            'context' => [],
            'occurred_at' => now(),
        ]);

        $this->signIn('IDN-A');

        $this->get(route('zumra.groups.activity', $group))
            ->assertOk()
            ->assertSee('FIL · ZUMRA')
            ->assertSee('Un membre a rejoint la ZUMRA')
            ->assertDontSee('ZUMRA Beta');
    }

    public function test_outsider_cannot_open_zumra_activity(): void
    {
        $group = $this->group('ZUMRA Alpha');
        $this->member($group, 'IDN-A');
        $this->signIn('IDN-OUTSIDER');

        $this->get(route('zumra.groups.activity', $group))->assertNotFound();
    }

    public function test_suspended_zumra_is_hidden_from_regular_member(): void
    {
        $group = $this->group('ZUMRA Alpha');
        $group->update(['state' => ZumraGroup::STATE_SUSPENDED]);
        $this->member($group, 'IDN-A');
        $this->signIn('IDN-A');

        $this->get(route('zumra.groups.activity', $group))->assertNotFound();
    }

    private function group(string $name): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Agir ensemble autour d’un objectif utile.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect et transmission. ', 5),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-FOUNDER',
            'active_member_count' => 1,
        ]);
    }

    private function member(ZumraGroup $group, string $reference): void
    {
        ZumraGroupMembership::query()->create([
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
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-31T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre GAMAD', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-31T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
