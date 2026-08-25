<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProjectMilestone extends Model
{
    use HasUuids;

    public const STATUS_PLANNED = 'PLANNED';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $table = 'dg_project_milestones';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['project_id', 'title', 'position', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'immutable_datetime'];
    }
}
