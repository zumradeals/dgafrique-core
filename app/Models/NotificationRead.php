<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Marqueur personnel de lecture (CAP-054, fiche §15). Ne stocke jamais de titre, de corps ni
 * d'état métier dupliqué — la notification reste calculée depuis sa source à chaque lecture.
 */
final class NotificationRead extends Model
{
    use HasUuids;

    protected $table = 'dg_notification_reads';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['core_identity_reference', 'source_type', 'source_reference', 'read_at', 'dismissed_at'];

    protected function casts(): array
    {
        return ['read_at' => 'immutable_datetime', 'dismissed_at' => 'immutable_datetime'];
    }
}
