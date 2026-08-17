<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionMatchingEngine;
use App\Application\Missions\MissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\PortalAdministrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MissionMatchingController
{
    public function index(Request $request, Mission $mission, MissionMatchingEngine $engine, MissionService $missions): View
    {
        $identity = $this->identity($request);
        $mission = $missions->find($mission->public_reference, $identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $recommendations = $engine->recommend($mission, $identity->reference);

        return view('missions.matching', compact('identity', 'isAdministrator', 'mission', 'recommendations'));
    }

    public function hide(Request $request, Mission $mission, string $subject, MissionMatchingEngine $engine): RedirectResponse
    {
        $identity = $this->identity($request);
        $engine->hide($mission, $identity->reference, $subject);

        return back()->with('status', 'Cette piste est masquée pour cette Mission.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
