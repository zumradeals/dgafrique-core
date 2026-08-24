<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Organizations\OrganizationService;
use App\Application\Zahab\ZahabWalletService;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\LedgerEntry;
use App\Models\Organization;
use App\Models\PortalAdministrator;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ZAHAB-001 — surface de lecture uniquement (art. 28 du mandat : « pas de grande maquette Wallet »).
 * Aucune écriture n'est jamais déclenchée par une requête HTTP ici : `ZahabWalletService::credit()`/
 * `debit()` restent des API internes, appelées uniquement par du futur code métier de confiance
 * (ZAHAB-002/CONTRIBUTION-ZAHAB-001). Même patron d'autorisation que `LedgerController` : une
 * personne ne voit que son propre Wallet, un responsable ZUMRA (`isLeader()`) que celui de sa
 * ZUMRA, un gestionnaire d'Organisation (`isManager()`) que celui de son Organisation.
 */
final class WalletController
{
    public function person(Request $request, ZahabWalletService $wallets): JsonResponse
    {
        $actor = $this->actor($request);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $actor, $actor);

        return $this->present($wallet, $wallets);
    }

    /**
     * ZAHAB-002, art. 8 du mandat — surface minimale, jamais un redesign : solde, montant à
     * acquérir, bouton, historique. L'historique EST la preuve du crédit (« ne jamais afficher
     * "ZAHAB acquis" simplement parce que l'utilisateur revient de la page GeniusPay ») : cette
     * page ne montre jamais un état optimiste, uniquement ce que `reconcile()` a réellement acté.
     */
    public function dashboard(Request $request, ZahabWalletService $wallets): View
    {
        $actor = $this->actor($request);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $actor, $actor);
        $movements = LedgerEntry::query()->where('wallet_id', $wallet->id)->orderByDesc('occurred_at')->get();
        $acquisitions = ZahabAcquisition::query()->where('person_core_reference', $actor)->orderByDesc('created_at')->get();
        $isAdministrator = PortalAdministrator::query()->whereKey($actor)->exists();

        return view('wallet.dashboard', [
            'identity' => $request->attributes->get('dg_identity'),
            'isAdministrator' => $isAdministrator,
            'balance' => $wallets->balance($wallet),
            'movements' => $movements,
            'acquisitions' => $acquisitions,
        ]);
    }

    public function zumraGroup(Request $request, ZumraGroup $group, ZumraGroupService $zumraGroups, ZahabWalletService $wallets): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($zumraGroups->isLeader($group, $actor), 404);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, $group->id, $actor);

        return $this->present($wallet, $wallets);
    }

    public function organization(Request $request, Organization $organization, OrganizationService $organizations, ZahabWalletService $wallets): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($organizations->isManager($organization, $actor), 404);
        $wallet = $wallets->walletFor(ZahabWallet::SUBJECT_ORGANIZATION, $organization->id, $actor);

        return $this->present($wallet, $wallets);
    }

    private function present(ZahabWallet $wallet, ZahabWalletService $wallets): JsonResponse
    {
        $movements = LedgerEntry::query()->where('wallet_id', $wallet->id)->orderByDesc('occurred_at')->get();

        return response()->json([
            'wallet' => ['id' => $wallet->id, 'subject_type' => $wallet->subject_type, 'currency' => ZahabWalletService::CURRENCY],
            'balance' => $wallets->balance($wallet),
            'movements' => $movements->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'direction' => $entry->direction,
                'amount' => $entry->amount,
                'purpose_code' => $entry->purpose_code,
                'occurred_at' => $entry->occurred_at,
            ])->values(),
        ]);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
