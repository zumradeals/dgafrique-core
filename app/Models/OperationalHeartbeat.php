<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OperationalHeartbeat extends Model
{
    protected $table = 'dg_operational_heartbeats';

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'source',
        'last_succeeded_at',
    ];

    protected function casts(): array
    {
        return [
            'last_succeeded_at' => 'immutable_datetime',
        ];
    }
}
