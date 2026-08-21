<?php

declare(strict_types=1);

namespace App\Application\Moderation;

use App\Application\Messaging\MessagingService;
use App\Models\ContextComment;
use App\Models\MessageConversation;
use App\Models\MessageEntry;
use App\Models\ModerationReport;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * MODERATION-COMP-001 (art. 13/19) — signalement transversal, persistant dès V1. Cibles limitées à
 * CONTEXT_COMMENT, MESSAGE_ENTRY, ZUMRA_MEMBERSHIP (aucun support universel non testé). context_type/
 * context_reference ne décrivent jamais le contenu ciblé mais la portée d'autorité niveau 2 : 'ZUMRA'
 * + id de groupe uniquement lorsque la cible relève réellement d'une ZUMRA gouvernée ; sinon null,
 * ce qui réserve le signalement à DG Afrique (niveau 3) — jamais une ZUMRA ne peut intercepter un
 * signalement hors de sa portée de gouvernance.
 *
 * CRITIQUE (art. 21, confidentialité) : reporter_core_reference n'est JAMAIS exposé par les méthodes
 * de présentation niveau 2 (`presentForZumraLeader`). Seule l'autorité DG Afrique compétente (niveau
 * 3, `presentForAdministrator`) y accède. La personne visée n'a jamais accès au signalement lui-même
 * — seulement à la décision qui peut en résulter (ModerationDecisionService).
 */
final class ModerationReportService
{
    private const MAX_REPORTS_RETURNED = 100;

    public function __construct(private readonly MessagingService $messaging) {}

    public function reportContextComment(ContextComment $comment, string $actor, string $reasonCode, ?string $reasonDetails): ModerationReport
    {
        abort_if($comment->author_core_reference === $actor, 422, 'Vous ne pouvez pas signaler votre propre contribution.');

        // La lecture du fil ZUMRA_ACTIVITY (ContextCommentService::zumraActivityThread) n'exige déjà
        // aucune adhésion active — seulement une ZUMRA non suspendue. Le signalement ne peut donc pas
        // être plus restrictif que la lecture qu'il présuppose.
        [$contextType, $contextReference] = [null, null];
        if ($comment->context_type === ContextComment::CONTEXT_ZUMRA_ACTIVITY) {
            $group = ZumraGroup::query()->where('public_reference', $comment->context_reference)->first();
            abort_if($group === null || $group->state === ZumraGroup::STATE_SUSPENDED, 404);
            [$contextType, $contextReference] = [ModerationReport::CONTEXT_ZUMRA, $group->id];
        }

        return $this->create($actor, ModerationReport::TARGET_CONTEXT_COMMENT, $comment->id, $contextType, $contextReference, $reasonCode, $reasonDetails);
    }

    /**
     * Un responsable ZUMRA n'obtient JAMAIS l'accès à la conversation entière par ce chemin : seul
     * un participant réellement autorisé (canAccess) peut signaler ; le signalement ne conserve que
     * la référence du message et son extrait (targetExcerpt), jamais le fil complet.
     */
    public function reportMessageEntry(MessageEntry $entry, string $actor, string $reasonCode, ?string $reasonDetails): ModerationReport
    {
        abort_if($entry->sender_core_reference === $actor, 422, 'Vous ne pouvez pas signaler votre propre message.');
        $conversation = $entry->conversation;
        abort_unless($conversation !== null && $this->messaging->canAccess($conversation, $actor), 404);

        [$contextType, $contextReference] = [null, null];
        if ($conversation->context_type === MessageConversation::CONTEXT_ZUMRA) {
            $group = ZumraGroup::query()->where('public_reference', $conversation->context_reference)->first();
            if ($group !== null) {
                [$contextType, $contextReference] = [ModerationReport::CONTEXT_ZUMRA, $group->id];
            }
        }

        return $this->create($actor, ModerationReport::TARGET_MESSAGE_ENTRY, $entry->id, $contextType, $contextReference, $reasonCode, $reasonDetails);
    }

    public function reportZumraMembership(ZumraGroupMembership $membership, string $actor, string $reasonCode, ?string $reasonDetails): ModerationReport
    {
        abort_if($membership->core_identity_reference === $actor, 422, 'Vous ne pouvez pas vous signaler vous-même.');
        abort_unless($this->isActiveMember($membership->zumra_group_id, $actor), 403);

        return $this->create(
            $actor, ModerationReport::TARGET_ZUMRA_MEMBERSHIP, $membership->id,
            ModerationReport::CONTEXT_ZUMRA, $membership->zumra_group_id, $reasonCode, $reasonDetails,
        );
    }

