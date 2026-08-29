<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Needs\NeedService;
use App\Application\Partnerships\PartnershipService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectFundingContributionService;
use App\Application\Projects\ProjectHubPresentation;
use App\Application\Projects\ProjectMaturityService;
use App\Application\Projects\ProjectService;
use App\Application\Projects\ProjectSignalsEngine;
use App\Domain\Identity\CoreIdentity;
use App\Http\Controllers\Concerns\PresentsPartnerships;
use App\Models\Need;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectFunding;
use App\Models\ProjectMilestone;
use App\Models\ProjectTeamMember;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProjectController
{
    use PresentsPartnerships;

    // UIUX-009B — la création (create/store) vit désormais dans ProjectDraftController, parcours
    // progressif et sauvegardable indépendant du Cerveau. « projects.create » (route publique)
    // pointe vers ce nouveau contrôleur ; index/show/transition/maturity restent ici, inchangés.
    public function index(Request $request, ProjectConfiguration $configuration, ProjectService $service, ProjectHubPresentation $presentation): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $query = Project::query()->where('status', '!=', Project::STATUS_ARCHIVED)->latest();
        if ($request->filled('q')) {
            $term = Str::limit((string) $request->query('q'), 120, '');
            // Le JSON required_capabilities n’est volontairement pas interrogé comme texte :
            // SQLite et PostgreSQL n’ont pas la même sémantique. L’index de compétences reste
            // une capacité future, sans requête fragile ni promesse fabriquée ici.
            $query->where(fn ($builder) => $builder->where('name', 'like', '%'.$term.'%')->orWhere('summary', 'like', '%'.$term.'%'));
        }
        if ($request->filled('domain') && isset($settings['domains'][(string) $request->query('domain')])) {
            $query->where('domain', (string) $request->query('domain'));
        }
        if ($request->filled('status') && in_array($request->query('status'), [Project::STATUS_PROPOSED, Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED], true)) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('country')) {
            $query->where('location', 'like', '%'.Str::limit((string) $request->query('country'), 80, '').'%');
        }
        if ($request->filled('group')) {
            $groupId = ZumraGroup::query()->where('public_reference', $request->query('group'))->value('id');
            $query->where('zumra_group_id', $groupId ?: '00000000-0000-0000-0000-000000000000');
        }
        $visible = $query->limit(300)->get()->filter(fn (Project $project) => $service->canView($project, $identity->reference))->values();
        $allVisible = Project::query()
            ->where('status', '!=', Project::STATUS_ARCHIVED)
            ->latest()
            ->limit(300)
            ->get()
            ->filter(fn (Project $project): bool => $service->canView($project, $identity->reference))
            ->values();
        $page = max(1, (int) $request->query('page', 1));
        $projects = new LengthAwarePaginator($visible->forPage($page, 8), $visible->count(), 8, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $groups = ZumraGroup::query()->whereIn('id', $visible->pluck('zumra_group_id')->filter())->get()->keyBy('id');
        $memberCounts = ProjectTeamMember::query()->whereIn('project_id', $visible->pluck('id'))->where('status', ProjectTeamMember::STATUS_ACTIVE)->selectRaw('project_id, count(*) as aggregate')->groupBy('project_id')->pluck('aggregate', 'project_id');
        $cards = $projects->getCollection()->mapWithKeys(fn (Project $project) => [$project->id => $presentation->for($project, (int) ($memberCounts[$project->id] ?? 0))]);
        $filterGroups = ZumraGroup::query()->whereIn('id', $allVisible->pluck('zumra_group_id')->filter())->orderBy('name')->limit(100)->get();
        $filterLocations = $allVisible->pluck('location')->filter()->map(fn (string $location): string => trim($location))->unique()->sort()->values();
        $categoryDistribution = $allVisible->groupBy('domain')->map(fn ($projects, string $domain): array => [
            'code' => $domain,
            'label' => $settings['domains'][$domain] ?? $domain,
            'count' => $projects->count(),
        ])->sortByDesc('count')->values();
        $recentProjects = $allVisible->take(3);
        $recentGroups = ZumraGroup::query()->whereIn('id', $recentProjects->pluck('zumra_group_id')->filter())->get()->keyBy('id');
        $activeProjectIds = $allVisible
            ->whereIn('status', [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS])
            ->pluck('id');
        $networkStats = [
            'projects' => $activeProjectIds->count(),
            'groups' => $allVisible->pluck('zumra_group_id')->filter()->unique()->count(),
            'members' => ProjectTeamMember::query()->whereIn('project_id', $activeProjectIds)->where('status', ProjectTeamMember::STATUS_ACTIVE)->distinct()->count('core_identity_reference'),
            'completed' => $allVisible->where('status', Project::STATUS_COMPLETED)->count(),
        ];
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.index', compact(
            'identity', 'projects', 'groups', 'cards', 'filterGroups', 'filterLocations',
            'categoryDistribution', 'recentProjects', 'recentGroups', 'networkStats', 'isAdministrator',
        ) + ['configuration' => $settings]);
    }

    public function show(Request $request, Project $project, ProjectConfiguration $configuration, ProjectService $service, NeedService $needs, ProjectSignalsEngine $signalsEngine, PartnershipService $partnerships, MissionContextRegistry $missionContexts, ProjectFundingContributionService $fundingContributions): View
    {/** @var CoreIdentity $identity */ $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($project, $identity->reference), 404);
        $group = $project->owner_type === Project::OWNER_GROUP ? ZumraGroup::query()->find($project->owner_reference) : null;
        $maturityHistory = $project->events()->where('event', 'PROJECT_MATURITY_CHANGED')->orderByDesc('occurred_at')->get();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $canDecide = $service->canDecide($project, $identity->reference);
        // PROJECT-FUNDING-002 — déclaration CAP-063 la plus récente + montants dérivés du Ledger
        // (ProjectFundingContributionService), jamais un compteur stocké. Jeton de contribution
        // frais à CHAQUE affichage : un double clic/retry réutilise ce même jeton (idempotence),
        // un nouvel affichage de page en reçoit un nouveau (nouvelle contribution légitime).
        $funding = ProjectFunding::query()->where('project_id', $project->id)->latest('created_at')->first();
        $fundingCollected = $funding ? $fundingContributions->collectedAmount($funding, $project) : 0;
        $fundingRemaining = $funding ? max(0, $funding->target_amount - $fundingCollected) : 0;
        $fundingHistory = $funding ? $fundingContributions->history($funding) : collect();
        $fundingContributorProfiles = PersonProfile::query()->whereIn('core_identity_reference', $fundingHistory->pluck('subject_reference'))->get()->keyBy('core_identity_reference');
        $fundingContributionToken = (string) Str::uuid();
        // UIUX-007 — porte visible vers la création de Mission déjà existante (CAP-069), jamais dupliquée : mêmes conditions que le Cerveau.
        $canProposeMission = $missionContexts->for('PROJECT')->canPropose($project, $identity->reference);
        // UIUX-005 — Partenariats réellement associés à ce Projet, filtrés par la même autorité
        // que le service lui-même (PartnershipService::canView()), jamais recalculée en Blade.
        $projectPartnerships = Partnership::query()->where('context_type', Partnership::CONTEXT_PROJECT)->where('context_reference', $project->public_reference)->latest('created_at')->limit(20)->get()->filter(fn (Partnership $p): bool => $partnerships->canView($p, $identity->reference))->values();
        $presentedPartnerships = $this->presentPartnerships($projectPartnerships, $identity->reference, $partnerships);
        $manageableOrganizations = $this->manageableOrganizations($identity->reference);
        $manageableOrganizationCapabilities = $this->manageableOrganizationCapabilities($identity->reference);
        // CAP-041 : équipe projet — adhésion réelle, distincte du masquage de suggestion géré par ProjectMatchDecision.
        $teamMembers = ProjectTeamMember::query()->where('project_id', $project->id)->where('status', ProjectTeamMember::STATUS_ACTIVE)->get();
        $myTeamMembership = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $identity->reference)->first();
        $pendingTeamRequests = $canDecide ? ProjectTeamMember::query()->where('project_id', $project->id)->where('status', ProjectTeamMember::STATUS_REQUESTED)->oldest('requested_at')->get() : collect();
        $teamProfiles = PersonProfile::query()->whereIn('core_identity_reference', $teamMembers->pluck('core_identity_reference')->merge($pendingTeamRequests->pluck('core_identity_reference')))->get()->keyBy('core_identity_reference');
        // CAP-042 : besoins vivants du projet, distincts de l'instantané figé required_capabilities/required_resources.
        $projectNeeds = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)->where('status', '!=', Need::STATUS_ARCHIVED)->latest('created_at')->limit(20)->get()->filter(fn (Need $n) => $needs->canView($n, $identity->reference))->values();
        $canProposeNeed = $myTeamMembership?->status === ProjectTeamMember::STATUS_ACTIVE || $project->initiator_core_reference === $identity->reference || ($project->owner_type === Project::OWNER_PERSON && $project->owner_reference === $identity->reference);
        // CAP-044 : signaux consultatifs uniquement, jamais un score, jamais une écriture sur maturity.
        $maturitySignals = $signalsEngine->forProject($project);
        $accompaniment = $canDecide ? $project->accompaniment : null;
        // Fiche V2 (« Activité récente ») : les 6 derniers événements réels du projet, jamais une reconstruction fictive.
        $recentEvents = $project->events()->latest('occurred_at')->limit(6)->get();
        $eventActorProfiles = PersonProfile::query()->whereIn('core_identity_reference', $recentEvents->pluck('actor_core_reference'))->get()->keyBy('core_identity_reference');
        $lastActivityAt = $recentEvents->first()?->occurred_at ?? $project->created_at;
        $progressPercentage = $project->milestoneProgressPercentage();

        return view('projects.show', compact('identity', 'project', 'group', 'maturityHistory', 'isAdministrator', 'teamMembers', 'myTeamMembership', 'pendingTeamRequests', 'teamProfiles', 'projectNeeds', 'canProposeNeed', 'canProposeMission', 'maturitySignals', 'accompaniment', 'recentEvents', 'eventActorProfiles', 'lastActivityAt', 'progressPercentage', 'funding', 'fundingCollected', 'fundingRemaining', 'fundingHistory', 'fundingContributorProfiles', 'fundingContributionToken') + ['configuration' => $configuration->get(), 'canDecide' => $canDecide, 'maturityStages' => ProjectMaturityService::STAGES, 'projectPartnerships' => $presentedPartnerships, 'manageableOrganizations' => $manageableOrganizations, 'manageableOrganizationCapabilities' => $manageableOrganizationCapabilities]);
    }

    public function transition(Request $request, Project $project, ProjectService $service): RedirectResponse
    {/** @var CoreIdentity $identity */ $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['status' => ['required', Rule::in([Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED, Project::STATUS_ARCHIVED])]]);
        $service->transition($project, $identity->reference, $data['status']);

        return back()->with('status', 'L’évolution du statut du projet est enregistrée et traçable. La maturité se gère séparément.');
    }

    public function maturity(Request $request, Project $project, ProjectMaturityService $service): RedirectResponse
    {/** @var CoreIdentity $identity */ $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['maturity' => ['required', Rule::in(array_keys(ProjectMaturityService::STAGES))], 'note' => ['nullable', 'string', 'max:1200']]);
        $service->change($project, $identity->reference, $data['maturity'], $data['note'] ?? null);

        return back()->with('status', 'Repère de maturité mis à jour. Il ne constitue ni statut juridique ni décision institutionnelle.');
    }

    // BETA-READY-004 (LOT 3) — clôture minimale d'un jalon existant, réservée à l'autorité du Projet (ProjectService::canDecide()).
    public function completeMilestone(Request $request,Project $project,ProjectMilestone $milestone,ProjectService $service): RedirectResponse
    {/** @var CoreIdentity $identity */ $identity = $request->attributes->get('dg_identity');
        $service->completeMilestone($project,$milestone,$identity->reference);

        return back()->with('status','Le jalon est marqué accompli.');
    }
}
