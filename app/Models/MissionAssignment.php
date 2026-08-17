<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MissionAssignment extends Model
{
    use HasUuids;

    public const STATUS_OFFERED = 'OFFERED';
    public const STATUS_INVITED = 'INVITED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_DECLINED = 'DECLINED';
    public const STATUS_WITHDRAWN = 'WITHDRAWN';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_REMOVED = 'REMOVED';

    public const CURRENT_STATUSES = [self::STATUS_OFFERED, self::STATUS_INVITED, self::STATUS_ACCEPTED];

    public const ROLE_EXECUTOR = 'EXECUTOR';
    public const ROLE_CO_EXECUTOR = 'CO_EXECUTOR';
    public const ROLE_COORDINATOR = 'COORDINATOR';
    public const ROLE_LEARNER = 'LEARNER';
    public const ROLE_OBSERVER = 'OBSERVER';

    public const EXECUTION_ROLES = [self::ROLE_EXECUTOR, self::ROLE_CO_EXECUTOR, self::ROLE_COORDINATOR];

    protected $table = 'dg_mission_assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'core_identity_reference', 'role', 'status',
        'initiated_by_core_reference', 'accepted_by_core_reference', 'reason',
        'offered_at', 'invited_at', 'accepted_at', 'declined_at',
        'withdrawn_at', 'released_at', 'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_at' => 'immutable_datetime',
            'invited_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'declined_at' => 'immutable_datetime',
            'withdrawn_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
            'removed_at' => 'immutable_datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }
}
