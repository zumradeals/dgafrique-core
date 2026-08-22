<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CapabilityStatement extends Model
{
    use HasUuids;

    public const KIND_POSSESSED = 'POSSESSED';
    public const KIND_LEARNING = 'LEARNING';
    public const KIND_TRANSMISSION = 'TRANSMISSION';

    public const STATUS_DECLARED = 'DECLARED';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_ATTESTED = 'ATTESTED';

    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_DISCOVERABLE = 'DISCOVERABLE';

    // CAP-067 — un porteur PERSON (défaut, comportement inchangé, référence
    // Core personnelle) ou un porteur ORGANIZATION (organization_id local,
    // jamais core_identity_reference — voir la migration dédiée). Jamais les
    // deux à la fois.
    public const HOLDER_PERSON = 'PERSON';
    public const HOLDER_ORGANIZATION = 'ORGANIZATION';

    protected $table = 'dg_capability_statements';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'holder_type', 'core_identity_reference', 'organization_id', 'catalog_item_id', 'kind', 'label',
        'normalized_label', 'proficiency', 'status', 'visibility',
        'matching_consent', 'source', 'attested_at', 'archived_at',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function casts(): array
    {
        return [
            'matching_consent' => 'boolean',
            'attested_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
