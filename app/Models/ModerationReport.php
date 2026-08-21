<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ModerationReport extends Model
{
    use HasUuids;

    public const TARGET_CONTEXT_COMMENT = 'CONTEXT_COMMENT';

    public const TARGET_MESSAGE_ENTRY = 'MESSAGE_ENTRY';

    public const TARGET_ZUMRA_MEMBERSHIP = 'ZUMRA_MEMBERSHIP';

    public const CONTEXT_ZUMRA = 'ZUMRA';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_DECIDED = 'DECIDED';

    public const STATUS_DISMISSED = 'DISMISSED';

    public const REASON_VIOLENCE = 'VIOLENCE';

    public const REASON_THREAT = 'THREAT';

    public const REASON_FRAUD = 'FRAUD';

    public const REASON_HARASSMENT = 'HARASSMENT';

    public const REASON_DISCRIMINATION = 'DISCRIMINATION';

    public const REASON_HATE = 'HATE';

    public const REASON_EXPLOITATION = 'EXPLOITATION';

    public const REASON_MISAPPROPRIATION = 'MISAPPROPRIATION';

    public const REASON_IMPERSONATION = 'IMPERSONATION';

    public const REASON_DANGEROUS_MISINFORMATION = 'DANGEROUS_MISINFORMATION';

    public const REASON_OTHER = 'OTHER';

    public const REASON_CODES = [
        self::REASON_VIOLENCE, self::REASON_THREAT, self::REASON_FRAUD, self::REASON_HARASSMENT,
        self::REASON_DISCRIMINATION, self::REASON_HATE, self::REASON_EXPLOITATION,
        self::REASON_MISAPPROPRIATION, self::REASON_IMPERSONATION, self::REASON_DANGEROUS_MISINFORMATION,
        self::REASON_OTHER,
    ];

    protected $table = 'dg_moderation_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'reporter_core_reference', 'target_type', 'target_reference',
        'context_type', 'context_reference', 'reason_code', 'reason_details',
        'status', 'escalated_at', 'reported_at',
    ];

    protected function casts(): array
    {
        return ['escalated_at' => 'immutable_datetime', 'reported_at' => 'immutable_datetime'];
    }
}
