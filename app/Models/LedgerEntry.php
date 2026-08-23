<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-062 — une écriture financière immuable, projection d'un paiement déjà CONFIRMÉ (CAP-061 ou
 * CAP-007B) ou d'un mouvement de Wallet ZAHAB déjà autorisé (ZAHAB-001). Jamais une source dérivée
 * d'ailleurs : `LedgerService` ne modifie jamais sa source, et aucune écriture n'est jamais réécrite
 * — une correction produit une nouvelle écriture (`reverses_entry_id`), jamais une mutation. Ni
 * solde stocké, ni compte comptable : `wallet_id`/`direction` identifient seulement quel Wallet et
 * quel sens un mouvement concerne — le solde reste 100% dérivé (`ZahabWalletService::balance()`),
 * jamais stocké ici ni ailleurs.
 */
final class LedgerEntry extends Model
{
    use HasUuids;

    public const SOURCE_CONTRIBUTION_PAYMENT = 'CONTRIBUTION_PAYMENT';

    public const SOURCE_MEMBERSHIP_PAYMENT = 'MEMBERSHIP_PAYMENT';

    /** ZAHAB-001 — tout crédit/débit d'un Wallet ZAHAB, quelle que soit sa raison métier (purpose_code). */
    public const SOURCE_ZAHAB_WALLET_MOVEMENT = 'ZAHAB_WALLET_MOVEMENT';

    public const TYPE_PAYMENT = 'PAYMENT';

    /** ZAHAB-001 — produite par `LedgerService::reverseWalletMovement()`, jamais par une mutation. */
    public const TYPE_REVERSAL = 'REVERSAL';

    public const TYPE_CORRECTION = 'CORRECTION';

    public const DIRECTION_CREDIT = 'CREDIT';

    public const DIRECTION_DEBIT = 'DEBIT';

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
