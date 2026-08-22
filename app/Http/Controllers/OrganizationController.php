<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Community\CommunityEventService;
use App\Application\Organizations\OrganizationCapabilityService;
use App\Application\Organizations\OrganizationService;
use App\Application\Partnerships\PartnershipService;
use App\Domain\Identity\CoreIdentity;
use App\Http\Controllers\Concerns\PresentsPartnerships;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CAP-066 — exposition minimale : liste, création, fiche et demande d'adhésion. La gouvernance
 * fine (invitation, approbation, retrait) reste au niveau service pour cette V1 ; aucun
 * back-office n'est construit ici (§14/§22 du chantier).
 */
final class OrganizationController
{
    use PresentsPartnerships;

    public function index(Request $request, OrganizationService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $organizations = Organization::query()
            ->whereNotIn('status', [Organization::STATUS_ARCHIVED])
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->filter(fn (Organization $organization): bool => $service->canView($organization, $identity->reference))
            ->values();

        return view('organizations.index', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'organizations' => $organizations,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return view('organizations.create', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
        ]);
    }

    public function store(Request $request, OrganizationService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:140'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'type' => ['required', Rule::in(array_keys(Organization::TYPES))],
            'other_type_label' => ['nullable', 'string', 'max:140'],
            'visibility' => ['required', Rule::in([Organization::VISIBILITY_PRIVATE, Organization::VISIBILITY_PUBLIC])],
        ]);

        try {
            $organization = $service->create($identity->reference, $data);
        } catch (CoreUnavailableException|CoreProtocolException|CoreSessionRejectedException) {
            // CAP-067 — le raccordement canonique GAMAD Core est demandé avant toute écriture
            // locale : aucune fausse Organisation locale n'est jamais finalisée si Core échoue.
            return back()->withInput()->withErrors([
                'name' => 'La création n’a pas pu être confirmée par GAMAD Core. Réessayez dans un instant.',
            ]);
        }

        return redirect()->route('organizations.show', $organization)->with('status', 'Votre organisation a été créée.');
    }

    public function show(Request $request, Organization $organization, OrganizationService $service, CommunityEventService $events, PartnershipService $partnerships, OrganizationCapabilityService $capabilities): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($organization, $identity->reference), 404);

        // UIUX-005 — décision produit : ne jamais déduire les capacités intrinsèques d'une
        // Organisation de ses Partnerships. Cette section montre les collaborations réelles
        // (« avec qui, sur quoi »), jamais un catalogue de capacités reconstitué depuis elles.
        $organizationPartnerships = Partnership::query()
            ->where('provider_type', Partnership::PROVIDER_ORGANIZATION)
            ->where('provider_reference', $organization->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->filter(fn (Partnership $partnership): bool => $partnerships->canView($partnership, $identity->reference))
            ->values();

        $memberships = $organization->memberships()->where('status', OrganizationMembership::STATUS_ACTIVE)->orderBy('joined_at')->get();

        return view('organizations.show', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'organization' => $organization,
            // UIUX-006 — un libellé humain par membre (« Vous » / nom de découverte consenti /
            // « Membre DG Afrique »), même convention que PresentsPartnerships::partnershipProvider()
            // — jamais une référence Core exposée, jamais uniquement un rôle répété sans identité.
            'members' => $this->presentMembers($memberships, $identity->reference),
            'isMember' => $service->isMember($organization, $identity->reference),
            'isManager' => $service->isManager($organization, $identity->reference),
            // UIUX-003 — décision #4 : Événements réellement organisés par cette Organisation
            // (relation organizer_type=ORGANIZATION déjà réelle) — même autorité canView() que
            // la page elle-même, jamais recalculée. Aucune relation Projet/Besoin fabriquée.
            'organizationEvents' => $events->forOrganization($organization, $identity->reference)->take(6),
            'organizationPartnerships' => $this->presentPartnerships($organizationPartnerships, $identity->reference, $partnerships),
            'organizationCapabilities' => $capabilities->list($organization),
        ]);
    }

    public function requestToJoin(Request $request, Organization $organization, OrganizationService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['motivation' => ['nullable', 'string', 'max:1500']]);

        $service->requestToJoin($organization, $identity->reference, $data['motivation'] ?? null);

        return back()->with('status', 'Votre demande d’adhésion a été transmise.');
    }

    /**
     * UIUX-006 — un libellé humain lisible par membre au lieu du seul rôle répété : « Vous » pour
     * l'acteur courant, un nom de découverte s'il a été volontairement consenti (même règle que
     * `PresentsPartnerships::partnershipProvider()`), sinon « Membre DG Afrique ». Aucune référence
     * Core n'est jamais exposée.
     *
     * @param  Collection<int, OrganizationMembership>  $memberships
     * @return array<int, array{label: string, role: string}>
     */
    private function presentMembers(Collection $memberships, string $actor): array
    {
        $profiles = PersonProfile::query()
            ->whereIn('core_identity_reference', $memberships->pluck('core_identity_reference'))
            ->get()
            ->keyBy('core_identity_reference');

        return $memberships->map(function (OrganizationMembership $membership) use ($profiles, $actor): array {
            $label = 'Membre DG Afrique';

            if (hash_equals($membership->core_identity_reference, $actor)) {
                $label = 'Vous';
            } else {
                $profile = $profiles->get($membership->core_identity_reference);
                if ($profile?->discovery_consent === true && $profile->discovery_display_name) {
                    $label = $profile->discovery_display_name;
                }
            }

            return [
                'label' => $label,
                'role' => OrganizationMembership::ROLES[$membership->role] ?? $membership->role,
            ];
        })->values()->all();
    }
}
