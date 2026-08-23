<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationService;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProjectDraft;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-008 Phase B — fermeture des ruptures concrètes identifiées par l'audit Phase A. Chaque
 * test vérifie explicitement une absence autant qu'une présence : aucune action non autorisée,
 * aucune transition inventée, aucune fuite de jargon CAP.
 */
final class UIUX008BridgesTest extends TestCase
{
    use RefreshDatabase;

    // ===== §2 Agir → Transmission =====

    public function test_the_agir_sheet_links_to_the_real_transmission_creation_route(): void
    {
        $this->signIn('IDN-U8-AGIR');
        $content = $this->get(route('member.space'))->assertOk()->getContent();

        // La régression identifiée par l'audit Phase A (href vers member.profile.edit) ne doit
        // plus jamais réapparaître : le lien précédant immédiatement le libellé « Proposer une
        // transmission » doit être la vraie route de création.
        $pos = strpos($content, 'Proposer une transmission');
        self::assertNotFalse($pos, 'Le geste « Proposer une transmission » a disparu de la sheet Agir.');
        $before = substr($content, max(0, $pos - 300), 300);
        self::assertMatchesRegularExpression('/href="'.preg_quote(route('transmissions.create'), '/').'"/', $before);
        self::assertDoesNotMatchRegularExpression('/href="'.preg_quote(route('member.profile.edit'), '/').'"/', $before);
    }

    // ===== §3 Portes persistantes Mes transmissions / Carnet de preuves =====

    public function test_mon_espace_links_to_transmissions_and_proofs_indexes(): void
    {
        $this->signIn('IDN-U8-OUTILS');
        $content = $this->get(route('member.space'))->assertOk()->getContent();

        self::assertStringContainsString('href="'.route('transmissions.index').'"', $content);
        self::assertStringContainsString('href="'.route('proofs.index').'"', $content);
    }

    public function test_transmissions_and_proofs_indexes_remain_reachable(): void
    {
        $this->signIn('IDN-U8-REACH');
        $this->get(route('transmissions.index'))->assertOk();
        $this->get(route('proofs.index'))->assertOk();
    }

    // ===== §4 /projets — voie directe et voie Brain =====

    public function test_projets_page_offers_both_the_brain_and_a_direct_creation_path(): void
    {
        $this->signIn('IDN-U8-PROJETS');
        $content = $this->get(route('projects.index'))->assertOk()->getContent();

        self::assertStringContainsString('href="'.route('projects.brain.start').'"', $content);
        self::assertStringContainsString('href="'.route('projects.create').'"', $content);
    }

    public function test_a_project_can_be_started_from_projets_without_ever_hitting_a_brain_route(): void
    {
        $this->activateProgram('IDN-U8-DIRECT');
        $this->signIn('IDN-U8-DIRECT');

        // La voie directe découverte sur /projets mène bien au parcours déterministe progressif
        // (UIUX-009B), jamais au Cerveau — le formulaire simple d'origine a depuis été remplacé
        // par ce parcours, sous le même nom de route « projects.create ».
        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-U8-DIRECT')->firstOrFail();
        $this->get(route('projects.draft.show', [$draft, 'audience']))->assertOk()->assertDontSee(route('projects.brain.start'), false);
    }

    // ===== §5 Organisation — approbation d'adhésion =====

