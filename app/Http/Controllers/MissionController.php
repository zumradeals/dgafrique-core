<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionService;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionChecklistItem;
use App\Models\MissionRecurrence;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class MissionController
{
    private const DISCOVERY_STATUSES = [
        Mission::STATUS_OPEN, Mission::STATUS_IN_PROGRESS, Mission::STATUS_BLOCKED,
        Mission::STATUS_SUBMITTED, Mission::STATUS_COMPLETED,
    ];

    private const DISCOVERY_PAGE_SIZE = 10;

    private const DUE_SOON_DAYS = 7;

    public function index(Request $request, MissionService $missions, MissionVisibilityService $visibility, ProjectConfiguration $projectConfiguration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        if ($request->query('scope') === 'mine') {
            $sections = $missions->myMissionsSections($identity->reference);

            return view('missions.mine', compact('identity', 'sections', 'isAdministrator'));
        }

        // UX-HARMONY-MISSIONS-001 — annuaire public des Missions, même doctrine de visibilité que
        // MissionService::forContext() : chaque Mission candidate est revalidée avec
        // MissionVisibilityService::canViewMission(), jamais un simple filtre de statut. Le domaine
        // n'existe pas comme colonne Mission : il est dérivé honnêtement du contexte porteur
        // (Project::domain / ZumraGroup::domain) ; un contexte Besoin n'a pas d'équivalent et
        // rejoint « Sans domaine identifié » plutôt qu'une valeur inventée.
        $statusFilter = (string) $request->query('status', '');
        $domainFilter = (string) $request->query('domain', '');
        $locationFilter = (string) $request->query('location', '');
        $searchTerm = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $candidates = Mission::query()
            ->whereIn('status', self::DISCOVERY_STATUSES)
            ->whereNull('parent_mission_id')
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->filter(fn (Mission $mission): bool => $visibility->canViewMission($mission, $identity->reference))
            ->values();

        $domainLabels = $projectConfiguration->get()['domains'];
        $projectDomains = Project::query()
            ->whereIn('public_reference', $candidates->where('context_type', 'PROJECT')->pluck('context_reference'))
            ->pluck('domain', 'public_reference');
        $zumraDomains = ZumraGroup::query()
            ->whereIn('public_reference', $candidates->where('context_type', 'ZUMRA')->pluck('context_reference'))
            ->pluck('domain', 'public_reference');

        $domainOf = function (Mission $mission) use ($projectDomains, $zumraDomains, $domainLabels): string {
            return match ($mission->context_type) {
                'PROJECT' => $domainLabels[$projectDomains[$mission->context_reference] ?? ''] ?? 'Sans domaine identifié',
                'ZUMRA' => $zumraDomains[$mission->context_reference] ?? 'Sans domaine identifié',
                default => 'Sans domaine identifié',
            };
        };

        $bandeau = [
            'total' => $candidates->count(),
            'open' => $candidates->where('status', Mission::STATUS_OPEN)->count(),
            'in_progress' => $candidates->where('status', Mission::STATUS_IN_PROGRESS)->count(),
            'completed' => $candidates->where('status', Mission::STATUS_COMPLETED)->count(),
            'blocked' => $candidates->where('status', Mission::STATUS_BLOCKED)->count(),
        ];

        $contributorsThisMonth = MissionAssignment::query()
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('mission_id', $candidates->pluck('id'))
            ->where('accepted_at', '>=', now()->startOfMonth())
            ->distinct('core_identity_reference')
            ->count('core_identity_reference');

        $byDomain = $candidates->groupBy($domainOf)->map->count()->sortDesc();
        $byLocation = $candidates->filter(fn (Mission $m): bool => filled($m->location))->groupBy('location')->map->count()->sortDesc();

        $dueSoon = $candidates
            ->filter(fn (Mission $m): bool => $m->due_at !== null && $m->due_at->isFuture() && $m->due_at->diffInDays(now()) <= self::DUE_SOON_DAYS)
            ->sortBy(fn (Mission $m) => $m->due_at)
            ->values();

        $filtered = $candidates
            ->when($statusFilter !== '', fn (Collection $c) => $c->where('status', $statusFilter))
            ->when($domainFilter !== '', fn (Collection $c) => $c->filter(fn (Mission $m): bool => $domainOf($m) === $domainFilter))
            ->when($locationFilter !== '', fn (Collection $c) => $c->where('location', $locationFilter))
            ->when($searchTerm !== '', fn (Collection $c) => $c->filter(fn (Mission $m): bool => str_contains(mb_strtolower($m->title), mb_strtolower($searchTerm))
                || str_contains(mb_strtolower($m->description), mb_strtolower($searchTerm))))
            ->values();

        $pageItems = $filtered->forPage($page, self::DISCOVERY_PAGE_SIZE)->values();
        $checklistStats = MissionChecklistItem::query()
            ->whereIn('mission_id', $pageItems->pluck('id'))
            ->get()
            ->groupBy('mission_id')
            ->map(fn (Collection $items): array => ['total' => $items->count(), 'completed' => $items->whereNotNull('completed_at')->count()]);
        $contributorCounts = MissionAssignment::query()
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('mission_id', $pageItems->pluck('id'))
            ->get()
            ->groupBy('mission_id')
            ->map->count();

        $missionsPage = new LengthAwarePaginator($pageItems, $filtered->count(), self::DISCOVERY_PAGE_SIZE, $page, [
            'path' => $request->url(),
            'query' => $request->except('page'),
        ]);

        $mineAssignedCount = MissionAssignment::query()
            ->where('core_identity_reference', $identity->reference)
            ->distinct('mission_id')
            ->count('mission_id');
        $mineEngagedCount = MissionAssignment::query()
            ->where('core_identity_reference', $identity->reference)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereHas('mission', fn ($q) => $q->whereIn('status', [Mission::STATUS_OPEN, Mission::STATUS_IN_PROGRESS, Mission::STATUS_SUBMITTED]))
            ->distinct('mission_id')
            ->count('mission_id');

        return view('missions.index', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'missionsPage' => $missionsPage,
            'bandeau' => $bandeau,
            'contributorsThisMonth' => $contributorsThisMonth,
            'byDomain' => $byDomain,
            'byLocation' => $byLocation,
            'dueSoon' => $dueSoon,
            'statusFilter' => $statusFilter,
            'domainFilter' => $domainFilter,
            'locationFilter' => $locationFilter,
            'searchTerm' => $searchTerm,
            'domainOf' => $domainOf,
            'checklistStats' => $checklistStats,
            'contributorCounts' => $contributorCounts,
            'mineAssignedCount' => $mineAssignedCount,
            'mineEngagedCount' => $mineEngagedCount,
        ]);
    }

    public function show(Request $request, Mission $mission, MissionService $missions, MissionContextRegistry $registry, MissionVisibilityService $visibility): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $adapter = $registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        $mission->load(['assignments', 'checklistItems', 'capabilityRequirements', 'resourceRequirements', 'blockers', 'contributions', 'submissions', 'dependencies']);
        $children = $missions->children($mission, $identity->reference);
        $recurrence = $mission->parent_mission_id === null
            ? MissionRecurrence::query()->where('source_mission_id', $mission->id)->first()
            : null;
        $myAssignment = $mission->assignments->firstWhere('core_identity_reference', $identity->reference);

        // CAP-072 : la fiche ne montre un formulaire d'action que si l'acteur peut
        // réellement l'exécuter — ces indicateurs reflètent exactement les autorités
        // vérifiées côté service (MissionBlockerService/MissionNeedLinkService/
        // MissionSubmissionService), sans devenir une deuxième source d'autorité : le
        // backend reste la seule décision réelle, ceci n'est qu'un affichage cohérent.
        $canOfficialize = $adapter->canOfficialize($context, $identity->reference);
        $canManageAssignments = $adapter->canManageAssignments($context, $identity->reference);
        $isAcceptedExecutionRole = $myAssignment?->status === 'ACCEPTED'
            && in_array($myAssignment->role, ['EXECUTOR', 'CO_EXECUTOR', 'COORDINATOR'], true);
        $canReportBlocker = $canManageAssignments || $isAcceptedExecutionRole;

        $hasAcceptedCoordinator = $mission->assignments->contains(fn ($a): bool => $a->status === 'ACCEPTED' && $a->role === 'COORDINATOR');
        $canConsolidateSubmission = $hasAcceptedCoordinator
            ? ($myAssignment?->status === 'ACCEPTED' && $myAssignment->role === 'COORDINATOR')
            : ($myAssignment?->status === 'ACCEPTED' && in_array($myAssignment->role, ['EXECUTOR', 'CO_EXECUTOR'], true));

        $canToggleChecklist = $canOfficialize || $myAssignment?->status === 'ACCEPTED';

        $participantLabels = [];
        foreach ($mission->assignments as $assignment) {
            $reference = $assignment->core_identity_reference;
            if (hash_equals($reference, $identity->reference)) {
                $participantLabels[$assignment->id] = 'Vous';

                continue;
            }
            $exposed = $visibility->canExposeParticipantIdentity($mission, $identity->reference, $assignment);
            $profile = $exposed ? PersonProfile::query()->where('core_identity_reference', $reference)->first() : null;
            $participantLabels[$assignment->id] = $profile?->discovery_display_name ?: 'Membre DG Afrique';
        }

        return view('missions.show', [
            'recurrence' => $recurrence,
            'myAssignment' => $myAssignment,
            'participantLabels' => $participantLabels,
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'mission' => $mission,
            'contextLabel' => $adapter->label($context),
            'contextUrl' => $this->contextUrl($mission->context_type, $context),
            'canOfficialize' => $canOfficialize,
            'canManageAssignments' => $canManageAssignments,
            'canValidate' => $adapter->canValidate($context, $identity->reference),
            'canCancel' => $adapter->canCancel($context, $identity->reference),
            'canReopen' => $adapter->canReopen($context, $identity->reference),
            'canReportBlocker' => $canReportBlocker,
            'canConsolidateSubmission' => $canConsolidateSubmission,
            'canToggleChecklist' => $canToggleChecklist,
            'invitationCandidates' => $canManageAssignments ? $missions->invitationCandidates($mission, $identity->reference) : [],
            'children' => $children,
        ]);
    }

    public function createForProject(Request $request, Project $project): View
    {
        return $this->createView($request, 'PROJECT', $project->public_reference, route('projects.missions.store', $project), route('projects.show', $project), null, $project->name);
    }

    public function storeForProject(Request $request, Project $project, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'PROJECT', $project->public_reference, $workflow, route('projects.show', $project));
    }

    public function createForZumraGroup(Request $request, ZumraGroup $group): View
    {
        return $this->createView($request, 'ZUMRA', $group->public_reference, route('zumra.groups.missions.store', $group), route('zumra.groups.show', $group), null, $group->name);
    }

    public function storeForZumraGroup(Request $request, ZumraGroup $group, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'ZUMRA', $group->public_reference, $workflow, route('zumra.groups.show', $group));
    }

    public function createForNeed(Request $request, Need $need): View
    {
        return $this->createView($request, 'NEED', $need->public_reference, route('needs.missions.store', $need), route('needs.show', $need), null, $need->title);
    }

    public function storeForNeed(Request $request, Need $need, MissionWorkflow $workflow): RedirectResponse
    {
        return $this->store($request, 'NEED', $need->public_reference, $workflow, route('needs.show', $need));
    }

    public function createChild(Request $request, Mission $mission, MissionService $missions, MissionContextRegistry $registry): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);
        abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

        $adapter = $registry->for($mission->context_type);
        $contextLabel = $adapter->label($adapter->resolve($mission->context_reference));

        return $this->createView(
            $request, $mission->context_type, $mission->context_reference,
            route('missions.children.store', $mission), route('missions.show', $mission), $mission, $contextLabel
        );
    }

    public function storeChild(Request $request, Mission $mission, MissionWorkflow $workflow, MissionService $missions): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $mission = $missions->find($mission->public_reference, $identity->reference);

        return $this->store(
            $request, $mission->context_type, $mission->context_reference, $workflow,
            route('missions.show', $mission), $mission->public_reference
        );
    }

    private function createView(Request $request, string $contextType, string $contextReference, string $storeUrl, string $backUrl, ?Mission $parent = null, ?string $contextLabel = null): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('missions.create', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'contextType' => $contextType,
            'contextReference' => $contextReference,
            'contextLabel' => $contextLabel,
            'storeUrl' => $storeUrl,
            'backUrl' => $backUrl,
            'parent' => $parent,
        ]);
    }

    private function store(Request $request, string $contextType, string $contextReference, MissionWorkflow $workflow, string $redirectUrl, ?string $parentReference = null): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:4000'],
            'expected_result' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in([Mission::VISIBILITY_PRIVATE, Mission::VISIBILITY_CONTEXT, Mission::VISIBILITY_PROGRAM, Mission::VISIBILITY_PUBLIC])],
            'participation_mode' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:200'],
            'min_executors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'max_executors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'due_at' => ['nullable', 'date'],
        ]);

        if ($parentReference !== null) {
            $data['parent_mission_id'] = $parentReference;
        }

        $mission = $workflow->create($identity->reference, $contextType, $contextReference, $data);

        return redirect()->route('missions.show', $mission)
            ->with('status', 'Le brouillon de Mission est enregistré. Proposez-le pour lancer la décision.');
    }

    private function contextUrl(string $contextType, object $context): ?string
    {
        return match ($contextType) {
            'PROJECT' => route('projects.show', $context),
            'ZUMRA' => route('zumra.groups.show', $context),
            'NEED' => route('needs.show', $context),
            default => null,
        };
    }
}
