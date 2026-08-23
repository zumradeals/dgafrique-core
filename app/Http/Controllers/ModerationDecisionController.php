<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Moderation\ModerationDecisionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ModerationDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * MODERATION-COMP-001 — surface membre : consultation de ses propres décisions (jamais le
 * reporter_core_reference, structurellement absent de ModerationDecision) et recours (art. 19,
 * non suspensif).
 */
final class ModerationDecisionController
{
    public function mine(Request $request, ModerationDecisionService $decisions): JsonResponse
    {
        $presented = $decisions->myDecisions($this->actor($request))
            ->map(fn (ModerationDecision $decision): array => $decisions->presentForSubject($decision))
            ->values();

        return response()->json(['decisions' => $presented]);
    }

    public function requestAppeal(Request $request, ModerationDecision $decision, ModerationDecisionService $decisions): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $decisions->requestAppeal($decision, $this->actor($request), $data['reason']);

        return back()->with('status', 'Recours transmis. La décision reste en vigueur pendant son examen.');
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
