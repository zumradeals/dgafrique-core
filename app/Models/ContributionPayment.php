<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CAP-061 — une TENTATIVE de paiement pour une période mensuelle (jamais l'engagement lui-même).
 * Patron identique à ZumraPayment (CAP-007B), jamais réutilisé directement : nouvelle table dédiée
 * (Architecture A, Phase A).
 */
final class ContributionPayment extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_REFUNDED = 'REFUNDED';

    protected $table = 'dg_contribution_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['provider_snapshot' => 'array', 'completed_at' => 'immutable_datetime', 'last_verified_at' => 'immutable_datetime'];
    }
}
