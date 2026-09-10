<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MEMBERS-001 — projection humaine des membres d'une ZUMRA.
 *
 * L'appartenance reste portée exclusivement par ZumraGroupMembership et les responsabilités par
 * ZumraGroupRole. PersonProfile n'est utilisé que lorsque la personne a consenti à la découverte ;
 * aucune identité, aucun rôle ni annuaire parallèle n'est créé ici.
 */
final class ZumraMembersController
{
    public function __invoke(Request $request, ZumraGroup $group, ZumraGroupService $groups): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isLeader = $groups->isLeader($group, $identity->reference);
        $membership = $group->memberships()
            ->where('core_identity_reference', $identity->reference)
            ->first();
        $isActiveMember = $membership?->status === ZumraGroupMembership::STATUS_ACTIVE;

        abort_if($group->state === ZumraGroup::STATE_SUSPENDED && ! $isLeader, 404);
        abort_unless($isActiveMember || $isLeader, 404);

        $memberships = $group->memberships()
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->orderBy('joined_at')
            ->get();

        $references = $memberships->pluck('core_identity_reference')->filter()->values();
        $profiles = PersonProfile::query()
            ->whereIn('core_identity_reference', $references)
            ->where('discovery_consent', true)
            ->get()
            ->keyBy('core_identity_reference');

        $roles = $group->roles()
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->whereIn('core_identity_reference', $references)
            ->get()
            ->keyBy('core_identity_reference');

        $members = $memberships->map(function (ZumraGroupMembership $membership) use ($identity, $profiles, $roles): array {
            $reference = $membership->core_identity_reference;
            $profile = $profiles->get($reference);
            $role = $roles->get($reference);
            $isSelf = hash_equals($reference, $identity->reference);

            return [
                'reference' => $reference,
                'is_self' => $isSelf,
                'display_name' => $isSelf ? $identity->label : ($profile?->discovery_display_name ?: 'Membre de la ZUMRA'),
                'initial' => mb_strtoupper(mb_substr($isSelf ? $identity->label : ($profile?->discovery_display_name ?: 'M'), 0, 1)),
                'bio' => $profile?->discovery_bio,
                'city' => $profile?->city,
                'current_activity' => $profile?->current_activity,
                'discovery_reference' => $profile?->discovery_reference,
                'role_label' => $role ? (ZumraGroupRole::LABELS[$role->role] ?? $role->role) : null,
                'joined_at' => $membership->joined_at,
            ];
        })->sortBy([
            fn (array $member): int => $member['role_label'] === (ZumraGroupRole::LABELS['PRIMARY_LEAD'] ?? '') ? 0 : 1,
            fn (array $member): string => mb_strtolower($member['display_name']),
        ])->values();

        return view('zumra.groups.members', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'group' => $group,
            'members' => $members,
            'membersCount' => $memberships->count(),
            'isLeader' => $isLeader,
        ]);
    }
}
