<?php

declare(strict_types=1);

namespace App\Application\Zahab;

use App\Application\Ledger\LedgerService;
use App\Models\LedgerEntry;
use App\Models\ZahabWallet;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * ZAHAB-001 — moteur central du Wallet ZAHAB (arbitrage produit : 1 ZAHAB = 1 FCFA, addendum daté
 * dans docs/architecture/ARCHITECTURE-PRODUIT-V2.md §8). Porte tous les invariants :
 *
 * - le solde n'est JAMAIS stocké : `balance()` le recalcule toujours depuis dg_ledger_entries
 *   (CAP-062, seule vérité des mouvements) ;
 * - un sujet ne possède jamais plus d'un Wallet actif (`walletFor()`, création paresseuse
 *   idempotente, même filet de sécurité UNIQUE que `LedgerService::post()`) ;
 * - aucun débit ne peut dépasser le solde disponible, même sous concurrence : `debit()` verrouille
 *   la ligne Wallet (`lockForUpdate()`) et recalcule le solde à l'intérieur de cette même
 *   transaction — deux débits concurrents pour le même Wallet se sérialisent sur ce verrou ;
 * - aucun crédit ni débit sans raison métier fermée (`REASONS`) — jamais un montant choisi
 *   librement par une route générique (art. 14 du mandat ZAHAB-001) ;
 * - l'idempotence (rejouer la même opération métier) est portée par `LedgerService`
 *   (UNIQUE(source_type, source_id)), jamais réimplémentée ici.
 *
 * Ni cette classe ni aucun contrôleur HTTP n'expose de route générique « créditer ce montant » :
 * `credit()`/`debit()` ne sont appelés que par du code métier interne de confiance (aucun appelant
 * HTTP en V1 — ZAHAB-001 prépare le moteur, ZAHAB-002/CONTRIBUTION-ZAHAB-001 le raccorderont).
 */
final class ZahabWalletService
{
    /** ZAHAB doit avoir la même parité que le FCFA (art. 1 du mandat ZAHAB-001) — jamais une autre devise. */
    public const CURRENCY = 'XOF';

    public const REASON_MEMBERSHIP_PAYMENT = 'MEMBERSHIP_PAYMENT';

    public const REASON_INDIVIDUAL_CONTRIBUTION = 'INDIVIDUAL_CONTRIBUTION';

    public const REASON_COLLECTIVE_CONTRIBUTION = 'COLLECTIVE_CONTRIBUTION';

    public const REASON_AID = 'AID';

    public const REASON_PROJECT_FUNDING = 'PROJECT_FUNDING';

    public const REASON_SERVICE_PURCHASE = 'SERVICE_PURCHASE';

    public const REASON_SPONSORSHIP = 'SPONSORSHIP';

    public const REASON_REFUND = 'REFUND';

    /** Catalogue fermé (art. 11 du mandat) — étendre ici, jamais accepter une raison arbitraire. */
    public const REASONS = [
        self::REASON_MEMBERSHIP_PAYMENT,
        self::REASON_INDIVIDUAL_CONTRIBUTION,
        self::REASON_COLLECTIVE_CONTRIBUTION,
        self::REASON_AID,
        self::REASON_PROJECT_FUNDING,
        self::REASON_SERVICE_PURCHASE,
        self::REASON_SPONSORSHIP,
        self::REASON_REFUND,
    ];

    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Création paresseuse idempotente (art. 9 du mandat) : un Wallet vide ne nécessite aucune
     * écriture financière fictive. Deux appels concurrents pour le même sujet ne créent jamais deux
     * lignes — même filet de sécurité UNIQUE(source_type, source_id) que `LedgerService::post()`.
     */
    public function walletFor(string $subjectType, string $subjectReference, string $actorCoreReference): ZahabWallet
    {
        abort_unless(in_array($subjectType, ZahabWallet::SUBJECTS, true), 422, 'Sujet Wallet ZAHAB inconnu.');

        $existing = $this->find($subjectType, $subjectReference);
        if ($existing !== null) {
            return $existing;
        }

        try {
            return ZahabWallet::query()->create([
                'subject_type' => $subjectType,
                'subject_reference' => $subjectReference,
                'created_by_core_reference' => $actorCoreReference,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                return $this->find($subjectType, $subjectReference) ?? throw $exception;
            }
            throw $exception;
        }
    }

    /**
     * Solde 100% dérivé (art. 5 du mandat) : jamais une colonne, toujours la somme des écritures
     * Ledger de ce Wallet. Reconstruisible à tout instant, jamais autoritatif ailleurs que le Ledger.
     */
    public function balance(ZahabWallet $wallet): int
    {
        $credits = (int) LedgerEntry::query()
            ->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_CREDIT)->sum('amount');
        $debits = (int) LedgerEntry::query()
            ->where('wallet_id', $wallet->id)->where('direction', LedgerEntry::DIRECTION_DEBIT)->sum('amount');

        return $credits - $debits;
    }

