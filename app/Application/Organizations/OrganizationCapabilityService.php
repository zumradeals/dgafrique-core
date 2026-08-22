<?php

declare(strict_types=1);

namespace App\Application\Organizations;

use App\Models\CapabilityStatement;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * CAP-067 — les capacités d'une Organisation. Réutilise strictement le moteur de capacités
 * existant (CAP-016, `CapabilityStatement`) plutôt qu'un second système parallèle : un porteur
 * `ORGANIZATION` explicite, jamais déduit d'un Partnership, d'un Projet, d'un événement, d'un
 * `provider_label`, ni des capacités personnelles de son manager.
 *
 * Une capacité Organisation est un fait métier explicite, déclaré volontairement par un manager
 * habilité (`OrganizationService::isManager()`) — jamais une conséquence automatique d'une autre
 * écriture. `KIND_POSSESSED` seulement : « ce que la structure sait apporter », pas le triptyque
 * possédé/apprentissage/transmission propre au parcours pédagogique d'une personne (CAP-016).
 *
 * Aucun score, niveau, classement ni popularité. Aucun raccordement au moteur de matching dans ce
 * chantier : `matching_consent` reste toujours `false` pour un porteur ORGANIZATION.
 */
final class OrganizationCapabilityService
{
    public function __construct(private readonly OrganizationService $organizations) {}

    public function declare(Organization $organization, string $actor, array $data): CapabilityStatement
    {
        abort_unless($this->organizations->isManager($organization, $actor), 403);

        $label = mb_substr(trim((string) ($data['label'] ?? '')), 0, 200);
        abort_if($label === '', 422, 'La capacité doit être décrite.');
        $normalized = mb_substr(Str::of($label)->ascii()->lower()->squish()->toString(), 0, 200);
        abort_if($normalized === '', 422, 'La capacité doit être décrite.');

        $exists = CapabilityStatement::query()
            ->where('holder_type', CapabilityStatement::HOLDER_ORGANIZATION)
            ->where('organization_id', $organization->id)
            ->where('kind', CapabilityStatement::KIND_POSSESSED)
            ->where('normalized_label', $normalized)
            ->whereNull('archived_at')
            ->exists();
        abort_if($exists, 409, 'Cette capacité est déjà déclarée.');

        return CapabilityStatement::query()->create([
            'holder_type' => CapabilityStatement::HOLDER_ORGANIZATION,
            'organization_id' => $organization->id,
            'core_identity_reference' => null,
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label,
            'normalized_label' => $normalized,
            'status' => CapabilityStatement::STATUS_DECLARED,
            // La visibilité réelle d'une capacité Organisation est gouvernée par
            // OrganizationService::canView() (fiche entière), jamais par ce champ pris seul.
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
            'matching_consent' => false,
            'source' => 'ORGANIZATION_PROFILE',
        ]);
    }

    public function archive(Organization $organization, string $actor, CapabilityStatement $statement): void
    {
        abort_unless($this->organizations->isManager($organization, $actor), 403);
        abort_unless($statement->holder_type === CapabilityStatement::HOLDER_ORGANIZATION, 404);
        abort_unless($statement->organization_id === $organization->id, 404);
        abort_if($statement->archived_at !== null, 409, 'Cette capacité est déjà archivée.');

        $statement->update(['archived_at' => now()]);
    }

    /** @return Collection<int, CapabilityStatement> */
    public function list(Organization $organization): Collection
    {
        return CapabilityStatement::query()
            ->where('holder_type', CapabilityStatement::HOLDER_ORGANIZATION)
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at')
            ->orderBy('created_at')
            ->get();
    }
}
