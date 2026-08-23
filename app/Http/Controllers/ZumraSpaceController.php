<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraAttentionSource;
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
    public function __invoke(Request $request, ZumraAttentionSource $zumraAttention): View
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

        // UIUX-002 : même source que MemberSpaceController::priority() et
        // NotificationSourceRegistry — une seule définition de « demande d'adhésion à décider »
        // et de « responsabilité proposée », jamais recalculée séparément ici.
        $pendingRequestsToDecide = $zumraAttention->pendingJoinRequestsForLeader($identity->reference)
            ->groupBy(fn (array $row): string => $row['group']->id)
            ->map(fn (Collection $rows): array => [
                'group' => $rows->first()['group'],
                'count' => $rows->count(),
            ])
            ->values();

        $myPendingRoleProposals = $zumraAttention->myPendingRoleProposals($identity->reference);

        // « À faire maintenant » reste volontairement court : uniquement des actions réelles,
        // jamais une activité simulée ni un score. Deux éléments maximum pour préserver la
        // hiérarchie calme du hub.
        $attentionItems = $this->attentionItems($myPendingRoleProposals, $pendingRequestsToDecide, $myGroups);

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
     * @param  Collection<int, array{group: ZumraGroup, role: ZumraGroupRole}>  $myPendingRoleProposals
     * @param  Collection<int, array{group: ZumraGroup, count: int}>  $pendingRequestsToDecide
     * @param  Collection<int, array{group: ZumraGroup, status: string, role_label: ?string}>  $myGroups
     * @return Collection<int, array{kind: string, eyebrow: string, heading: string, body: string, action_label: string, action_href: string}>
     */
    private function attentionItems(Collection $myPendingRoleProposals, Collection $pendingRequestsToDecide, Collection $myGroups): Collection
    {
        $items = collect();

        foreach ($myPendingRoleProposals as $row) {
            /** @var ZumraGroup $group */
            $group = $row['group'];
            /** @var ZumraGroupRole $role */
            $role = $row['role'];

            $items->push([
                'kind' => 'role_proposal',
                'eyebrow' => 'Responsabilité proposée',
                'heading' => (ZumraGroupRole::LABELS[$role->role] ?? $role->role).' — '.$group->name,
                'body' => 'Accepter reste entièrement votre choix.',
                'action_label' => 'Voir la proposition',
                'action_href' => route('zumra.groups.show', $group),
            ]);

            if ($items->count() >= 2) {
                return $items;
            }
        }

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
