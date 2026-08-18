<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ActivityFeedService;
use App\Application\Needs\NeedConfiguration;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ActivityFeedController
{
    public function index(
        Request $request,
        ActivityFeedService $activity,
        PersonRecommendationEngine $recommendationEngine,
        RecommendationConfiguration $recommendationConfiguration,
        NeedConfiguration $needConfiguration,
    ): View {
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

        $myGroups = ZumraGroup::query()
            ->whereIn('id', ZumraGroupMembership::query()
                ->where('core_identity_reference', $identity->reference)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->pluck('zumra_group_id'))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $recommendedPeople = array_slice(
            $recommendationEngine->forIdentity($identity->reference, $recommendationConfiguration->get())['recommendations'],
            0,
            3,
        );

        return view('activity.index', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'feed' => $feed,
            'filter' => $filter,
            'filters' => ActivityFeedService::FILTERS,
            'myGroups' => $myGroups,
            'recommendedPeople' => $recommendedPeople,
            'composerCategories' => $needConfiguration->get()['categories'],
        ]);
    }
}
