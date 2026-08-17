<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Mission extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const TERMINAL_STATUSES = [self::STATUS_REJECTED, self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_PROPOSED => 'Proposée',
        self::STATUS_CHANGES_REQUESTED => 'Modifications demandées',
        self::STATUS_REJECTED => 'Rejetée',
        self::STATUS_OPEN => 'Ouverte',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_BLOCKED => 'Bloquée',
        self::STATUS_SUBMITTED => 'Soumise',
        self::STATUS_COMPLETED => 'Terminée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const STATUS_BADGE_TONES = [
        self::STATUS_DRAFT => 'neutral',
        self::STATUS_PROPOSED => 'decision',
        self::STATUS_CHANGES_REQUESTED => 'decision',
        self::STATUS_REJECTED => 'neutral',
        self::STATUS_OPEN => 'action',
        self::STATUS_IN_PROGRESS => 'action',
        self::STATUS_BLOCKED => 'need',
        self::STATUS_SUBMITTED => 'decision',
        self::STATUS_COMPLETED => 'project',
        self::STATUS_CANCELLED => 'neutral',
    ];

    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_CONTEXT = 'CONTEXT';
    public const VISIBILITY_PROGRAM = 'PROGRAM';
    public const VISIBILITY_PUBLIC = 'PUBLIC';

    public const VISIBILITY_ORDER = [
        self::VISIBILITY_PRIVATE => 0,
        self::VISIBILITY_CONTEXT => 1,
        self::VISIBILITY_PROGRAM => 2,
        self::VISIBILITY_PUBLIC => 3,
    ];

    protected $table = 'dg_missions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'context_type', 'context_reference', 'parent_mission_id',
        'recurrence_id', 'occurrence_key', 'created_by_core_reference', 'title',
        'description', 'expected_result', 'acceptance_criteria', 'participation_mode',
        'location', 'visibility', 'status', 'min_executors', 'max_executors',
        'starts_at', 'due_at', 'proposed_at', 'officialized_at', 'started_at',
        'submitted_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'acceptance_criteria' => 'array',
            'min_executors' => 'integer',
            'max_executors' => 'integer',
            'starts_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'proposed_at' => 'immutable_datetime',
            'officialized_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MissionAssignment::class, 'mission_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MissionEvent::class, 'mission_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(MissionChecklistItem::class, 'mission_id');
    }

    public function capabilityRequirements(): HasMany
    {
        return $this->hasMany(MissionCapabilityRequirement::class, 'mission_id');
    }

    public function resourceRequirements(): HasMany
    {
        return $this->hasMany(MissionResourceRequirement::class, 'mission_id');
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(MissionBlocker::class, 'mission_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(MissionDependency::class, 'mission_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(MissionContribution::class, 'mission_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(MissionSubmission::class, 'mission_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_mission_id');
    }

    public function activeBlockers()
    {
        return $this->blockers()->whereNull('resolved_at');
    }
}