    /**
     * Crédit autorisé par une raison métier fermée. `$idempotencyKey` doit identifier de façon
     * unique l'opération métier source (ex. l'id d'un futur paiement d'acquisition) : la rejouer ne
     * crédite jamais deux fois.
     */
    public function credit(ZahabWallet $wallet, int $amount, string $businessReason, string $idempotencyKey, string $actorCoreReference): LedgerEntry
    {
        $this->assertValidMovement($amount, $businessReason);

        return DB::transaction(function () use ($wallet, $amount, $businessReason, $idempotencyKey, $actorCoreReference): LedgerEntry {
            $locked = ZahabWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            return $this->ledger->postWalletMovement(
                $locked, LedgerEntry::DIRECTION_CREDIT, $amount, self::CURRENCY, $businessReason, $idempotencyKey, $actorCoreReference
            );
        });
    }

    /**
     * Débit atomique : jamais de solde négatif (art. 13 du mandat), jamais de double dépense sous
     * concurrence. Le verrou `lockForUpdate()` sur la ligne Wallet sérialise deux débits concurrents
     * pour le MÊME Wallet — le second ne recalcule son solde qu'après que le premier a validé et
     * committé le sien. Une clé d'idempotence déjà utilisée retourne l'écriture existante SANS
     * revérifier le solde : rejouer un débit déjà comptabilisé ne doit jamais échouer parce que ce
     * même débit a, entre-temps, réduit le solde disponible.
     */
    public function debit(ZahabWallet $wallet, int $amount, string $businessReason, string $idempotencyKey, string $actorCoreReference): LedgerEntry
    {
        $this->assertValidMovement($amount, $businessReason);

        return DB::transaction(function () use ($wallet, $amount, $businessReason, $idempotencyKey, $actorCoreReference): LedgerEntry {
            $locked = ZahabWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $existing = LedgerEntry::query()
                ->where('source_type', LedgerEntry::SOURCE_ZAHAB_WALLET_MOVEMENT)
                ->where('source_id', $idempotencyKey)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            abort_if($this->balance($locked) < $amount, 409, 'Solde ZAHAB insuffisant.');

            return $this->ledger->postWalletMovement(
                $locked, LedgerEntry::DIRECTION_DEBIT, $amount, self::CURRENCY, $businessReason, $idempotencyKey, $actorCoreReference
            );
        });
    }

    /**
     * Compense un mouvement déjà posté (art. 22 du mandat) : jamais une modification, une nouvelle
     * écriture de sens opposé référençant l'originale (`reverses_entry_id`). `$idempotencyKey` est
     * celle de LA COMPENSATION, distincte de celle du mouvement d'origine.
     */
    public function reverse(LedgerEntry $movement, string $idempotencyKey, string $actorCoreReference): LedgerEntry
    {
        abort_if($movement->wallet_id === null, 422, 'Cette écriture ne concerne aucun Wallet ZAHAB.');

        return DB::transaction(function () use ($movement, $idempotencyKey, $actorCoreReference): LedgerEntry {
            ZahabWallet::query()->whereKey($movement->wallet_id)->lockForUpdate()->firstOrFail();

            return $this->ledger->reverseWalletMovement($movement, $idempotencyKey, $actorCoreReference);
        });
    }

    private function assertValidMovement(int $amount, string $businessReason): void
    {
        abort_if($amount <= 0, 422, 'Le montant ZAHAB doit être un entier strictement positif.');
        abort_unless(in_array($businessReason, self::REASONS, true), 422, 'Raison métier ZAHAB inconnue.');
    }

    private function find(string $subjectType, string $subjectReference): ?ZahabWallet
    {
        return ZahabWallet::query()->where('subject_type', $subjectType)->where('subject_reference', $subjectReference)->first();
    }
}
