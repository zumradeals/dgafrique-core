<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraGroupMembership extends Model
{
    use HasUuids;

    public const STATUS_REQUESTED = 'REQUESTED';
    public const STATUS_INVITED = 'INVITED';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_LEFT = 'LEFT';
    public const STATUS_EXCLUDED = 'EXCLUDED';

    protected $table = 'dg_zumra_group_memberships';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'zumra_group_id', 'core_identity_reference', 'status', 'entry_mode',
        'initiated_by_core_reference', 'motivation', 'decision_reason',
        'requested_at', 'invited_at', 'joined_at', 'left_at',
        'collective_capability_consent', 'collective_capability_consented_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime', 'invited_at' => 'immutable_datetime',
            'joined_at' => 'immutable_datetime', 'left_at' => 'immutable_datetime',
            'collective_capability_consent' => 'boolean',
            'collective_capability_consented_at' => 'immutable_datetime',
        ];
    }
}
