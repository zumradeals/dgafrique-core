<?php

declare(strict_types=1);

namespace App\Application\Moderation;

use App\Application\Zumra\ZumraGroupConfiguration;
use App\Application\Zumra\ZumraGroupService;
use App\Models\ContextComment;
use App\Models\MessageEntry;
use App\Models\ModerationDecision;
use App\Models\ModerationReport;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * MODERATION-COMP-001 (art. 19) — décision disciplinaire VIVANTE, jamais un simple événement
 * journalisé : porte seule l'état du recours (confirmé/modifié/levé), l'expiration et la traçabilité
 * exigées par la doctrine. Toute décision V1 provient d'un ModerationReport PENDING (ModerationReport
 * reste le registre disciplinaire canonique — §0.2 du mandat) ; moderation_report_id reste nullable au
 * schéma pour ne pas fermer une évolution future, mais aucune voie HTTP V1 ne décide sans signalement.
 *
 * Actions V1 : CONTENT_HIDDEN (ContextComment/MessageEntry), WARNING (aucun effet caché — art. 21),
 * MEMBERSHIP_SUSPENSION, MEMBERSHIP_EXCLUSION, ROLE_REVOCATION. LIMITATION n'a aucun effet propre
 * borné sans toucher de nombreux services : différée hors V1 plutôt que simulée (§22 du mandat).
 */
final class ModerationDecisionService
{
    private const SYSTEM_ACTOR = 'SYSTEM';

    private const MAX_DECISIONS_SCANNED = 200;

    public function __construct(
        private readonly ZumraGroupService $zumraGroups,
        private readonly ZumraGroupConfiguration $zumraGroupConfiguration,
        private readonly ModerationConfiguration $settings,
    ) {}

    public function decideAsZumraLeader(ZumraGroup $group, ModerationReport $report, string $actor, string $actionType, ?string $reasonDetails): ModerationDecision
    {
        abort_unless($this->zumraGroups->isLeader($group, $actor), 403);
        abort_unless($this->reportIsWithinGroupScope($report, $group), 404);

        $subject = $this->subjectOf($report->target_type, $report->target_reference);
        abort_if($subject !== null && $this->zumraGroups->isPrimaryLead($group, $subject), 403, 'Seule l’autorité DG Afrique/GAMAD peut décider d’une sanction visant le premier responsable.');

        return $this->decide($report, $actor, ModerationDecision::AUTHORITY_LEVEL_ZUMRA, $actionType, $reasonDetails, $group);
    }

    public function decideAsAdministrator(ModerationReport $report, string $actor, string $actionType, ?string $reasonDetails): ModerationDecision
    {
        $this->assertAdministrator($actor);

        $group = $report->context_type === ModerationReport::CONTEXT_ZUMRA ? ZumraGroup::query()->find($report->context_reference) : null;

        return $this->decide($report, $actor, ModerationDecision::AUTHORITY_LEVEL_DG_AFRIQUE, $actionType, $reasonDetails, $group);
    }

    public function requestAppeal(ModerationDecision $decision, string $actor, string $reason): ModerationDecision
    {
        $decision = $this->withExpiryApplied($decision);
        abort_unless($this->subjectOfDecision($decision) === $actor, 403);
        abort_if(trim($reason) === '', 422, 'Un motif de recours est obligatoire.');
        abort_unless($decision->status === ModerationDecision::STATUS_ACTIVE, 409, 'Seule une décision active peut faire l’objet d’un recours.');
        abort_if($decision->appeal_requested_at !== null, 409, 'Un recours est déjà en cours pour cette décision.');

        // Non suspensif (art. 19) : le statut de la décision reste inchangé.
        $decision->update(['appeal_requested_at' => now(), 'appeal_reason' => $reason]);

        return $decision;
    }

