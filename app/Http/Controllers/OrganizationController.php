<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Organizations\OrganizationService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PortalAdministrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CAP-066 — exposition minimale : liste, création, fiche et demande d'adhésion. La gouvernance
 * fine (invitation, approbation, retrait) reste au niveau service pour cette V1 ; aucun
 * back-office n'est construit ici (§14/§22 du chantier).
 */
final class OrganizationController
{
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

        $organization = $service->create($identity->reference, $data);

        return redirect()->route('organizations.show', $organization)->with('status', 'Votre organisation a été créée.');
    }

    public function show(Request $request, Organization $organization, OrganizationService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($organization, $identity->reference), 404);

        return view('organizations.show', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'organization' => $organization,
            'members' => $organization->memberships()->where('status', OrganizationMembership::STATUS_ACTIVE)->orderBy('joined_at')->get(),
            'isMember' => $service->isMember($organization, $identity->reference),
            'isManager' => $service->isManager($organization, $identity->reference),
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
}
