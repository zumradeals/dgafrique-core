<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Sharing\ContextShareService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ContextShare;
use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ContextShareController
{
    public function index(Request $request, ContextShareService $shares): View
    {
        return view('shares.index', $shares->personalInbox($this->identity($request)->reference));
    }

    public function group(Request $request, ZumraGroup $group, ContextShareService $shares): View
    {
        return view('shares.index', $shares->groupInbox($group, $this->identity($request)->reference));
    }

    public function need(Request $request, Need $need, ContextShareService $shares): View
    {
        return view('shares.create', $shares->needComposer($need, $this->identity($request)->reference));
    }

    public function storeNeed(Request $request, Need $need, ContextShareService $shares): RedirectResponse
    {
        $data = $this->validated($request);
        $share = $shares->shareNeed(
            $need,
            $this->identity($request)->reference,
            $data['target_type'],
            $data['target_reference'],
            $data['context_note'],
        );

        return redirect()->route('shares.need', $need)->with(
            'status',
            $share->wasRecentlyCreated
                ? 'Partage transmis avec son contexte d’origine.'
                : 'Ce besoin avait déjà été transmis à cette destination. Aucun doublon n’a été créé.',
        );
    }

    public function project(Request $request, Project $project, ContextShareService $shares): View
    {
        return view('shares.create', $shares->projectComposer($project, $this->identity($request)->reference));
    }

    public function storeProject(Request $request, Project $project, ContextShareService $shares): RedirectResponse
    {
        $data = $this->validated($request);
        $share = $shares->shareProject(
            $project,
            $this->identity($request)->reference,
            $data['target_type'],
            $data['target_reference'],
            $data['context_note'],
        );

        return redirect()->route('shares.project', $project)->with(
            'status',
            $share->wasRecentlyCreated
                ? 'Partage transmis avec son contexte d’origine.'
                : 'Ce projet avait déjà été transmis à cette destination. Aucun doublon n’a été créé.',
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'target_type' => ['required', 'string', Rule::in([
                ContextShare::TARGET_PERSON,
                ContextShare::TARGET_ZUMRA,
            ])],
            'target_reference' => ['required', 'string', 'max:64'],
            'context_note' => ['required', 'string', 'min:2', 'max:'.ContextShareService::MAX_CONTEXT_LENGTH],
        ]);
    }

    private function identity(Request $request): CoreIdentity
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity;
    }
}
