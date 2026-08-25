<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Administration\AdminJournalAggregator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — Journal V1 : agrège les journaux métier déjà existants
 * (AdminJournalAggregator), jamais une nouvelle table d'audit générique.
 */
final class AdminJournalController
{
    public function index(Request $request, AdminJournalAggregator $journal): View
    {
        $type = $request->filled('type') ? $request->string('type')->toString() : null;

        return view('administration.journal.index', [
            'entries' => $journal->paginated($type, max(1, (int) $request->integer('page', 1))),
            'typeFilter' => $type,
        ]);
    }
}
