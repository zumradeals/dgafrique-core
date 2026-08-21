<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Collection;

/**
 * UIUX-002 — source applicative unique des signaux ZUMRA « ceci vous concerne réellement »,
 * réutilisée par `MemberSpaceController::priority()`, `NotificationSourceRegistry` et
 * `ZumraSpaceController` : une seule définition de chaque fait métier, jamais trois requêtes
 * indépendantes du même fait. N'accorde jamais un accès qu'une autorité existante refuserait —
 * chaque méthode reste strictement scopée à l'identité de l'acteur (rôle accepté pour décider,
 * ou identité directement destinataire pour une invitation/proposition).
 */
final class ZumraAttentionSource
{
    /** Groupes où l'acteur détient une responsabilité fondatrice acceptée (autorité de décision). */
    public function ledGroupIds(string $actor): Collection
    {
        return ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');
    }

    /**
     * Demandes d'adhésion en attente d'une décision, pour les groupes que l'acteur dirige.
     *
     * @return Collection<int, array{group: ZumraGroup, membership: ZumraGroupMembership}>
     */
    public function pendingJoinRequestsForLeader(string $actor): Collection
    {
        $ledGroupIds = $this->ledGroupIds($actor);
        if ($ledGroupIds->isEmpty()) {
            return collect();
        }

        return $this->withGroups(
            ZumraGroupMembership::query()
                ->whereIn('zumra_group_id', $ledGroupIds)
                ->where('status', ZumraGroupMembership::STATUS_REQUESTED)
                ->oldest('requested_at')
                ->get(),
            'membership',
        );
    }

    /**
     * Invitations ZUMRA reçues personnellement par l'acteur, en attente de sa réponse.
     *
     * @return Collection<int, array{group: ZumraGroup, membership: ZumraGroupMembership}>
     */
    public function myPendingInvitations(string $actor): Collection
    {
        return $this->withGroups(
            ZumraGroupMembership::query()
                ->where('core_identity_reference', $actor)
                ->where('status', ZumraGroupMembership::STATUS_INVITED)
                ->oldest('invited_at')
                ->get(),
            'membership',
        );
    }

    /**
     * Propositions de responsabilité fondatrice adressées personnellement à l'acteur, en attente
     * de son acceptation explicite (art. 7 ZUMRA-DOCTRINE-INVARIANTE.md).
     *
     * @return Collection<int, array{group: ZumraGroup, role: ZumraGroupRole}>
     */
    public function myPendingRoleProposals(string $actor): Collection
    {
        return $this->withGroups(
            ZumraGroupRole::query()
                ->where('core_identity_reference', $actor)
                ->where('status', ZumraGroupRole::STATUS_PROPOSED)
                ->oldest('proposed_at')
                ->get(),
            'role',
        );
    }

    /**
     * @param  Collection<int, ZumraGroupMembership|ZumraGroupRole>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function withGroups(Collection $rows, string $key): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $groups = ZumraGroup::query()->whereIn('id', $rows->pluck('zumra_group_id')->unique())->get()->keyBy('id');

        return $rows
            ->map(fn ($row): array => ['group' => $groups->get($row->zumra_group_id), $key => $row])
            ->filter(fn (array $item): bool => $item['group'] !== null)
            ->values();
    }
}
