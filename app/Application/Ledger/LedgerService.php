<?php

declare(strict_types=1);

namespace App\Application\Ledger;

use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\ContributionPurpose;
use App\Models\LedgerEntry;
use App\Models\ZahabWallet;
use App\Models\ZumraPayment;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\QueryException;
use RuntimeException;

/**
 * CAP-062 — journal financier simple, immuable, additif (art. 6.5, 23.1). Ce service ne modifie
 * JAMAIS un paiement source (`ContributionPayment`/`ZumraPayment`), une `Contribution`, un
 * `ZumraGroup` ni une personne : il ne fait qu'observer un paiement déjà CONFIRMÉ par la
 * réconciliation serveur-à-serveur du domaine appelant et en projeter une écriture immuable.
 * Aucune méthode de mutation (`updateEntry()`/`deleteEntry()`) n'existe ici — une correction
 * future se ferait par une nouvelle écriture (`reverses_entry_id`), jamais implémentée en V1.
 */
final class LedgerService
{
    /** Projette un ContributionPayment CAP-061 CONFIRMÉ. Idempotent : rejoué, retrouve la même écriture. */
    public function postContributionPayment(ContributionPayment $payment): ?LedgerEntry
    {
        if ($payment->status !== ContributionPayment::STATUS_COMPLETED) {
            return null;
        }

        $existing = $this->find(LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT, $payment->id);
        if ($existing !== null) {
            return $existing;
        }

        $contribution = Contribution::query()->findOrFail($payment->contribution_id);
        $purposeCode = ContributionPurpose::query()->whereKey($payment->purpose_id)->value('code');

        return $this->post(LedgerEntry::SOURCE_CONTRIBUTION_PAYMENT, $payment->id, [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'purpose_code' => $purposeCode,
            'period' => $payment->period,
            'payer_core_reference' => $payment->initiated_by_core_reference,
            'subject_type' => $contribution->subject_type,
            'subject_reference' => $contribution->subject_reference,
            'occurred_at' => $payment->completed_at ?? now(),
        ]);
    }

    /**
     * Projette un ZumraPayment CAP-007B CONFIRMÉ. L'adhésion n'a ni période ni ContributionPurpose
     * propre (ne pas en fabriquer une) : le `purpose_code` reprend directement `ZumraPayment::purpose`
     * (déjà `MEMBERSHIP`, la représentation la plus simple disponible). Idempotent.
     */
    public function postMembershipPayment(ZumraPayment $payment): ?LedgerEntry
    {
        if ($payment->status !== ZumraPayment::STATUS_COMPLETED) {
            return null;
        }

        $existing = $this->find(LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT, $payment->id);
        if ($existing !== null) {
            return $existing;
        }

        $membership = ZumraProgramMembership::query()->findOrFail($payment->membership_id);

        return $this->post(LedgerEntry::SOURCE_MEMBERSHIP_PAYMENT, $payment->id, [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'purpose_code' => $payment->purpose,
            'period' => null,
            'payer_core_reference' => $membership->core_identity_reference,
            'subject_type' => Contribution::SUBJECT_PERSON,
            'subject_reference' => $membership->core_identity_reference,
            'occurred_at' => $payment->completed_at ?? now(),
        ]);
    }

    /**
     * ZAHAB-001 — projette un mouvement de Wallet déjà autorisé par `ZahabWalletService` (crédit ou
     * débit déjà validé, verrou déjà tenu). `$idempotencyKey` est fourni par l'appelant : rejouer le
     * même mouvement (même clé) ne produit jamais une deuxième écriture — même filet de sécurité
     * UNIQUE(source_type, source_id) que `post()`. `$businessReason` réutilise `purpose_code` (déjà
     * utilisé pour les finalités CAP-061) plutôt que d'inventer une nouvelle colonne.
     */
    public function postWalletMovement(
        ZahabWallet $wallet,
        string $direction,
        int $amount,
        string $currency,
        string $businessReason,
        string $idempotencyKey,
        string $actorCoreReference,
    ): LedgerEntry {
        $existing = $this->find(LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT, $idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        return $this->post(LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT, $idempotencyKey, [
            'amount' => $amount,
            'currency' => $currency,
            'direction' => $direction,
            'purpose_code' => $businessReason,
            'period' => null,
            'payer_core_reference' => $actorCoreReference,
            'subject_type' => $wallet->subject_type,
            'subject_reference' => $wallet->subject_reference,
            'wallet_id' => $wallet->id,
            'occurred_at' => now(),
        ]);
    }

    /**
     * ZAHAB-001 — compense un mouvement de Wallet déjà posté, jamais en le modifiant (art. CAP-062,
     * même principe que `reverses_entry_id` déjà réservé au schéma). Sens opposé, même montant, même
     * Wallet ; `$idempotencyKey` est celle de LA COMPENSATION elle-même, jamais celle du mouvement
     * d'origine (sinon la contrainte UNIQUE(source_type, source_id) la rejetterait comme un doublon).
     */
    public function reverseWalletMovement(LedgerEntry $movement, string $idempotencyKey, string $actorCoreReference): LedgerEntry
    {
        if ($movement->source_type !== LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT || $movement->wallet_id === null) {
            throw new RuntimeException('LEDGER_ENTRY_NOT_A_WALLET_MOVEMENT');
        }
        if ($movement->entry_type !== LedgerEntry::TYPE_PAYMENT) {
            throw new RuntimeException('LEDGER_ENTRY_ALREADY_A_REVERSAL');
        }

        $existing = $this->find(LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT, $idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $opposite = $movement->direction === LedgerEntry::DIRECTION_CREDIT
            ? LedgerEntry::DIRECTION_DEBIT
            : LedgerEntry::DIRECTION_CREDIT;

        try {
            return LedgerEntry::query()->create([
                'source_type' => LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT,
                'source_id' => $idempotencyKey,
                'entry_type' => LedgerEntry::TYPE_REVERSAL,
                'reverses_entry_id' => $movement->id,
                'amount' => $movement->amount,
                'currency' => $movement->currency,
                'direction' => $opposite,
                'purpose_code' => $movement->purpose_code,
                'period' => null,
                'payer_core_reference' => $actorCoreReference,
                'subject_type' => $movement->subject_type,
                'subject_reference' => $movement->subject_reference,
                'wallet_id' => $movement->wallet_id,
                'occurred_at' => now(),
                'posted_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                return $this->find(LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT, $idempotencyKey) ?? throw $exception;
            }
            throw $exception;
        }
    }

    private function find(string $sourceType, string $sourceId): ?LedgerEntry
    {
        return LedgerEntry::query()->where('source_type', $sourceType)->where('source_id', $sourceId)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function post(string $sourceType, string $sourceId, array $attributes): LedgerEntry
    {
        try {
            return LedgerEntry::query()->create([
                ...$attributes,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'entry_type' => LedgerEntry::TYPE_PAYMENT,
                'posted_at' => now(),
            ]);
        } catch (QueryException $exception) {
            // Filet de sécurité de la contrainte UNIQUE(source_type, source_id) — un reconcile()
            // concurrent ou un backfill rejoué peut courir contre ce même appel : jamais une
            // deuxième écriture, jamais une erreur, on retrouve simplement celle déjà postée.
            if ((string) $exception->getCode() === '23505') {
                return $this->find($sourceType, $sourceId) ?? throw $exception;
            }
            throw $exception;
        }
    }
}