    public function test_the_manager_can_approve_a_requested_membership(): void
    {
        $organization = $this->organization('IDN-U8-ORGOWNER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $requester = 'IDN-U8-ORGREQ';
        app(OrganizationService::class)->requestToJoin($organization, $requester, 'Je souhaite contribuer.');
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', $requester)->firstOrFail();

        $this->signIn('IDN-U8-ORGOWNER');
        $content = $this->get(route('organizations.show', $organization))->assertOk()->getContent();
        self::assertStringContainsString('Demandes d’adhésion en attente', $content);

        $this->post(route('organizations.requests.approve', [$organization, $membership]))->assertRedirect();

        self::assertDatabaseHas('dg_organization_memberships', [
            'id' => $membership->id, 'status' => OrganizationMembership::STATUS_ACTIVE,
        ]);
    }

    public function test_an_unauthorized_member_cannot_approve_a_requested_membership(): void
    {
        $organization = $this->organization('IDN-U8-ORGOWNER2', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $requester = 'IDN-U8-ORGREQ2';
        app(OrganizationService::class)->requestToJoin($organization, $requester, null);
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', $requester)->firstOrFail();

        // Un simple membre actif (jamais OWNER/ADMIN) ne doit jamais pouvoir approuver.
        $plainMember = 'IDN-U8-ORGPLAIN2';
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'core_identity_reference' => $plainMember,
            'role' => OrganizationMembership::ROLE_MEMBER, 'status' => OrganizationMembership::STATUS_ACTIVE,
            'entry_mode' => 'INVITATION', 'initiated_by_core_reference' => 'IDN-U8-ORGOWNER2', 'joined_at' => now(),
        ]);

        $this->signIn($plainMember);
        $content = $this->get(route('organizations.show', $organization))->assertOk()->getContent();
        self::assertStringNotContainsString('Demandes d’adhésion en attente', $content);

        $this->post(route('organizations.requests.approve', [$organization, $membership]))->assertForbidden();

        self::assertDatabaseHas('dg_organization_memberships', [
            'id' => $membership->id, 'status' => OrganizationMembership::STATUS_REQUESTED,
        ]);
    }

    public function test_no_rejection_route_was_invented_for_a_requested_membership(): void
    {
        // Le mandat interdit explicitement d'inventer un refus : aucune transition métier de
        // refus n'existe pour une demande REQUESTED (removeMember() exige STATUS_ACTIVE).
        self::assertFalse(Route::has('organizations.requests.reject'));
        self::assertFalse(Route::has('organizations.requests.decline'));
        self::assertFalse(Route::has('organizations.requests.deny'));
    }

    // ===== Aucune fuite de jargon CAP =====

    public function test_none_of_the_touched_surfaces_ever_leak_a_cap_identifier(): void
    {
        $organization = $this->organization('IDN-U8-JARGON', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        app(OrganizationService::class)->requestToJoin($organization, 'IDN-U8-JARGONREQ', null);

        $this->signIn('IDN-U8-JARGON');
        foreach ([
            route('member.space'),
            route('projects.index'),
            route('organizations.show', $organization),
        ] as $url) {
            $content = $this->get($url)->assertOk()->getContent();
            self::assertDoesNotMatchRegularExpression('/CAP-\d/', $content);
        }
    }

    // ===== Helpers =====

    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Organisation UIUX-008 '.Str::random(6),
            'description' => 'Une structure durable qui porte des responsabilités et des ressources dans la durée.',
            'type' => 'COOPERATIVE', 'visibility' => Organization::VISIBILITY_PRIVATE,
        ], $overrides));
    }

    private function fakeCoreOrganizationProvisioning(): void
    {
        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_ends_with($url, '/sessions') && ($request['entite'] ?? null) === 'PRD-GAMAD-005') {
                return Http::response([
                    'jeton' => 'product-bearer-'.Str::random(8), 'entite' => 'PRD-GAMAD-005',
                    'assurance' => 'A1', 'expire_le' => '2026-08-16T23:59:00+00:00',
                ], 201);
            }
            if (str_ends_with($url, '/identites')) {
                return Http::response([
                    'identite' => ['reference' => 'IDN-CORE-ORG-'.Str::random(12), 'etat' => 'ACTIVE', 'assurance' => 'A1'],
                ], 201);
            }
            if (str_ends_with($url, '/organisations')) {
                return Http::response([
                    'resultat' => [
                        'reference' => 'ORG-GAMAD-'.Str::random(8), 'identite_reference' => 'IDN-CORE-ORG-'.Str::random(12),
                        'etat' => 'PREPARATION', 'type_organisation_reference' => 'INDETERMINE',
                    ],
                ], 201);
            }

            return null;
        });
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $reference],
            ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()],
        );
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
