<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Moderation\ModerationReportService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ContextComment;
use App\Models\MessageEntry;
use App\Models\ModerationReport;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MODERATION-COMP-001 — surface membre minimale. Cibles V1 : CONTEXT_COMMENT, MESSAGE_ENTRY,
 * ZUMRA_MEMBERSHIP (art. 13/19). Aucune vue dédiée : JSON pour la lecture, redirection `back()` pour
 * l'écriture, même patron que ProjectFundingController (CAP-063), le précédent le plus récent.
 */
final class ModerationReportController
{
    public function storeContextComment(Request $request, ContextComment $comment, ModerationReportService $reports): RedirectResponse
    {
        $data = $this->validated($request);
        $reports->reportContextComment($comment, $this->actor($request), $data['reason_code'], $data['reason_details'] ?? null);

        return back()->with('status', 'Signalement transmis. Il reste confidentiel vis-à-vis de la personne concernée.');
    }

    public function storeMessageEntry(Request $request, MessageEntry $entry, ModerationReportService $reports): RedirectResponse
    {
        $data = $this->validated($request);
        $reports->reportMessageEntry($entry, $this->actor($request), $data['reason_code'], $data['reason_details'] ?? null);

        return back()->with('status', 'Signalement transmis. Il reste confidentiel vis-à-vis de la personne concernée.');
    }

    public function storeZumraMembership(Request $request, ZumraGroupMembership $membership, ModerationReportService $reports): RedirectResponse
    {
        $data = $this->validated($request);
        $reports->reportZumraMembership($membership, $this->actor($request), $data['reason_code'], $data['reason_details'] ?? null);

        return back()->with('status', 'Signalement transmis. Il reste confidentiel vis-à-vis de la personne concernée.');
    }

    public function mine(Request $request, ModerationReportService $reports): JsonResponse
    {
        return response()->json(['reports' => $reports->myReports($this->actor($request))->values()]);
    }

    public function escalate(Request $request, ModerationReport $report, ModerationReportService $reports): RedirectResponse
    {
        $reports->escalate($report, $this->actor($request));

        return back()->with('status', 'Ce signalement est transmis directement à DG Afrique.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'reason_code' => ['required', 'string', Rule::in(ModerationReport::REASON_CODES)],
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
