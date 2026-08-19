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

        $page = max(1, $request->integer('page', 1));

        $feed = $activity->paginate($identity->reference, $filter, $page);

        if ($filter !== 'ALL') {
            $feed->appends(['type' => $filter]);
        }

        // Fil V2 — règle DEMO-FIRST, REAL-DATA-TAKES-OVER (docs/design/DESIGN-INVARIANTS.md §17) :
        // des cartes d'exemple ne s'affichent qu'en première page et seulement quand le filtre
        // actif n'a aucune donnée réelle — jamais mélangées à de vraies cartes.
        $demoCards = [];
        if ($page === 1 && $feed->isEmpty()) {
            $demo = json_decode(
                file_get_contents(resource_path('design-reference/fil-demo.json')),
                true,
            );
            $demoCards = $filter === 'ALL'
                ? $demo['cards']
                : array_values(array_filter($demo['cards'], static fn (array $card): bool => $card['kind'] === $filter));
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
            'demoCards' => $demoCards,
            'filter' => $filter,
            'filters' => ActivityFeedService::FILTERS,
            'myGroups' => $myGroups,
            'recommendedPeople' => $recommendedPeople,
            'composerCategories' => $needConfiguration->get()['categories'],
        ]);
    }
}
