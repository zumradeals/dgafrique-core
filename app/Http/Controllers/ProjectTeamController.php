<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectTeamService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProjectTeamController
{
    public function requestToJoin(Request $request, Project $project, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['motivation' => ['nullable', 'string', 'max:800']]);
        $service->requestToJoin($project, $identity->reference, $data['motivation'] ?? null);

        return back()->with('status', 'Votre demande a été transmise au porteur du projet. Vous n’êtes pas encore dans l’équipe.');
    }

    public function invite(Request $request, Project $project, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['person_reference' => ['required', 'uuid']]);
        $profile = PersonProfile::query()->where('discovery_reference', $data['person_reference'])->where('discovery_consent', true)->firstOrFail();
        $service->invite($project, $identity->reference, $profile->core_identity_reference);

        return back()->with('status', 'Invitation envoyée. La personne ne rejoindra l’équipe qu’après acceptation.');
    }

    public function acceptInvitation(Request $request, Project $project, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->acceptInvitation($project, $identity->reference);

        return back()->with('status', 'Invitation acceptée. Vous faites maintenant partie de l’équipe.');
    }

    public function approveRequest(Request $request, Project $project, string $member, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->approveRequest($project, $identity->reference, $member);

        return back()->with('status', 'La demande est approuvée et la personne a rejoint l’équipe.');
    }

    public function leave(Request $request, Project $project, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->leave($project, $identity->reference);

        return back()->with('status', 'Vous avez quitté l’équipe de ce projet.');
    }

    public function remove(Request $request, Project $project, string $member, ProjectTeamService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:800']]);
        $service->remove($project, $identity->reference, $member, $data['reason']);

        return back()->with('status', 'La personne a été retirée de l’équipe.');
    }
}
