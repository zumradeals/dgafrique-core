<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OrganizationMembership extends Model
{
    use HasUuids;

    public const ROLE_OWNER = 'OWNER';

    public const ROLE_ADMIN = 'ADMIN';

    public const ROLE_MEMBER = 'MEMBER';

    public const ROLES = [
        self::ROLE_OWNER => 'Fondateur / propriétaire',
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_MEMBER => 'Membre',
    ];

    public const STATUS_REQUESTED = 'REQUESTED';

    public const STATUS_INVITED = 'INVITED';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_LEFT = 'LEFT';

    public const STATUS_REMOVED = 'REMOVED';

    protected $table = 'dg_organization_memberships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id', 'core_identity_reference', 'role', 'status', 'entry_mode',
        'initiated_by_core_reference', 'motivation', 'decision_reason',
        'requested_at', 'invited_at', 'joined_at', 'left_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'invited_at' => 'immutable_datetime',
            'joined_at' => 'immutable_datetime',
            'left_at' => 'immutable_datetime',
        ];
    }
}
