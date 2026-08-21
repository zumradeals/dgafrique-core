<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ModerationDecision extends Model
{
    use HasUuids;

    public const TARGET_CONTEXT_COMMENT = 'CONTEXT_COMMENT';

    public const TARGET_MESSAGE_ENTRY = 'MESSAGE_ENTRY';

    public const TARGET_ZUMRA_MEMBERSHIP = 'ZUMRA_MEMBERSHIP';

    public const ACTION_CONTENT_HIDDEN = 'CONTENT_HIDDEN';

    public const ACTION_WARNING = 'WARNING';

    public const ACTION_MEMBERSHIP_SUSPENSION = 'MEMBERSHIP_SUSPENSION';

    public const ACTION_MEMBERSHIP_EXCLUSION = 'MEMBERSHIP_EXCLUSION';

    public const ACTION_ROLE_REVOCATION = 'ROLE_REVOCATION';

    public const ACTION_TYPES = [
        self::ACTION_CONTENT_HIDDEN, self::ACTION_WARNING, self::ACTION_MEMBERSHIP_SUSPENSION,
        self::ACTION_MEMBERSHIP_EXCLUSION, self::ACTION_ROLE_REVOCATION,
    ];

    public const AUTHORITY_LEVEL_ZUMRA = 2;

    public const AUTHORITY_LEVEL_DG_AFRIQUE = 3;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_LIFTED = 'LIFTED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_MODIFIED = 'MODIFIED';

    public const APPEAL_OUTCOME_CONFIRMED = 'CONFIRMED';

    public const APPEAL_OUTCOME_MODIFIED = 'MODIFIED';

    public const APPEAL_OUTCOME_LIFTED = 'LIFTED';

    protected $table = 'dg_moderation_decisions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'moderation_report_id', 'target_type', 'target_reference', 'action_type',
        'reason_code', 'reason_details', 'decided_by_core_reference', 'authority_level',
        'effective_at', 'expires_at', 'status',
        'appeal_requested_at', 'appeal_reason', 'appeal_decided_at',
        'appeal_decided_by_core_reference', 'appeal_outcome', 'appeal_explanation',
    ];

    protected function casts(): array
    {
        return [
            'authority_level' => 'integer',
            'effective_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'appeal_requested_at' => 'immutable_datetime',
            'appeal_decided_at' => 'immutable_datetime',
        ];
    }

    public function isCurrentlyEffective(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
