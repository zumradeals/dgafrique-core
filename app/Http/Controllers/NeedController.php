<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedDirectoryDemoContent;
use App\Application\Needs\NeedService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Need;
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
    public function index(Request $request, NeedConfiguration $configuration, NeedService $service, NeedDirectoryDemoContent $demoContent): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $categoryFilter = $request->filled('category') && array_key_exists((string) $request->query('category'), $settings['categories']) ? (string) $request->query('category') : null;
        $statusFilter = $request->filled('status') && in_array($request->query('status'), [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS, Need::STATUS_RESOLVED], true) ? (string) $request->query('status') : null;
        $query = Need::query()->whereNotIn('status', [Need::STATUS_ARCHIVED])->latest('created_at')->limit(300);
        if ($categoryFilter !== null) {
            $query->where('category', $categoryFilter);
        }
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }
        $visible = $query->get()->filter(fn (Need $need): bool => $service->canView($need, $identity->reference))->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $settings['directory_page_size'];
        $needs = new LengthAwarePaginator($visible->forPage($page, $perPage), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $demoCards = $demoContent->demoCards($visible, $page, $categoryFilter);
        $groups = ZumraGroup::query()->whereIn('id', $visible->where('owner_type', Need::OWNER_GROUP)->pluck('owner_reference'))->get()->keyBy('id');
        $projects = Project::query()->whereIn('id', $visible->where('owner_type', Need::OWNER_PROJECT)->pluck('owner_reference'))->get()->keyBy('id');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // « Aperçu des besoins » : un calcul réel sur l'ensemble des besoins accessibles à l'identité
        // (indépendant des filtres appliqués à la liste), jamais une projection — le modèle le permet.
        $allVisible = Need::query()->whereNotIn('status', [Need::STATUS_ARCHIVED])->limit(300)->get()->filter(fn (Need $need): bool => $service->canView($need, $identity->reference));
        $overview = [
            'open' => $allVisible->where('status', Need::STATUS_OPEN)->count(),
            'pending' => $allVisible->where('status', Need::STATUS_PROPOSED)->count(),
            'resolved' => $allVisible->where('status', Need::STATUS_RESOLVED)->count(),
        ];

        return view('needs.index', compact('identity', 'needs', 'demoCards', 'groups', 'projects', 'isAdministrator', 'overview') + ['configuration' => $settings]);
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
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('needs.create', compact('identity', 'groups', 'projects', 'preselectedProject', 'isAdministrator') + ['configuration' => $configuration->get()]);
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

    public function show(Request $request, Need $need, NeedConfiguration $configuration, NeedService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($need, $identity->reference), 404);
        $group = $need->owner_type === Need::OWNER_GROUP ? ZumraGroup::query()->find($need->owner_reference) : null;
        $project = $need->owner_type === Need::OWNER_PROJECT ? Project::query()->find($need->owner_reference) : null;
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('needs.show', compact('identity', 'need', 'group', 'project', 'isAdministrator') + ['configuration' => $configuration->get(), 'canDecide' => $service->canDecide($need, $identity->reference)]);
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
