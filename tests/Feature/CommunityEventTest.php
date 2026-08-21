<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Community\CommunityEventService;
use App\Application\Organizations\OrganizationService;
use App\Models\CommunityEvent;
use App\Models\CommunityEventParticipant;
use App\Models\Contribution;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-068 — Événement (rencontre/atelier/activité calendaire), distinct de Mission et des journaux
 * append-only *Event. Organisateur V1 : ZUMRA_GROUP ou ORGANIZATION uniquement, réutilisant
 * ZumraGroupService::isLeader() / OrganizationService::isManager()/canView().
 */
final class CommunityEventTest extends TestCase
{
    use RefreshDatabase;

    // ===== Autorisation de création =====

    public function test_a_zumra_leader_can_create_an_event(): void
    {
        $group = $this->group('IDN-LEADER');

        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        self::assertSame(CommunityEvent::STATUS_SCHEDULED, $event->status);
        self::assertSame(CommunityEvent::ORGANIZER_ZUMRA_GROUP, $event->organizer_type);
    }

    public function test_an_ordinary_zumra_member_cannot_create_an_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        $this->assertAborts(403, fn () => app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-MEMBER', $this->payload()));
    }

    public function test_an_organization_manager_can_create_an_event(): void
    {
        $organization = $this->organization('IDN-OWNER');

        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-OWNER', $this->payload());

        self::assertSame(CommunityEvent::ORGANIZER_ORGANIZATION, $event->organizer_type);
    }

    public function test_an_ordinary_organization_member_cannot_create_an_event(): void
    {
        $organization = $this->organization('IDN-OWNER');
        $this->orgMember($organization, 'IDN-MEMBER', OrganizationMembership::ROLE_MEMBER);

        $this->assertAborts(403, fn () => app(CommunityEventService::class)->createForOrganization($organization, 'IDN-MEMBER', $this->payload()));
    }

    // ===== Visibilité =====

