<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\CapabilityStatement;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CollectiveCapabilityProfile
{
    /** @return Collection<int, object{label: string, normalized_label: string, member_count: int}> */
    public function forGroup(ZumraGroup $group, array $configuration): Collection
    {
        $memberReferences = ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->where('collective_capability_consent', true)
            ->pluck('core_identity_reference');

        if ($memberReferences->isEmpty()) {
            return collect();
        }

        return CapabilityStatement::query()
            ->select(['normalized_label', DB::raw('MIN(label) as label'), DB::raw('COUNT(DISTINCT core_identity_reference) as member_count')])
            ->whereIn('core_identity_reference', $memberReferences)
            ->where('kind', CapabilityStatement::KIND_POSSESSED)
            ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
            ->where('matching_consent', true)
            ->whereNull('archived_at')
            ->groupBy('normalized_label')
            ->havingRaw('COUNT(DISTINCT core_identity_reference) >= ?', [(int) $configuration['minimum_members']])
            ->orderByDesc('member_count')
            ->orderBy('normalized_label')
            ->limit((int) $configuration['max_capabilities'])
            ->get();
    }
}
