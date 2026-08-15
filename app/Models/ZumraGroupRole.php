<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraGroupRole extends Model
{
    use HasUuids;

    public const STATUS_VACANT = 'VACANT';
    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_ACCEPTED = 'ACCEPTED';

    public const LABELS = [
        'PRIMARY_LEAD' => 'Premier responsable',
        'FIRST_DEPUTY' => 'Premier responsable adjoint',
        'SECOND_DEPUTY' => 'Second responsable adjoint',
        'FINANCE_LEAD' => 'Responsable financier',
        'SOCIAL_RELATIONS_LEAD' => 'Relations, affaires sociales et religieuses',
    ];

    protected $table = 'dg_zumra_group_roles';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['zumra_group_id', 'role', 'core_identity_reference', 'status', 'proposed_by_core_reference', 'proposed_at', 'accepted_at'];

    protected function casts(): array
    {
        return ['proposed_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime'];
    }
}
