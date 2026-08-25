<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CAP-062 — audit administratif : lecture globale uniquement, aucune écriture manuelle possible
 * ici (voir tests/Feature/ZahabWalletTest.php::test_no_controller_exposes_a_public_credit_or_debit_action —
 * ce contrôleur ne doit jamais exposer credit/debit/reverse/store/update). ADMIN-CONTROL-002 y
 * ajoute des filtres d'investigation (raison métier, sens, période) — jamais une nouvelle écriture.
 */
final class LedgerController
{
    public function index(Request $request): View
    {
        $query = LedgerEntry::query();
        if ($request->filled('purpose_code')) {
            $query->where('purpose_code', $request->string('purpose_code'));
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->string('direction'));
        }
        if ($request->filled('subject_reference')) {
            $query->where('subject_reference', $request->string('subject_reference'));
        }
        if ($request->filled('from')) {
            $query->where('occurred_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('occurred_at', '<=', $request->date('to'));
        }

        return view('administration.finance.ledger', [
            'entries' => $query->orderByDesc('occurred_at')->paginate(40)->withQueryString(),
            'purposeCodes' => LedgerEntry::query()->select('purpose_code')->distinct()->whereNotNull('purpose_code')->orderBy('purpose_code')->pluck('purpose_code'),
            'filters' => $request->only(['purpose_code', 'direction', 'subject_reference', 'from', 'to']),
        ]);
    }
}
