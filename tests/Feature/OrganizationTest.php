<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationCapabilityService;
use App\Application\Organizations\OrganizationService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Organization;
use App\Models\OrganizationEvent;
use App\Models\OrganizationMembership;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-066 — Organisation : une structure durable distincte d'un Projet (une action organisée
 * autour d'une transformation) et d'une ZUMRA (une communauté d'action et de transmission).
 * Gouvernance explicite (OWNER/ADMIN/MEMBER), visibilité réelle, événements audités, aucune
 * mutation automatique depuis un Projet ou une ZUMRA.
 */
final class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_is_authorized_for_a_signed_in_member(): void
    {
        $this->signIn('IDN-FOUNDER');
        $this->fakeCoreOrganizationProvisioning();

        $this->post('/organisations', $this->payload())->assertRedirect();

        self::assertSame(1, Organization::query()->count());
    }

    public function test_core_references_are_persisted_on_creation(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        self::assertNotNull($organization->core_identity_reference);
        self::assertNotNull($organization->core_organization_reference);
        self::assertSame(Organization::CORE_LINK_LINKED, $organization->core_link_status);
    }

    public function test_core_failure_finalizes_no_local_organization(): void
    {
        $this->signIn('IDN-FOUNDER');
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'product-bearer', 'entite' => 'PRD-GAMAD-005',
                'assurance' => 'A1', 'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites' => Http::response([], 503),
        ]);

        $this->post('/organisations', $this->payload())->assertRedirect();

        self::assertSame(0, Organization::query()->count());
    }

    public function test_creation_without_identity_is_refused(): void
    {
        $this->post('/organisations', $this->payload())->assertRedirect('/connexion?next=%2Forganisations');

        self::assertSame(0, Organization::query()->count());
    }

    public function test_public_reference_is_unique_across_organizations(): void
    {
        $first = $this->organization('IDN-FOUNDER');
        $second = $this->organization('IDN-OTHER');

        self::assertNotSame($first->public_reference, $second->public_reference);
    }

    public function test_the_founder_is_correctly_associated_as_owner(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('core_identity_reference', 'IDN-FOUNDER')
            ->sole();

        self::assertSame(OrganizationMembership::ROLE_OWNER, $membership->role);
        self::assertSame(OrganizationMembership::STATUS_ACTIVE, $membership->status);
        self::assertSame('IDN-FOUNDER', $organization->founder_core_reference);
    }

    public function test_reading_a_public_organization_is_authorized_for_any_member(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $this->signIn('IDN-STRANGER');

        $this->get('/organisations/'.$organization->public_reference)
            ->assertOk()
            ->assertSee($organization->name);
    }

    public function test_reading_an_inaccessible_private_organization_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PRIVATE]);
        $this->signIn('IDN-STRANGER');

        $this->get('/organisations/'.$organization->public_reference)->assertNotFound();
    }

    public function test_update_by_an_authorized_actor_succeeds(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $updated = app(OrganizationService::class)->update($organization, 'IDN-FOUNDER', ['name' => 'Nouveau nom']);

        self::assertSame('Nouveau nom', $updated->name);
    }

    public function test_update_by_a_stranger_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $this->assertAborts(403, fn () => app(OrganizationService::class)->update($organization, 'IDN-STRANGER', ['name' => 'Usurpation']));
    }

    public function test_a_manager_can_approve_a_membership_request(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $service = app(OrganizationService::class);
        $service->requestToJoin($organization, 'IDN-CANDIDATE', 'Je veux contribuer.');
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', 'IDN-CANDIDATE')->sole();

        $service->approveRequest($organization, 'IDN-FOUNDER', $membership->id);

        self::assertSame(OrganizationMembership::STATUS_ACTIVE, $membership->refresh()->status);
        self::assertSame(2, $organization->refresh()->active_member_count);
    }

    public function test_approving_a_membership_request_by_a_non_manager_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $service = app(OrganizationService::class);
        $service->requestToJoin($organization, 'IDN-CANDIDATE', null);
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', 'IDN-CANDIDATE')->sole();

        $this->assertAborts(403, fn () => $service->approveRequest($organization, 'IDN-OUTSIDER', $membership->id));
    }

    public function test_a_manager_can_remove_a_member(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $service = app(OrganizationService::class);
        $service->invite($organization, 'IDN-FOUNDER', 'IDN-MEMBER');
        $service->acceptInvitation($organization, 'IDN-MEMBER');
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', 'IDN-MEMBER')->sole();

        $service->removeMember($organization, 'IDN-FOUNDER', $membership->id, 'Inactivité prolongée.');

        self::assertSame(OrganizationMembership::STATUS_REMOVED, $membership->refresh()->status);
        self::assertSame(1, $organization->refresh()->active_member_count);
    }

    public function test_minimal_governance_cannot_be_broken_by_removing_or_self_removing_the_owner(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $service = app(OrganizationService::class);
        $ownerMembership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', 'IDN-FOUNDER')->sole();

        $this->assertAborts(409, fn () => $service->removeMember($organization, 'IDN-FOUNDER', $ownerMembership->id, 'Tentative de retrait du fondateur.'));
        $this->assertAborts(409, fn () => $service->leave($organization, 'IDN-FOUNDER'));
    }

    public function test_an_unknown_role_is_rejected_on_invite(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $this->assertAborts(422, fn () => app(OrganizationService::class)->invite($organization, 'IDN-FOUNDER', 'IDN-CANDIDATE', 'SUPER_ADMIN'));
    }

    public function test_organizations_are_isolated_from_one_another(): void
    {
        $mine = $this->organization('IDN-FOUNDER');
        $other = $this->organization('IDN-OTHER-FOUNDER');
        $service = app(OrganizationService::class);
        $service->invite($other, 'IDN-OTHER-FOUNDER', 'IDN-CANDIDATE');
        $membershipInOther = OrganizationMembership::query()->where('organization_id', $other->id)->where('core_identity_reference', 'IDN-CANDIDATE')->sole();

        $this->assertAborts(403, fn () => $service->approveRequest($mine, 'IDN-OTHER-FOUNDER', $membershipInOther->id));
    }

    public function test_business_events_are_produced_for_creation_join_and_removal(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $service = app(OrganizationService::class);
        $service->invite($organization, 'IDN-FOUNDER', 'IDN-MEMBER');
        $service->acceptInvitation($organization, 'IDN-MEMBER');
        $membership = OrganizationMembership::query()->where('organization_id', $organization->id)->where('core_identity_reference', 'IDN-MEMBER')->sole();
        $service->removeMember($organization, 'IDN-FOUNDER', $membership->id, 'Test.');

        $events = OrganizationEvent::query()->where('organization_id', $organization->id)->pluck('event')->all();

        self::assertContains('ORGANIZATION_CREATED', $events);
        self::assertContains('MEMBER_INVITED', $events);
        self::assertContains('INVITATION_ACCEPTED', $events);
        self::assertContains('MEMBER_REMOVED', $events);
    }

    public function test_no_private_resource_leaks_through_the_index_listing(): void
    {
        $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PRIVATE, 'name' => 'Organisation secrète']);
        $this->signIn('IDN-STRANGER');

        $this->get('/organisations')->assertOk()->assertDontSee('Organisation secrète');
    }

    public function test_creating_an_organization_never_mutates_unrelated_projects_or_zumra_groups(): void
    {
        $projectsBefore = Project::query()->count();
        $missionsBefore = Mission::query()->count();
        $needsBefore = Need::query()->count();
        $groupsBefore = ZumraGroup::query()->count();

        $this->organization('IDN-FOUNDER');

        self::assertSame($projectsBefore, Project::query()->count());
        self::assertSame($missionsBefore, Mission::query()->count());
        self::assertSame($needsBefore, Need::query()->count());
        self::assertSame($groupsBefore, ZumraGroup::query()->count());
    }

    public function test_a_person_can_belong_to_several_organizations_at_once(): void
    {
        $first = $this->organization('IDN-MULTI');
        $second = $this->organization('IDN-OTHER-FOUNDER');
        app(OrganizationService::class)->invite($second, 'IDN-OTHER-FOUNDER', 'IDN-MULTI');
        app(OrganizationService::class)->acceptInvitation($second, 'IDN-MULTI');

        self::assertSame(2, OrganizationMembership::query()->where('core_identity_reference', 'IDN-MULTI')->where('status', OrganizationMembership::STATUS_ACTIVE)->count());
        self::assertNotSame($first->id, $second->id);
    }

    public function test_type_and_status_validations_are_enforced_on_creation(): void
    {
        $this->assertAborts(422, fn () => app(OrganizationService::class)->create('IDN-FOUNDER', array_replace($this->payload(), ['type' => 'NOT_A_REAL_TYPE'])));
        $this->assertAborts(422, fn () => app(OrganizationService::class)->create('IDN-FOUNDER', array_replace($this->payload(), ['type' => 'OTHER', 'other_type_label' => ''])));
    }

    public function test_a_simple_read_never_mutates_the_organization(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $updatedAtBefore = $organization->updated_at;
        $this->signIn('IDN-STRANGER');

        app(OrganizationService::class)->canView($organization, 'IDN-STRANGER');
        $this->get('/organisations/'.$organization->public_reference)->assertOk();

        self::assertEquals($updatedAtBefore, $organization->refresh()->updated_at);
        self::assertSame(1, $organization->fresh()->active_member_count, 'Une lecture ne doit jamais faire varier le compteur de membres.');
    }

    // ===== UIUX-006 — fiche Organisation recomposée =====

    public function test_a_regular_member_sees_no_manager_only_management_section(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER1', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        app(OrganizationService::class)->invite($organization, 'IDN-UI6-FOUNDER1', 'IDN-UI6-MEMBER1');
        app(OrganizationService::class)->acceptInvitation($organization, 'IDN-UI6-MEMBER1');

        $this->signIn('IDN-UI6-MEMBER1');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringNotContainsString('Déclarer cette capacité', $content);
        self::assertStringNotContainsString('<legend><span class="dg-label">Gestion</span>', $content);
    }

    public function test_a_manager_sees_the_management_actions(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER2');

        $this->signIn('IDN-UI6-FOUNDER2');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringContainsString('<legend><span class="dg-label">Gestion</span>', $content);
        self::assertStringContainsString('Déclarer cette capacité', $content);
    }

    public function test_capabilities_are_presented_in_human_language(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER3');
        app(OrganizationCapabilityService::class)->declare($organization, 'IDN-UI6-FOUNDER3', ['label' => 'Formation en maraîchage urbain']);

        $this->signIn('IDN-UI6-FOUNDER3');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringContainsString('Ce que cette organisation peut apporter', $content);
        self::assertStringContainsString('Formation en maraîchage urbain', $content);
    }

    public function test_members_are_displayed_with_a_readable_identity_and_role(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER4');
        app(OrganizationService::class)->invite($organization, 'IDN-UI6-FOUNDER4', 'IDN-UI6-DISCOVERABLE');
        app(OrganizationService::class)->acceptInvitation($organization, 'IDN-UI6-DISCOVERABLE');
        app(OrganizationService::class)->invite($organization, 'IDN-UI6-FOUNDER4', 'IDN-UI6-HIDDEN');
        app(OrganizationService::class)->acceptInvitation($organization, 'IDN-UI6-HIDDEN');
        PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-UI6-DISCOVERABLE',
            'discovery_consent' => true,
            'discovery_display_name' => 'Aïcha Traoré',
        ]);

        $this->signIn('IDN-UI6-FOUNDER4');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringContainsString('Vous', $content);
        self::assertStringContainsString('Fondateur / propriétaire', $content);
        self::assertStringContainsString('Aïcha Traoré', $content);
        self::assertStringContainsString('Membre DG Afrique', $content);
        self::assertStringNotContainsString('IDN-UI6-HIDDEN', $content);
    }

    public function test_no_cap_067_or_core_jargon_leaks_on_the_organization_fiche(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER5');
        app(OrganizationCapabilityService::class)->declare($organization, 'IDN-UI6-FOUNDER5', ['label' => 'Appui logistique']);

        $this->signIn('IDN-UI6-FOUNDER5');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringNotContainsString('CAP-067', $content);
        self::assertStringNotContainsString('CapabilityStatement', $content);
        self::assertStringNotContainsString('holder_type', $content);
        self::assertStringNotContainsString((string) $organization->core_identity_reference, $content);
        self::assertStringNotContainsString((string) $organization->core_organization_reference, $content);
    }

    public function test_no_activity_is_fabricated_when_the_organization_has_no_collaboration_or_event(): void
    {
        $organization = $this->organization('IDN-UI6-FOUNDER6');

        $this->signIn('IDN-UI6-FOUNDER6');
        $content = $this->get('/organisations/'.$organization->public_reference)->assertOk()->getContent();

        self::assertStringContainsString('Aucune activité dans le réseau pour le moment.', $content);
    }

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace($this->payload(), $overrides));
    }

    /**
     * CAP-067 — la création d'une Organisation demande désormais une identité (CAP-CORE-001) et
     * une fiche (CAP-CORE-002) canoniques réelles à GAMAD Core avant toute écriture locale. Une
     * fermeture globale, jamais un tableau d'URL statiques : elle inspecte le corps de la requête
     * pour ne répondre qu'aux appels de session PRODUIT (`PRD-GAMAD-005`), sans jamais intercepter
     * la session MEMBRE ouverte par signIn() — quel que soit l'ordre d'appel des deux — et génère
     * des références Core fraîches à chaque appel, jamais un doublon.
     */
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

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Atelier numérique coopératif',
            'description' => 'Une structure durable qui porte des responsabilités et des ressources dans la durée.',
            'type' => 'COOPERATIVE',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ], $overrides);
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
