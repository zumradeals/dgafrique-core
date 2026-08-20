<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Organization;
use App\Models\OrganizationEvent;
use App\Models\OrganizationMembership;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $this->post('/organisations', $this->payload())->assertRedirect();

        self::assertSame(1, Organization::query()->count());
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
        return app(OrganizationService::class)->create($founder, array_replace($this->payload(), $overrides));
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
