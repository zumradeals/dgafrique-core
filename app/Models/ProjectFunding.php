<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-063 — un BESOIN financier déclaré par un Projet (art. 15.3) : un montant cible, une devise,
 * une justification et un usage prévu. Jamais un mouvement d'argent : aucun paiement, aucune
 * collecte, aucun décaissement. Un Projet ne porte qu'une seule déclaration OPEN à la fois
 * (contrainte unique en base) ; une nouvelle déclaration reste possible après clôture/annulation.
 */
final class ProjectFunding extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'dg_project_fundings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id', 'status', 'target_amount', 'currency', 'purpose', 'intended_use', 'conditions',
        'author_core_reference', 'decided_by_core_reference', 'closing_note',
        'opened_at', 'closed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
