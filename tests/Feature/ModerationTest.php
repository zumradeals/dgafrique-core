<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Application\Comments\ContextCommentService;
use App\Application\Messaging\MessagingService;
use App\Application\Moderation\ModerationDecisionService;
use App\Application\Moderation\ModerationReportService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\ContextComment;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\LedgerEntry;
use App\Models\MessageConversation;
use App\Models\MessageEntry;
use App\Models\ModerationDecision;
use App\Models\ModerationReport;
use App\Models\Organization;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * MODERATION-COMP-001 — Modération, discipline et recours (art. 19 ZUMRA-DOCTRINE-INVARIANTE.md).
 * Ce n'est pas une CAP officielle (docs/roadmap/ROADMAP-METIER-CANONIQUE.md — ROADMAP-003).
 * Architecture HYBRIDE C→B validée en Phase A : (A) discipline ZUMRA sur ZumraGroupMembership/
 * ZumraGroupService/ZumraGroupEvent, (B) ModerationReport transversal, (C) ModerationDecision vivante,
 * (D) masquage local ContextComment/MessageEntry.
 */
final class ModerationTest extends TestCase
{
    use RefreshDatabase;

    // ===== 1. Signalement (10) =====

    public function test_a_member_can_report_a_context_comment(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');

        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);

        self::assertSame(ModerationReport::TARGET_CONTEXT_COMMENT, $report->target_type);
        self::assertSame($comment->id, $report->target_reference);
        self::assertSame(ModerationReport::STATUS_PENDING, $report->status);
    }

    public function test_a_participant_can_report_a_message_entry(): void
    {
        $entry = $this->directMessage('IDN-A', 'IDN-B');

        $report = app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-B', ModerationReport::REASON_THREAT, null);

        self::assertSame(ModerationReport::TARGET_MESSAGE_ENTRY, $report->target_type);
        self::assertSame($entry->id, $report->target_reference);
    }

    public function test_a_non_participant_cannot_report_a_message_from_an_inaccessible_private_conversation(): void
    {
        $entry = $this->directMessage('IDN-A', 'IDN-B');

        $this->assertAborts(404, fn () => app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-OUTSIDER', ModerationReport::REASON_THREAT, null));
    }

    public function test_an_active_zumra_member_can_report_a_fellow_members_membership(): void
    {
        $group = $this->group('IDN-LEADER');
        $membership = $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $this->membership($group, 'IDN-REPORTER', ZumraGroupMembership::STATUS_ACTIVE);

        $report = app(ModerationReportService::class)->reportZumraMembership($membership, 'IDN-REPORTER', ModerationReport::REASON_VIOLENCE, null);

        self::assertSame(ModerationReport::TARGET_ZUMRA_MEMBERSHIP, $report->target_type);
        self::assertSame(ModerationReport::CONTEXT_ZUMRA, $report->context_type);
        self::assertSame($group->id, $report->context_reference);
    }

    public function test_other_reason_requires_details(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');

        $this->assertAborts(422, fn () => app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_OTHER, null));

        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_OTHER, 'Explication suffisante du motif autre.');
        self::assertSame(ModerationReport::REASON_OTHER, $report->reason_code);
    }

    public function test_an_invalid_reason_code_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');

        $this->assertAborts(422, fn () => app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', 'INVENTED_REASON', null));
    }

    public function test_reporting_a_nonexistent_target_is_refused_at_the_http_boundary(): void
    {
        $this->signIn('IDN-REPORTER');

        $this->post(route('moderation.reports.context-comment', (string) Str::uuid()), [
            'reason_code' => ModerationReport::REASON_HARASSMENT,
        ])->assertNotFound();

        self::assertSame(0, ModerationReport::query()->count());
    }

    public function test_a_report_is_persisted_and_not_an_ephemeral_event(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);

        self::assertSame(1, ModerationReport::query()->count());
        self::assertTrue(Schema::hasTable('dg_moderation_reports'));
    }

    public function test_the_reporter_identity_is_never_exposed_to_the_reported_person(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);
        $decision = app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_WARNING, null);

        $presented = app(ModerationDecisionService::class)->presentForSubject($decision);

        self::assertArrayNotHasKey('reporter_core_reference', $presented);
        self::assertStringNotContainsString('reporter', implode(',', array_keys($presented)));
    }

    public function test_a_zumra_cannot_block_a_level_three_report(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);
        app(ModerationReportService::class)->escalate($report, 'IDN-REPORTER');

        self::assertFalse(app(ModerationReportService::class)->canZumraLeaderDecide($report->refresh(), $group));
        $this->assertAborts(404, fn () => app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report->refresh(), 'IDN-LEADER', ModerationDecision::ACTION_WARNING, null));

        $administrator = $this->administrator();
        $decision = app(ModerationDecisionService::class)->decideAsAdministrator($report->refresh(), $administrator, ModerationDecision::ACTION_WARNING, null);
        self::assertSame(ModerationDecision::STATUS_ACTIVE, $decision->status);
    }

    // ===== 2. Messages privés (5) =====

    public function test_a_zumra_leader_cannot_open_a_third_party_private_conversation(): void
    {
        $entry = $this->directMessage('IDN-A', 'IDN-B');

        self::assertFalse(app(MessagingService::class)->canAccess($entry->conversation, 'IDN-LEADER'));
    }

    public function test_reporting_a_message_does_not_grant_the_zumra_leader_access_to_the_whole_conversation(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Un premier message sans rapport avec le signalement.');
        $reported = app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Un message problématique à signaler.');

        $report = app(ModerationReportService::class)->reportMessageEntry($reported, 'IDN-LEADER', ModerationReport::REASON_HARASSMENT, null);
        $presented = app(ModerationReportService::class)->presentForZumraLeader($report);

        self::assertSame('Un message problématique à signaler.', $presented['target_excerpt']);
        self::assertArrayNotHasKey('conversation', $presented);
        self::assertArrayNotHasKey('entries', $presented);
    }

    public function test_a_participant_can_report_a_message_within_a_zumra_conversation_and_it_reaches_the_leader_queue(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        $entry = app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Message signalé par un pair.');
        app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-LEADER', ModerationReport::REASON_HARASSMENT, null);

        $queue = app(ModerationReportService::class)->forZumraLeader($group);

        self::assertCount(1, $queue);
    }

    public function test_the_reporter_identity_stays_protected_in_a_message_report_too(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        $entry = app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Message signalé.');
        app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-LEADER', ModerationReport::REASON_HARASSMENT, null);

        $report = ModerationReport::query()->sole();
        $presented = app(ModerationReportService::class)->presentForZumraLeader($report);

        self::assertArrayNotHasKey('reporter_core_reference', $presented);
    }

    public function test_no_moderation_route_grants_general_conversation_surveillance(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Un message jamais signalé.');
        $this->signIn('IDN-LEADER');

        $this->get(route('zumra.groups.moderation.index', $group))
            ->assertJson(['reports' => []])
            ->assertJsonMissing(['body' => 'Un message jamais signalé.']);
    }

    // ===== 3. Modération de contenu (6) =====

    public function test_a_hidden_comment_disappears_from_the_ordinary_thread(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);

        $thread = app(ContextCommentService::class)->zumraActivityThread($group, 'IDN-AUTHOR');

        self::assertCount(0, $thread['comments']);
    }

    public function test_a_hidden_comment_stays_physically_in_the_database(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);

        self::assertSame(1, ContextComment::query()->count());
        self::assertNotNull(ContextComment::query()->find($comment->id)?->hidden_at);
    }

    public function test_a_hidden_message_stays_physically_in_the_database(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        $entry = app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Message à masquer.');
        $report = app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-LEADER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);

        self::assertSame(1, MessageEntry::query()->count());
        self::assertNotNull(MessageEntry::query()->find($entry->id)?->hidden_at);
    }

    public function test_a_hidden_message_no_longer_renders_in_the_ordinary_thread_or_inbox_preview(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $conversation = app(MessagingService::class)->openZumra('IDN-MEMBER', $group);
        $entry = app(MessagingService::class)->send($conversation, 'IDN-MEMBER', 'Message à masquer.');
        $report = app(ModerationReportService::class)->reportMessageEntry($entry, 'IDN-LEADER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);

        $thread = app(MessagingService::class)->thread($conversation, 'IDN-MEMBER');
        self::assertCount(0, $thread['entries']);

        $inbox = app(MessagingService::class)->inbox('IDN-MEMBER');
        self::assertNull($inbox->first()['last_message']);
    }

    public function test_the_disciplinary_authority_can_still_access_the_hidden_content_as_proof(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);

        $proof = ContextComment::query()->find($comment->id);
        self::assertNotNull($proof);
        self::assertSame($comment->body, $proof->body);
    }

    public function test_no_delete_or_destroy_route_exists_for_context_comments_or_message_entries(): void
    {
        $names = collect(app('router')->getRoutes())->map(fn ($route) => $route->getName())->filter()->values();

        self::assertFalse($names->contains(fn (string $name): bool => str_contains($name, 'comments.destroy') || str_contains($name, 'messages.destroy')));
    }

    // ===== 4. ZUMRA (10) =====

    public function test_a_leader_can_exclude_an_ordinary_member(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', 'Comportement violent constaté.', 50);

        self::assertSame(ZumraGroupMembership::STATUS_EXCLUDED, ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);
    }

    public function test_a_non_leader_cannot_exclude_a_member(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $this->membership($group, 'IDN-PEER', ZumraGroupMembership::STATUS_ACTIVE);

        $this->assertAborts(403, fn () => app(ZumraGroupService::class)->exclude($group, 'IDN-PEER', 'IDN-MEMBER', 'Motif quelconque.', 50));
    }

    public function test_exclusion_requires_a_reason(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        $this->assertAborts(422, fn () => app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', '', 50));
    }

    public function test_exclusion_transitions_active_to_excluded(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', 'Motif réel.', 50);

        $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', 'IDN-MEMBER')->sole();
        self::assertSame(ZumraGroupMembership::STATUS_EXCLUDED, $membership->status);
        self::assertNotNull($membership->left_at);
        self::assertSame('Motif réel.', $membership->decision_reason);
    }

    public function test_left_status_stays_distinct_from_excluded(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-VOLUNTARY', ZumraGroupMembership::STATUS_ACTIVE);
        $this->membership($group, 'IDN-DISCIPLINED', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->leave($group, 'IDN-VOLUNTARY', 50);
        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-DISCIPLINED', 'Motif réel.', 50);

        self::assertSame(ZumraGroupMembership::STATUS_LEFT, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-VOLUNTARY')->sole()->status);
        self::assertSame(ZumraGroupMembership::STATUS_EXCLUDED, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-DISCIPLINED')->sole()->status);
    }

    public function test_member_excluded_event_is_traced(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', 'Motif réel.', 50);

        self::assertTrue(ZumraGroupEvent::query()->where('zumra_group_id', $group->id)->where('event', 'MEMBER_EXCLUDED')->exists());
    }

    public function test_membership_history_is_preserved_after_exclusion(): void
    {
        $group = $this->group('IDN-LEADER');
        $membership = $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', 'Motif réel.', 50);

        self::assertNotNull(ZumraGroupMembership::query()->find($membership->id));
        self::assertSame(1, ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', 'IDN-MEMBER')->count());
    }

    public function test_primary_lead_is_protected_from_level_two_exclusion(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->addRole($group, 'FIRST_DEPUTY', 'IDN-DEPUTY');
        $this->membership($group, 'IDN-DEPUTY', ZumraGroupMembership::STATUS_ACTIVE);

        $this->assertAborts(403, fn () => app(ZumraGroupService::class)->exclude($group, 'IDN-DEPUTY', 'IDN-LEADER', 'Motif quelconque.', 50));
    }

    public function test_portal_administrator_can_process_the_primary_lead(): void
    {
        $group = $this->group('IDN-LEADER');
        $administrator = $this->administrator();

        app(ZumraGroupService::class)->exclude($group, $administrator, 'IDN-LEADER', 'Décision DG Afrique motivée.', 50);

        self::assertSame(ZumraGroupMembership::STATUS_EXCLUDED, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-LEADER')->sole()->status);
    }

    public function test_individual_suspension_is_distinct_from_zumra_group_suspension(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        app(ZumraGroupService::class)->suspendMember($group, 'IDN-LEADER', 'IDN-MEMBER', 'Motif réel.', 50);

        self::assertSame(ZumraGroupMembership::STATUS_SUSPENDED, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);
        self::assertSame(ZumraGroup::STATE_ACTIVE, $group->fresh()->state);
        self::assertFalse(app(MessagingService::class)->canAccess(app(MessagingService::class)->openZumra('IDN-LEADER', $group), 'IDN-MEMBER'));
    }

    // ===== 5. Recours (10) =====

    public function test_the_sanctioned_person_can_request_an_appeal(): void
    {
        [$decision] = $this->warnedMember();

        $appealed = app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste cette décision.');

        self::assertNotNull($appealed->appeal_requested_at);
    }

    public function test_a_third_party_cannot_request_an_appeal(): void
    {
        [$decision] = $this->warnedMember();

        $this->assertAborts(403, fn () => app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-STRANGER', 'Je conteste.'));
    }

    public function test_a_level_two_decision_appeal_requires_level_three_authority(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste cette décision.');

        $this->assertAborts(403, fn () => app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), 'IDN-LEADER', ModerationDecision::APPEAL_OUTCOME_CONFIRMED, null));

        $administrator = $this->administrator();
        $confirmed = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, 'Décision confirmée.');
        self::assertSame(ModerationDecision::APPEAL_OUTCOME_CONFIRMED, $confirmed->appeal_outcome);
    }

    public function test_requesting_an_appeal_does_not_suspend_the_decision(): void
    {
        [$decision] = $this->warnedMember();

        $appealed = app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');

        self::assertSame(ModerationDecision::STATUS_ACTIVE, $appealed->status);
        self::assertTrue($appealed->isCurrentlyEffective());
    }

    public function test_the_administrator_can_confirm_an_appeal(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');
        $administrator = $this->administrator();

        $confirmed = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, null);

        self::assertSame(ModerationDecision::STATUS_ACTIVE, $confirmed->status);
    }

    public function test_the_administrator_can_modify_an_appeal(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');
        $administrator = $this->administrator();

        $modified = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_MODIFIED, 'Sanction révisée.');

        self::assertSame(ModerationDecision::STATUS_MODIFIED, $modified->status);
    }

    public function test_the_administrator_can_lift_an_appeal_and_restore_hidden_content(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-MEMBER');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        $decision = app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_CONTENT_HIDDEN, null);
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Ce n’était pas justifié.');
        $administrator = $this->administrator();

        $lifted = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_LIFTED, 'Décision levée.');

        self::assertSame(ModerationDecision::STATUS_LIFTED, $lifted->status);
        self::assertNull(ContextComment::query()->find($comment->id)?->hidden_at);
    }

    public function test_the_appeal_decision_is_traced(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');
        $administrator = $this->administrator();

        $decided = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, 'Motif de confirmation.');

        self::assertSame($administrator, $decided->appeal_decided_by_core_reference);
        self::assertNotNull($decided->appeal_decided_at);
        self::assertSame('Motif de confirmation.', $decided->appeal_explanation);
    }

    public function test_a_double_appeal_is_refused(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');

        $this->assertAborts(409, fn () => app(ModerationDecisionService::class)->requestAppeal($decision->fresh(), 'IDN-MEMBER', 'Nouvelle contestation.'));

        $administrator = $this->administrator();
        app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, null);
        $this->assertAborts(409, fn () => app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, null));
    }

    public function test_the_reporter_identity_stays_secret_during_the_appeal(): void
    {
        [$decision] = $this->warnedMember();
        app(ModerationDecisionService::class)->requestAppeal($decision, 'IDN-MEMBER', 'Je conteste.');
        $administrator = $this->administrator();

        $decided = app(ModerationDecisionService::class)->decideAppeal($decision->fresh(), $administrator, ModerationDecision::APPEAL_OUTCOME_CONFIRMED, null);
        $presented = app(ModerationDecisionService::class)->presentForSubject($decided);

        self::assertArrayNotHasKey('reporter_core_reference', $presented);
    }

    // ===== 6. Effets cachés interdits (10) =====

    public function test_no_activity_feed_entry_is_ever_created_for_a_moderation_decision(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        // Un événement Fil réel et éligible (art. 19 : le Fil ne doit projeter aucune décision
        // disciplinaire, pas même en présence d'autre activité ZUMRA légitime dans le même flux).
        app(ZumraGroupService::class)->invite($group, 'IDN-LEADER', 'IDN-INVITEE');
        app(ZumraGroupService::class)->acceptInvitation($group, 'IDN-INVITEE', 50);
        $before = app(ActivityFeedService::class)->paginate('IDN-LEADER', 'ZUMRA')->total();
        self::assertGreaterThan(0, $before, 'Le test suppose un Fil non vide pour être probant.');

        app(ZumraGroupService::class)->exclude($group, 'IDN-LEADER', 'IDN-MEMBER', 'Motif réel.', 50);
        app(ZumraGroupService::class)->suspendMember($group, 'IDN-LEADER', 'IDN-INVITEE', 'Motif réel.', 50);

        $after = app(ActivityFeedService::class)->paginate('IDN-LEADER', 'ZUMRA');
        self::assertSame($before, $after->total(), 'Une décision disciplinaire ne doit jamais augmenter le Fil.');
        foreach ($after->getCollection() as $item) {
            self::assertStringNotContainsString('exclu', mb_strtolower((string) ($item['label'] ?? '')));
            self::assertStringNotContainsString('suspend', mb_strtolower((string) ($item['label'] ?? '')));
            self::assertStringNotContainsString('signal', mb_strtolower((string) ($item['label'] ?? '')));
        }

        $whitelisted = (new \ReflectionClassConstant(ActivityFeedService::class, 'ZUMRA_EVENTS'))->getValue();
        foreach (['MEMBER_EXCLUDED', 'MEMBER_SUSPENDED', 'MEMBER_REINSTATED', 'ROLE_REVOKED'] as $moderationEvent) {
            self::assertArrayNotHasKey($moderationEvent, $whitelisted);
        }
    }

    public function test_a_warning_creates_no_hidden_hidden_at_hidden_or_reversible_effect(): void
    {
        [$decision, $group] = $this->warnedMember();

        self::assertSame(ModerationDecision::ACTION_WARNING, $decision->action_type);
        self::assertNull(ContextComment::query()->where('author_core_reference', 'IDN-MEMBER')->first()?->hidden_at);
        self::assertSame(ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);
    }

    public function test_a_decision_never_modifies_capability_statement(): void
    {
        $before = CapabilityStatement::query()->count();
        $this->warnedMember();

        self::assertSame($before, CapabilityStatement::query()->count());
    }

    public function test_a_decision_never_modifies_contribution_or_payment(): void
    {
        $before = [Contribution::query()->count(), ContributionPayment::query()->count()];
        $this->warnedMember();

        self::assertSame($before, [Contribution::query()->count(), ContributionPayment::query()->count()]);
    }

    public function test_a_decision_never_creates_a_ledger_entry(): void
    {
        $before = LedgerEntry::query()->count();
        $this->warnedMember();

        self::assertSame($before, LedgerEntry::query()->count());
    }

    public function test_a_decision_never_modifies_project_funding(): void
    {
        $before = ProjectFunding::query()->count();
        $this->warnedMember();

        self::assertSame($before, ProjectFunding::query()->count());
    }

    public function test_a_decision_never_creates_an_organization(): void
    {
        $before = Organization::query()->count();
        $this->warnedMember();

        self::assertSame($before, Organization::query()->count());
    }

    public function test_a_decision_never_creates_a_partnership(): void
    {
        $before = Partnership::query()->count();
        $this->warnedMember();

        self::assertSame($before, Partnership::query()->count());
    }

    public function test_a_decision_never_modifies_project_ownership(): void
    {
        $project = Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-MEMBER', 'initiator_core_reference' => 'IDN-MEMBER',
            'name' => 'Projet', 'summary' => 'Un projet concret et suffisamment décrit.',
            'problem' => 'Un problème réel.', 'proposed_solution' => 'Une solution progressive.',
            'beneficiaries' => 'Communauté locale.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Agir'], 'required_capabilities' => ['Coordination'], 'required_resources' => ['Temps'], 'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => Project::STATUS_IN_PROGRESS, 'maturity' => 'ACTIVITY', 'decided_by_core_reference' => 'IDN-MEMBER', 'started_at' => now(),
        ]);
        $ownerBefore = $project->owner_reference;

        $this->warnedMember();

        self::assertSame($ownerBefore, $project->fresh()->owner_reference);
    }

    public function test_the_decisions_and_reports_tables_have_no_score_or_reputation_column(): void
    {
        foreach (['score', 'reputation', 'rank', 'trust_level', 'balance', 'debt'] as $forbidden) {
            self::assertNotContains($forbidden, Schema::getColumnListing('dg_moderation_reports'));
            self::assertNotContains($forbidden, Schema::getColumnListing('dg_moderation_decisions'));
        }
    }

    // ===== Structure : idempotence, concurrence, LIMITATION différée, expiration =====

    public function test_double_decision_on_the_same_report_is_refused(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_WARNING, null);

        $this->assertAborts(409, fn () => app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report->fresh(), 'IDN-LEADER', ModerationDecision::ACTION_WARNING, null));
    }

    public function test_the_partial_unique_index_itself_rejects_a_concurrent_duplicate_active_decision(): void
    {
        $group = $this->group('IDN-LEADER');
        $comment = $this->zumraComment($group, 'IDN-AUTHOR');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HATE, null);
        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_WARNING, null);

        $this->expectException(QueryException::class);
        DB::table('dg_moderation_decisions')->insert([
            'id' => (string) Str::uuid(), 'target_type' => ModerationReport::TARGET_CONTEXT_COMMENT, 'target_reference' => $comment->id,
            'action_type' => ModerationDecision::ACTION_WARNING, 'reason_code' => ModerationReport::REASON_HATE,
            'decided_by_core_reference' => 'IDN-LEADER', 'authority_level' => 2, 'effective_at' => now(),
            'status' => ModerationDecision::STATUS_ACTIVE, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_deciding_a_role_revocation_revokes_the_correct_role(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->addRole($group, 'FINANCE_LEAD', 'IDN-FINANCE');
        $this->membership($group, 'IDN-FINANCE', ZumraGroupMembership::STATUS_ACTIVE);
        $membership = ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-FINANCE')->sole();
        $report = app(ModerationReportService::class)->reportZumraMembership($membership, 'IDN-LEADER', ModerationReport::REASON_FRAUD, null);

        app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_ROLE_REVOCATION, null);

        $role = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', 'FINANCE_LEAD')->sole();
        self::assertSame(ZumraGroupRole::STATUS_VACANT, $role->status);
        self::assertNull($role->core_identity_reference);
    }

    public function test_a_limitation_action_type_does_not_exist_in_v1(): void
    {
        self::assertNotContains('LIMITATION', ModerationDecision::ACTION_TYPES);
    }

    public function test_an_expired_suspension_is_reinstated_when_read(): void
    {
        $group = $this->group('IDN-LEADER');
        $membership = $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $report = app(ModerationReportService::class)->reportZumraMembership($membership, 'IDN-LEADER', ModerationReport::REASON_HARASSMENT, null);
        $decision = app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_MEMBERSHIP_SUSPENSION, null);
        ModerationDecision::query()->whereKey($decision->id)->update(['expires_at' => now()->subDay()]);

        $refreshed = app(ModerationDecisionService::class)->withExpiryApplied($decision->fresh());

        self::assertSame(ModerationDecision::STATUS_EXPIRED, $refreshed->status);
        self::assertSame(ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::query()->where('core_identity_reference', 'IDN-MEMBER')->sole()->status);
    }

    public function test_the_support_channel_stays_available_and_is_not_the_disciplinary_registry(): void
    {
        $administrator = $this->administrator();
        $conversation = app(MessagingService::class)->openSupport('IDN-MEMBER');

        self::assertSame(MessageConversation::CONTEXT_DG_AFRIQUE, $conversation->context_type);
        self::assertSame(0, ModerationReport::query()->count());
        self::assertTrue(app(MessagingService::class)->canAccess($conversation, $administrator));
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

    /** @return array{0: ModerationDecision, 1: ZumraGroup} */
    private function warnedMember(): array
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $comment = $this->zumraComment($group, 'IDN-MEMBER');
        $report = app(ModerationReportService::class)->reportContextComment($comment, 'IDN-REPORTER', ModerationReport::REASON_HARASSMENT, null);
        $decision = app(ModerationDecisionService::class)->decideAsZumraLeader($group, $report, 'IDN-LEADER', ModerationDecision::ACTION_WARNING, 'Comportement à corriger.');

        return [$decision, $group];
    }

    private function zumraComment(ZumraGroup $group, string $author): ContextComment
    {
        return ContextComment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'context_type' => ContextComment::CONTEXT_ZUMRA_ACTIVITY,
            'context_reference' => $group->public_reference,
            'author_core_reference' => $author,
            'purpose' => 'COORDINATION',
            'body' => 'Un contenu réel posté dans l’activité de cette ZUMRA.',
            'posted_at' => now(),
        ]);
    }

    private function directMessage(string $sender, string $recipient): MessageEntry
    {
        $this->profile($recipient, 'Membre '.$recipient);
        $conversation = app(MessagingService::class)->startDirect($sender, PersonProfile::query()->where('core_identity_reference', $recipient)->sole()->discovery_reference);

        return app(MessagingService::class)->send($conversation, $sender, 'Un message réel entre deux personnes.');
    }

    private function profile(string $identity, string $name): PersonProfile
    {
        return PersonProfile::query()->firstOrCreate(['core_identity_reference' => $identity], [
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_bio' => 'Profil utile et volontaire.',
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
            'country_code' => 'CI',
            'city' => 'Abidjan',
            'participation_mode' => 'HYBRIDE',
        ]);
    }

    private function group(string $leader): ZumraGroup
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => 'ZUMRA Modération '.Str::random(6),
            'slug' => 'zumra-moderation-'.Str::lower(Str::random(8)),
            'domain' => 'Numérique',
            'founding_objective' => 'Coordonner des personnes autour d’une action utile et concrète.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => $leader,
            'active_member_count' => 1,
        ]);
        $this->membership($group, $leader, ZumraGroupMembership::STATUS_ACTIVE);
        $this->addRole($group, 'PRIMARY_LEAD', $leader);

        return $group;
    }

    private function membership(ZumraGroup $group, string $identity, string $status): ZumraGroupMembership
    {
        return ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $identity,
            'status' => $status,
            'entry_mode' => 'FOUNDER',
            'initiated_by_core_reference' => $group->proposer_core_reference,
            'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function addRole(ZumraGroup $group, string $role, string $identity): void
    {
        ZumraGroupRole::query()->updateOrCreate(
            ['zumra_group_id' => $group->id, 'role' => $role],
            [
                'core_identity_reference' => $identity, 'status' => ZumraGroupRole::STATUS_ACCEPTED,
                'proposed_by_core_reference' => $group->proposer_core_reference, 'proposed_at' => now(), 'accepted_at' => now(),
            ],
        );
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::lower(Str::random(6));
        PortalAdministrator::query()->firstOrCreate(['core_identity_reference' => $reference], ['granted_by_core_reference' => $reference]);

        return $reference;
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-30T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-30T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
