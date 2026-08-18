<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProjectTeamMember extends Model
{
    use HasUuids;

    public const STATUS_REQUESTED = 'REQUESTED';
    public const STATUS_INVITED = 'INVITED';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_LEFT = 'LEFT';
    public const STATUS_REMOVED = 'REMOVED';

    public const ENTRY_MODE_REQUEST = 'REQUEST';
    public const ENTRY_MODE_INVITATION = 'INVITATION';

    protected $table = 'dg_project_team_members';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id', 'core_identity_reference', 'role', 'status', 'entry_mode',
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
