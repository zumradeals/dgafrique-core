<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProjectBrainIntent extends Model
{
    use HasUuids;

    protected $table = 'dg_project_brain_intents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'context' => 'array',
        ];
    }
}
