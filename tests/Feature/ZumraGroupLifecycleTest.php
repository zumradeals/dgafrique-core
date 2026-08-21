<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Comments\ContextCommentService;
use App\Application\Messaging\MessagingService;
use App\Application\Missions\Contexts\ZumraMissionContext;
use App\Application\Sharing\ContextShareService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\Satellite;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ZUMRA-COMP-001 — cycle de vie opérationnel réel (CONSTITUTING → READY → VALIDATED → ACTIVE →
 * WARNED → SUSPENDED → REHABILITATING → ACTIVE) et gestion des sièges fondateurs vacants. Ne
 * modifie aucun comportement des 12 lecteurs de state déjà réels (statu quo CONSTITUTING/SUSPENDED
 * garanti par les tests §7-8 de ce fichier) et ne touche pas au seuil de maturité déjà fonctionnel
 * (§9, ajouté seulement en preuve).
 */
final class ZumraGroupLifecycleTest extends TestCase
{
    use RefreshDatabase;

    // ===== 1. Rôles fondateurs =====

    public function test_a_leader_can_propose_a_vacant_role_and_the_subject_must_accept_explicitly(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-DEPUTY');
        $service = app(ZumraGroupService::class);

        $service->proposeRole($group, 'IDN-LEADER', 'FIRST_DEPUTY', 'IDN-DEPUTY');
        $role = $group->roles()->where('role', 'FIRST_DEPUTY')->sole();
        self::assertSame(ZumraGroupRole::STATUS_PROPOSED, $role->status);
        self::assertSame('IDN-DEPUTY', $role->core_identity_reference);
        self::assertTrue(ZumraGroupEvent::query()->where('event', 'ROLE_PROPOSED')->exists());

        $service->acceptRole($group, 'IDN-DEPUTY', 'FIRST_DEPUTY', 3, false);
        self::assertSame(ZumraGroupRole::STATUS_ACCEPTED, $role->refresh()->status);
        self::assertNotNull($role->accepted_at);
        self::assertTrue(ZumraGroupEvent::query()->where('event', 'ROLE_ACCEPTED')->exists());
    }

    public function test_a_non_leader_cannot_propose_a_role(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-OUTSIDER');
        $this->programMember('IDN-SUBJECT');

        $this->assertAborts(403, fn () => app(ZumraGroupService::class)->proposeRole($group, 'IDN-OUTSIDER', 'FIRST_DEPUTY', 'IDN-SUBJECT'));
    }

    public function test_a_person_not_concerned_cannot_accept_a_proposed_role(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-SUBJECT');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-LEADER', 'FIRST_DEPUTY', 'IDN-SUBJECT');

