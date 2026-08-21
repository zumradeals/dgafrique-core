<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\LedgerEntry;
use Illuminate\Http\JsonResponse;

/** CAP-062 — audit administratif : lecture globale uniquement, aucune écriture manuelle possible ici. */
final class LedgerController
{
    public function index(): JsonResponse
    {
        $entries = LedgerEntry::query()->orderByDesc('occurred_at')->paginate(50);

        return response()->json($entries);
    }
}
