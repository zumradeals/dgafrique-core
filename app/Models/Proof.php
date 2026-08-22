<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Proof extends Model
{
    use HasUuids;

    public const OWNER_PERSON = 'PERSON';

    public const OWNER_GROUP = 'GROUP';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_WITNESSED = 'WITNESSED';

    public const STATUS_ACKNOWLEDGED = 'ACKNOWLEDGED';

    public const STATUS_DISPUTED = 'DISPUTED';

    public const STATUS_LABELS = [
        self::STATUS_SUBMITTED => 'Soumise',
        self::STATUS_WITNESSED => 'Corroborée par un témoin',
        self::STATUS_ACKNOWLEDGED => 'Reconnue par le contexte',
        self::STATUS_DISPUTED => 'Contestée',
    ];

    public const STATUS_BADGE_TONES = [
        self::STATUS_SUBMITTED => 'decision',
        self::STATUS_WITNESSED => 'action',
        self::STATUS_ACKNOWLEDGED => 'project',
        self::STATUS_DISPUTED => 'need',
    ];

    public const ORIGIN_NONE = 'NONE';

    public const ORIGIN_MISSION = 'MISSION';

    public const ORIGIN_TRANSMISSION = 'TRANSMISSION';

    public const ORIGIN_NEED = 'NEED';

    public const ORIGIN_PROJECT = 'PROJECT';

    public const ORIGIN_ZUMRA = 'ZUMRA';

    public const ORIGIN_INTERACTION = 'INTERACTION';

    public const ORIGIN_TYPES = [
        self::ORIGIN_NONE, self::ORIGIN_MISSION, self::ORIGIN_TRANSMISSION,
        self::ORIGIN_NEED, self::ORIGIN_PROJECT, self::ORIGIN_ZUMRA, self::ORIGIN_INTERACTION,
    ];

    /** Origines pour lesquelles une autorité de contexte peut reconnaître la preuve. */
    public const ACKNOWLEDGEABLE_ORIGINS = [
        self::ORIGIN_MISSION, self::ORIGIN_TRANSMISSION, self::ORIGIN_NEED, self::ORIGIN_PROJECT, self::ORIGIN_ZUMRA,
    ];

    public const VISIBILITY_PRIVATE = 'PRIVATE';

    public const VISIBILITY_DISCOVERABLE = 'DISCOVERABLE';

    public const VISIBILITY_LABELS = [
        self::VISIBILITY_PRIVATE => 'Privée',
        self::VISIBILITY_DISCOVERABLE => 'Visible dans ma mémoire d\'expérience',
    ];

    protected $table = 'dg_proofs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'owner_type', 'owner_reference', 'title', 'description',
        'capability_label', 'normalized_label', 'catalog_item_id',
        'origin_type', 'origin_reference', 'occurred_at',
        'submitted_by_core_reference', 'submitted_at', 'visibility', 'status',
        'context_acknowledged_by_core_reference', 'context_acknowledged_at',
        'disputed_by_core_reference', 'disputed_at', 'dispute_note', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'context_acknowledged_at' => 'immutable_datetime',
            'disputed_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function references(): HasMany
    {
        return $this->hasMany(ProofReference::class, 'proof_id');
    }

    public function witnesses(): HasMany
    {
        return $this->hasMany(ProofWitness::class, 'proof_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProofEvent::class, 'proof_id');
    }
}
