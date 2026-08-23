<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Models\ZahabWallet;
use Illuminate\Http\JsonResponse;

/**
 * ZAHAB-001, art. 23 du mandat — capacité backend minimale : consultation globale des Wallets
 * uniquement, jamais un bouton « ajouter du ZAHAB » ni aucune autre écriture manuelle ici.
 */
final class WalletController
{
    public function index(): JsonResponse
    {
        $wallets = ZahabWallet::query()->orderByDesc('created_at')->paginate(50);

        return response()->json($wallets);
    }
}
