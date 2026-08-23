<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Organization extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const VISIBILITY_PRIVATE = 'PRIVATE';

    public const VISIBILITY_PUBLIC = 'PUBLIC';

    // CAP-067 — une Organisation raccordée porte une identité et une fiche
    // organisationnelle canoniques réelles (GAMAD Core, CAP-CORE-001/002).
    // UNLINKED reste le défaut honnête pour toute Organisation dont le
    // raccordement n'est pas démontré (aucun rapprochement par nom inventé).
    public const CORE_LINK_LINKED = 'LINKED';

    public const CORE_LINK_UNLINKED = 'UNLINKED';

    // Vocabulaire local (ARCH-006 / ZUMRA-DOCTRINE-INVARIANTE.md §2) mappé
    // vers le vocabulaire fermé PolitiqueOrganisations::TYPES_ORGANISATION
    // de GAMAD Core (CAP-CORE-002) — jamais deviné à l'exécution.
    public const CORE_TYPE_MAP = [
        'COMPANY' => 'SOCIETE',
        'ASSOCIATION' => 'ASSOCIATION',
        'COOPERATIVE' => 'COOPERATIVE',
        'STARTUP' => 'SOCIETE',
        'PLATFORM' => 'INDETERMINE',
        'OTHER' => 'INDETERMINE',
    ];

    // Mêmes formes canoniques qu'App\Application\Projects\ProjectAutonomyPathwayService::TARGET_FORMS
    // et de ZUMRA-DOCTRINE-INVARIANTE.md §2 (« startup, entreprise, organisation, coopérative,
    // association... ») — vocabulaire déjà établi pour désigner une forme d'autonomie, jamais
    // réinventé ici.
    public const TYPES = [
        'COMPANY' => 'Entreprise',
        'ASSOCIATION' => 'Association',
        'COOPERATIVE' => 'Coopérative',
        'STARTUP' => 'Startup',
        'PLATFORM' => 'Plateforme',
        'OTHER' => 'Autre forme pertinente',
    ];

    protected $table = 'dg_organizations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'name', 'description', 'type', 'other_type_label',
        'status', 'visibility', 'founder_core_reference', 'active_member_count',
        'archived_at', 'core_identity_reference', 'core_organization_reference',
        'core_link_status',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class, 'organization_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrganizationEvent::class, 'organization_id');
    }

    public function capabilityStatements(): HasMany
    {
        return $this->hasMany(CapabilityStatement::class, 'organization_id');
    }
}
