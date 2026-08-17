<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransmissionParticipant extends Model
{
    use HasUuids;

    public const ROLE_TRANSMITTER = 'TRANSMITTER';
    public const ROLE_LEARNER = 'LEARNER';

    public const ROLES = [self::ROLE_TRANSMITTER, self::ROLE_LEARNER];

    public const STATUS_INVITED = 'INVITED';
    public const STATUS_OFFERED = 'OFFERED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_DECLINED = 'DECLINED';
    public const STATUS_WITHDRAWN = 'WITHDRAWN';
    public const STATUS_REMOVED = 'REMOVED';

    public const CURRENT_STATUSES = [self::STATUS_INVITED, self::STATUS_OFFERED, self::STATUS_ACCEPTED];

    protected $table = 'dg_transmission_participants';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'transmission_id', 'core_identity_reference', 'role', 'status',
        'invited_by_core_reference', 'responded_at', 'declared_done_at', 'declared_done_note',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'immutable_datetime',
            'declared_done_at' => 'immutable_datetime',
        ];
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(Transmission::class, 'transmission_id');
    }
}
