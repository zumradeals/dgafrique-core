<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Soumission versionnée du résultat global. SUBMITTED != COMPLETED : seule une décision
 * explicite de l'autorité contextuelle transforme une soumission en résultat validé.
 */
final class MissionSubmission extends Model
{
    use HasUuids;

    public const DECISION_ACCEPTED = 'ACCEPTED';
    public const DECISION_CHANGES_REQUESTED = 'CHANGES_REQUESTED';

    protected $table = 'dg_mission_submissions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mission_id', 'version', 'result_summary', 'submitted_by_core_reference',
        'submitted_at', 'decision', 'decided_by_core_reference', 'decision_note', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime'];
    }
}
