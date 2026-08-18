<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectAccompanimentRequest extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACKNOWLEDGED = 'ACKNOWLEDGED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $table = 'dg_project_accompaniment_requests';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_accompaniment_id', 'requested_by_core_reference', 'subject', 'description',
        'status', 'requested_at', 'acknowledged_by_core_reference', 'acknowledged_at',
        'closed_by_core_reference', 'closed_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'acknowledged_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function accompaniment(): BelongsTo
    {
        return $this->belongsTo(ProjectAccompaniment::class, 'project_accompaniment_id');
    }
}
