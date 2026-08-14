<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraPaymentReceipt extends Model
{
    use HasUuids;

    protected $table = 'dg_zumra_payment_receipts';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'immutable_datetime'];
    }
}
