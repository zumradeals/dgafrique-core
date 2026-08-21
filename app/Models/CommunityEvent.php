<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-068 — rencontre/atelier/activité calendaire à laquelle on participe (définition canonique :
 * « Mission = action à accomplir avec responsabilité ; Événement = rencontre située dans le temps
 * à laquelle on participe »). Nommé CommunityEvent, jamais Event, pour ne jamais être confondu avec
 * les journaux append-only *Event (ProjectEvent, ZumraGroupEvent, ...) — ceux-ci restent inchangés
 * et continuent de tracer les faits techniques, jamais les rencontres elles-mêmes.
 */
final class CommunityEvent extends Model
{
    use HasUuids;

    public const ORGANIZER_ZUMRA_GROUP = 'ZUMRA_GROUP';

    public const ORGANIZER_ORGANIZATION = 'ORGANIZATION';

    public const VISIBILITY_INTERNAL = 'INTERNAL';

    public const VISIBILITY_PUBLIC = 'PUBLIC';

    public const STATUS_SCHEDULED = 'SCHEDULED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'dg_community_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'organizer_type', 'organizer_reference', 'organizer_core_reference',
        'title', 'description', 'location', 'visibility', 'status', 'scheduled_at',
        'decided_by_core_reference', 'decision_note', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }
}
