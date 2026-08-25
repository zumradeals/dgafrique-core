<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Zahab\ZahabWalletService;
use App\Models\ZahabWallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ZAHAB-001, art. 23 du mandat — capacité backend minimale : consultation globale des Wallets
 * uniquement, jamais un bouton « ajouter du ZAHAB » ni aucune autre écriture manuelle ici (voir
 * tests/Feature/ZahabWalletTest.php::test_no_controller_exposes_a_public_credit_or_debit_action et
 * ZahabAcquisitionTest.php::test_no_generic_wallet_mutation_route_was_added — ce contrôleur ne doit
 * jamais exposer credit/debit/store/update/walletCredit/walletDebit/walletTransfer).
 */
final class WalletController
{
    public function index(Request $request, ZahabWalletService $wallets): View
    {
        $query = ZahabWallet::query();
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type'));
        }
        if ($request->filled('subject_reference')) {
            $query->where('subject_reference', 'like', '%'.$request->string('subject_reference').'%');
        }

        $paginated = $query->orderByDesc('created_at')->paginate(30)->withQueryString();
        $paginated->getCollection()->transform(function (ZahabWallet $wallet) use ($wallets): ZahabWallet {
            $wallet->setAttribute('derived_balance', $wallets->balance($wallet));

            return $wallet;
        });

        return view('administration.finance.wallets', [
            'wallets' => $paginated,
            'filters' => $request->only(['subject_type', 'subject_reference']),
        ]);
    }
}
