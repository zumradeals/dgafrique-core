<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraProgramMembershipEvent extends Model
{
    use HasUuids;

    protected $table = 'dg_zumra_program_membership_events';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['membership_id', 'event', 'from_status', 'to_status', 'actor_core_reference', 'context', 'occurred_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
