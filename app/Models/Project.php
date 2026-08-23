<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Project extends Model
{
    use HasUuids;

    public const OWNER_PERSON = 'PERSON';

    public const OWNER_GROUP = 'GROUP';

    public const STATUS_PROPOSED = 'PROPOSED';

    public const STATUS_ADOPTED = 'ADOPTED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const VISIBILITY_PRIVATE = 'PRIVATE';

    public const VISIBILITY_GROUP = 'GROUP';

    public const VISIBILITY_PROGRAM = 'PROGRAM';

    public const VISIBILITY_PUBLIC = 'PUBLIC';

    protected $table = 'dg_projects';

    public $incrementing = false;

    protected $keyType = 'string';

    // PROJET-ZUMRA-INVARIANT-001 — zumra_group_id est l'ancrage ZUMRA du Projet, orthogonal à
    // owner_type/owner_reference (qui gouverne). Nullable uniquement pour les Projects nés avant
    // cette évolution ; tout nouveau Projet en reçoit toujours un (ProjectService::create()).
    protected $fillable = ['public_reference', 'owner_type', 'owner_reference', 'zumra_group_id', 'initiator_core_reference', 'source_need_id', 'name', 'summary', 'problem', 'proposed_solution', 'beneficiaries', 'domain', 'participation_mode', 'location', 'image_path', 'objectives', 'required_capabilities', 'required_resources', 'risks', 'property_regime', 'visibility', 'status', 'maturity', 'decided_by_core_reference', 'adopted_at', 'started_at', 'completed_at', 'archived_at'];

    protected function casts(): array
    {
        return ['objectives' => 'array', 'required_capabilities' => 'array', 'required_resources' => 'array', 'risks' => 'array', 'adopted_at' => 'immutable_datetime', 'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectEvent::class);
    }

    public function accompaniment(): HasOne
    {
        return $this->hasOne(ProjectAccompaniment::class);
    }

    public function autonomyPathway(): HasOne
    {
        return $this->hasOne(ProjectAutonomyPathway::class);
    }

    public function zumraGroup(): BelongsTo
    {
        return $this->belongsTo(ZumraGroup::class);
    }

    // « Progression globale » (fiche V2, Cerveau) : projection d'affichage déterministe, jamais un
    // calcul métier réel ni une écriture Core — voir docs/design/DESIGN-INVARIANTS.md §19/§20.
    // Ne jamais confondre avec la maturité (CAP-017, jamais un pourcentage).
    public function progressionSeed(): int
    {
        return 20 + (crc32($this->id) % 56);
    }
}
