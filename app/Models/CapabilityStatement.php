<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    protected $table = 'dg_capability_statements';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'core_identity_reference', 'catalog_item_id', 'kind', 'label',
        'normalized_label', 'proficiency', 'status', 'visibility',
        'matching_consent', 'source', 'attested_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'matching_consent' => 'boolean',
            'attested_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
