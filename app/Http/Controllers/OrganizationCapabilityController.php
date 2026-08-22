<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Organizations\OrganizationCapabilityService;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CAP-067 — parcours minimal humain : fiche Organisation → Capacités → déclarer ce que
 * l'Organisation sait offrir. Seuls les managers habilités (`OrganizationService::isManager()`)
 * gèrent ces capacités ; la gouvernance fine reste au niveau service, comme CAP-066.
 */
final class OrganizationCapabilityController
{
    public function store(Request $request, Organization $organization, OrganizationCapabilityService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['label' => ['required', 'string', 'min:2', 'max:200']]);

        $service->declare($organization, $identity->reference, $data);

        return redirect()->route('organizations.show', $organization)->with('status', 'Capacité ajoutée à la fiche de l’organisation.');
    }

    public function destroy(Request $request, Organization $organization, CapabilityStatement $capability, OrganizationCapabilityService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $service->archive($organization, $identity->reference, $capability);

        return redirect()->route('organizations.show', $organization)->with('status', 'Capacité retirée de la fiche de l’organisation.');
    }
}
