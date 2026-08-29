<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Partnerships\PartnershipService;
use App\Domain\Identity\CoreIdentity;
use App\Http\Controllers\Concerns\PresentsPartnerships;
use App\Models\Need;
use App\Models\Partnership;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class NeedController
{
    use PresentsPartnerships;

    /**
     * UX-HARMONY-BESOINS-001 — la définition « besoin urgent » n'est stockée nulle part dans le
     * domaine Need (aucune colonne de priorité/urgence n'existe) : c'est un calcul de présentation,
     * jamais une règle métier persistée. On reprend exactement le même seuil que celui déjà établi
     * pour les besoins « stagnants » côté admin (AdminCommunityController::needs(), 30 jours) afin de
     * garder une seule définition dans tout le produit.
     */
    private const URGENT_AFTER_DAYS = 30;

    public function index(Request $request, NeedConfiguration $configuration, NeedService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $categoryFilter = $request->filled('category') && array_key_exists((string) $request->query('category'), $settings['categories']) ? (string) $request->query('category') : null;
        $statusFilter = $request->filled('status') && in_array($request->query('status'), [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS, Need::STATUS_RESOLVED], true) ? (string) $request->query('status') : null;
        $urgentOnly = $request->boolean('urgent');
        $mineOnly = $request->boolean('mine');
        $searchTerm = trim((string) $request->query('q', ''));
        $urgentSince = now()->subDays(self::URGENT_AFTER_DAYS);

        $query = Need::query()->whereNotIn('status', [Need::STATUS_ARCHIVED])->latest('created_at')->limit(300);
        if ($categoryFilter !== null) {
            $query->where('category', $categoryFilter);
        }
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }
        if ($searchTerm !== '') {
            $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$searchTerm.'%')->orWhere('context', 'like', '%'.$searchTerm.'%')->orWhere('location', 'like', '%'.$searchTerm.'%'));
        }
        if ($urgentOnly) {
            $query->whereIn('status', [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS])->where('created_at', '<=', $urgentSince);
        }
        if ($mineOnly) {
            $query->where('author_core_reference', $identity->reference);
        }
        $visible = $query->get()->filter(fn (Need $need): bool => $service->canView($need, $identity->reference))->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $settings['directory_page_size'];
        $needs = new LengthAwarePaginator($visible->forPage($page, $perPage), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $groups = ZumraGroup::query()->whereIn('id', $visible->where('owner_type', Need::OWNER_GROUP)->pluck('owner_reference'))->get()->keyBy('id');
        $projects = Project::query()->whereIn('id', $visible->where('owner_type', Need::OWNER_PROJECT)->pluck('owner_reference'))->get()->keyBy('id');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // Bandeau, colonne droite, onglets : un calcul réel sur l'ensemble des besoins accessibles à
        // l'identité (indépendant des filtres appliqués à la liste ci-dessus), jamais une projection.
        $allVisible = Need::query()->whereNotIn('status', [Need::STATUS_ARCHIVED])->limit(300)->get()->filter(fn (Need $need): bool => $service->canView($need, $identity->reference));
        $urgentNeeds = $allVisible->filter(fn (Need $need): bool => in_array($need->status, [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS], true) && $need->created_at->lte($urgentSince))->sortBy('created_at')->values();
        $overview = [
            'open' => $allVisible->where('status', Need::STATUS_OPEN)->count(),
            'pending' => $allVisible->where('status', Need::STATUS_PROPOSED)->count(),
            'resolved' => $allVisible->where('status', Need::STATUS_RESOLVED)->count(),
        ];
        $bandeau = [
            'total' => $allVisible->count(),
            'urgent' => $urgentNeeds->count(),
            'resolved' => $overview['resolved'],
        ];
        $byCategory = collect($settings['categories'])->map(fn (string $label, string $code): array => [
            'label' => $label,
            'count' => $allVisible->where('category', $code)->count(),
        ])->sortByDesc('count')->values();
        $byLocation = $allVisible->filter(fn (Need $need): bool => filled($need->location))
            ->groupBy(fn (Need $need): string => trim((string) $need->location))
            ->map(fn ($group, string $location): array => ['label' => $location, 'count' => $group->count()])
            ->sortByDesc('count')->take(6)->values();
        $mineCount = $allVisible->where('author_core_reference', $identity->reference)->count();

        return view('needs.index', compact(
            'identity', 'needs', 'groups', 'projects', 'isAdministrator', 'overview',
            'bandeau', 'byCategory', 'byLocation', 'urgentNeeds', 'mineCount', 'categoryFilter', 'statusFilter', 'urgentOnly', 'mineOnly', 'searchTerm',
        ) + ['configuration' => $settings]);
    }

    public function create(Request $request, NeedConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $groups = ZumraGroup::query()->whereIn('id', ZumraGroupMembership::query()->where('core_identity_reference', $identity->reference)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->pluck('zumra_group_id'))->orderBy('name')->get();
        $projectIds = ProjectTeamMember::query()->where('core_identity_reference', $identity->reference)->where('status', ProjectTeamMember::STATUS_ACTIVE)->pluck('project_id');
        $projects = Project::query()->where(function ($query) use ($identity, $projectIds): void {
            $query->where('initiator_core_reference', $identity->reference)
                ->orWhere(function ($query) use ($identity): void {
                    $query->where('owner_type', Project::OWNER_PERSON)->where('owner_reference', $identity->reference);
                })
                ->orWhereIn('id', $projectIds);
        })->whereNotIn('status', [Project::STATUS_ARCHIVED])->orderBy('name')->get();
        $preselectedProject = $projects->firstWhere('public_reference', $request->query('project'));
        // UIUX-007 — CTA contextuel « Créer un Besoin pour cette ZUMRA » depuis la fiche ZUMRA :
        // même patron que $preselectedProject, jamais une préautorisation nouvelle — le groupe
        // doit déjà figurer dans $groups (adhésion active déjà vérifiée par la requête ci-dessus).
        $preselectedGroup = $groups->firstWhere('public_reference', $request->query('group'));
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('needs.create', compact('identity', 'groups', 'projects', 'preselectedProject', 'preselectedGroup', 'isAdministrator') + ['configuration' => $configuration->get()]);
    }

    public function store(Request $request, NeedConfiguration $configuration, NeedService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $data = $request->validate([
            'owner_type' => ['required', Rule::in([Need::OWNER_PERSON, Need::OWNER_GROUP, Need::OWNER_PROJECT])],
            'group_reference' => ['nullable', 'uuid', 'required_if:owner_type,GROUP'],
            'project_reference' => ['nullable', 'uuid', 'required_if:owner_type,PROJECT'],
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'context' => ['required', 'string', 'min:40', 'max:3000'],
            'category' => ['required', Rule::in(array_keys($settings['categories']))],
            'capability_label' => ['nullable', 'string', 'max:200'],
            'collaboration_mode' => ['required', Rule::in(['LOCAL', 'REMOTE', 'HYBRID', 'ANY'])],
            'location' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', Rule::in([Need::VISIBILITY_PRIVATE, Need::VISIBILITY_GROUP, Need::VISIBILITY_PROGRAM, Need::VISIBILITY_PUBLIC])],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);
        if ($request->hasFile('image')) {
            $data['image_path'] = Storage::disk('public')->putFile('needs', $request->file('image'));
        }
        $need = $service->create($identity->reference, $data, $settings);

        return redirect()->route('needs.show', $need)->with('status', $need->status === Need::STATUS_PROPOSED ? 'Le besoin est proposé aux responsables de la ZUMRA. Il n’est pas encore publié officiellement.' : 'Votre besoin est publié selon la visibilité choisie.');
    }

    public function show(Request $request, Need $need, NeedConfiguration $configuration, NeedService $service, PartnershipService $partnerships, MissionContextRegistry $missionContexts): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($need, $identity->reference), 404);
        $group = $need->owner_type === Need::OWNER_GROUP ? ZumraGroup::query()->find($need->owner_reference) : null;
        $project = $need->owner_type === Need::OWNER_PROJECT ? Project::query()->find($need->owner_reference) : null;
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // UIUX-007 — « Je peux aider » : la seule transition métier qui représente réellement la
        // réponse d'une personne à un Besoin est déjà `NeedMissionContext::canPropose()` (adhésion
        // Programme ZUMRA active + visibilité + Besoin non archivé) — jamais Partnership, qui reste
        // une relation métier distincte et n'est jamais choisie par défaut ici.
        $canProposeMission = $missionContexts->for('NEED')->canPropose($need, $identity->reference);

        // UIUX-005 — Partenariats réellement associés à ce Besoin, filtrés par la même autorité
        // que le service lui-même (PartnershipService::canView()), jamais recalculée en Blade.
        $needPartnerships = Partnership::query()
            ->where('context_type', Partnership::CONTEXT_NEED)
            ->where('context_reference', $need->public_reference)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->filter(fn (Partnership $partnership): bool => $partnerships->canView($partnership, $identity->reference))
            ->values();

        return view('needs.show', compact('identity', 'need', 'group', 'project', 'isAdministrator') + [
            'configuration' => $configuration->get(),
            'canDecide' => $service->canDecide($need, $identity->reference),
            'canProposeMission' => $canProposeMission,
            'needPartnerships' => $this->presentPartnerships($needPartnerships, $identity->reference, $partnerships),
            'manageableOrganizations' => $this->manageableOrganizations($identity->reference),
            'manageableOrganizationCapabilities' => $this->manageableOrganizationCapabilities($identity->reference),
        ]);
    }

    public function transition(Request $request, Need $need, NeedService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['status' => ['required', Rule::in([Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS, Need::STATUS_RESOLVED, Need::STATUS_ARCHIVED])], 'resolution_note' => ['nullable', 'string', 'max:1500']]);
        $service->transition($need, $identity->reference, $data['status'], $data['resolution_note'] ?? null);

        return back()->with('status', 'L’état du besoin a été mis à jour et journalisé.');
    }
}
