<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CleanupZumraPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_results_are_exhaustive_and_the_second_page_links_to_spaces(): void
    {
        $this->programMember('IDN-PAGINATION-SEARCH');
        $groups = $this->makeGroups(11, 'Recherche Audit', null, 'IDN-PAGINATION-SEARCH');
        $this->signIn('IDN-PAGINATION-SEARCH');

        $this->get('/zumra?q=Recherche+Audit')->assertOk()
            ->assertSee($groups[0]->name)
            ->assertDontSee($groups[8]->name)
            ->assertSee('q=Recherche%20Audit', false);

        $this->get('/zumra?q=Recherche+Audit&page=2')->assertOk()
            ->assertSee($groups[8]->name)
            ->assertSee(route('zumra.groups.show', $groups[8]), false);
    }

    public function test_mine_results_are_exhaustive_and_keep_combined_filters(): void
    {
        $actor = 'IDN-PAGINATION-MINE';
        $this->programMember($actor);
        $groups = $this->makeGroups(11, 'Mine Audit', ZumraGroupMembership::STATUS_ACTIVE, $actor);
        $this->signIn($actor);

        $query = 'view=mine&q=Mine+Audit&mode=HYBRID&location=Abidjan';
        $this->get('/zumra?'.$query)->assertOk()
            ->assertSee($groups[0]->name)
            ->assertDontSee($groups[8]->name)
            ->assertSee('view=mine', false)
            ->assertSee('mode=HYBRID', false)
            ->assertSee('location=Abidjan', false);

        $this->get('/zumra?'.$query.'&page=2')->assertOk()->assertSee($groups[8]->name);
    }

    public function test_invitation_results_are_exhaustive(): void
    {
        $actor = 'IDN-PAGINATION-INVITED';
        $this->programMember($actor);
        $groups = $this->makeGroups(11, 'Invitation Audit', ZumraGroupMembership::STATUS_INVITED, $actor);
        $this->signIn($actor);

        $this->get('/zumra?view=invited')->assertOk()->assertDontSee($groups[8]->name);
        $this->get('/zumra?view=invited&page=2')->assertOk()->assertSee($groups[8]->name);
    }

    public function test_requested_results_are_exhaustive_and_legacy_directory_still_redirects(): void
    {
        $actor = 'IDN-PAGINATION-REQUESTED';
        $this->programMember($actor);
        $groups = $this->makeGroups(11, 'Demande Audit', ZumraGroupMembership::STATUS_REQUESTED, $actor);
        $this->signIn($actor);

        $this->get('/zumra?view=requested')->assertOk()->assertDontSee($groups[8]->name);
        $this->get('/zumra?view=requested&page=2')->assertOk()->assertSee($groups[8]->name);
        $this->get('/zumra/groupes?view=requested')
            ->assertRedirect(route('zumra.index', ['view' => 'requested']));
    }

    /** @return array<int, ZumraGroup> */
    private function makeGroups(int $count, string $prefix, ?string $membershipStatus, string $actor): array
    {
        $groups = [];
        foreach (range(1, $count) as $index) {
            $group = ZumraGroup::query()->create([
                'public_reference' => (string) Str::uuid(),
                'name' => $prefix.' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'slug' => Str::slug($prefix).'-'.$index,
                'domain' => 'Numérique',
                'founding_objective' => 'Une ZUMRA créée pour vérifier que toute exploration reste exhaustive et accessible sans restaurer l’ancien annuaire.',
                'participation_mode' => 'HYBRID',
                'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY,
                'location' => 'Abidjan',
                'internal_charter' => null,
                'state' => ZumraGroup::STATE_ACTIVE,
                'maturity' => ZumraGroup::MATURITY_EMERGING,
                'proposer_core_reference' => 'IDN-PAGINATION-OWNER-'.$index,
                'active_member_count' => $membershipStatus === ZumraGroupMembership::STATUS_ACTIVE ? 1 : 0,
            ]);
            $group->forceFill(['created_at' => now()->addSeconds($index)])->saveQuietly();
            if ($membershipStatus !== null) {
                ZumraGroupMembership::query()->create([
                    'zumra_group_id' => $group->id,
                    'core_identity_reference' => $actor,
                    'status' => $membershipStatus,
                    'entry_mode' => $membershipStatus === ZumraGroupMembership::STATUS_INVITED ? 'INVITATION' : 'REQUEST',
                    'initiated_by_core_reference' => $actor,
                    'requested_at' => $membershipStatus === ZumraGroupMembership::STATUS_REQUESTED ? now() : null,
                    'invited_at' => $membershipStatus === ZumraGroupMembership::STATUS_INVITED ? now() : null,
                    'joined_at' => $membershipStatus === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
                ]);
            }
            $groups[] = $group;
        }

        return $groups;
    }

    private function programMember(string $identity): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $identity, 'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(),
            'submitted_at' => now(), 'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-24T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-24T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