    public function decideAppeal(ModerationDecision $decision, string $actor, string $outcome, ?string $explanation): ModerationDecision
    {
        abort_unless(in_array($outcome, [ModerationDecision::APPEAL_OUTCOME_CONFIRMED, ModerationDecision::APPEAL_OUTCOME_MODIFIED, ModerationDecision::APPEAL_OUTCOME_LIFTED], true), 422, 'Issue de recours invalide.');
        // Le recours exige une autorité supérieure (art. 19 : décision niveau 2 → recours niveau 3).
        // Pour une décision déjà niveau 3, aucune autorité GAMAD distincte n'est techniquement
        // disponible en V1 : seul un réexamen administratif par le niveau 3 reste possible, jamais
        // présenté comme un « recours GAMAD » réel (limite documentée — §18 du mandat).
        $this->assertAdministrator($actor);
        abort_if($decision->appeal_requested_at === null, 409, 'Aucun recours n’a été demandé pour cette décision.');

        return DB::transaction(function () use ($decision, $actor, $outcome, $explanation): ModerationDecision {
            $fresh = ModerationDecision::query()->whereKey($decision->id)->lockForUpdate()->firstOrFail();
            abort_if($fresh->appeal_decided_at !== null, 409, 'Ce recours a déjà été tranché.');

            $newStatus = match ($outcome) {
                ModerationDecision::APPEAL_OUTCOME_CONFIRMED => $fresh->status,
                ModerationDecision::APPEAL_OUTCOME_MODIFIED => ModerationDecision::STATUS_MODIFIED,
                ModerationDecision::APPEAL_OUTCOME_LIFTED => ModerationDecision::STATUS_LIFTED,
            };

            $fresh->update([
                'appeal_decided_at' => now(), 'appeal_decided_by_core_reference' => $actor,
                'appeal_outcome' => $outcome, 'appeal_explanation' => $explanation, 'status' => $newStatus,
            ]);

            if ($outcome === ModerationDecision::APPEAL_OUTCOME_LIFTED) {
                $this->reverseEffect($fresh, $actor);
            }

            return $fresh;
        });
    }

    public function myDecisions(string $actor): Collection
    {
        return ModerationDecision::query()->latest('effective_at')->limit(self::MAX_DECISIONS_SCANNED)->get()
            ->map(fn (ModerationDecision $decision): ModerationDecision => $this->withExpiryApplied($decision))
            ->filter(fn (ModerationDecision $decision): bool => $this->subjectOfDecision($decision) === $actor)
            ->values();
    }

    public function withExpiryApplied(ModerationDecision $decision): ModerationDecision
    {
        if ($decision->status !== ModerationDecision::STATUS_ACTIVE || $decision->expires_at === null || $decision->expires_at->isFuture()) {
            return $decision;
        }

        return DB::transaction(function () use ($decision): ModerationDecision {
            $fresh = ModerationDecision::query()->whereKey($decision->id)->lockForUpdate()->first();
            if ($fresh === null || $fresh->status !== ModerationDecision::STATUS_ACTIVE || $fresh->expires_at === null || $fresh->expires_at->isFuture()) {
                return $fresh ?? $decision;
            }

            $fresh->update(['status' => ModerationDecision::STATUS_EXPIRED]);
            if ($fresh->action_type === ModerationDecision::ACTION_MEMBERSHIP_SUSPENSION) {
                $this->reinstateMembership($fresh, self::SYSTEM_ACTOR);
            }

            return $fresh;
        });
    }

    /** @return array<string, mixed> */
    public function presentForSubject(ModerationDecision $decision): array
    {
        $decision = $this->withExpiryApplied($decision);

        return [
            'id' => $decision->id,
            'action_type' => $decision->action_type,
            'reason_code' => $decision->reason_code,
            'reason_details' => $decision->reason_details,
            'decided_by_core_reference' => $decision->decided_by_core_reference,
            'authority_level' => $decision->authority_level,
            'effective_at' => $decision->effective_at,
            'expires_at' => $decision->expires_at,
            'status' => $decision->status,
            'appeal_requested_at' => $decision->appeal_requested_at,
            'appeal_decided_at' => $decision->appeal_decided_at,
            'appeal_outcome' => $decision->appeal_outcome,
            'appeal_explanation' => $decision->appeal_explanation,
            'is_currently_effective' => $decision->isCurrentlyEffective(),
        ];
    }

