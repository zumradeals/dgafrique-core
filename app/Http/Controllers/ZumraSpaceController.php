<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Zumra\ZumraAttentionSource;
use App\Domain\Identity\CoreIdentity;
use App\Models\CommunityEvent;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupActivity;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProximityShowcase;
use App\Support\ZumraDomainPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * UIUX-010 — le carrefour ZUMRA. Chaque bloc réutilise une capacité déjà confirmée par l'audit
 * (adhésion, décisions, activités dérivées, domaines) ; les deux seules surfaces sans métier réel
 * derrière elles (Fil ZUMRA détaillé, proximité géographique) sont explicitement documentées
 * comme vitrines en attendant leur moteur, jamais présentées comme le produit final.
 */
final class ZumraSpaceController
{
    public function __invoke(Request $request, ZumraAttentionSource $zumraAttention): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $query = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $mode = in_array($request->query('mode'), ['PHYSICAL', 'DIGITAL', 'HYBRID'], true) ? $request->query('mode') : null;
        $location = is_string($request->query('location')) ? trim($request->query('location')) : '';
        $personalFilter = in_array($request->query('view'), ['mine', 'invited', 'requested'], true) ? $request->query('view') : null;

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

        $pendingRequestsToDecide = $zumraAttention->pendingJoinRequestsForLeader($identity->reference)
            ->groupBy(fn (array $row): string => $row['group']->id)
            ->map(fn (Collection $rows): array => [
                'group' => $rows->first()['group'],
                'count' => $rows->count(),
            ])
            ->values();

        $myPendingRoleProposals = $zumraAttention->myPendingRoleProposals($identity->reference);

        // « À faire maintenant » reste volontairement court dans son détail (deux éléments
        // maximum, jamais un score) ; le badge de navigation porte lui le total réel.
        $attentionItems = $this->attentionItems($myPendingRoleProposals, $pendingRequestsToDecide, $myGroups);
        $attentionTotal = $myPendingRoleProposals->count() + (int) $pendingRequestsToDecide->sum('count')
            + $myGroups->where('status', ZumraGroupMembership::STATUS_INVITED)->count();

        $navCounts = [
            'mine' => $myGroups->where('status', ZumraGroupMembership::STATUS_ACTIVE)->count(),
            'invitations' => $myGroups->where('status', ZumraGroupMembership::STATUS_INVITED)->count(),
            'requests' => $myGroups->where('status', ZumraGroupMembership::STATUS_REQUESTED)->count(),
            'attention' => $attentionTotal,
        ];

        $discoverDomains = ZumraGroup::query()
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->selectRaw('domain, COUNT(*) AS groups_count')
            ->groupBy('domain')
            ->orderByDesc('groups_count')
            ->orderBy('domain')
            ->limit(8)
            ->get()
            ->map(fn (ZumraGroup $row): array => [
                'domain' => $row->domain,
                'count' => (int) $row->getAttribute('groups_count'),
                'icon_key' => ZumraDomainPresentation::key($row->domain),
            ]);

        $popularActivities = ZumraGroupActivity::query()
            ->selectRaw('label, COUNT(*) AS uses_count')
            ->groupBy('label')
            ->orderByDesc('uses_count')
            ->orderBy('label')
            ->limit(6)
            ->get()
            ->pluck('label');

        $discoverGroups = $this->diverseDiscoverGroups(8, $identity->reference, $query, $mode, $location, $personalFilter)
            ->map(fn (ZumraGroup $group): array => [
                'group' => $group,
                'cover' => ZumraDomainPresentation::cover($group->domain),
                'initials' => mb_strtoupper(mb_substr($group->name, 0, 1)),
                'mode_label' => match ($group->participation_mode) {
                    'PHYSICAL' => 'Physique', 'DIGITAL' => 'Numérique', default => 'Hybride',
                },
                'welcome_open' => in_array($group->welcome_capacity, [ZumraGroup::WELCOME_ALREADY_CAPABLE, ZumraGroup::WELCOME_PROGRESSIVELY], true),
            ]);

        $fil = $this->filPanel();
        $stats = $this->stats();
        $nearby = ZumraProximityShowcase::query()->orderBy('sort_order')->limit(4)->get();

