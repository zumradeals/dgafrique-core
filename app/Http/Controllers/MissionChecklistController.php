<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MissionChecklistController
{
    public function store(Request $request, Mission $mission, MissionService $missions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'label' => ['required', 'string', 'min:2', 'max:300'],
            'is_required' => ['nullable', 'boolean'],
        ]);
        $missions->addChecklistItem($mission, $identity->reference, $data['label'], (bool) ($data['is_required'] ?? false));

        return back()->with('status', 'Élément ajouté à la checklist.');
    }

    public function update(Request $request, Mission $mission, MissionChecklistItem $item, MissionService $missions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['completed' => ['required', 'boolean']]);
        $missions->setChecklistItemCompletion($mission, $identity->reference, $item, (bool) $data['completed']);

        return back()->with('status', 'Checklist mise à jour.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
