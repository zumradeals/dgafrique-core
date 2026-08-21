<?php

declare(strict_types=1);

namespace App\Application\Ecosystem;

use App\Application\Organizations\OrganizationService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\CommunityEvent;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\Organization;
use App\Models\Partnership;
use App\Models\Project;
use App\Models\Proof;
use App\Models\Transmission;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;

/**
 * CAP-080 — « Ce que devrait mesurer DG Afrique ». Clarification doctrinale validée : mesurer la
 * capacité collective à transformer des capacités disponibles en actions et résultats réels — des
 * faits collectifs et des flux métier, jamais la valeur des personnes. Projection de lecture pure,
 * dérivée exclusivement des domaines existants (aucune nouvelle table, aucun snapshot, aucun ETL,
 * aucun cron). Granularité : portail, ZUMRA, Organisation — jamais individuelle/comparative.
 *
 * INTERDIT par construction : aucun score, classement, niveau de valeur, réputation, gamification,
 * KPI d'engagement (likes/vues/popularité). Chaque méthode retourne uniquement des compteurs
 * collectifs entiers, jamais une liste de personnes ni un montant financier par personne.
 */
final class ImpactMetricsService
{
    public function __construct(
        private readonly ZumraGroupService $zumraGroups,
        private readonly OrganizationService $organizations,
    ) {}

    /** @return array<string, int> */
    public function portal(): array
    {
        return [
            'capabilities_declared_count' => CapabilityStatement::query()->where('kind', CapabilityStatement::KIND_POSSESSED)->count(),
            'needs_expressed_count' => Need::query()->count(),
            'needs_resolved_count' => Need::query()->where('status', Need::STATUS_RESOLVED)->count(),
            'projects_initiated_count' => Project::query()->count(),
            'projects_completed_count' => Project::query()->where('status', Project::STATUS_COMPLETED)->count(),
            'missions_proposed_count' => Mission::query()->where('status', '!=', Mission::STATUS_DRAFT)->count(),
            'missions_assigned_count' => MissionAssignment::query()->where('status', MissionAssignment::STATUS_ACCEPTED)->count(),
            'missions_completed_count' => Mission::query()->where('status', Mission::STATUS_COMPLETED)->count(),
            'transmissions_completed_count' => Transmission::query()->whereIn('status', [Transmission::STATUS_COMPLETED_CONFIRMED, Transmission::STATUS_COMPLETED_BY_CONTEXT])->count(),
            'proofs_produced_count' => Proof::query()->count(),
            'proofs_validated_count' => Proof::query()->whereIn('status', [Proof::STATUS_WITNESSED, Proof::STATUS_ACKNOWLEDGED])->count(),
            'zumra_groups_active_count' => ZumraGroup::query()->where('state', ZumraGroup::STATE_ACTIVE)->count(),
            'organizations_active_count' => Organization::query()->where('status', Organization::STATUS_ACTIVE)->count(),
            'partnerships_active_count' => Partnership::query()->where('status', Partnership::STATUS_ACTIVE)->count(),
            'community_events_organized_count' => CommunityEvent::query()->where('status', '!=', CommunityEvent::STATUS_CANCELLED)->count(),
            'contributions_confirmed_count' => LedgerEntry::query()->count(),
        ];
    }

    /** @return array<string, int> */
    public function forZumraGroup(ZumraGroup $group, string $actor): array
    {
        abort_unless($this->isActiveZumraMember($group->id, $actor) || $this->zumraGroups->isLeader($group, $actor), 404);

        return [
            'active_member_count' => $group->active_member_count,
            'needs_expressed_count' => Need::query()->where('owner_type', Need::OWNER_GROUP)->where('owner_reference', $group->id)->count(),
            'needs_resolved_count' => Need::query()->where('owner_type', Need::OWNER_GROUP)->where('owner_reference', $group->id)->where('status', Need::STATUS_RESOLVED)->count(),
            'projects_initiated_count' => Project::query()->where('owner_type', Project::OWNER_GROUP)->where('owner_reference', $group->id)->count(),
            'projects_completed_count' => Project::query()->where('owner_type', Project::OWNER_GROUP)->where('owner_reference', $group->id)->where('status', Project::STATUS_COMPLETED)->count(),
            'missions_proposed_count' => Mission::query()->where('context_type', 'ZUMRA')->where('context_reference', $group->public_reference)->where('status', '!=', Mission::STATUS_DRAFT)->count(),
            'missions_completed_count' => Mission::query()->where('context_type', 'ZUMRA')->where('context_reference', $group->public_reference)->where('status', Mission::STATUS_COMPLETED)->count(),
            'partnerships_active_count' => Partnership::query()->where('context_type', Partnership::CONTEXT_ZUMRA)->where('context_reference', $group->public_reference)->where('status', Partnership::STATUS_ACTIVE)->count(),
            'community_events_organized_count' => CommunityEvent::query()->where('organizer_type', CommunityEvent::ORGANIZER_ZUMRA_GROUP)->where('organizer_reference', $group->id)->where('status', '!=', CommunityEvent::STATUS_CANCELLED)->count(),
        ];
    }

    /** @return array<string, int> */
    public function forOrganization(Organization $organization, string $actor): array
    {
        abort_unless($this->organizations->canView($organization, $actor), 404);

        return [
            'active_member_count' => $organization->active_member_count,
            'partnerships_active_count' => Partnership::query()->where('provider_type', Partnership::PROVIDER_ORGANIZATION)->where('provider_reference', $organization->id)->where('status', Partnership::STATUS_ACTIVE)->count(),
            'community_events_organized_count' => CommunityEvent::query()->where('organizer_type', CommunityEvent::ORGANIZER_ORGANIZATION)->where('organizer_reference', $organization->id)->where('status', '!=', CommunityEvent::STATUS_CANCELLED)->count(),
        ];
    }

    private function isActiveZumraMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $groupId)->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)->exists();
    }
}
