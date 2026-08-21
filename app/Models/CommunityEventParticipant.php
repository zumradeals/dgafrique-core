<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** CAP-068 — inscription/désinscription légère, jamais une émargement de présence effective. */
final class CommunityEventParticipant extends Model
{
    use HasUuids;

    public const STATUS_REGISTERED = 'REGISTERED';

    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    protected $table = 'dg_community_event_participants';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['community_event_id', 'core_identity_reference', 'status', 'registered_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return ['registered_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime'];
    }
}
