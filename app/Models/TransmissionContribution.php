<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class TransmissionContribution extends Model
{
    use HasUuids;

    protected $table = 'dg_transmission_contributions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['transmission_id', 'core_identity_reference', 'note', 'evidence_context', 'occurred_at'];

    protected function casts(): array
    {
        return ['evidence_context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