        $this->assertAborts(409, fn () => app(ZumraGroupService::class)->acceptRole($group, 'IDN-STRANGER', 'FIRST_DEPUTY', 3, false));
    }

    public function test_a_role_already_taken_cannot_be_proposed_again(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-SUBJECT');
        $this->programMember('IDN-OTHER');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-LEADER', 'FIRST_DEPUTY', 'IDN-SUBJECT');

        $this->assertAborts(409, fn () => app(ZumraGroupService::class)->proposeRole($group, 'IDN-LEADER', 'FIRST_DEPUTY', 'IDN-OTHER'));
    }

    public function test_the_same_person_cannot_hold_two_responsibilities_in_one_zumra(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-SUBJECT');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-LEADER', 'FIRST_DEPUTY', 'IDN-SUBJECT');

        $this->assertAborts(409, fn () => app(ZumraGroupService::class)->proposeRole($group, 'IDN-LEADER', 'SECOND_DEPUTY', 'IDN-SUBJECT'));
    }

    // ===== 2. Limite de responsabilités fondatrices simultanées (art. 8) =====

    public function test_the_founder_role_limit_blocks_a_fourth_simultaneous_role(): void
    {
        $service = app(ZumraGroupService::class);
        $this->programMember('IDN-OVERBOOKED');
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);

        $fourth = $this->group('IDN-LEADER-4');
        $service->proposeRole($fourth, 'IDN-LEADER-4', 'FIRST_DEPUTY', 'IDN-OVERBOOKED');
        $this->assertAborts(409, fn () => $service->acceptRole($fourth, 'IDN-OVERBOOKED', 'FIRST_DEPUTY', 3, false));
    }

    public function test_the_founder_role_limit_also_blocks_assuming_primary_lead_at_creation(): void
    {
        $this->programMember('IDN-OVERBOOKED');
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);
        $this->acceptRoleIn('IDN-OVERBOOKED', 3);

        $this->assertAborts(409, fn () => app(ZumraGroupService::class)->create('IDN-OVERBOOKED', $this->groupPayload(), 3));
    }

    public function test_the_founder_role_limit_is_the_configured_value_not_a_hardcoded_three(): void
    {
        $service = app(ZumraGroupService::class);
        $this->programMember('IDN-SINGLE-SEAT');
        $this->acceptRoleIn('IDN-SINGLE-SEAT', 1);

        $second = $this->group('IDN-LEADER-2');
        $service->proposeRole($second, 'IDN-LEADER-2', 'FIRST_DEPUTY', 'IDN-SINGLE-SEAT');
        $this->assertAborts(409, fn () => $service->acceptRole($second, 'IDN-SINGLE-SEAT', 'FIRST_DEPUTY', 1, false));
    }

    // ===== 3. READY =====

    public function test_five_accepted_roles_and_all_criteria_transition_to_ready_when_automation_is_enabled(): void
    {
        $group = $this->readyGroup();

        self::assertSame(ZumraGroup::STATE_READY, $group->refresh()->state);
        self::assertNotNull($group->ready_at);
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'GROUP_READY')->count());
    }

    public function test_a_missing_criterion_keeps_the_group_in_constituting(): void
    {
        $group = $this->group('IDN-LEADER');
        $service = app(ZumraGroupService::class);
        // Seuls 4 des 5 sièges sont pourvus (PRIMARY_LEAD + 3 sur 4) : le cinquième reste VACANT.
        foreach (['FIRST_DEPUTY', 'SECOND_DEPUTY', 'FINANCE_LEAD'] as $role) {
            $identity = 'IDN-ROLE-'.$role;
            $this->programMember($identity);
            $service->proposeRole($group, 'IDN-LEADER', $role, $identity);
            $service->acceptRole($group, $identity, $role, 3, true);
        }
        self::assertSame(1, $group->roles()->where('status', ZumraGroupRole::STATUS_VACANT)->count());
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->refresh()->state);

        $criteria = $service->evaluateReadiness($group);
        self::assertFalse($criteria['ready']);
        self::assertContains('five_distinct_roles_accepted', $criteria['missing']);
    }

    public function test_automation_disabled_never_transitions_to_ready(): void
    {
        $group = $this->groupWithFiveAcceptedRoles(autoValidationEnabled: false);

        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->refresh()->state);
        self::assertFalse(ZumraGroupEvent::query()->where('event', 'GROUP_READY')->exists());
    }

    public function test_ready_is_logged_only_once_and_is_idempotent(): void
    {
        $group = $this->readyGroup();
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'GROUP_READY')->count());

        // Rejouer l'évaluation (ex. via une action non structurante) ne doit rien changer :
        // la garde d'état (state !== CONSTITUTING) rend la transition idempotente.
        $criteria = app(ZumraGroupService::class)->evaluateReadiness($group->refresh());
        self::assertTrue($criteria['ready']);
        self::assertSame(ZumraGroup::STATE_READY, $group->refresh()->state);
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'GROUP_READY')->count());
    }

    // ===== 4. VALIDATED =====

    public function test_validation_by_a_non_administrator_is_refused(): void
    {
        $group = $this->readyGroup();

        $this->assertAborts(403, fn () => app(ZumraGroupService::class)->validate($group, 'IDN-LEADER'));
    }

    public function test_ready_transitions_to_validated_by_a_real_administrator(): void
    {
        $group = $this->readyGroup();
        $admin = $this->administrator();

        $validated = app(ZumraGroupService::class)->validate($group, $admin);

        self::assertSame(ZumraGroup::STATE_VALIDATED, $validated->state);
        self::assertNotNull($validated->validated_at);
        self::assertTrue(ZumraGroupEvent::query()->where('event', 'GROUP_VALIDATED')->exists());
    }

    public function test_validating_a_group_still_in_constituting_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $admin = $this->administrator();

        $this->assertAborts(409, fn () => app(ZumraGroupService::class)->validate($group, $admin));
    }

    // ===== 5. ACTIVE / WARNED / SUSPENDED / REHABILITATING / réactivation =====

    public function test_the_full_forward_cycle_transitions_and_events(): void
    {
        $group = $this->readyGroup();
        $admin = $this->administrator();
        $service = app(ZumraGroupService::class);

        $service->validate($group, $admin);
        $active = $service->activate($group, $admin);
        self::assertSame(ZumraGroup::STATE_ACTIVE, $active->state);
        self::assertNotNull($active->activated_at);

        $warned = $service->warn($group, $admin);
        self::assertSame(ZumraGroup::STATE_WARNED, $warned->state);
        self::assertNotNull($warned->warned_at);

        $suspended = $service->suspend($group, $admin);
        self::assertSame(ZumraGroup::STATE_SUSPENDED, $suspended->state);
        self::assertNotNull($suspended->suspended_at);

        $rehabilitating = $service->enterRehabilitation($group, $admin);
        self::assertSame(ZumraGroup::STATE_REHABILITATING, $rehabilitating->state);
        self::assertNotNull($rehabilitating->rehabilitating_at);

        $reactivated = $service->reactivate($group, $admin);
        self::assertSame(ZumraGroup::STATE_ACTIVE, $reactivated->state);
        self::assertNotNull($reactivated->activated_at);

        self::assertSame([
            'GROUP_PROPOSED', 'ROLE_PROPOSED', 'ROLE_ACCEPTED', 'ROLE_PROPOSED', 'ROLE_ACCEPTED',
            'ROLE_PROPOSED', 'ROLE_ACCEPTED', 'ROLE_PROPOSED', 'ROLE_ACCEPTED', 'GROUP_READY',
            'GROUP_VALIDATED', 'GROUP_ACTIVATED', 'GROUP_WARNED', 'GROUP_SUSPENDED',
            'GROUP_REHABILITATING', 'GROUP_REACTIVATED',
        ], ZumraGroupEvent::query()->where('zumra_group_id', $group->id)->orderBy('occurred_at')->pluck('event')->all());
    }

    public function test_illegal_transitions_are_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $admin = $this->administrator();
        $service = app(ZumraGroupService::class);

        $this->assertAborts(409, fn () => $service->activate($group, $admin));
        $this->assertAborts(409, fn () => $service->warn($group, $admin));
        $this->assertAborts(409, fn () => $service->suspend($group, $admin));
        $this->assertAborts(409, fn () => $service->enterRehabilitation($group, $admin));
        $this->assertAborts(409, fn () => $service->reactivate($group, $admin));
    }

    // ===== 6. Surfaces déjà réelles — statu quo volontairement préservé =====

    public function test_messaging_stays_usable_during_constituting(): void
    {
        $group = $this->group('IDN-LEADER');

        $conversation = app(MessagingService::class)->openZumra('IDN-LEADER', $group);
        self::assertNotNull($conversation->id);
    }

    public function test_sharing_and_comments_stay_usable_during_constituting(): void
    {
        $group = $this->group('IDN-LEADER');

        $inbox = app(ContextShareService::class)->groupInbox($group, 'IDN-LEADER');
        self::assertSame('Partages utiles — '.$group->name, $inbox['title']);

        $thread = app(ContextCommentService::class)->zumraActivityThread($group, 'IDN-LEADER');
        self::assertTrue($thread['context']['can_comment']);
    }

    public function test_mission_proposal_stays_usable_during_constituting(): void
    {
        $group = $this->group('IDN-LEADER');
        $context = app(ZumraMissionContext::class);

        self::assertTrue($context->isOperational($group));
        self::assertTrue($context->canPropose($group, 'IDN-LEADER'));
    }

    public function test_suspended_still_blocks_messaging_sharing_and_comments(): void
    {
        $group = $this->readyGroup();
        $admin = $this->administrator();
        $service = app(ZumraGroupService::class);
        $service->validate($group, $admin);
        $service->activate($group, $admin);
        $service->warn($group, $admin);
        $service->suspend($group, $admin);
        $group->refresh();

        $this->assertAborts(409, fn () => app(MessagingService::class)->openZumra('IDN-LEADER', $group));
        $this->assertAborts(404, fn () => app(ContextShareService::class)->groupInbox($group, 'IDN-LEADER'));
        $this->assertAborts(404, fn () => app(ContextCommentService::class)->zumraActivityThread($group, 'IDN-LEADER'));
        self::assertFalse(app(ZumraMissionContext::class)->isOperational($group));
    }

    // ===== 7. Aucune création automatique =====

    public function test_no_organization_project_or_satellite_is_ever_created_by_the_lifecycle(): void
    {
        $organizationsBefore = Organization::query()->count();
        $projectsBefore = Project::query()->count();
        $satellitesBefore = Satellite::query()->count();

        $group = $this->readyGroup();
        $admin = $this->administrator();
        $service = app(ZumraGroupService::class);
        $service->validate($group, $admin);
        $service->activate($group, $admin);
        $service->warn($group, $admin);
        $service->suspend($group, $admin);
        $service->enterRehabilitation($group, $admin);
        $service->reactivate($group, $admin);

        self::assertSame($organizationsBefore, Organization::query()->count());
        self::assertSame($projectsBefore, Project::query()->count());
        self::assertSame($satellitesBefore, Satellite::query()->count());
    }

    // ===== 8. Maturité (déjà fonctionnelle — preuve seulement) =====

    public function test_the_fifty_member_threshold_still_flips_maturity_to_established(): void
    {
        $group = $this->group('IDN-LEADER');
        self::assertSame(ZumraGroup::MATURITY_EMERGING, $group->maturity);

        $service = app(ZumraGroupService::class);
        for ($i = 1; $i <= 49; $i++) {
            $identity = 'IDN-MEMBER-'.$i;
            $this->programMember($identity);
            $service->requestToJoin($group, $identity, null);
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $identity)->sole();
            $service->approveRequest($group, 'IDN-LEADER', $membership->id, 50);
        }

        self::assertSame(50, $group->refresh()->active_member_count);
        self::assertSame(ZumraGroup::MATURITY_ESTABLISHED, $group->maturity);
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

    private function readyGroup(): ZumraGroup
    {
        return $this->groupWithFiveAcceptedRoles(autoValidationEnabled: true);
    }

    private function groupWithFiveAcceptedRoles(bool $autoValidationEnabled): ZumraGroup
    {
        $group = $this->group('IDN-LEADER');
        $this->fillFourRemainingRoles($group, $autoValidationEnabled);

        return $group->refresh();
    }

    private function fillFourRemainingRoles(ZumraGroup $group, bool $autoValidationEnabled = false): void
    {
        $service = app(ZumraGroupService::class);
        $remaining = array_diff(array_keys(ZumraGroupRole::LABELS), ['PRIMARY_LEAD']);
        foreach ($remaining as $i => $role) {
            $identity = 'IDN-ROLE-'.$role;
            $this->programMember($identity);
            $service->proposeRole($group, 'IDN-LEADER', $role, $identity);
            $service->acceptRole($group, $identity, $role, 3, $autoValidationEnabled);
        }
    }

    /** Crée une ZUMRA distincte où $identity accepte immédiatement un unique siège FIRST_DEPUTY. */
    private function acceptRoleIn(string $identity, int $maxSimultaneousFounderRoles): void
    {
        static $counter = 0;
        $counter++;
        $leader = 'IDN-LEADER-SEAT-'.$counter;
        $group = $this->group($leader);
        $service = app(ZumraGroupService::class);
        $service->proposeRole($group, $leader, 'FIRST_DEPUTY', $identity);
        $service->acceptRole($group, $identity, 'FIRST_DEPUTY', $maxSimultaneousFounderRoles, false);
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::random(6);
        PortalAdministrator::query()->create(['core_identity_reference' => $reference]);

        return $reference;
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);

        return app(ZumraGroupService::class)->create($leader, $this->groupPayload(), 3);
    }

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier numérique solidaire '.Str::random(8),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables. Les admissions sont approuvées, le départ reste libre et toute exclusion doit être motivée.',
            'assume_primary_lead' => true,
        ];
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }
}
