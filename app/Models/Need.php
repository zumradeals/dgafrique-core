<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Need extends Model
{
    use HasUuids;

    public const OWNER_PERSON = 'PERSON';
    public const OWNER_GROUP = 'GROUP';
    public const OWNER_PROJECT = 'PROJECT';
    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_ARCHIVED = 'ARCHIVED';
    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_GROUP = 'GROUP';
    public const VISIBILITY_PROGRAM = 'PROGRAM';
    public const VISIBILITY_PUBLIC = 'PUBLIC';

    protected $table = 'dg_needs';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'public_reference', 'owner_type', 'owner_reference', 'author_core_reference',
        'title', 'context', 'category', 'capability_label', 'collaboration_mode',
        'location', 'visibility', 'status', 'decided_by_core_reference',
        'resolution_note', 'published_at', 'resolved_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function events(): HasMany
    {
        return $this->hasMany(NeedEvent::class, 'need_id');
    }
}
