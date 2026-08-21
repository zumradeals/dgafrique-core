<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-062 — une écriture financière immuable, projection d'un paiement déjà CONFIRMÉ (CAP-061 ou
 * CAP-007B). Jamais une source de vérité : `LedgerService` ne modifie jamais un paiement source, et
 * aucune écriture n'est jamais réécrite — une correction future produit une nouvelle écriture
 * (`reverses_entry_id`), jamais une mutation. Ni wallet, ni solde stocké, ni compte comptable.
 */
final class LedgerEntry extends Model
{
    use HasUuids;

    public const SOURCE_CONTRIBUTION_PAYMENT = 'CONTRIBUTION_PAYMENT';

    public const SOURCE_MEMBERSHIP_PAYMENT = 'MEMBERSHIP_PAYMENT';

    public const TYPE_PAYMENT = 'PAYMENT';

    /** Réservés au schéma pour une correction future — aucun runtime ne les produit en V1. */
    public const TYPE_REVERSAL = 'REVERSAL';

    public const TYPE_CORRECTION = 'CORRECTION';

    protected $table = 'dg_ledger_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'posted_at' => 'immutable_datetime',
        ];
    }
}
