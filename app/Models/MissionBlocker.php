<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionBlocker extends Model
{
    use HasUuids;

    public const TYPE_MISSING_CAPABILITY = 'MISSING_CAPABILITY';

    public const TYPE_MISSING_RESOURCE = 'MISSING_RESOURCE';

    public const TYPE_MISSING_DECISION = 'MISSING_DECISION';

    public const TYPE_MISSING_DOCUMENT = 'MISSING_DOCUMENT';

    public const TYPE_MISSING_AUTHORIZATION = 'MISSING_AUTHORIZATION';

    public const TYPE_MISSING_FINANCING = 'MISSING_FINANCING';

    public const TYPE_PERSON_UNAVAILABLE = 'PERSON_UNAVAILABLE';

    public const TYPE_EXTERNAL_DEPENDENCY = 'EXTERNAL_DEPENDENCY';

    public const TYPE_TECHNICAL_PROBLEM = 'TECHNICAL_PROBLEM';

    public const TYPE_SAFETY_ISSUE = 'SAFETY_ISSUE';

    public const TYPE_OTHER = 'OTHER';

    public const TYPES = [
        self::TYPE_MISSING_CAPABILITY, self::TYPE_MISSING_RESOURCE, self::TYPE_MISSING_DECISION,
        self::TYPE_MISSING_DOCUMENT, self::TYPE_MISSING_AUTHORIZATION, self::TYPE_MISSING_FINANCING,
        self::TYPE_PERSON_UNAVAILABLE, self::TYPE_EXTERNAL_DEPENDENCY, self::TYPE_TECHNICAL_PROBLEM,
        self::TYPE_SAFETY_ISSUE, self::TYPE_OTHER,
    ];

    public const TYPE_LABELS = [
        self::TYPE_MISSING_CAPABILITY => 'Une capacité manque',
        self::TYPE_MISSING_RESOURCE => 'Une ressource manque',
        self::TYPE_MISSING_DECISION => 'Une décision est attendue',
        self::TYPE_MISSING_DOCUMENT => 'Un document manque',
        self::TYPE_MISSING_AUTHORIZATION => 'Une autorisation manque',
        self::TYPE_MISSING_FINANCING => 'Un financement manque',
        self::TYPE_PERSON_UNAVAILABLE => 'Une personne est indisponible',
        self::TYPE_EXTERNAL_DEPENDENCY => 'Dépend d\'une action extérieure',
        self::TYPE_TECHNICAL_PROBLEM => 'Un problème technique',
        self::TYPE_SAFETY_ISSUE => 'Un enjeu de sécurité',
        self::TYPE_OTHER => 'Autre raison',
    ];

    protected $table = 'dg_mission_blockers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'type', 'description', 'opened_by_core_reference', 'opened_at',
        'resolved_by_core_reference', 'resolved_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return ['opened_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime'];
    }
}
