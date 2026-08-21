<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zumra\ZumraGroupService;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UIUX-002 — décision #4 : fermer le trou UX autour de ZumraGroupRole::STATUS_PROPOSED.
 * Découvrir/comprendre/accepter via la transition métier existante ; aucun bouton « Refuser »
 * n'est ajouté puisque cette transition n'existe pas dans ZumraGroupService.
 */
final class ZumraRoleProposalUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_proposed_person_discovers_and_understands_the_proposal_on_the_group_page(): void
    {
        $group = $this->zumraGroup('IDN-ROLE-LEADER');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-ROLE-LEADER', 'FIRST_DEPUTY', 'IDN-ROLE-PROPOSED');

        $this->signIn('IDN-ROLE-PROPOSED');
        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();

        self::assertStringContainsString('Une responsabilité vous est proposée', $content);
        self::assertStringContainsString(ZumraGroupRole::LABELS['FIRST_DEPUTY'], $content);
        self::assertStringContainsString($group->name, $content);
        self::assertStringContainsString(route('zumra.groups.roles.accept', [$group, 'FIRST_DEPUTY']), $content);
        self::assertStringNotContainsString('Refuser', $content);
    }

    public function test_accepting_uses_the_real_business_transition(): void
    {
        $group = $this->zumraGroup('IDN-ROLE-LEADER2');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-ROLE-LEADER2', 'FIRST_DEPUTY', 'IDN-ROLE-PROPOSED2');

        $this->signIn('IDN-ROLE-PROPOSED2');
        $this->post(route('zumra.groups.roles.accept', [$group, 'FIRST_DEPUTY']))->assertRedirect();

        $role = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', 'FIRST_DEPUTY')->sole();
        self::assertSame(ZumraGroupRole::STATUS_ACCEPTED, $role->status);
        self::assertNotNull($role->accepted_at);

        // La proposition acceptée ne doit plus jamais être présentée comme « à accepter ».
        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();
        self::assertStringNotContainsString('Une responsabilité vous est proposée', $content);
    }

    public function test_another_member_cannot_see_or_accept_a_proposal_addressed_to_someone_else(): void
    {
        $group = $this->zumraGroup('IDN-ROLE-LEADER3');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-ROLE-LEADER3', 'FIRST_DEPUTY', 'IDN-ROLE-PROPOSED3');

        $this->signIn('IDN-ROLE-OTHER3');
        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();
        self::assertStringNotContainsString('Une responsabilité vous est proposée', $content);

        $this->post(route('zumra.groups.roles.accept', [$group, 'FIRST_DEPUTY']))->assertStatus(409);

        $role = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', 'FIRST_DEPUTY')->sole();
        self::assertSame(ZumraGroupRole::STATUS_PROPOSED, $role->status, 'Un tiers ne peut jamais accepter une responsabilité qui ne lui est pas destinée.');
    }

    private function zumraGroup(string $leader): ZumraGroup
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $leader,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);

        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Rôle '.uniqid(),
            'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.',
            'assume_primary_lead' => true,
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
