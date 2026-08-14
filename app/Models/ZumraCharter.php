<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ZumraCharter extends Model
{
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_RETIRED = 'RETIRED';

    protected $table = 'dg_zumra_charters';
    protected $fillable = ['version', 'title', 'body', 'content_hash', 'status', 'published_at', 'published_by_core_reference'];

    protected function casts(): array
    {
        return ['published_at' => 'immutable_datetime'];
    }
}
