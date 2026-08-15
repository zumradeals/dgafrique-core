<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraCard extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_REVOKED = 'REVOKED';

    protected $table = 'dg_zumra_cards';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    protected function casts(): array
    {
        return ['issued_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }
}
