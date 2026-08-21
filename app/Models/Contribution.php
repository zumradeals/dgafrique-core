<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CAP-061 — l'ENGAGEMENT de contribution volontaire (art. 6), jamais un paiement. Une personne
 * ou une ZUMRA ne porte qu'un seul engagement à vie par type (contrainte unique en base) :
 * STOPPED se réactive (resume()), il ne se recrée jamais en doublon.
 *
 * « non activée » n'est jamais un statut persisté : c'est l'absence de ligne Contribution pour
 * ce sujet — éviter une valeur d'état qui ne correspondrait jamais à une ligne réelle en base.
 */
final class Contribution extends Model
{
    use HasUuids;

    public const TYPE_INDIVIDUAL = 'INDIVIDUAL';

    public const TYPE_COLLECTIVE = 'COLLECTIVE';

    public const SUBJECT_PERSON = 'PERSON';

    public const SUBJECT_ZUMRA_GROUP = 'ZUMRA_GROUP';

    public const STATUS_PROPOSED = 'PROPOSED';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_PAUSED = 'PAUSED';

    public const STATUS_STOPPED = 'STOPPED';

    protected $table = 'dg_contributions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'type', 'subject_type', 'subject_reference', 'status',
        'initiated_by_core_reference', 'proposed_by_core_reference', 'proposed_role', 'proposed_at',
        'approved_by_core_reference', 'approved_role', 'approved_at',
        'paused_at', 'resumed_at', 'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'resumed_at' => 'immutable_datetime',
            'stopped_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContributionPayment::class, 'contribution_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContributionEvent::class, 'contribution_id');
    }
}
