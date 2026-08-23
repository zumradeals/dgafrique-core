<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Application\Partnerships\PartnershipService;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Support\Collection;

/**
 * UIUX-005 — présentation partagée d'un Partnership pour les fiches Organisation/Besoin/Projet/
 * ZUMRA : fournisseur, ce qui est apporté, contexte réel, état humain. Aucune autorité recalculée
 * ici — `canManageAsProvider`/`canManageAsContextAuthority` réutilisent strictement
 * `PartnershipService::isProviderActor()`/`canManageAsContextAuthority()`, la seule décision
 * réelle reste dans le service.
 */
trait PresentsPartnerships
{
    /**
     * @param  Collection<int, Partnership>  $partnerships
     * @return array<int, array<string, mixed>>
     */
    private function presentPartnerships(Collection $partnerships, string $actor, PartnershipService $partnershipService): array
    {
        return $partnerships->map(fn (Partnership $partnership): array => $this->presentPartnership($partnership, $actor, $partnershipService))->values()->all();
    }

    /** @return array<string, mixed> */
    private function presentPartnership(Partnership $partnership, string $actor, PartnershipService $partnershipService): array
    {
        $provider = $this->partnershipProvider($partnership, $actor);
        $context = $this->partnershipContext($partnershipService->resolvedContext($partnership));

        return [
            'partnership' => $partnership,
            'providerLabel' => $provider['label'],
            'providerUrl' => $provider['url'],
            'contextLabel' => $context['label'],
            'contextUrl' => $context['url'],
            'statusLabel' => match ($partnership->status) {
                Partnership::STATUS_PROPOSED => 'Proposé',
                Partnership::STATUS_ACTIVE => 'Actif',
                Partnership::STATUS_PAUSED => 'En pause',
                Partnership::STATUS_ENDED => 'Terminé',
                default => $partnership->status,
            },
            'statusTone' => match ($partnership->status) {
                Partnership::STATUS_PROPOSED => 'saffron',
                Partnership::STATUS_ACTIVE => 'forest',
                default => 'neutral',
            },
            'canManageAsProvider' => $partnershipService->isProviderActor($partnership, $actor),
            'canManageAsContextAuthority' => $partnershipService->canManageAsContextAuthority($partnership, $actor),
        ];
    }

    /** @return array{label: string, url: ?string} */
    private function partnershipProvider(Partnership $partnership, string $actor): array
    {
        if ($partnership->provider_type === Partnership::PROVIDER_ORGANIZATION) {
            $organization = Organization::query()->find($partnership->provider_reference);

            return [
                'label' => $organization?->name ?? 'Organisation',
                'url' => $organization !== null ? route('organizations.show', $organization) : null,
            ];
        }

        if (hash_equals($partnership->provider_reference, $actor)) {
            return ['label' => 'Vous', 'url' => null];
        }

        $profile = PersonProfile::query()->where('core_identity_reference', $partnership->provider_reference)->first();
        $label = ($profile?->discovery_consent === true && $profile->discovery_display_name)
            ? $profile->discovery_display_name
            : 'Membre DG Afrique';

        return ['label' => $label, 'url' => null];
    }

    /** @return array{label: ?string, url: ?string} */
    private function partnershipContext(Need|Project|ZumraGroup|null $context): array
    {
        return match (true) {
            $context instanceof Need => ['label' => 'Besoin · '.$context->title, 'url' => route('needs.show', $context)],
            $context instanceof Project => ['label' => 'Projet · '.$context->name, 'url' => route('projects.show', $context)],
            $context instanceof ZumraGroup => ['label' => 'ZUMRA · '.$context->name, 'url' => route('zumra.groups.show', $context)],
            default => ['label' => null, 'url' => null],
        };
    }

    /**
     * UIUX-005 — Organisations que l'acteur gère réellement (OWNER/ADMIN actifs), pour décider si
     * le formulaire « Notre organisation peut apporter… » doit apparaître sur un Besoin/Projet/
     * ZUMRA consultable. Aucune autorité nouvelle : PartnershipService::propose() revérifie
     * lui-même OrganizationService::isManager() au moment de la soumission.
     *
     * @return Collection<int, Organization>
     */
    private function manageableOrganizations(string $actor): Collection
    {
        return Organization::query()
            ->where('status', Organization::STATUS_ACTIVE)
            ->whereIn('id', OrganizationMembership::query()
                ->where('core_identity_reference', $actor)
                ->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->whereIn('role', [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_ADMIN])
                ->pluck('organization_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * CAP-065/CAP-067 — capacités réellement déclarées et actives des Organisations que l'acteur
     * gère, pour peupler la sélection du formulaire « Notre organisation peut apporter… » (jamais
     * un champ libre). `PartnershipService::propose()` revérifie lui-même l'appartenance de la
     * capacité choisie à l'Organisation soumise ; cette liste ne fait qu'aider à choisir, elle ne
     * décide rien.
     *
     * @return Collection<int, CapabilityStatement>
     */
    private function manageableOrganizationCapabilities(string $actor): Collection
    {
        return CapabilityStatement::query()
            ->where('holder_type', CapabilityStatement::HOLDER_ORGANIZATION)
            ->whereIn('organization_id', $this->manageableOrganizations($actor)->pluck('id'))
            ->whereNull('archived_at')
            ->with('organization')
            ->orderBy('label')
            ->get();
    }
}
