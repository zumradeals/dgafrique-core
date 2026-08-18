<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectAccompanimentConfiguration;
use App\Application\Projects\ProjectAccompanimentService;
use App\Application\Projects\ProjectService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
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

        $project->load(['accompaniment.actions', 'accompaniment.requests']);
        $allActions = $project->accompaniment?->actions ?? collect();

        // CAP-046 : synthèse honnête (comptes réels), jamais un score — et filtres appliqués à la
        // même chronologie sous-jacente, sans état caché (paramètres en GET).
        $typeCounts = $allActions->countBy('action_type');
        $sourceCounts = $allActions->countBy('delivery_source');
        $availablePartners = $allActions->pluck('provider_label')->unique()->sort()->values();

        $filteredActions = $allActions
            ->when($request->filled('type'), fn ($actions) => $actions->where('action_type', $request->query('type')))
            ->when($request->filled('partenaire'), fn ($actions) => $actions->where('provider_label', $request->query('partenaire')))
            ->values();

        return view('projects.accompaniment', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'project' => $project,
            'accompaniment' => $project->accompaniment,
            'requests' => $project->accompaniment?->requests->sortByDesc('requested_at')->values() ?? collect(),
            'actions' => $filteredActions,
            'typeCounts' => $typeCounts,
            'sourceCounts' => $sourceCounts,
            'availablePartners' => $availablePartners,
            'selectedType' => $request->query('type'),
            'selectedPartner' => $request->query('partenaire'),
            'configuration' => $configuration->get(),
        ]);
    }

    public function storeRequest(
        Request $request,
        Project $project,
        ProjectAccompanimentService $service
    ): RedirectResponse {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:2400'],
        ]);

        $service->request($project, $identity->reference, $data['subject'], $data['description']);

        return redirect()
            ->route('projects.accompaniment.show', $project)
            ->with('status', 'Votre demande a été transmise à DG Afrique. Elle sera traitée dans l’ordre d’arrivée.');
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
