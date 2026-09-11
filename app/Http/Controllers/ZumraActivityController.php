<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ZumraActivityProjection;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

final class ZumraActivityController
{
    public function __invoke(
        Request $request,
        ZumraGroup $group,
        ZumraActivityProjection $activity,
        ZumraGroupService $groups,
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isLeader = $groups->isLeader($group, $identity->reference);
        $isActiveMember = ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $identity->reference)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->exists();

        abort_if($group->state === ZumraGroup::STATE_SUSPENDED && ! $isLeader, 404);
        abort_unless($isActiveMember || $isLeader, 404);

        $page = max(1, $request->integer('page', 1));
        $perPage = 12;
        $items = $activity->forZumra($group, $identity->reference);
        $feed = new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => route('zumra.groups.activity', $group), 'pageName' => 'page'],
        );

        return view('zumra.groups.activity', [
            'identity' => $identity,
            'group' => $group,
            'feed' => $feed,
            'isLeader' => $isLeader,
        ]);
    }
}
