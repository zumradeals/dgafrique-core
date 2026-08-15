<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ZumraGroup extends Model
{
    use HasUuids;

    public const STATE_CONSTITUTING = 'CONSTITUTING';
    public const STATE_READY = 'READY';
    public const STATE_VALIDATED = 'VALIDATED';
    public const STATE_ACTIVE = 'ACTIVE';
    public const STATE_WARNED = 'WARNED';
    public const STATE_SUSPENDED = 'SUSPENDED';
    public const STATE_REHABILITATING = 'REHABILITATING';
    public const MATURITY_EMERGING = 'EMERGING';
    public const MATURITY_ESTABLISHED = 'ESTABLISHED';

    protected $table = 'dg_zumra_groups';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'public_reference', 'name', 'slug', 'domain', 'founding_objective',
        'participation_mode', 'internal_charter', 'state', 'maturity',
        'proposer_core_reference', 'active_member_count', 'ready_at',
        'validated_at', 'suspended_at',
    ];

    protected function casts(): array
    {
        return ['ready_at' => 'immutable_datetime', 'validated_at' => 'immutable_datetime', 'suspended_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ZumraGroupMembership::class, 'zumra_group_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ZumraGroupRole::class, 'zumra_group_id');
    }
}