    /** Droit d'escalade (art. 19) : une ZUMRA ne peut ni intercepter ni bloquer ce chemin. */
    public function escalate(ModerationReport $report, string $actor): ModerationReport
    {
        abort_unless($report->reporter_core_reference === $actor, 403);
        abort_if($report->escalated_at !== null, 409, 'Ce signalement est déjà transmis à DG Afrique.');
        $report->update(['escalated_at' => now()]);

        return $report;
    }

    public function myReports(string $actor): Collection
    {
        return ModerationReport::query()->where('reporter_core_reference', $actor)->latest('reported_at')->limit(self::MAX_REPORTS_RETURNED)->get();
    }

    /** Signalements décidables par ce niveau 2 : jamais ceux déjà escaladés (art. 19). */
    public function forZumraLeader(ZumraGroup $group): Collection
    {
        return ModerationReport::query()
            ->where('context_type', ModerationReport::CONTEXT_ZUMRA)
            ->where('context_reference', $group->id)
            ->whereNull('escalated_at')
            ->latest('reported_at')
            ->limit(self::MAX_REPORTS_RETURNED)
            ->get();
    }

    /** DG Afrique voit tout, y compris ce qu'une ZUMRA n'a jamais eu le droit de voir. */
    public function forAdministrator(): Collection
    {
        return ModerationReport::query()->latest('reported_at')->limit(self::MAX_REPORTS_RETURNED)->get();
    }

    public function canZumraLeaderDecide(ModerationReport $report, ZumraGroup $group): bool
    {
        return $report->context_type === ModerationReport::CONTEXT_ZUMRA
            && $report->context_reference === $group->id
            && $report->escalated_at === null;
    }

    /** @return array<string, mixed> */
    public function presentForZumraLeader(ModerationReport $report): array
    {
        return [
            'id' => $report->id,
            'target_type' => $report->target_type,
            'target_reference' => $report->target_reference,
            'target_excerpt' => $this->targetExcerpt($report),
            'reason_code' => $report->reason_code,
            'reason_details' => $report->reason_details,
            'status' => $report->status,
            'reported_at' => $report->reported_at,
        ];
    }

    /** @return array<string, mixed> */
    public function presentForAdministrator(ModerationReport $report): array
    {
        return array_merge($this->presentForZumraLeader($report), [
            'reporter_core_reference' => $report->reporter_core_reference,
            'context_type' => $report->context_type,
            'context_reference' => $report->context_reference,
            'escalated_at' => $report->escalated_at,
        ]);
    }

    /** Jamais le fil complet d'une conversation — uniquement le message/le commentaire signalé. */
    private function targetExcerpt(ModerationReport $report): ?string
    {
        $body = match ($report->target_type) {
            ModerationReport::TARGET_MESSAGE_ENTRY => MessageEntry::query()->find($report->target_reference)?->body,
            ModerationReport::TARGET_CONTEXT_COMMENT => ContextComment::query()->find($report->target_reference)?->body,
            default => null,
        };

        return $body === null ? null : Str::limit($body, 300);
    }

    private function isActiveMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()->where('zumra_group_id', $groupId)->where('core_identity_reference', $actor)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->exists();
    }

    private function create(string $actor, string $targetType, string $targetReference, ?string $contextType, ?string $contextReference, string $reasonCode, ?string $reasonDetails): ModerationReport
    {
        abort_unless(in_array($reasonCode, ModerationReport::REASON_CODES, true), 422, 'Motif de signalement invalide.');
        if ($reasonCode === ModerationReport::REASON_OTHER) {
            abort_if(trim((string) $reasonDetails) === '', 422, 'Une explication est obligatoire pour le motif « Autre ».');
        }

        return ModerationReport::query()->create([
            'reporter_core_reference' => $actor,
            'target_type' => $targetType,
            'target_reference' => $targetReference,
            'context_type' => $contextType,
            'context_reference' => $contextReference,
            'reason_code' => $reasonCode,
            'reason_details' => $reasonDetails,
            'status' => ModerationReport::STATUS_PENDING,
            'reported_at' => now(),
        ]);
    }
}
