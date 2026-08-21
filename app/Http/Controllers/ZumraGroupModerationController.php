<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Moderation\ModerationDecisionService;
use App\Application\Moderation\ModerationReportService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ModerationDecision;
use App\Models\ModerationReport;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MODERATION-COMP-001 — autorité niveau 2 (art. 19 : « responsables, pour les espaces internes de la
 * ZUMRA »). Réutilise ZumraGroupService::isLeader() par l'intermédiaire des services applicatifs —
 * aucune matrice d'autorisation nouvelle. Jamais de signalement escaladé ici (ModerationReportService
 * exclut déjà ceux-là de forZumraLeader()).
 */
final class ZumraGroupModerationController
{
    public function index(Request $request, ZumraGroup $group, ModerationReportService $reports): JsonResponse
    {
        $presented = $reports->forZumraLeader($group)->map(fn (ModerationReport $report): array => $reports->presentForZumraLeader($report))->values();

        return response()->json(['reports' => $presented]);
    }

    public function decide(Request $request, ZumraGroup $group, ModerationReport $report, ModerationDecisionService $decisions): RedirectResponse
    {
        $data = $this->validated($request);
        $decisions->decideAsZumraLeader($group, $report, $this->actor($request), $data['action_type'], $data['reason_details'] ?? null);

        return back()->with('status', 'Décision disciplinaire enregistrée pour cette ZUMRA.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'action_type' => ['required', 'string', Rule::in(ModerationDecision::ACTION_TYPES)],
            'reason_details' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