    public function test_an_outsider_cannot_view_an_internal_zumra_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        self::assertFalse(app(CommunityEventService::class)->canView($event, 'IDN-OUTSIDER'));
    }

    public function test_an_active_zumra_member_can_view_an_internal_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        self::assertTrue(app(CommunityEventService::class)->canView($event, 'IDN-MEMBER'));
    }

    public function test_anyone_can_view_a_public_event_without_membership(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload(['visibility' => CommunityEvent::VISIBILITY_PUBLIC]));

        self::assertTrue(app(CommunityEventService::class)->canView($event, 'IDN-OUTSIDER'));
    }

    public function test_registering_for_an_inaccessible_internal_event_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        $this->assertAborts(404, fn () => app(CommunityEventService::class)->register($event, 'IDN-OUTSIDER'));
        self::assertSame(0, CommunityEventParticipant::query()->count());
    }

    // ===== Inscription / désinscription =====

    public function test_a_member_can_register_and_unregister(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        $service = app(CommunityEventService::class);

        $participant = $service->register($event, 'IDN-MEMBER');
        self::assertSame(CommunityEventParticipant::STATUS_REGISTERED, $participant->status);

        $service->unregister($event, 'IDN-MEMBER');
        self::assertSame(CommunityEventParticipant::STATUS_WITHDRAWN, CommunityEventParticipant::query()->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);
    }

    public function test_registering_twice_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        $service = app(CommunityEventService::class);
        $service->register($event, 'IDN-MEMBER');

        $this->assertAborts(409, fn () => $service->register($event, 'IDN-MEMBER'));
    }

    public function test_the_partial_unique_index_itself_rejects_a_concurrent_duplicate_registration(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        app(CommunityEventService::class)->register($event, 'IDN-MEMBER');

        $this->expectException(QueryException::class);
        DB::table('dg_community_event_participants')->insert([
            'id' => (string) Str::uuid(), 'community_event_id' => $event->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => CommunityEventParticipant::STATUS_REGISTERED, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_unregistering_without_registration_fails(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        try {
            app(CommunityEventService::class)->unregister($event, 'IDN-NEVER-REGISTERED');
            self::fail('Une désinscription sans inscription doit échouer.');
        } catch (ModelNotFoundException) {
            self::assertTrue(true);
        }
    }

    public function test_registering_for_a_cancelled_event_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        app(CommunityEventService::class)->cancel($event, 'IDN-LEADER', null);

        $this->assertAborts(409, fn () => app(CommunityEventService::class)->register($event->fresh(), 'IDN-MEMBER'));
    }

    // ===== Cycle de vie =====

    public function test_the_organizer_can_cancel_a_scheduled_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        $cancelled = app(CommunityEventService::class)->cancel($event, 'IDN-LEADER', 'Empêchement.');

        self::assertSame(CommunityEvent::STATUS_CANCELLED, $cancelled->status);
        self::assertNotNull($cancelled->cancelled_at);
    }

    public function test_the_organizer_can_mark_an_event_completed(): void
    {
        $organization = $this->organization('IDN-OWNER');
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-OWNER', $this->payload());

        $completed = app(CommunityEventService::class)->markCompleted($event, 'IDN-OWNER', null);

        self::assertSame(CommunityEvent::STATUS_COMPLETED, $completed->status);
        self::assertNotNull($completed->completed_at);
    }

    public function test_a_non_organizer_cannot_cancel_or_complete(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        $service = app(CommunityEventService::class);

        $this->assertAborts(403, fn () => $service->cancel($event, 'IDN-MEMBER', null));
        $this->assertAborts(403, fn () => $service->markCompleted($event, 'IDN-MEMBER', null));
    }

    public function test_a_cancelled_event_cannot_be_updated_or_double_cancelled(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        $service = app(CommunityEventService::class);
        $cancelled = $service->cancel($event, 'IDN-LEADER', null);

        $this->assertAborts(409, fn () => $service->update($cancelled, 'IDN-LEADER', $this->payload()));
        $this->assertAborts(409, fn () => $service->cancel($cancelled, 'IDN-LEADER', null));
    }

    public function test_a_completed_event_cannot_be_updated_or_registered(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        $service = app(CommunityEventService::class);
        $completed = $service->markCompleted($event, 'IDN-LEADER', null);

        $this->assertAborts(409, fn () => $service->update($completed, 'IDN-LEADER', $this->payload()));
        $this->assertAborts(409, fn () => $service->register($completed, 'IDN-MEMBER'));
    }

    public function test_the_organizer_can_update_a_scheduled_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());

        $updated = app(CommunityEventService::class)->update($event, 'IDN-LEADER', $this->payload(['title' => 'Titre corrigé']));

        self::assertSame('Titre corrigé', $updated->title);
    }

    // ===== Confidentialité des inscrits =====

    public function test_participants_list_is_organizer_only(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        app(CommunityEventService::class)->register($event, 'IDN-MEMBER');

        $this->assertAborts(403, fn () => app(CommunityEventService::class)->participants($event, 'IDN-MEMBER'));

        $participants = app(CommunityEventService::class)->participants($event, 'IDN-LEADER');
        self::assertCount(1, $participants);
    }

    // ===== Aucun effet caché sur Mission/Projet/Finance =====

    public function test_creating_and_completing_an_event_has_no_mission_project_or_finance_side_effect(): void
    {
        $missionsBefore = Mission::query()->count();
        $projectsBefore = Project::query()->count();
        $contributionsBefore = Contribution::query()->count();
        $ledgerBefore = LedgerEntry::query()->count();
        $fundingBefore = ProjectFunding::query()->count();

        $group = $this->group('IDN-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->payload());
        app(CommunityEventService::class)->markCompleted($event, 'IDN-LEADER', null);

        self::assertSame($missionsBefore, Mission::query()->count());
        self::assertSame($projectsBefore, Project::query()->count());
        self::assertSame($contributionsBefore, Contribution::query()->count());
        self::assertSame($ledgerBefore, LedgerEntry::query()->count());
        self::assertSame($fundingBefore, ProjectFunding::query()->count());
    }

    public function test_the_events_table_has_no_score_or_finance_column(): void
    {
        foreach (['score', 'reputation', 'balance', 'wallet_balance', 'amount', 'price'] as $forbidden) {
            self::assertNotContains($forbidden, Schema::getColumnListing('dg_community_events'));
        }
    }

    // ===== Helpers =====

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Atelier de coordination mensuel',
            'description' => 'Une rencontre réelle pour coordonner les actions du mois à venir.',
            'location' => 'Abidjan',
            'visibility' => CommunityEvent::VISIBILITY_INTERNAL,
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(), 'name' => 'ZUMRA Événement '.Str::random(6),
            'slug' => 'zumra-evenement-'.Str::lower(Str::random(8)), 'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE, 'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => $leader, 'active_member_count' => 1,
        ]);
        $this->membership($group, $leader, ZumraGroupMembership::STATUS_ACTIVE);
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id, 'role' => 'PRIMARY_LEAD', 'core_identity_reference' => $leader,
            'status' => ZumraGroupRole::STATUS_ACCEPTED, 'proposed_by_core_reference' => $leader,
            'proposed_at' => now(), 'accepted_at' => now(),
        ]);

        return $group;
    }

    private function membership(ZumraGroup $group, string $identity, string $status): void
    {
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => $identity, 'status' => $status,
            'entry_mode' => 'FOUNDER', 'initiated_by_core_reference' => $group->proposer_core_reference,
            'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function organization(string $founder): Organization
    {
        return app(OrganizationService::class)->create($founder, [
            'name' => 'Organisation Événement '.Str::random(6),
            'description' => 'Une structure durable qui organise des activités réelles.',
            'type' => 'COOPERATIVE', 'visibility' => Organization::VISIBILITY_PRIVATE,
        ]);
    }

    private function orgMember(Organization $organization, string $identity, string $role): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'core_identity_reference' => $identity, 'role' => $role,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => $identity, 'joined_at' => now(),
        ]);
    }
}
