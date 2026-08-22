<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CAP-065 — un Partenariat est une RELATION, jamais un nouvel acteur : un fournisseur déjà réel
 * (une Personne via son core_identity_reference, ou une Organisation CAP-066) met une capacité
 * réelle à disposition dans un contexte explicite (Projet, ZUMRA ou Besoin). Une Organisation agit
 * toujours via un membre réel habilité (OrganizationService::isManager) ; l'identité canonique
 * GAMAD Core éventuelle de l'Organisation (CAP-067) n'intervient jamais ici. Depuis la convergence
 * CAP-065/CAP-067, `capability_statement_id` référence toujours une CapabilityStatement réelle du
 * bon porteur (PERSON ou ORGANIZATION) — `capability_label` n'en est plus que le snapshot de
 * présentation au moment de la proposition, jamais la preuve métier.
 */
final class Partnership extends Model
{
    use HasUuids;

    public const PROVIDER_PERSON = 'PERSON';

    public const PROVIDER_ORGANIZATION = 'ORGANIZATION';

    public const CONTEXT_PROJECT = 'PROJECT';

    public const CONTEXT_ZUMRA = 'ZUMRA';

    public const CONTEXT_NEED = 'NEED';

    public const STATUS_PROPOSED = 'PROPOSED';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_PAUSED = 'PAUSED';

    public const STATUS_ENDED = 'ENDED';

    public const VISIBILITY_PRIVATE = 'PRIVATE';

    public const VISIBILITY_PUBLIC = 'PUBLIC';

    protected $table = 'dg_partnerships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'provider_type', 'provider_reference',
        'context_type', 'context_reference', 'capability_statement_id',
        'capability_label', 'description', 'status', 'visibility',
        'initiated_by_core_reference', 'decided_by_core_reference', 'decided_at',
        'ended_by_core_reference', 'paused_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function capabilityStatement(): BelongsTo
    {
        return $this->belongsTo(CapabilityStatement::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PartnershipEvent::class, 'partnership_id');
    }
}
