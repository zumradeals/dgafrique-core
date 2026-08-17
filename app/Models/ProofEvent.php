<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Journal append-only des Preuves. Jamais modifié ni supprimé depuis l'UI. */
final class ProofEvent extends Model
{
    use HasUuids;

    protected $table = 'dg_proof_events';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['proof_id', 'event', 'actor_core_reference', 'from_state', 'to_state', 'context', 'occurred_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
