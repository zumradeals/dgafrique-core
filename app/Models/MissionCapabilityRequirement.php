<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionCapabilityRequirement extends Model
{
    use HasUuids;

    public const LEVEL_REQUIRED = 'REQUIRED';
    public const LEVEL_PREFERRED = 'PREFERRED';

    protected $table = 'dg_mission_capability_requirements';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'catalog_item_id', 'label', 'normalized_label',
        'requirement_level', 'quantity', 'context',
    ];
}
