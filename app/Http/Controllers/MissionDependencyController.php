<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionDependencyService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionDependency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MissionDependencyController
{
    public function store(Request $request, Mission $mission, MissionDependencyService $dependencies): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'depends_on_mission_reference' => ['required', 'uuid'],
            'type' => ['required', Rule::in(MissionDependency::TYPES)],
        ]);
        $dependsOn = Mission::query()->where('public_reference', $data['depends_on_mission_reference'])->firstOrFail();
        $dependencies->add($mission, $identity->reference, $dependsOn, $data['type']);

        return back()->with('status', 'La dépendance est ajoutée.');
    }

    public function destroy(Request $request, Mission $mission, MissionDependency $dependency, MissionDependencyService $dependencies): RedirectResponse
    {
        $identity = $this->identity($request);
        $dependencies->remove($mission, $identity->reference, $dependency);

        return back()->with('status', 'La dépendance est retirée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
