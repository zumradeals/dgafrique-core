<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionRecurrence extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_STOPPED = 'STOPPED';

    protected $table = 'dg_mission_recurrences';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'source_mission_id', 'rrule', 'timezone', 'is_active', 'status',
        'next_occurrence_at', 'last_generated_at', 'last_error_at',
        'last_error_code', 'created_by_core_reference',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'next_occurrence_at' => 'immutable_datetime',
            'last_generated_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
        ];
    }
}
