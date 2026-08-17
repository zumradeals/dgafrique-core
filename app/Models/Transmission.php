<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Transmission extends Model
{
    use HasUuids;

    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED_CONFIRMED = 'COMPLETED_CONFIRMED';
    public const STATUS_COMPLETED_BY_CONTEXT = 'COMPLETED_BY_CONTEXT';
    public const STATUS_ENDED = 'ENDED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED_CONFIRMED, self::STATUS_COMPLETED_BY_CONTEXT,
        self::STATUS_ENDED, self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PROPOSED => 'Proposée',
        self::STATUS_ACCEPTED => 'Acceptée',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_COMPLETED_CONFIRMED => 'Terminée — confirmée',
        self::STATUS_COMPLETED_BY_CONTEXT => 'Terminée — validée par le contexte',
        self::STATUS_ENDED => 'Arrêtée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const STATUS_BADGE_TONES = [
        self::STATUS_PROPOSED => 'decision',
        self::STATUS_ACCEPTED => 'action',
        self::STATUS_IN_PROGRESS => 'action',
        self::STATUS_COMPLETED_CONFIRMED => 'project',
        self::STATUS_COMPLETED_BY_CONTEXT => 'project',
        self::STATUS_ENDED => 'neutral',
        self::STATUS_CANCELLED => 'neutral',
    ];

    public const ORIGIN_NONE = 'NONE';
    public const ORIGIN_NEED = 'NEED';
    public const ORIGIN_MISSION = 'MISSION';
    public const ORIGIN_PROJECT = 'PROJECT';
    public const ORIGIN_ZUMRA = 'ZUMRA';
    public const ORIGIN_RECOMMENDATION = 'RECOMMENDATION';
    public const ORIGIN_PROFILE = 'PROFILE';
    public const ORIGIN_INTERACTION = 'INTERACTION';

    public const ORIGIN_TYPES = [
        self::ORIGIN_NONE, self::ORIGIN_NEED, self::ORIGIN_MISSION, self::ORIGIN_PROJECT,
        self::ORIGIN_ZUMRA, self::ORIGIN_RECOMMENDATION, self::ORIGIN_PROFILE, self::ORIGIN_INTERACTION,
    ];

    public const CONTEXT_NEED = 'NEED';
    public const CONTEXT_MISSION = 'MISSION';
    public const CONTEXT_PROJECT = 'PROJECT';
    public const CONTEXT_ZUMRA = 'ZUMRA';

    public const CONTEXT_TYPES = [self::CONTEXT_NEED, self::CONTEXT_MISSION, self::CONTEXT_PROJECT, self::CONTEXT_ZUMRA];

    /** Contextes pour lesquels une autorité contextuelle peut officialiser le rattachement (§5.A). */
    public const OFFICIALIZABLE_CONTEXTS = [self::CONTEXT_MISSION, self::CONTEXT_PROJECT, self::CONTEXT_ZUMRA];

    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_CONTEXT = 'CONTEXT';
    public const VISIBILITY_PROGRAM = 'PROGRAM';

    public const VISIBILITY_ORDER = [
        self::VISIBILITY_PRIVATE => 0,
        self::VISIBILITY_CONTEXT => 1,
        self::VISIBILITY_PROGRAM => 2,
    ];

    protected $table = 'dg_transmissions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'capability_label', 'normalized_label', 'catalog_item_id',
        'learning_objective', 'origin_type', 'origin_reference', 'context_type', 'context_reference',
        'visibility', 'status', 'availability_note', 'proposed_by_core_reference', 'proposed_at',
        'accepted_at', 'started_at', 'completed_at', 'ended_at', 'cancelled_at', 'completion_summary',
        'context_officialized_by_core_reference', 'context_officialized_at',
        'context_validated_by_core_reference', 'context_validated_at', 'evidence_context',
    ];

    protected function casts(): array
    {
        return [
            'evidence_context' => 'array',
            'proposed_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'context_officialized_at' => 'immutable_datetime',
            'context_validated_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TransmissionParticipant::class, 'transmission_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(TransmissionMilestone::class, 'transmission_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(TransmissionContribution::class, 'transmission_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TransmissionEvent::class, 'transmission_id');
    }
}
