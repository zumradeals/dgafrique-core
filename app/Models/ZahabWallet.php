<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ZAHAB-001 — le sujet propriétaire d'un Wallet ZAHAB, jamais un solde. Un sujet ne possède jamais
 * plus d'un Wallet actif (UNIQUE(subject_type, subject_reference), même patron que dg_contributions).
 * Le solde utilisable n'est jamais une colonne de ce modèle : voir `App\Application\Zahab\
 * ZahabWalletService::balance()`, qui le recalcule toujours depuis dg_ledger_entries.
 *
 * Vocabulaire `subject_type` intentionnellement identique à `Contribution::SUBJECT_PERSON`/
 * `SUBJECT_ZUMRA_GROUP` (mêmes valeurs de chaîne, pour rester compatible avec `LedgerEntry::
 * subject_type`) — étendu ici avec ORGANIZATION, un sujet que CAP-061 ne porte pas encore.
 */
final class ZahabWallet extends Model
{
    use HasUuids;

    public const SUBJECT_PERSON = 'PERSON';

    public const SUBJECT_ZUMRA_GROUP = 'ZUMRA_GROUP';

    public const SUBJECT_ORGANIZATION = 'ORGANIZATION';

    public const SUBJECTS = [self::SUBJECT_PERSON, self::SUBJECT_ZUMRA_GROUP, self::SUBJECT_ORGANIZATION];

    protected $table = 'dg_zahab_wallets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
