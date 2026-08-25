<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\LedgerEntry;
use App\Models\ZahabAcquisition;
use App\Models\ZumraPayment;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — supervision Finance, STRICTEMENT en lecture. Aucune méthode ici n'écrit
 * jamais dans dg_ledger_entries, dg_zahab_wallets, dg_zahab_acquisitions, dg_contribution_payments
 * ni dg_zumra_payments — voir le rapport de livraison, garantie Finance/Ledger.
 */
final class AdminFinanceController
{
    public function index(): View
    {
        $massZahab = (int) LedgerEntry::query()->whereNotNull('wallet_id')->where('direction', LedgerEntry::DIRECTION_CREDIT)->sum('amount')
            - (int) LedgerEntry::query()->whereNotNull('wallet_id')->where('direction', LedgerEntry::DIRECTION_DEBIT)->sum('amount');

        return view('administration.finance.index', [
            'massZahab' => $massZahab,
            'byPurpose' => LedgerEntry::query()->selectRaw('purpose_code, count(*) as total, sum(amount) as volume')->groupBy('purpose_code')->orderByDesc('volume')->get(),
            'acquisitionsByStatus' => ZahabAcquisition::query()->selectRaw('status, count(*) as total, sum(amount) as volume')->groupBy('status')->get()->keyBy('status'),
            'contributionPaymentsByStatus' => ContributionPayment::query()->selectRaw('status, count(*) as total, sum(amount) as volume')->groupBy('status')->get()->keyBy('status'),
            'membershipPaymentsByStatus' => ZumraPayment::query()->selectRaw('status, count(*) as total, sum(amount) as volume')->groupBy('status')->get()->keyBy('status'),
            'ledgerEntriesTotal' => LedgerEntry::query()->count(),
        ]);
    }

    public function acquisitions(Request $request): View
    {
        $query = ZahabAcquisition::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('person_core_reference')) {
            $query->where('person_core_reference', 'like', '%'.$request->string('person_core_reference').'%');
        }

        return view('administration.finance.acquisitions', [
            'acquisitions' => $query->orderByDesc('created_at')->paginate(30)->withQueryString(),
            'filters' => $request->only(['status', 'person_core_reference']),
            'byStatus' => ZahabAcquisition::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function contributions(Request $request): View
    {
        $paymentsQuery = ContributionPayment::query();
        if ($request->filled('status')) {
            $paymentsQuery->where('status', $request->string('status'));
        }
        $payments = $paymentsQuery->orderByDesc('created_at')->paginate(20, ['*'], 'payments')->withQueryString();

        $membershipsQuery = ZumraPayment::query();
        if ($request->filled('membership_status')) {
            $membershipsQuery->where('status', $request->string('membership_status'));
        }
        $membershipPayments = $membershipsQuery->orderByDesc('created_at')->paginate(20, ['*'], 'adhesions')->withQueryString();
        $memberships = ZumraProgramMembership::query()->whereIn('id', $membershipPayments->pluck('membership_id'))->get()->keyBy('id');

        return view('administration.finance.contributions', [
            'payments' => $payments,
            'membershipPayments' => $membershipPayments,
            'memberships' => $memberships,
            'statusFilter' => $request->string('status')->toString(),
            'membershipStatusFilter' => $request->string('membership_status')->toString(),
            'contributionsActive' => Contribution::query()->where('status', Contribution::STATUS_ACTIVE)->count(),
            'byStatus' => ContributionPayment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
