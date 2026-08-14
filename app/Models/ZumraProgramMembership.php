<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraProgramMembership extends Model
{
    use HasUuids;

    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $table = 'dg_zumra_program_memberships';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'core_identity_reference', 'status', 'accepted_charter_id', 'accepted_charter_version',
        'accepted_charter_hash', 'charter_accepted_at', 'submitted_at', 'activated_at',
        'suspended_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'charter_accepted_at' => 'immutable_datetime', 'submitted_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime', 'suspended_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
