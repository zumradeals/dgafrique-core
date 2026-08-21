<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Moderation\ModerationDecisionService;
use App\Application\Moderation\ModerationReportService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ModerationDecision;
use App\Models\ModerationReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MODERATION-COMP-001 — autorité niveau 3 (art. 19 : « DG Afrique ou GAMAD, pour la Charte générale
 * et les risques transversaux »). `PortalAdministrator` reste la seule autorité niveau 3 réellement
 * disponible (aucune simulation d'une API GAMAD Core inexistante). Le middleware `portal.admin` du
 * groupe de routes gate l'accès ; ModerationDecisionService revérifie en défense en profondeur.
 * Voit TOUT, y compris ce qu'une ZUMRA n'a jamais eu le droit de voir (aucune interception possible).
 */
final class ModerationController
{
    public function index(Request $request, ModerationReportService $reports): JsonResponse
    {
        $presented = $reports->forAdministrator()->map(fn (ModerationReport $report): array => $reports->presentForAdministrator($report))->values();

        return response()->json(['reports' => $presented]);
    }

    public function decide(Request $request, ModerationReport $report, ModerationDecisionService $decisions): RedirectResponse
    {
        $data = $this->validatedDecision($request);
        $decisions->decideAsAdministrator($report, $this->actor($request), $data['action_type'], $data['reason_details'] ?? null);

        return back()->with('status', 'Décision disciplinaire enregistrée par DG Afrique.');
    }

    public function decideAppeal(Request $request, ModerationDecision $decision, ModerationDecisionService $decisions): RedirectResponse
    {
        $data = $this->validatedAppeal($request);
        $decisions->decideAppeal($decision, $this->actor($request), $data['outcome'], $data['explanation'] ?? null);

        return back()->with('status', 'Recours tranché par DG Afrique.');
    }

    private function validatedDecision(Request $request): array
    {
        return $request->validate([
            'action_type' => ['required', 'string', Rule::in(ModerationDecision::ACTION_TYPES)],
            'reason_details' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validatedAppeal(Request $request): array
    {
        return $request->validate([
            'outcome' => ['required', 'string', Rule::in([
                ModerationDecision::APPEAL_OUTCOME_CONFIRMED,
                ModerationDecision::APPEAL_OUTCOME_MODIFIED,
                ModerationDecision::APPEAL_OUTCOME_LIFTED,
            ])],
            'explanation' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
