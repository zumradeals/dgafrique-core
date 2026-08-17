<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionSubmissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MissionContributionController
{
    public function store(Request $request, Mission $mission, MissionSubmissionService $submissions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['summary' => ['required', 'string', 'min:5', 'max:2000']]);
        $submissions->contribute($mission, $identity->reference, $data['summary']);

        return back()->with('status', 'Votre contribution est enregistrée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
