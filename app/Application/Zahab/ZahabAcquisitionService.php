<?php

declare(strict_types=1);

namespace App\Application\Zahab;

use App\Application\Ledger\LedgerService;
use App\Infrastructure\Payments\GeniusPayClient;
use App\Models\ZahabAcquisition;
use App\Models\ZahabWallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ZAHAB-002 — acquisition de ZAHAB par un paiement externe GeniusPay confirmé (art. 1-3 du
 * mandat) : `FCFA → GeniusPay → confirmation serveur → CREDIT Ledger → Wallet ZAHAB Personne`.
 * Réutilise `GeniusPayClient::createContributionPayment()`/`payment()` (déjà génériques, à montant
 * variable — jamais un deuxième client GeniusPay) et `ZahabWalletService::credit()` (déjà mergé,
 * jamais réimplémenté ici). Le retour navigateur seul ne crédite jamais : seule `reconcile()`,
 * server-to-server, peut créditer un Wallet.
 *
 * Parité actuelle : 1 ZAHAB = 1 FCFA (docs/architecture/ARCHITECTURE-PRODUIT-V2.md §15) — le
 * montant FCFA confirmé par GeniusPay EST le montant ZAHAB crédité, sans conversion.
 */
final class ZahabAcquisitionService
{
    public function __construct(
        private readonly GeniusPayClient $provider,
        private readonly ZahabWalletService $wallets,
        private readonly LedgerService $ledger,
    ) {}

    /** Une Personne choisit un montant et initie l'acquisition — jamais pour un tiers, jamais pour un autre sujet Wallet. */
    public function start(string $actor, int $amount, string $successUrl, string $errorUrl, ?string $returnTokenHash = null): ZahabAcquisition
    {
        abort_if($amount <= 0, 422, 'Le montant doit être un entier strictement positif.');

        $currency = ZahabWalletService::CURRENCY;
        $environment = (string) config('payments.geniuspay.environment');

        $remote = $this->provider->createContributionPayment(
            $amount,
            $currency,
            'Acquisition ZAHAB',
            ['acquisition_type' => 'zahab', 'person_reference' => $actor],
            $successUrl,
            $errorUrl,
        );
        if ($remote['amount'] !== $amount || $remote['environment'] !== $environment || ! $remote['checkout_url']
            || parse_url((string) $remote['checkout_url'], PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('ZAHAB_ACQUISITION_CREATION_MISMATCH');
        }

        return ZahabAcquisition::query()->create([
            'person_core_reference' => $actor,
            'provider' => 'GENIUSPAY',
            'reference' => $remote['reference'],
            'provider_id' => $remote['provider_id'],
            'amount' => $amount,
            'currency' => $currency,
            'environment' => $environment,
            'status' => $remote['status'],
            'checkout_url' => $remote['checkout_url'],
            'return_token_hash' => $returnTokenHash,
            'provider_snapshot' => $remote['snapshot'],
            'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
        ]);
    }

    /**
     * Réconciliation serveur-à-serveur — jamais le retour navigateur (art. 3/8 du mandat). Idempotent :
     * rejouée 2, 5 ou 20 fois pour la même acquisition COMPLETED, ne crédite jamais deux fois — la
     * clé de mouvement Wallet est déterministe (`zahab-acquisition:{id}:wallet-credit`), et
     * `ZahabWalletService::credit()` porte elle-même cette idempotence, jamais réimplémentée ici.
     */
    public function reconcile(ZahabAcquisition $acquisition): ZahabAcquisition
    {
        $remote = $this->provider->payment($acquisition->reference);
        if ($remote['amount'] !== $acquisition->amount || $acquisition->currency !== ZahabWalletService::CURRENCY
            || $remote['environment'] !== $acquisition->environment) {
            throw new RuntimeException('ZAHAB_ACQUISITION_RECONCILIATION_MISMATCH');
        }

        return DB::transaction(function () use ($acquisition, $remote): ZahabAcquisition {
            $locked = ZahabAcquisition::query()->whereKey($acquisition->id)->lockForUpdate()->firstOrFail();
            $wasAlreadyFinal = in_array($locked->status, [ZahabAcquisition::STATUS_COMPLETED, ZahabAcquisition::STATUS_FAILED, ZahabAcquisition::STATUS_CANCELLED], true);

            $locked->fill([
                'status' => $remote['status'],
                'fees' => $remote['fees'],
                'net_amount' => $remote['net_amount'],
                'provider_snapshot' => $remote['snapshot'],
                'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
                'last_verified_at' => now(),
                'completed_at' => $remote['status'] === ZahabAcquisition::STATUS_COMPLETED ? ($locked->completed_at ?? now()) : $locked->completed_at,
            ])->save();

            // Même garde-fou sandbox que CAP-061/CAP-007B, jamais dupliqué sous un nouveau nom :
            // seul `live` finalise toujours ; `sandbox` ne finalise que si l'interrupteur dédié est
            // explicitement ouvert.
            $canCredit = $locked->environment === 'live'
                || ($locked->environment === 'sandbox' && (bool) config('payments.geniuspay.sandbox_activation_allowed'));

            if (! $wasAlreadyFinal && $canCredit && $remote['status'] === ZahabAcquisition::STATUS_COMPLETED) {
                $wallet = $this->wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $locked->person_core_reference, $locked->person_core_reference);
                $operationReference = 'zahab-acquisition:'.$locked->id;
                $this->wallets->credit(
                    $wallet,
                    $locked->amount,
                    ZahabWalletService::REASON_ZAHAB_ACQUISITION,
                    $operationReference.':wallet-credit',
                    $locked->person_core_reference,
                    $operationReference,
                );
                $locked->update(['credited_at' => $locked->credited_at ?? now()]);
            }

            return $locked->refresh();
        }, 3);
    }

    private function snapshotHash(array $snapshot): string
    {
        ksort($snapshot);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
