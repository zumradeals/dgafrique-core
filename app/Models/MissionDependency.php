<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionDependency extends Model
{
    use HasUuids;

    public const TYPE_FINISH_BEFORE_START = 'FINISH_BEFORE_START';

    public const TYPE_RESULT_REQUIRED = 'RESULT_REQUIRED';

    public const TYPE_RESOURCE_DEPENDENCY = 'RESOURCE_DEPENDENCY';

    public const TYPE_DECISION_DEPENDENCY = 'DECISION_DEPENDENCY';

    public const TYPES = [
        self::TYPE_FINISH_BEFORE_START, self::TYPE_RESULT_REQUIRED,
        self::TYPE_RESOURCE_DEPENDENCY, self::TYPE_DECISION_DEPENDENCY,
    ];

    public const TYPE_LABELS = [
        self::TYPE_FINISH_BEFORE_START => 'Doit être terminée avant de commencer',
        self::TYPE_RESULT_REQUIRED => 'A besoin d\'un résultat de l\'autre mission',
        self::TYPE_RESOURCE_DEPENDENCY => 'Dépend d\'une ressource partagée',
        self::TYPE_DECISION_DEPENDENCY => 'Dépend d\'une décision liée',
    ];

    protected $table = 'dg_mission_dependencies';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'depends_on_mission_id', 'type', 'created_by_core_reference',
    ];
}
