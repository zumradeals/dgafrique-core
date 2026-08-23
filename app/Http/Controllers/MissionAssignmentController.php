<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionAssignmentService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MissionAssignmentController
{
    public function offer(Request $request, Mission $mission, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['role' => ['required', Rule::in(MissionAssignmentService::ROLES)]]);
        $assignments->offer($mission, $identity->reference, $data['role']);

        return back()->with('status', 'Votre proposition de participation est enregistrée.');
    }

    public function acceptOffer(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $assignments->acceptOffer($mission, $identity->reference, $assignment);

        return back()->with('status', 'La proposition est acceptée.');
    }

    public function declineOffer(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $assignments->declineOffer($mission, $identity->reference, $assignment, $data['reason'] ?? null);

        return back()->with('status', 'La proposition est déclinée.');
    }

    public function withdrawOffer(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $assignments->withdraw($mission, $identity->reference, $assignment);

        return back()->with('status', 'Votre proposition est retirée.');
    }

    public function invite(Request $request, Mission $mission, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'discovery_reference' => ['required', 'uuid'],
            'role' => ['required', Rule::in(MissionAssignmentService::ROLES)],
        ]);
        $assignments->invite($mission, $identity->reference, $data['discovery_reference'], $data['role']);

        return back()->with('status', 'L’invitation est envoyée.');
    }

    public function acceptInvitation(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $assignments->acceptInvitation($mission, $identity->reference, $assignment);

        return back()->with('status', 'L’invitation est acceptée.');
    }

    public function declineInvitation(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $assignments->declineInvitation($mission, $identity->reference, $assignment, $data['reason'] ?? null);

        return back()->with('status', 'L’invitation est déclinée.');
    }

    public function release(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $assignments->release($mission, $identity->reference, $assignment, $data['reason'] ?? null);

        return back()->with('status', 'Votre participation est libérée.');
    }

    public function remove(Request $request, Mission $mission, MissionAssignment $assignment, MissionAssignmentService $assignments): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $assignments->remove($mission, $identity->reference, $assignment, $data['reason']);

        return back()->with('status', 'La participation est retirée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
