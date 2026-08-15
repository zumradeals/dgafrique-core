<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Need;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class NeedController
{
    public function index(Request $request, NeedConfiguration $configuration, NeedService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $query = Need::query()->whereNotIn('status', [Need::STATUS_ARCHIVED])->latest('created_at')->limit(300);
        if ($request->filled('category') && array_key_exists((string) $request->query('category'), $settings['categories'])) {
            $query->where('category', $request->query('category'));
        }
        if ($request->filled('status') && in_array($request->query('status'), [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS, Need::STATUS_RESOLVED], true)) {
            $query->where('status', $request->query('status'));
        }
        $visible = $query->get()->filter(fn (Need $need): bool => $service->canView($need, $identity->reference))->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $settings['directory_page_size'];
        $needs = new LengthAwarePaginator($visible->forPage($page, $perPage), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $groups = ZumraGroup::query()->whereIn('id', $visible->where('owner_type', Need::OWNER_GROUP)->pluck('owner_reference'))->get()->keyBy('id');

        return view('needs.index', compact('identity', 'needs', 'groups') + ['configuration' => $settings]);
    }

    public function create(Request $request, NeedConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $groups = ZumraGroup::query()->whereIn('id', ZumraGroupMembership::query()->where('core_identity_reference', $identity->reference)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->pluck('zumra_group_id'))->orderBy('name')->get();

        return view('needs.create', compact('groups') + ['configuration' => $configuration->get()]);
    }

    public function store(Request $request, NeedConfiguration $configuration, NeedService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $settings = $configuration->get();
        $data = $request->validate([
            'owner_type' => ['required', Rule::in([Need::OWNER_PERSON, Need::OWNER_GROUP])],
            'group_reference' => ['nullable', 'uuid', 'required_if:owner_type,GROUP'],
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'context' => ['required', 'string', 'min:40', 'max:3000'],
            'category' => ['required', Rule::in(array_keys($settings['categories']))],
            'capability_label' => ['nullable', 'string', 'max:200'],
            'collaboration_mode' => ['required', Rule::in(['LOCAL', 'REMOTE', 'HYBRID', 'ANY'])],
            'location' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', Rule::in([Need::VISIBILITY_PRIVATE, Need::VISIBILITY_GROUP, Need::VISIBILITY_PROGRAM, Need::VISIBILITY_PUBLIC])],
        ]);
        $need = $service->create($identity->reference, $data, $settings);

        return redirect()->route('needs.show', $need)->with('status', $need->status === Need::STATUS_PROPOSED ? 'Le besoin est proposé aux responsables de la ZUMRA. Il n’est pas encore publié officiellement.' : 'Votre besoin est publié selon la visibilité choisie.');
    }

    public function show(Request $request, Need $need, NeedConfiguration $configuration, NeedService $service): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($need, $identity->reference), 404);
        $group = $need->owner_type === Need::OWNER_GROUP ? ZumraGroup::query()->find($need->owner_reference) : null;

        return view('needs.show', compact('identity', 'need', 'group') + ['configuration' => $configuration->get(), 'canDecide' => $service->canDecide($need, $identity->reference)]);
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
