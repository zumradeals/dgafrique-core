<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal append-only des Missions. Jamais modifié ni supprimé depuis l'UI.
 */
final class MissionEvent extends Model
{
    use HasUuids;

    public const SYSTEM_RECURRENCE_ACTOR = 'SYSTEM:MISSION_RECURRENCE';

    protected $table = 'dg_mission_events';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'event', 'actor_core_reference', 'subject_type',
        'subject_reference', 'from_state', 'to_state', 'context', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
