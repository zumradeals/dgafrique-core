<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjectAccompaniment extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_ENDED = 'ENDED';

    protected $table = 'dg_project_accompaniments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'status',
        'activated_by_core_reference',
        'activated_at',
        'ended_by_core_reference',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProjectAccompanimentAction::class)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');
    }
}
