<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\ProjectBrain\ProjectBrainNeedDraftService;
use App\Application\Projects\ProjectService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Need;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectBrainDraft;
use App\Models\ProjectBrainMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProjectBrainController
{
    public function show(Request $request, Project $project, ProjectService $projects, NeedService $needs, NeedConfiguration $needConfiguration, ProjectBrainNeedDraftService $brain): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($projects->canView($project, $identity->reference), 404);

        $visibleProjects = Project::query()->where('status', '!=', Project::STATUS_ARCHIVED)->latest()->limit(100)->get()
            ->filter(fn (Project $candidate): bool => $projects->canView($candidate, $identity->reference))->values();
        $projectNeeds = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)
            ->where('status', '!=', Need::STATUS_ARCHIVED)->latest()->limit(20)->get()
            ->filter(fn (Need $need): bool => $needs->canView($need, $identity->reference))->values();
        $conversation = $brain->conversation($project, $identity->reference);
        $messages = ProjectBrainMessage::query()->where('conversation_id', $conversation->id)->latest()->limit(30)->get()->reverse()->values();
        $drafts = ProjectBrainDraft::query()->where('conversation_id', $conversation->id)->where('status', ProjectBrainDraft::STATUS_PENDING)->latest()->get()->keyBy('id');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.brain', compact('identity', 'project', 'visibleProjects', 'projectNeeds', 'conversation', 'messages', 'drafts', 'isAdministrator') + [
            'needConfiguration' => $needConfiguration->get(),
        ]);
    }

    public function prepareNeed(Request $request, Project $project, ProjectService $projects, ProjectBrainNeedDraftService $brain): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($projects->canView($project, $identity->reference), 404);
        $data = $request->validate(['message' => ['required', 'string', 'min:8', 'max:3000']]);
        $brain->prepare($project, $identity->reference, $data['message']);

        return redirect()->route('projects.brain.show', $project)->with('status', 'Brouillon préparé. Rien n’a encore été créé dans le Core.');
    }

    public function confirmNeed(Request $request, Project $project, ProjectBrainDraft $draft, ProjectService $projects, ProjectBrainNeedDraftService $brain): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($projects->canView($project, $identity->reference), 404);
        $need = $brain->confirm($project, $draft, $identity->reference);

        return redirect()->route('projects.brain.show', $project)->with('status', 'Besoin créé dans le Core : '.$need->title);
    }

    public function cancelDraft(Request $request, Project $project, ProjectBrainDraft $draft, ProjectService $projects, ProjectBrainNeedDraftService $brain): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($projects->canView($project, $identity->reference), 404);
        $brain->cancel($project, $draft, $identity->reference);

        return redirect()->route('projects.brain.show', $project)->with('status', 'Brouillon abandonné. Aucune mutation métier.');
    }
}
