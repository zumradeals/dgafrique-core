<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectAutonomyPathwayService;
use App\Application\Projects\ProjectService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProjectAutonomyController
{
    public function show(
        Request $request,
        Project $project,
        ProjectService $projects,
        ProjectAutonomyPathwayService $pathways
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        abort_unless($projects->canDecide($project, $identity->reference), 403);

        $project->load(['autonomyPathway', 'accompaniment']);

        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.autonomy', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'project' => $project,
            'pathway' => $project->autonomyPathway,
            'eligible' => $pathways->isEligible($project),
            'targetForms' => ProjectAutonomyPathwayService::TARGET_FORMS,
        ]);
    }

    public function open(
        Request $request,
        Project $project,
        ProjectAutonomyPathwayService $pathways
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'target_form' => ['required', Rule::in(array_keys(ProjectAutonomyPathwayService::TARGET_FORMS))],
            'other_form_label' => ['nullable', 'string', 'max:180'],
        ]);

        $pathways->open($project, $identity->reference, $data);

        return redirect()
            ->route('projects.autonomy.show', $project)
            ->with('status', 'Parcours d’autonomie ouvert. Il prépare une éventuelle structure distincte sans créer de statut juridique ni transférer la propriété du projet.');
    }

    public function close(
        Request $request,
        Project $project,
        ProjectAutonomyPathwayService $pathways
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $pathways->close($project, $identity->reference);

        return redirect()
            ->route('projects.autonomy.show', $project)
            ->with('status', 'Parcours d’autonomie fermé. Son historique reste attaché au projet.');
    }
}
