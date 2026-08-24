<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Application\Ledger\LedgerService;
use App\Application\Zahab\ZahabWalletService;
use App\Infrastructure\Payments\GeniusPayClient;
use App\Models\ZahabWallet;
use App\Models\ZumraPayment;
use App\Models\ZumraPaymentReceipt;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProgramMembershipEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class MembershipPaymentService
{
    public function __construct(
        private readonly GeniusPayClient $provider,
        private readonly LedgerService $ledger,
        private readonly ZahabWalletService $wallets,
    ) {}

    public function start(ZumraProgramMembership $membership, string $successUrl, string $errorUrl): ZumraPayment
    {
        if ($membership->status !== ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
            throw new RuntimeException('MEMBERSHIP_NOT_PAYABLE');
        }
        $configuredEnvironment = (string) config('payments.geniuspay.environment');
        $remote = $this->provider->createMembershipPayment($membership->core_identity_reference, $successUrl, $errorUrl);
        if ($remote['amount'] !== 500 || $remote['environment'] !== $configuredEnvironment || ! $remote['checkout_url']
            || parse_url($remote['checkout_url'], PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('PAYMENT_CREATION_MISMATCH');
        }

        return ZumraPayment::query()->create([
            'membership_id' => $membership->id, 'provider' => 'GENIUSPAY', 'purpose' => ZumraPayment::PURPOSE_MEMBERSHIP,
            'reference' => $remote['reference'], 'provider_id' => $remote['provider_id'], 'amount' => 500, 'currency' => 'XOF',
            'environment' => $configuredEnvironment, 'status' => $remote['status'], 'checkout_url' => $remote['checkout_url'],
            'provider_snapshot' => $remote['snapshot'], 'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
        ]);
    }

    /**
     * ADHESION-ZAHAB-001 — même déclenchement que `start()` (adhésion `PENDING_PAYMENT`, seule la
     * personne concernée), réglé immédiatement par son Wallet ZAHAB au lieu d'un checkout GeniusPay
     * externe : aucune étape PENDING/PROCESSING, aucune réconciliation à part — le débit ZAHAB EST
     * la confirmation, synchrone et atomique. GeniusPay reste intact et inchangé (`start()`/
     * `reconcile()` ci-dessus) : ce sont désormais deux moyens de paiement distincts pour la même
     * adhésion, jamais fusionnés.
     *
     * CAP-007B/`ZumraProgramMembership` ne porte AUCUNE référence à une ZUMRA précise — l'adhésion
     * est au Programme, pas à une communauté — confirmé en lisant le modèle (aucune colonne
     * `zumra_group_id`) : aucun bénéficiaire Wallet ZUMRA ne peut donc être établi. V1 n'enregistre
     * qu'UN mouvement : le DEBIT du Wallet Personne. Aucun Wallet ZUMRA/DG Afrique fabriqué pour
     * équilibrer.
     *
     * `payments.membership.enabled` reste le SEUL interrupteur canonique gouvernant si un paiement
     * d'adhésion (quel que soit le moyen) est ouvert — le raccordement ZAHAB le respecte à
     * l'identique plutôt que de créer une deuxième bascule qui contournerait un admin ayant
     * délibérément maintenu l'adhésion payante fermée.
     *
     * `payments.membership.amount`/`currency` restent l'unique source canonique du montant —
     * jamais un `500` dupliqué en dur ici.
     */
    public function payWithZahabWallet(ZumraProgramMembership $membership, string $actor): ZumraPayment
    {
        abort_unless($membership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT, 409, 'Cette adhésion n’attend pas de paiement.');
        abort_unless(hash_equals($membership->core_identity_reference, $actor), 403, 'Seule la personne concernée peut payer sa propre adhésion.');
        abort_unless((bool) config('payments.membership.enabled'), 409, 'Le paiement d’adhésion n’est pas encore ouvert.');

        $amount = (int) config('payments.membership.amount');
        $currency = (string) config('payments.membership.currency');
        abort_unless($currency === ZahabWalletService::CURRENCY, 422, 'L’adhésion réglée en ZAHAB n’est possible qu’en XOF.');

        // Déterministe (une adhésion = un seul paiement possible, jamais périodique) : jamais un
        // UUID aléatoire. UNIQUE(reference) est le premier rempart anti-double-débit ; un rejeu
        // après succès percute cette contrainte avant même d'atteindre le Wallet.
        $reference = 'ZAHAB-MEMBERSHIP-'.$membership->id;
        $operationReference = 'zumra-membership:'.$membership->id;
        $movementKey = $operationReference.':payer-debit';

        try {
            return DB::transaction(function () use ($membership, $actor, $amount, $currency, $reference, $operationReference, $movementKey): ZumraPayment {
                $wallet = $this->wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $actor, $actor);

                $payment = ZumraPayment::query()->create([
                    'membership_id' => $membership->id,
                    'provider' => 'ZAHAB',
                    'purpose' => ZumraPayment::PURPOSE_MEMBERSHIP,
                    'reference' => $reference,
                    'provider_id' => null,
                    'amount' => $amount,
                    'currency' => $currency,
                    'environment' => 'zahab',
                    'status' => ZumraPayment::STATUS_COMPLETED,
                    'checkout_url' => null,
                    'completed_at' => now(),
                ]);

                $this->wallets->debit($wallet, $amount, ZahabWalletService::REASON_MEMBERSHIP_PAYMENT, $movementKey, $actor, $operationReference);

                $locked = ZumraProgramMembership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT, 409, 'Cette adhésion n’attend plus de paiement.');
                $locked->update(['status' => ZumraProgramMembership::STATUS_ACTIVE, 'activated_at' => now()]);
                ZumraProgramMembershipEvent::query()->create([
                    'membership_id' => $locked->id, 'event' => 'PAYMENT_CONFIRMED',
                    'from_status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT, 'to_status' => ZumraProgramMembership::STATUS_ACTIVE,
                    'actor_core_reference' => $actor, 'context' => ['payment_reference' => $payment->reference, 'provider' => 'ZAHAB'], 'occurred_at' => now(),
                ]);

                $this->issueReceipt($payment, $locked);

                return $payment;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Un paiement existe déjà pour cette adhésion.');
            }
            throw $exception;
        }
    }

    public function reconcile(ZumraPayment $payment): ZumraPayment
    {
        $remote = $this->provider->payment($payment->reference);
        // La tentative ne peut jamais changer d'environnement en cours de route : un paiement
        // amorcé en sandbox doit se réconcilier en sandbox, jamais en live et inversement.
        if ($remote['amount'] !== $payment->amount || $payment->currency !== 'XOF' || $payment->purpose !== ZumraPayment::PURPOSE_MEMBERSHIP
            || $remote['environment'] !== $payment->environment) {
            throw new RuntimeException('PAYMENT_RECONCILIATION_MISMATCH');
        }

        return DB::transaction(function () use ($payment, $remote): ZumraPayment {
            $locked = ZumraPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $membership = ZumraProgramMembership::query()->whereKey($locked->membership_id)->lockForUpdate()->firstOrFail();
            $locked->fill([
                'status' => $remote['status'], 'fees' => $remote['fees'], 'net_amount' => $remote['net_amount'],
                'provider_snapshot' => $remote['snapshot'], 'provider_snapshot_hash' => $this->snapshotHash($remote['snapshot']),
                'last_verified_at' => now(), 'completed_at' => $remote['status'] === ZumraPayment::STATUS_COMPLETED ? ($locked->completed_at ?? now()) : $locked->completed_at,
            ])->save();

            // CAP-007B : seul `live` active toujours. `sandbox` ne peut activer que si
            // l'interrupteur dédié `sandbox_activation_allowed` est explicitement ouvert —
            // jamais déduit de APP_ENV. Off par défaut partout, y compris en local.
            $canActivate = $locked->environment === 'live'
                || ($locked->environment === 'sandbox' && (bool) config('payments.geniuspay.sandbox_activation_allowed'));

            if ($remote['status'] === ZumraPayment::STATUS_COMPLETED && $canActivate && $membership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
                $membership->update(['status' => ZumraProgramMembership::STATUS_ACTIVE, 'activated_at' => now()]);
                ZumraProgramMembershipEvent::query()->create([
                    'membership_id' => $membership->id, 'event' => 'PAYMENT_CONFIRMED',
                    'from_status' => ZumraProgramMembership::STATUS_PENDING_PAYMENT, 'to_status' => ZumraProgramMembership::STATUS_ACTIVE,
                    'actor_core_reference' => 'SYSTEM:PAYMENT_PROVIDER', 'context' => ['payment_reference' => $locked->reference, 'environment' => $locked->environment], 'occurred_at' => now(),
                ]);
                $this->issueReceipt($locked, $membership);
                // CAP-062 : projection ledger uniquement après confirmation réelle et activation.
                $this->ledger->postMembershipPayment($locked);
            }

            return $locked->refresh();
        }, 3);
    }

    private function issueReceipt(ZumraPayment $payment, ZumraProgramMembership $membership): void
    {
        if (ZumraPaymentReceipt::query()->where('payment_id', $payment->id)->exists()) {
            return;
        }
        $issuedAt = now();
        $number = 'DGZ-'.$issuedAt->format('Y').'-'.strtoupper(Str::random(12));
        // Lit désormais le montant/devise du paiement réel plutôt qu'un « 500 »/« XOF » supposé
        // (ADHESION-ZAHAB-001) : sans effet observable sur le flux GeniusPay existant (`$payment->
        // amount`/`currency` y valent déjà 500/XOF), mais indispensable pour rester correct si le
        // montant canonique (`payments.membership.amount`) est un jour reconfiguré.
        $canonical = implode('|', [$number, $payment->reference, $membership->core_identity_reference, (string) $payment->amount, $payment->currency, $issuedAt->toIso8601String()]);
        ZumraPaymentReceipt::query()->create([
            'payment_id' => $payment->id, 'membership_id' => $membership->id, 'number' => $number,
            'core_identity_reference' => $membership->core_identity_reference, 'provider' => $payment->provider,
            'provider_reference' => $payment->reference, 'amount' => $payment->amount, 'currency' => $payment->currency,
            'purpose' => ZumraPayment::PURPOSE_MEMBERSHIP, 'issued_at' => $issuedAt, 'integrity_hash' => hash('sha256', $canonical),
        ]);
    }

    private function snapshotHash(array $snapshot): string
    {
        ksort($snapshot);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
