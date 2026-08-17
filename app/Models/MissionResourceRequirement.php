<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionResourceRequirement extends Model
{
    use HasUuids;

    protected $table = 'dg_mission_resource_requirements';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'type', 'label', 'quantity', 'unit',
        'is_required', 'context', 'external_reference',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }
}
