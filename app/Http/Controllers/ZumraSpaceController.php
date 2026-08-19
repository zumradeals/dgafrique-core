<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ZumraSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $myMemberships = ZumraGroupMembership::query()
            ->where('core_identity_reference', $identity->reference)
            ->whereIn('status', [ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::STATUS_INVITED, ZumraGroupMembership::STATUS_REQUESTED])
            ->get()
            ->keyBy('zumra_group_id');

        $acceptedRoles = ZumraGroupRole::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->whereIn('zumra_group_id', $myMemberships->keys())
            ->get()
            ->keyBy('zumra_group_id');

        $myGroups = ZumraGroup::query()
            ->whereIn('id', $myMemberships->keys())
            ->orderBy('name')
            ->get()
            ->map(function (ZumraGroup $group) use ($myMemberships, $acceptedRoles): array {
                $role = $acceptedRoles->get($group->id);

                return [
                    'group' => $group,
                    'status' => $myMemberships->get($group->id)->status,
                    'role_label' => $role ? (ZumraGroupRole::LABELS[$role->role] ?? $role->role) : null,
                ];
            });

        // Groupes où ce membre détient une responsabilité acceptée : les demandes en attente
        // de leurs éventuels candidats méritent d'être visibles depuis le hub.
        $ledGroupIds = ZumraGroupRole::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');

        $pendingRequestsToDecide = $ledGroupIds->isEmpty() ? collect() : ZumraGroupMembership::query()
            ->whereIn('zumra_group_id', $ledGroupIds)
            ->where('status', ZumraGroupMembership::STATUS_REQUESTED)
            ->get()
            ->groupBy('zumra_group_id')
            ->map(fn ($rows, $groupId) => [
                'group' => ZumraGroup::query()->find($groupId),
                'count' => $rows->count(),
            ])
            ->filter(fn (array $row): bool => $row['group'] !== null)
            ->values();

        // « À faire maintenant » reste volontairement court : uniquement des actions réelles,
        // jamais une activité simulée ni un score. Deux éléments maximum pour préserver la
        // hiérarchie calme du hub.
        $attentionItems = $this->attentionItems($pendingRequestsToDecide, $myGroups);

        // Les domaines reflètent exactement le même univers découvrable que l'annuaire ZUMRA :
        // tout collectif non suspendu. Ils servent d'orientation visuelle, sans faux volume.
        $discoverDomains = ZumraGroup::query()
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->selectRaw('domain, COUNT(*) AS groups_count')
            ->groupBy('domain')
            ->orderByDesc('groups_count')
            ->orderBy('domain')
            ->limit(5)
            ->get()
            ->map(fn (ZumraGroup $row): array => [
                'domain' => $row->domain,
                'count' => (int) $row->getAttribute('groups_count'),
            ]);

        return view('zumra.index', compact(
            'identity', 'profile', 'membership', 'isAdministrator', 'myGroups',
            'pendingRequestsToDecide', 'attentionItems', 'discoverDomains',
        ));
    }

    /**
     * @param  Collection<int, array{group: ZumraGroup, count: int}>  $pendingRequestsToDecide
     * @param  Collection<int, array{group: ZumraGroup, status: string, role_label: ?string}>  $myGroups
     * @return Collection<int, array{kind: string, eyebrow: string, heading: string, body: string, action_label: string, action_href: string}>
     */
    private function attentionItems(Collection $pendingRequestsToDecide, Collection $myGroups): Collection
    {
        $items = collect();

        foreach ($pendingRequestsToDecide as $row) {
            /** @var ZumraGroup $group */
            $group = $row['group'];
            $count = (int) $row['count'];

            $items->push([
                'kind' => 'decision',
                'eyebrow' => 'Décision attendue',
                'heading' => $count > 1
                    ? $count.' demandes souhaitent rejoindre '.$group->name
                    : 'Une demande souhaite rejoindre '.$group->name,
                'body' => 'Votre responsabilité dans cette ZUMRA vous permet d’examiner cette demande.',
                'action_label' => 'Examiner',
                'action_href' => route('zumra.groups.show', $group).'#demandes',
            ]);

            if ($items->count() >= 2) {
                return $items;
            }
        }

        foreach ($myGroups->where('status', ZumraGroupMembership::STATUS_INVITED) as $row) {
            /** @var ZumraGroup $group */
            $group = $row['group'];

            $items->push([
                'kind' => 'invitation',
                'eyebrow' => 'Invitation reçue',
                'heading' => $group->name.' vous invite à rejoindre son collectif',
                'body' => 'Vous restez libre de consulter la ZUMRA puis d’accepter ou non l’invitation.',
                'action_label' => 'Voir l’invitation',
                'action_href' => route('zumra.groups.show', $group),
            ]);

            if ($items->count() >= 2) {
                break;
            }
        }

        return $items;
    }
}
