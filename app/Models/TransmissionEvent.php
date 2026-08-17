<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal append-only des Transmissions. Jamais modifié ni supprimé depuis l'UI.
 */
final class TransmissionEvent extends Model
{
    use HasUuids;

    protected $table = 'dg_transmission_events';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['transmission_id', 'event', 'actor_core_reference', 'from_state', 'to_state', 'context', 'occurred_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
