<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectAccompanimentAction extends Model
{
    use HasUuids;

    public const SOURCE_INTERNAL = 'INTERNAL';
    public const SOURCE_PARTNER = 'PARTNER';

    protected $table = 'dg_project_accompaniment_actions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_accompaniment_id',
        'action_type',
        'delivery_source',
        'provider_label',
        'summary',
        'next_step',
        'occurred_at',
        'recorded_by_core_reference',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function accompaniment(): BelongsTo
    {
        return $this->belongsTo(ProjectAccompaniment::class, 'project_accompaniment_id');
    }
}
