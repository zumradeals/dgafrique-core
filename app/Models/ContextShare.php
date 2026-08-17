<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ContextShare extends Model
{
    use HasUuids;

    public const SOURCE_NEED = 'NEED';
    public const SOURCE_PROJECT = 'PROJECT';
    public const SOURCE_MISSION = 'MISSION';
    public const SOURCE_TRANSMISSION = 'TRANSMISSION';

    public const TARGET_PERSON = 'PERSON';
    public const TARGET_ZUMRA = 'ZUMRA';

    protected $table = 'dg_context_shares';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_reference',
        'source_type',
        'source_reference',
        'sharer_core_reference',
        'target_type',
        'target_reference',
        'context_note',
        'shared_at',
    ];

    protected function casts(): array
    {
        return ['shared_at' => 'immutable_datetime'];
    }
}
