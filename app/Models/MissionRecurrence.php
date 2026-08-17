<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'source_mission_id', 'rrule', 'timezone', 'due_offset_minutes', 'is_active', 'status',
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

    public function source(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'source_mission_id');
    }
}