        return view('zumra.index', compact(
            'identity', 'profile', 'membership', 'isAdministrator', 'myGroups', 'navCounts',
            'pendingRequestsToDecide', 'attentionItems', 'discoverDomains', 'popularActivities',
            'discoverGroups', 'fil', 'stats', 'nearby', 'query', 'mode', 'location', 'personalFilter',
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

    /**
     * « ZUMRA à découvrir » privilégie la diversité des activités représentées (une ZUMRA par
     * domaine avant d'en montrer une seconde) plutôt qu'un simple tri chronologique ou par
     * taille, pour que la découverte reste un vrai aperçu du réseau plutôt qu'un domaine unique
     * qui écraserait les autres.
     */
    private function diverseDiscoverGroups(int $limit, string $identityReference, string $query, ?string $mode, string $location, ?string $personalFilter): Collection
    {
        $candidates = ZumraGroup::query()
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query): void {
                $like = '%'.$query.'%';
                $builder->whereRaw('LOWER(domain) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(name) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(founding_objective) LIKE LOWER(?)', [$like]);
            }))
            ->when($mode !== null, fn ($builder) => $builder->where('participation_mode', $mode))
            ->when($location !== '', fn ($builder) => $builder->whereRaw('LOWER(location) LIKE LOWER(?)', ['%'.$location.'%']))
            ->when($personalFilter !== null, function ($builder) use ($personalFilter, $identityReference): void {
                $status = match ($personalFilter) {
                    'invited' => ZumraGroupMembership::STATUS_INVITED,
                    'requested' => ZumraGroupMembership::STATUS_REQUESTED,
                    default => ZumraGroupMembership::STATUS_ACTIVE,
                };
                $builder->whereHas('memberships', fn ($memberships) => $memberships
                    ->where('core_identity_reference', $identityReference)
                    ->where('status', $status));
            })
            ->oldest()
            ->limit($limit * 4)
            ->get();

        $seenDomains = [];
        $primary = collect();
        $rest = collect();
        foreach ($candidates as $group) {
            $domainKey = mb_strtolower(trim((string) $group->domain));
            if ($domainKey !== '' && ! isset($seenDomains[$domainKey])) {
                $seenDomains[$domainKey] = true;
                $primary->push($group);
            } else {
                $rest->push($group);
            }
        }

        return $primary->concat($rest)->take($limit)->values();
    }

    /**
     * Le Fil ZUMRA détaillé (un fil dédié, filtrable, commentable) reste une direction produit
     * documentée, pas un chantier de cette mission : ce panneau ne fait qu'orienter vers la vue
     * déjà réelle du Fil global filtrée par type ZUMRA (`activity.index`), jamais un second Fil.
     *
     * @return array{href: string, avatars: list<string>, remainder: int}
     */
    private function filPanel(): array
    {
        $recentMembers = ZumraGroupMembership::query()
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->join('dg_person_profiles', 'dg_person_profiles.core_identity_reference', '=', 'dg_zumra_group_memberships.core_identity_reference')
            ->where('dg_person_profiles.discovery_consent', true)
            ->orderByDesc('dg_zumra_group_memberships.joined_at')
            ->limit(60)
            ->pluck('dg_person_profiles.discovery_display_name', 'dg_zumra_group_memberships.core_identity_reference')
            ->unique();

        $shown = $recentMembers->take(4);

        return [
            'href' => route('activity.index', ['type' => 'ZUMRA']),
            'avatars' => $shown->map(fn (string $name): string => mb_strtoupper(mb_substr($name, 0, 1)))->values()->all(),
            'remainder' => max(0, $recentMembers->count() - $shown->count()),
        ];
    }

    /**
     * Statistiques réelles, jamais des nombres fabriqués : elles reflètent exactement ce que
     * contient la base au moment de l'affichage — vides sur un portail neuf, vivantes une fois
     * `ZumraWorldDemoSeeder` exécuté sur un environnement de démonstration.
     *
     * @return array{groups: int, groups_delta: int, members: int, members_delta: int, domains: int, actions: int}
     */
    private function stats(): array
    {
        $activeGroups = ZumraGroup::query()->where('state', '!=', ZumraGroup::STATE_SUSPENDED);

        $needsOpen = Need::query()->where('owner_type', Need::OWNER_GROUP)->where('status', Need::STATUS_OPEN)->count();
        $projectsOngoing = Project::query()->where('owner_type', Project::OWNER_GROUP)->whereIn('status', [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS])->count();
        $eventsScheduled = CommunityEvent::query()->where('organizer_type', CommunityEvent::ORGANIZER_ZUMRA_GROUP)->where('status', CommunityEvent::STATUS_SCHEDULED)->count();

        return [
            'groups' => (clone $activeGroups)->count(),
            'groups_delta' => (clone $activeGroups)->where('created_at', '>=', now()->subDays(30))->count(),
            'members' => (int) (clone $activeGroups)->sum('active_member_count'),
            'members_delta' => ZumraGroupMembership::query()->where('status', ZumraGroupMembership::STATUS_ACTIVE)->where('joined_at', '>=', now()->subDays(30))->count(),
            'domains' => (int) (clone $activeGroups)->whereNotNull('domain')->where('domain', '!=', '')->distinct()->count('domain'),
            'actions' => $needsOpen + $projectsOngoing + $eventsScheduled,
        ];
    }
}
