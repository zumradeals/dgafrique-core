<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionRecurrenceService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionRecurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MissionRecurrenceController
{
    public function store(Request $request, Mission $mission, MissionRecurrenceService $recurrences): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'rrule' => ['required', 'string', 'max:500'],
            'timezone' => ['required', 'string', 'max:80'],
        ]);
        $recurrences->create($mission, $identity->reference, $data['rrule'], $data['timezone']);

        return back()->with('status', 'La récurrence est créée et active.');
    }

    public function pause(Request $request, Mission $mission, MissionRecurrence $recurrence, MissionRecurrenceService $recurrences): RedirectResponse
    {
        $identity = $this->identity($request);
        $recurrences->pause($mission, $recurrence, $identity->reference);

        return back()->with('status', 'La récurrence est en pause.');
    }

    public function resume(Request $request, Mission $mission, MissionRecurrence $recurrence, MissionRecurrenceService $recurrences): RedirectResponse
    {
        $identity = $this->identity($request);
        $recurrences->activate($mission, $recurrence, $identity->reference);

        return back()->with('status', 'La récurrence est réactivée.');
    }

    public function stop(Request $request, Mission $mission, MissionRecurrence $recurrence, MissionRecurrenceService $recurrences): RedirectResponse
    {
        $identity = $this->identity($request);
        $recurrences->stop($mission, $recurrence, $identity->reference);

        return back()->with('status', 'La récurrence est arrêtée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
