<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-061 — finalités d'affectation (art. 6.5) : une vraie table administrable, versionnée et
 * auditée, jamais un enum PHP. Retirer une finalité empêche de nouveaux paiements (RETIRED) mais
 * ne touche jamais l'historique des paiements déjà associés (FK restrictOnDelete, jamais cascade).
 */
final class ContributionPurpose extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_RETIRED = 'RETIRED';

    protected $table = 'dg_contribution_purposes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'label', 'status', 'created_by_core_reference', 'retired_by_core_reference', 'retired_at'];

    protected function casts(): array
    {
        return ['retired_at' => 'immutable_datetime'];
    }
}
