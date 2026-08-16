<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ActivityFeedService;
use App\Domain\Identity\CoreIdentity;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ActivityFeedController
{
    public function index(Request $request, ActivityFeedService $activity): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $filter = strtoupper((string) $request->query('type', 'ALL'));
        if (! isset(ActivityFeedService::FILTERS[$filter])) {
            $filter = 'ALL';
        }

        $feed = $activity->paginate(
            $identity->reference,
            $filter,
            max(1, $request->integer('page', 1)),
        );

        if ($filter !== 'ALL') {
            $feed->appends(['type' => $filter]);
        }

        return view('activity.index', [
            'identity' => $identity,
            'feed' => $feed,
            'filter' => $filter,
            'filters' => ActivityFeedService::FILTERS,
        ]);
    }
}
