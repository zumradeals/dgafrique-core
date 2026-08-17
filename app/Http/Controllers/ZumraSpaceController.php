<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ZumraSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $myMemberships = ZumraGroupMembership::query()
            ->where('core_identity_reference', $identity->reference)
            ->whereIn('status', [ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::STATUS_INVITED, ZumraGroupMembership::STATUS_REQUESTED])
            ->get()
            ->keyBy('zumra_group_id');

        $myGroups = ZumraGroup::query()
            ->whereIn('id', $myMemberships->keys())
            ->orderBy('name')
            ->get()
            ->map(fn (ZumraGroup $group) => [
                'group' => $group,
                'status' => $myMemberships->get($group->id)->status,
            ]);

        // Groupes où ce membre détient une responsabilité acceptée : les demandes en attente
        // de leurs éventuels candidats méritent d'être visibles depuis le hub.
        $ledGroupIds = ZumraGroupRole::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');

        $pendingRequestsToDecide = $ledGroupIds->isEmpty() ? collect() : ZumraGroupMembership::query()
            ->whereIn('zumra_group_id', $ledGroupIds)
            ->where('status', ZumraGroupMembership::STATUS_REQUESTED)
            ->with([])
            ->get()
            ->groupBy('zumra_group_id')
            ->map(fn ($rows, $groupId) => [
                'group' => ZumraGroup::query()->find($groupId),
                'count' => $rows->count(),
            ])
            ->values();

        return view('zumra.index', compact(
            'identity', 'profile', 'membership', 'isAdministrator', 'myGroups', 'pendingRequestsToDecide',
        ));
    }
}
