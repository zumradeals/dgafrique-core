<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MissionNeedLink extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'dg_mission_need_links';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['mission_id', 'blocker_id', 'need_id', 'created_by_core_reference', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
