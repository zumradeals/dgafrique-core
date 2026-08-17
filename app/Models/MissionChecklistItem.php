<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionChecklistItem extends Model
{
    use HasUuids;

    protected $table = 'dg_mission_checklist_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'label', 'position', 'is_required',
        'completed_by_core_reference', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'completed_at' => 'immutable_datetime'];
    }
}
