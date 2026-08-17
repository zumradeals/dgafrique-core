<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Apport individuel d'un exécutant. Ne soumet jamais automatiquement le résultat global.
 */
final class MissionContribution extends Model
{
    use HasUuids;

    protected $table = 'dg_mission_contributions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['mission_id', 'assignment_id', 'summary', 'evidence_context', 'submitted_at'];

    protected function casts(): array
    {
        return ['evidence_context' => 'array', 'submitted_at' => 'immutable_datetime'];
    }
}
