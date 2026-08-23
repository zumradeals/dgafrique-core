<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** CAP-061 — reçu immuable, patron identique à ZumraPaymentReceipt (CAP-007B), table dédiée. */
final class ContributionReceipt extends Model
{
    use HasUuids;

    protected $table = 'dg_contribution_receipts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'immutable_datetime'];
    }
}
