<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Projects\ProjectFundingService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Project;
use App\Models\ProjectFunding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CAP-063 — déclaration strictement déclarative, INCHANGÉE (`ProjectFundingService`) : aucune
 * route de paiement, aucune redirection de checkout, aucun retour de réconciliation. `show()`
 * reste soumis à `canView()` du Projet — une déclaration financière n'expose jamais un Projet
 * PRIVATE au-delà de sa visibilité normale.
 *
 * PROJECT-FUNDING-002 — `contribute()` est le SEUL point d'entrée qui déplace réellement du
 * ZAHAB (via `ProjectFundingContributionService`, jamais un second système financier) : les
 * champs `collected`/`remaining` ajoutés à `show()` sont purement additifs, jamais un retrait
 * de champ existant.
 */
final class ProjectFundingController
{
    public function show(Request $request, Project $project, ProjectFundingService $service, ProjectFundingContributionService $contributions): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($service->canView($project, $actor), 404);
        $funding = ProjectFunding::query()->where('project_id', $project->id)->latest('created_at')->first();

        return response()->json(['funding' => $funding ? $this->present($funding, $project, $contributions) : null]);
    }

    public function contribute(Request $request, Project $project, ProjectFundingService $service, ProjectFundingContributionService $contributions): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_unless($service->canView($project, $actor), 404);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'contribution_token' => ['required', 'string', 'max:80'],
        ]);
        $funding = ProjectFunding::query()->where('project_id', $project->id)->where('status', ProjectFunding::STATUS_OPEN)->firstOrFail();

        $contributions->contribute($funding, $project, $actor, (int) $data['amount'], $data['contribution_token']);

        return back()->with('status', 'Contribution ZAHAB enregistrée. Le Ledger est la preuve du mouvement.');
    }

    public function store(Request $request, Project $project, ProjectFundingService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $service->create($project, $this->actor($request), $data);

        return back()->with('status', 'Besoin financier déclaré. Aucun paiement n’est ouvert par cette déclaration.');
    }

    public function update(Request $request, Project $project, ProjectFundingService $service): RedirectResponse
    {
        $funding = ProjectFunding::query()->where('project_id', $project->id)->where('status', ProjectFunding::STATUS_OPEN)->firstOrFail();
        $data = $this->validated($request);
        $service->update($funding, $project, $this->actor($request), $data);

        return back()->with('status', 'Déclaration financière mise à jour.');
    }

    public function close(Request $request, Project $project, ProjectFundingService $service): RedirectResponse
    {
        $funding = ProjectFunding::query()->where('project_id', $project->id)->where('status', ProjectFunding::STATUS_OPEN)->firstOrFail();
        $data = $request->validate(['note' => ['nullable', 'string', 'max:800']]);
        $service->close($funding, $project, $this->actor($request), $data['note'] ?? null);

        return back()->with('status', 'Déclaration financière clôturée.');
    }

    public function cancel(Request $request, Project $project, ProjectFundingService $service): RedirectResponse
    {
        $funding = ProjectFunding::query()->where('project_id', $project->id)->where('status', ProjectFunding::STATUS_OPEN)->firstOrFail();
        $data = $request->validate(['note' => ['nullable', 'string', 'max:800']]);
        $service->cancel($funding, $project, $this->actor($request), $data['note'] ?? null);

        return back()->with('status', 'Déclaration financière annulée.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'target_amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'currency' => ['required', 'string', 'size:3'],
            'purpose' => ['required', 'string', 'min:10', 'max:2000'],
            'intended_use' => ['required', 'string', 'min:10', 'max:2000'],
            'conditions' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }

    private function present(ProjectFunding $funding, Project $project, ProjectFundingContributionService $contributions): array
    {
        $collected = $contributions->collectedAmount($funding, $project);

        return [
            'status' => $funding->status,
            'target_amount' => $funding->target_amount,
            'currency' => $funding->currency,
            'purpose' => $funding->purpose,
            'intended_use' => $funding->intended_use,
            'conditions' => $funding->conditions,
            'opened_at' => $funding->opened_at,
            'closed_at' => $funding->closed_at,
            'cancelled_at' => $funding->cancelled_at,
            // PROJECT-FUNDING-002 — dérivé du Ledger, jamais un compteur stocké sur ProjectFunding.
            'collected_amount' => $collected,
            'remaining_amount' => max(0, $funding->target_amount - $collected),
        ];
    }
}