    private function decide(ModerationReport $report, string $actor, int $authorityLevel, string $actionType, ?string $reasonDetails, ?ZumraGroup $group): ModerationDecision
    {
        abort_unless(in_array($actionType, ModerationDecision::ACTION_TYPES, true), 422, 'Type de décision invalide.');

        try {
            return DB::transaction(function () use ($report, $actor, $authorityLevel, $actionType, $reasonDetails, $group): ModerationDecision {
                $freshReport = ModerationReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
                abort_unless($freshReport->status === ModerationReport::STATUS_PENDING, 409, 'Ce signalement a déjà été décidé.');

                $this->applyEffect($freshReport, $actor, $actionType, $group);

                $decision = ModerationDecision::query()->create([
                    'moderation_report_id' => $freshReport->id,
                    'target_type' => $freshReport->target_type,
                    'target_reference' => $freshReport->target_reference,
                    'action_type' => $actionType,
                    'reason_code' => $freshReport->reason_code,
                    'reason_details' => $reasonDetails,
                    'decided_by_core_reference' => $actor,
                    'authority_level' => $authorityLevel,
                    'effective_at' => now(),
                    'expires_at' => $this->computeExpiry($actionType),
                    'status' => ModerationDecision::STATUS_ACTIVE,
                ]);

                $freshReport->update(['status' => ModerationReport::STATUS_DECIDED]);

                return $decision;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Une décision est déjà active pour ce signalement ou cette cible.');
            }
            throw $exception;
        }
    }

    private function applyEffect(ModerationReport $report, string $actor, string $actionType, ?ZumraGroup $group): void
    {
        match (true) {
            $actionType === ModerationDecision::ACTION_CONTENT_HIDDEN && $report->target_type === ModerationReport::TARGET_CONTEXT_COMMENT => ContextComment::query()->whereKey($report->target_reference)->update(['hidden_at' => now()]),
            $actionType === ModerationDecision::ACTION_CONTENT_HIDDEN && $report->target_type === ModerationReport::TARGET_MESSAGE_ENTRY => MessageEntry::query()->whereKey($report->target_reference)->update(['hidden_at' => now()]),
            $actionType === ModerationDecision::ACTION_WARNING => null,
            $actionType === ModerationDecision::ACTION_MEMBERSHIP_SUSPENSION => $this->applyMembershipSuspension($report, $actor),
            $actionType === ModerationDecision::ACTION_MEMBERSHIP_EXCLUSION => $this->applyMembershipExclusion($report, $actor),
            $actionType === ModerationDecision::ACTION_ROLE_REVOCATION => $this->applyRoleRevocation($report, $actor),
            default => abort(422, 'Cette action n’est pas applicable à la cible signalée.'),
        };
    }

    private function applyMembershipSuspension(ModerationReport $report, string $actor): void
    {
        abort_unless($report->target_type === ModerationReport::TARGET_ZUMRA_MEMBERSHIP, 422, 'La suspension individuelle cible une adhésion ZUMRA.');
        $membership = ZumraGroupMembership::query()->findOrFail($report->target_reference);
        $group = ZumraGroup::query()->findOrFail($membership->zumra_group_id);
        $this->zumraGroups->suspendMember($group, $actor, $membership->core_identity_reference, $this->reasonLabel($report), $this->establishedThreshold());
    }

    private function applyMembershipExclusion(ModerationReport $report, string $actor): void
    {
        abort_unless($report->target_type === ModerationReport::TARGET_ZUMRA_MEMBERSHIP, 422, 'L’exclusion cible une adhésion ZUMRA.');
        $membership = ZumraGroupMembership::query()->findOrFail($report->target_reference);
        $group = ZumraGroup::query()->findOrFail($membership->zumra_group_id);
        $this->zumraGroups->exclude($group, $actor, $membership->core_identity_reference, $this->reasonLabel($report), $this->establishedThreshold());
    }

    private function applyRoleRevocation(ModerationReport $report, string $actor): void
    {
        abort_unless($report->target_type === ModerationReport::TARGET_ZUMRA_MEMBERSHIP, 422, 'La révocation de rôle cible une adhésion ZUMRA.');
        $membership = ZumraGroupMembership::query()->findOrFail($report->target_reference);
        $group = ZumraGroup::query()->findOrFail($membership->zumra_group_id);
        $role = $group->roles()->where('core_identity_reference', $membership->core_identity_reference)->where('status', 'ACCEPTED')->first();
        abort_if($role === null, 409, 'Cette personne n’occupe aucune responsabilité à révoquer.');
        $this->zumraGroups->revokeRole($group, $actor, $role->role);
    }

    private function reverseEffect(ModerationDecision $decision, string $actor): void
    {
        match ($decision->action_type) {
            ModerationDecision::ACTION_CONTENT_HIDDEN => $this->unhide($decision),
            ModerationDecision::ACTION_MEMBERSHIP_SUSPENSION => $this->reinstateMembership($decision, $actor),
            // Exclusion/révocation levées restent tracées mais ne réadmettent jamais automatiquement :
            // une réintégration passe par une nouvelle décision de gouvernance ZUMRA explicite,
            // jamais un rétablissement silencieux (limite V1 documentée).
            default => null,
        };
    }

    private function unhide(ModerationDecision $decision): void
    {
        match ($decision->target_type) {
            ModerationDecision::TARGET_CONTEXT_COMMENT => ContextComment::query()->whereKey($decision->target_reference)->update(['hidden_at' => null]),
            ModerationDecision::TARGET_MESSAGE_ENTRY => MessageEntry::query()->whereKey($decision->target_reference)->update(['hidden_at' => null]),
            default => null,
        };
    }

    private function reinstateMembership(ModerationDecision $decision, string $actor): void
    {
        $membership = ZumraGroupMembership::query()->find($decision->target_reference);
        if ($membership === null || $membership->status !== ZumraGroupMembership::STATUS_SUSPENDED) {
            return;
        }
        $group = ZumraGroup::query()->find($membership->zumra_group_id);
        if ($group === null) {
            return;
        }
        $this->zumraGroups->reinstate($group, $actor, $membership->core_identity_reference, $this->establishedThreshold());
    }

    private function computeExpiry(string $actionType): ?Carbon
    {
        $settings = $this->settings->get();

        return match ($actionType) {
            ModerationDecision::ACTION_WARNING => now()->addDays((int) $settings['warning_default_duration_days']),
            ModerationDecision::ACTION_MEMBERSHIP_SUSPENSION => now()->addDays((int) $settings['suspension_default_duration_days']),
            default => null,
        };
    }

    private function subjectOf(string $targetType, string $targetReference): ?string
    {
        return match ($targetType) {
            ModerationReport::TARGET_CONTEXT_COMMENT => ContextComment::query()->find($targetReference)?->author_core_reference,
            ModerationReport::TARGET_MESSAGE_ENTRY => MessageEntry::query()->find($targetReference)?->sender_core_reference,
            ModerationReport::TARGET_ZUMRA_MEMBERSHIP => ZumraGroupMembership::query()->find($targetReference)?->core_identity_reference,
            default => null,
        };
    }

    private function subjectOfDecision(ModerationDecision $decision): ?string
    {
        return $this->subjectOf($decision->target_type, $decision->target_reference);
    }

    private function reportIsWithinGroupScope(ModerationReport $report, ZumraGroup $group): bool
    {
        return $report->context_type === ModerationReport::CONTEXT_ZUMRA
            && $report->context_reference === $group->id
            && $report->escalated_at === null;
    }

    private function reasonLabel(ModerationReport $report): string
    {
        return trim((string) $report->reason_details) !== '' ? (string) $report->reason_details : $report->reason_code;
    }

    private function establishedThreshold(): int
    {
        return (int) $this->zumraGroupConfiguration->get()['established_member_threshold'];
    }

    private function assertAdministrator(string $actor): void
    {
        abort_unless(PortalAdministrator::query()->whereKey($actor)->exists(), 403, 'Seule l’autorité DG Afrique/GAMAD peut décider de cette action.');
    }
}
