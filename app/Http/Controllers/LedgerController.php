<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Contribution;
use App\Models\LedgerEntry;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CAP-062 — surface de lecture uniquement. Aucune écriture ne naît jamais d'une requête HTTP :
 * elles naissent uniquement de `LedgerService`, appelé par les domaines financiers eux-mêmes après
 * confirmation réelle. Jamais public : une personne ne voit que ses propres écritures, un
 * responsable ZUMRA (`isLeader()`, l'autorité déjà établie par CAP-061) ne voit que celles de sa
 * ZUMRA.
 */
final class LedgerController
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $leadGroupIds = ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');

        $entries = LedgerEntry::query()
            ->where(function ($query) use ($actor, $leadGroupIds): void {
                $query->where(fn ($q) => $q->where('subject_type', Contribution::SUBJECT_PERSON)->where('subject_reference', $actor))
                    ->orWhere(fn ($q) => $q->where('subject_type', Contribution::SUBJECT_ZUMRA_GROUP)->whereIn('subject_reference', $leadGroupIds));
            })
            ->orderByDesc('occurred_at')
            ->get();

        return response()->json(['entries' => $entries->map(fn (LedgerEntry $entry): array => $this->present($entry))->values()]);
    }

    public function show(Request $request, LedgerEntry $entry, ZumraGroupService $zumraGroups): JsonResponse
    {
        $actor = $this->actor($request);
        if ($entry->subject_type === Contribution::SUBJECT_PERSON) {
            abort_unless(hash_equals((string) $entry->subject_reference, $actor), 404);
        } else {
            $group = ZumraGroup::query()->findOrFail($entry->subject_reference);
            abort_unless($zumraGroups->isLeader($group, $actor), 404);
        }

        return response()->json($this->present($entry));
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }

    private function present(LedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'source_type' => $entry->source_type,
            'entry_type' => $entry->entry_type,
            'amount' => $entry->amount,
            'currency' => $entry->currency,
            'purpose_code' => $entry->purpose_code,
            'period' => $entry->period,
            'subject_type' => $entry->subject_type,
            'occurred_at' => $entry->occurred_at,
        ];
    }
}
