<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Comments\ContextCommentService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ContextComment;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\Proof;
use App\Models\Transmission;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ContextCommentController
{
    public function need(Request $request, Need $need, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->needThread($need, $this->identity($request)->reference));
    }

    public function storeNeed(Request $request, Need $need, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addNeed($need, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.need', $need)->with('status', 'Contribution ajoutée à ce besoin.');
    }

    public function project(Request $request, Project $project, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->projectThread($project, $this->identity($request)->reference));
    }

    public function storeProject(Request $request, Project $project, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addProject($project, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.project', $project)->with('status', 'Contribution ajoutée à ce projet.');
    }

    public function zumraActivity(Request $request, ZumraGroup $group, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->zumraActivityThread($group, $this->identity($request)->reference));
    }

    public function storeZumraActivity(Request $request, ZumraGroup $group, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addZumraActivity($group, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.zumra-activity', $group)->with('status', 'Contribution ajoutée à cette activité ZUMRA.');
    }

    public function mission(Request $request, Mission $mission, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->missionThread($mission, $this->identity($request)->reference));
    }

    public function storeMission(Request $request, Mission $mission, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addMission($mission, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.mission', $mission)->with('status', 'Contribution ajoutée à cette Mission.');
    }

    public function transmission(Request $request, Transmission $transmission, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->transmissionThread($transmission, $this->identity($request)->reference));
    }

    public function storeTransmission(Request $request, Transmission $transmission, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addTransmission($transmission, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.transmission', $transmission)->with('status', 'Contribution ajoutée à cette Transmission.');
    }

    public function proof(Request $request, Proof $proof, ContextCommentService $comments): View
    {
        return view('comments.context', $comments->proofThread($proof, $this->identity($request)->reference));
    }

    public function storeProof(Request $request, Proof $proof, ContextCommentService $comments): RedirectResponse
    {
        $data = $this->validated($request);
        $comments->addProof($proof, $this->identity($request)->reference, $data['purpose'], $data['body']);

        return redirect()->route('comments.proof', $proof)->with('status', 'Contribution ajoutée à cette preuve.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'purpose' => ['required', 'string', Rule::in(array_keys(ContextComment::PURPOSES))],
            'body' => ['required', 'string', 'min:2', 'max:'.ContextCommentService::MAX_BODY_LENGTH],
        ]);
    }

    private function identity(Request $request): CoreIdentity
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity;
    }
}
