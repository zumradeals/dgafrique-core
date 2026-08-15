<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectAccompanimentConfiguration;
use App\Application\Projects\ProjectAccompanimentService;
use App\Application\Projects\ProjectService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProjectAccompanimentController
{
    public function show(
        Request $request,
        Project $project,
        ProjectService $projects,
        ProjectAccompanimentConfiguration $configuration
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        abort_unless($projects->canDecide($project, $identity->reference), 403);

        $project->load(['accompaniment.actions']);

        return view('projects.accompaniment', [
            'project' => $project,
            'accompaniment' => $project->accompaniment,
            'configuration' => $configuration->get(),
        ]);
    }

    public function activate(
        Request $request,
        Project $project,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $service->activate($project, $identity->reference);

        return redirect()
            ->route('projects.accompaniment.show', $project)
            ->with('status', 'Accompagnement activé. Le projet conserve intégralement son portage et son pouvoir de décision.');
    }

    public function end(
        Request $request,
        Project $project,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $service->end($project, $identity->reference);

        return redirect()
            ->route('projects.accompaniment.show', $project)
            ->with('status', 'Accompagnement terminé. Son historique reste traçable.');
    }
}
