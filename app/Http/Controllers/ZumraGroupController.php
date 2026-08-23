<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Community\CommunityEventService;
use App\Application\Missions\MissionService;
use App\Application\Needs\NeedService;
use App\Application\Partnerships\PartnershipService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\CollectiveCapabilityConfiguration;
use App\Application\Zumra\CollectiveCapabilityProfile;
use App\Application\Zumra\ZumraGroupConfiguration;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Http\Controllers\Concerns\PresentsPartnerships;
use App\Models\Need;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ZumraGroupController
{
    use PresentsPartnerships;

    public function index(Request $request, ZumraGroupConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $membership = $this->programMembership($identity->reference);
        $groups = ZumraGroup::query()->whereNotIn('state', [ZumraGroup::STATE_SUSPENDED])->latest()->paginate(12);
        $myMemberships = ZumraGroupMembership::query()->where('core_identity_reference', $identity->reference)->whereIn('status', [ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::STATUS_INVITED, ZumraGroupMembership::STATUS_REQUESTED])->pluck('status', 'zumra_group_id');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('zumra.groups.index', compact('identity', 'membership', 'groups', 'myMemberships', 'isAdministrator') + ['configuration' => $configuration->get()]);
    }

    public function create(Request $request, ZumraGroupConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $this->requireActiveProgramMembership($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('zumra.groups.create', compact('identity', 'isAdministrator') + ['configuration' => $configuration->get(), 'welcomeCapacities' => ZumraGroup::WELCOME_CAPACITIES]);
    }

    public function store(Request $request, ZumraGroupConfiguration $configuration, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $this->requireActiveProgramMembership($identity->reference);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:140', Rule::unique('dg_zumra_groups', 'name')],
            'domain' => ['required', 'string', 'min:3', 'max:140'],
            'founding_objective' => ['required', 'string', 'min:40', 'max:1800'],
            'participation_mode' => ['required', Rule::in(['PHYSICAL', 'DIGITAL', 'HYBRID'])],
            'welcome_capacity' => ['nullable', Rule::in(array_keys(ZumraGroup::WELCOME_CAPACITIES))],
            'location' => ['nullable', 'string', 'max:160'],
            'internal_charter' => ['nullable', 'string', 'min:80', 'max:6000'],
            'assume_primary_lead' => ['nullable', 'boolean'],
            'activity_label' => ['nullable', 'array'],
            'activity_label.*' => ['nullable', 'string', 'max:140'],
            'activity_relation' => ['nullable', 'array'],
            'activity_relation.*' => ['nullable', 'string', 'max:600'],
        ]);
        $data['assume_primary_lead'] = $request->boolean('assume_primary_lead');
        $data['activities'] = $this->derivedActivitiesFromRequest($data);
        $group = $service->create($identity->reference, $data, (int) $configuration->get()['max_simultaneous_founder_roles']);

        return redirect()->route('zumra.groups.show', $group)->with('status', 'Votre ZUMRA est née. Aucun rôle vacant n’a été attribué automatiquement.');
    }

    public function setCharter(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['internal_charter' => ['required', 'string', 'min:80', 'max:6000']]);
        $service->setCharter($group, $identity->reference, $data['internal_charter']);

        return back()->with('status', 'Votre charte interne est enregistrée.');
    }

    public function addActivity(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'label' => ['required', 'string', 'max:140'],
            'relation_to_principal' => ['required', 'string', 'max:600'],
        ]);
        $service->addActivity($group, $identity->reference, $data['label'], $data['relation_to_principal']);

        return back()->with('status', 'Activité ajoutée.');
    }

    /** @return list<array{label: string, relation_to_principal: string}> */
    private function derivedActivitiesFromRequest(array $data): array
    {
        $labels = $data['activity_label'] ?? [];
        $relations = $data['activity_relation'] ?? [];
        $activities = [];
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            $relation = trim((string) ($relations[$index] ?? ''));
            if ($label === '' || $relation === '') {
                continue;
            }
            $activities[] = ['label' => $label, 'relation_to_principal' => $relation];
        }

        return $activities;
    }

    public function show(
        Request $request,
        ZumraGroup $group,
        ZumraGroupService $service,
        CollectiveCapabilityConfiguration $capabilityConfiguration,
        CollectiveCapabilityProfile $capabilityProfile,
        NeedService $needs,
        ProjectService $projects,
        MissionService $missions,
        CommunityEventService $events,
        PartnershipService $partnerships,
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED && ! $service->isLeader($group, $identity->reference), 404);
        $isLeader = $service->isLeader($group, $identity->reference);
        $membership = $group->memberships()->where('core_identity_reference', $identity->reference)->first();
        $roles = $group->roles()->orderByRaw("case role when 'PRIMARY_LEAD' then 1 when 'FIRST_DEPUTY' then 2 when 'SECOND_DEPUTY' then 3 when 'FINANCE_LEAD' then 4 else 5 end")->get();
        $roleProfiles = PersonProfile::query()->whereIn('core_identity_reference', $roles->pluck('core_identity_reference')->filter())->get()->keyBy('core_identity_reference');

        // UIUX-002 — décision #4 : découvrir/comprendre/accepter une responsabilité qui vous est
        // personnellement proposée, directement sur la fiche de la ZUMRA — aucune requête
        // supplémentaire, dérivé de $roles déjà chargé.
        $myPendingRoleProposal = $roles->first(
            fn (ZumraGroupRole $role): bool => $role->status === ZumraGroupRole::STATUS_PROPOSED
                && $role->core_identity_reference !== null
                && hash_equals($role->core_identity_reference, $identity->reference),
        );
        $pendingRequests = $isLeader
            ? $group->memberships()->where('status', ZumraGroupMembership::STATUS_REQUESTED)->oldest('requested_at')->get()
            : collect();
        $requestProfiles = PersonProfile::query()->whereIn('core_identity_reference', $pendingRequests->pluck('core_identity_reference'))->get()->keyBy('core_identity_reference');
        $collectiveCapabilitySettings = $capabilityConfiguration->get();
        $collectiveCapabilities = $capabilityProfile->forGroup($group, $collectiveCapabilitySettings);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // CAP-037 : la ZUMRA comme micro-espace de travail — ce qu'elle porte réellement devient
        // visible, filtré par les autorités déjà réelles de Need/Project, jamais une nouvelle
        // autorité fabriquée ici.
        $groupNeeds = Need::query()
            ->where('owner_type', Need::OWNER_GROUP)->where('owner_reference', $group->id)
            ->where('status', '!=', Need::STATUS_ARCHIVED)
            ->latest('created_at')->limit(20)->get()
            ->filter(fn (Need $need): bool => $needs->canView($need, $identity->reference))
            ->values();
        $groupProjects = Project::query()
            ->where('owner_type', Project::OWNER_GROUP)->where('owner_reference', $group->id)
            ->where('status', '!=', Project::STATUS_ARCHIVED)
            ->latest('created_at')->limit(20)->get()
            ->filter(fn (Project $project): bool => $projects->canView($project, $identity->reference))
            ->values();

        $canSetCharter = $isLeader && $group->state === ZumraGroup::STATE_CONSTITUTING && trim((string) $group->internal_charter) === '';
        $collectivePriority = $isLeader ? $this->collectivePriority($group, $pendingRequests, $groupNeeds, $groupProjects, $canSetCharter) : null;

        // UIUX-003 — décision #2 : Missions et Événements réellement rattachés à cette ZUMRA
        // (relations backend déjà prouvées : Mission.context_type=ZUMRA, CommunityEvent
        // organizer_type=ZUMRA_GROUP), affichés seulement pour un membre actif ou responsable —
        // exactement la même autorité que ces deux services exigent déjà eux-mêmes (jamais
        // recalculée ici, jamais assouplie pour peupler la page).
        $isActiveMember = $membership?->status === ZumraGroupMembership::STATUS_ACTIVE;
        $groupMissions = $isActiveMember || $isLeader
            ? $missions->forContext('ZUMRA', $group->public_reference, $identity->reference)->take(6)
            : collect();
        $groupEvents = $isActiveMember || $isLeader
            ? $events->forZumraGroup($group, $identity->reference)->take(6)
            : collect();

        // UIUX-005 — Partenariats réellement associés à cette ZUMRA (CAP-065), visibles seulement
        // pour un membre actif ou responsable — même autorité que Missions/Événements ci-dessus,
        // jamais assouplie pour peupler la page.
        $groupPartnerships = $isActiveMember || $isLeader
            ? Partnership::query()
                ->where('context_type', Partnership::CONTEXT_ZUMRA)
                ->where('context_reference', $group->public_reference)
                ->latest('created_at')
                ->limit(20)
                ->get()
                ->filter(fn (Partnership $partnership): bool => $partnerships->canView($partnership, $identity->reference))
                ->values()
            : collect();
        $presentedPartnerships = $this->presentPartnerships($groupPartnerships, $identity->reference, $partnerships);
        $manageableOrganizations = $this->manageableOrganizations($identity->reference);
        $manageableOrganizationCapabilities = $this->manageableOrganizationCapabilities($identity->reference);

        $activities = $group->activities()->latest('created_at')->get();

        return view('zumra.groups.show', compact(
            'identity', 'group', 'membership', 'roles', 'roleProfiles', 'pendingRequests', 'requestProfiles',
            'collectiveCapabilitySettings', 'collectiveCapabilities', 'isAdministrator', 'groupNeeds', 'groupProjects',
            'collectivePriority', 'myPendingRoleProposal', 'groupMissions', 'groupEvents', 'activities', 'canSetCharter',
        ) + ['isLeader' => $isLeader, 'groupPartnerships' => $presentedPartnerships, 'manageableOrganizations' => $manageableOrganizations, 'manageableOrganizationCapabilities' => $manageableOrganizationCapabilities]);
    }

    /**
     * CAP-038 : une seule priorité collective dominante, jamais un mur de statistiques — même
     * patron que MemberSpaceController::priority(), scopé au groupe plutôt qu'à l'individu.
     * Aucun siège vacant ici : aucune action réelle n'existe encore pour en proposer un (§2 de
     * la fiche), une priorité doit toujours pointer vers une action réellement exécutable.
     *
     * @param  Collection<int, ZumraGroupMembership>  $pendingRequests
     * @param  Collection<int, Need>  $groupNeeds
     * @param  Collection<int, Project>  $groupProjects
     */
    private function collectivePriority(ZumraGroup $group, Collection $pendingRequests, Collection $groupNeeds, Collection $groupProjects, bool $canSetCharter): ?array
    {
        if ($pendingRequests->isNotEmpty()) {
            return [
                'heading' => 'Une demande d’adhésion attend une décision.',
                'body' => 'Une personne souhaite rejoindre cette ZUMRA.',
                'primary' => ['label' => 'Voir les demandes', 'href' => route('zumra.groups.show', $group).'#demandes'],
            ];
        }

        $proposedNeed = $groupNeeds->first(fn (Need $need): bool => $need->status === Need::STATUS_PROPOSED);
        if ($proposedNeed) {
            return [
                'heading' => 'Le besoin « '.$proposedNeed->title.' » attend une décision de publication.',
                'body' => 'Il a été proposé par un membre et n’est pas encore publié.',
                'primary' => ['label' => 'Décider', 'href' => route('needs.show', $proposedNeed)],
            ];
        }

        $proposedProject = $groupProjects->first(fn (Project $project): bool => $project->status === Project::STATUS_PROPOSED);
        if ($proposedProject) {
            return [
                'heading' => 'Le projet « '.$proposedProject->name.' » attend une décision d’adoption.',
                'body' => 'Il a été proposé par un membre et n’est pas encore adopté.',
                'primary' => ['label' => 'Décider', 'href' => route('projects.show', $proposedProject)],
            ];
        }

        if ($canSetCharter) {
            return [
                'heading' => 'Votre charte interne n’est pas encore écrite.',
                'body' => 'Elle n’était pas requise pour naître — elle le devient pour rendre cette ZUMRA prête à valider.',
                'primary' => ['label' => 'Rédiger la charte', 'href' => route('zumra.groups.show', $group).'#charte'],
            ];
        }

        return null;
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

    public function proposeRole(Request $request, ZumraGroup $group, string $role, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless(array_key_exists($role, ZumraGroupRole::LABELS), 422, 'Responsabilité inconnue.');
        $data = $request->validate(['person_reference' => ['required', 'uuid']]);
        $profile = PersonProfile::query()->where('discovery_reference', $data['person_reference'])->where('discovery_consent', true)->firstOrFail();
        $service->proposeRole($group, $identity->reference, $role, $profile->core_identity_reference);

        return back()->with('status', 'Responsabilité proposée. Elle ne sera occupée qu’après acceptation explicite.');
    }

    public function acceptRole(Request $request, ZumraGroup $group, string $role, ZumraGroupConfiguration $configuration, ZumraGroupService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless(array_key_exists($role, ZumraGroupRole::LABELS), 422, 'Responsabilité inconnue.');
        $settings = $configuration->get();
        $service->acceptRole($group, $identity->reference, $role, (int) $settings['max_simultaneous_founder_roles'], (bool) $settings['auto_validation_enabled']);

        return back()->with('status', 'Vous occupez désormais cette responsabilité.');
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
