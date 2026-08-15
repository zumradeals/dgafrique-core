<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraGroupConfiguration;
use App\Application\Zumra\ZumraGroupService;
use App\Application\Zumra\CollectiveCapabilityConfiguration;
use App\Application\Zumra\CollectiveCapabilityProfile;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ZumraGroupController
{
    public function index(Request $request, ZumraGroupConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = $this->programMembership($identity->reference);
        $groups = ZumraGroup::query()->whereNotIn('state', [ZumraGroup::STATE_SUSPENDED])->latest()->paginate(12);
        $myMemberships = ZumraGroupMembership::query()->where('core_identity_reference', $identity->reference)->whereIn('status', [ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::STATUS_INVITED, ZumraGroupMembership::STATUS_REQUESTED])->pluck('status', 'zumra_group_id');

        return view('zumra.groups.index', compact('identity', 'membership', 'groups', 'myMemberships') + ['configuration' => $configuration->get()]);
    }

    public function create(Request $request, ZumraGroupConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $this->requireActiveProgramMembership($identity->reference);

        return view('zumra.groups.create', ['configuration' => $configuration->get()]);
    }

    public function store(Request $request, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $this->requireActiveProgramMembership($identity->reference);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:140', Rule::unique('dg_zumra_groups', 'name')],
            'domain' => ['required', 'string', 'min:3', 'max:140'],
            'founding_objective' => ['required', 'string', 'min:40', 'max:1800'],
            'participation_mode' => ['required', Rule::in(['PHYSICAL', 'DIGITAL', 'HYBRID'])],
            'internal_charter' => ['required', 'string', 'min:80', 'max:6000'],
            'assume_primary_lead' => ['nullable', 'boolean'],
        ]);
        $data['assume_primary_lead'] = $request->boolean('assume_primary_lead');
        $group = $service->create($identity->reference, $data);

        return redirect()->route('zumra.groups.show', $group)->with('status', 'Votre ZUMRA est créée en constitution. Aucun rôle vacant n’a été attribué automatiquement.');
    }

    public function show(Request $request, ZumraGroup $group, ZumraGroupService $service, CollectiveCapabilityConfiguration $capabilityConfiguration, CollectiveCapabilityProfile $capabilityProfile): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED && !$service->isLeader($group, $identity->reference), 404);
        $membership = $group->memberships()->where('core_identity_reference', $identity->reference)->first();
        $roles = $group->roles()->orderByRaw("case role when 'PRIMARY_LEAD' then 1 when 'FIRST_DEPUTY' then 2 when 'SECOND_DEPUTY' then 3 when 'FINANCE_LEAD' then 4 else 5 end")->get();
        $roleProfiles = PersonProfile::query()->whereIn('core_identity_reference', $roles->pluck('core_identity_reference')->filter())->get()->keyBy('core_identity_reference');
        $pendingRequests = $service->isLeader($group, $identity->reference)
            ? $group->memberships()->where('status', ZumraGroupMembership::STATUS_REQUESTED)->oldest('requested_at')->get()
            : collect();
        $requestProfiles = PersonProfile::query()->whereIn('core_identity_reference', $pendingRequests->pluck('core_identity_reference'))->get()->keyBy('core_identity_reference');
        $collectiveCapabilitySettings = $capabilityConfiguration->get();
        $collectiveCapabilities = $capabilityProfile->forGroup($group, $collectiveCapabilitySettings);

        return view('zumra.groups.show', compact('identity', 'group', 'membership', 'roles', 'roleProfiles', 'pendingRequests', 'requestProfiles', 'collectiveCapabilitySettings', 'collectiveCapabilities') + ['isLeader' => $service->isLeader($group, $identity->reference)]);
    }

    public function requestToJoin(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $this->requireActiveProgramMembership($identity->reference);
        $data = $request->validate(['motivation' => ['nullable', 'string', 'max:800']]);
        $service->requestToJoin($group, $identity->reference, $data['motivation'] ?? null);

        return back()->with('status', 'Votre demande a été transmise aux responsables. Vous n’êtes pas encore membre.');
    }

    public function invite(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['person_reference' => ['required', 'uuid']]);
        $profile = PersonProfile::query()->where('discovery_reference', $data['person_reference'])->where('discovery_consent', true)->firstOrFail();
        $this->requireActiveProgramMembership($profile->core_identity_reference);
        $service->invite($group, $identity->reference, $profile->core_identity_reference);

        return back()->with('status', 'Invitation envoyée. La personne ne deviendra membre qu’après acceptation.');
    }

    public function acceptInvitation(Request $request, ZumraGroup $group, ZumraGroupConfiguration $configuration, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->acceptInvitation($group, $identity->reference, (int) $configuration->get()['established_member_threshold']);

        return back()->with('status', 'Invitation acceptée. Vous êtes maintenant membre de cette ZUMRA.');
    }

    public function approveRequest(Request $request, ZumraGroup $group, string $membership, ZumraGroupConfiguration $configuration, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->approveRequest($group, $identity->reference, $membership, (int) $configuration->get()['established_member_threshold']);

        return back()->with('status', 'La demande est approuvée et le membre a rejoint la ZUMRA.');
    }

    public function leave(Request $request, ZumraGroup $group, ZumraGroupConfiguration $configuration, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $service->leave($group, $identity->reference, (int) $configuration->get()['established_member_threshold']);

        return redirect()->route('zumra.groups.index')->with('status', 'Vous avez quitté cette ZUMRA. Son historique reste conservé.');
    }

    private function programMembership(string $identity): ?ZumraProgramMembership
    {
        return ZumraProgramMembership::query()->where('core_identity_reference', $identity)->first();
    }

    private function requireActiveProgramMembership(string $identity): ZumraProgramMembership
    {
        return ZumraProgramMembership::query()->where('core_identity_reference', $identity)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->firstOrFail();
    }
}
