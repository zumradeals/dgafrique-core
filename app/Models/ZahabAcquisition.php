<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ZAHAB-002 — une TENTATIVE d'acquisition de ZAHAB via un paiement externe GeniusPay (jamais le
 * crédit lui-même, qui reste une écriture `LedgerEntry` distincte). `amount` représente à la fois
 * le montant FCFA payé et le montant ZAHAB à créditer (parité 1:1 actuelle). Ni solde, ni compte :
 * voir `App\Models\ZahabWallet`/`App\Application\Zahab\ZahabWalletService::balance()`.
 */
final class ZahabAcquisition extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_REFUNDED = 'REFUNDED';

    protected $table = 'dg_zahab_acquisitions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'provider_snapshot' => 'array',
            'completed_at' => 'immutable_datetime',
            'credited_at' => 'immutable_datetime',
            'last_verified_at' => 'immutable_datetime',
        ];
    }
}
