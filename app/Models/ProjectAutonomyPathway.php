<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectAutonomyPathway extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CLOSED = 'CLOSED';

    protected $table = 'dg_project_autonomy_pathways';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'status',
        'target_form',
        'other_form_label',
        'opened_by_core_reference',
        'opened_at',
        'closed_by_core_reference',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
