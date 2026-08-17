<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionNeedLinkService;
use App\Application\Needs\NeedConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionBlocker;
use App\Models\Need;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * « Exprimer ce manque comme besoin » — jamais automatique : `confirm` est une case à
 * cocher explicite dans le formulaire, distincte du blocage lui-même.
 */
final class MissionNeedLinkController
{
    public function create(Request $request, Mission $mission, MissionBlocker $blocker, NeedConfiguration $configuration): View
    {
        $identity = $this->identity($request);
        abort_unless($blocker->mission_id === $mission->id, 404);

        return view('missions.blocker-need', [
            'identity' => $identity,
            'isAdministrator' => \App\Models\PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'mission' => $mission,
            'blocker' => $blocker,
            'configuration' => $configuration->get(),
        ]);
    }

    public function store(Request $request, Mission $mission, MissionBlocker $blocker, MissionNeedLinkService $links, NeedConfiguration $configuration): RedirectResponse
    {
        $identity = $this->identity($request);
        $settings = $configuration->get();

        $data = $request->validate([
            'confirm' => ['accepted'],
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'context' => ['required', 'string', 'min:40', 'max:3000'],
            'category' => ['required', Rule::in(array_keys($settings['categories']))],
            'collaboration_mode' => ['required', Rule::in(['LOCAL', 'REMOTE', 'HYBRID', 'ANY'])],
            'visibility' => ['required', Rule::in([Need::VISIBILITY_PRIVATE, Need::VISIBILITY_GROUP, Need::VISIBILITY_PROGRAM, Need::VISIBILITY_PUBLIC])],
        ]);

        $need = $links->expressBlockerAsNeed($mission, $blocker, $identity->reference, [
            'owner_type' => Need::OWNER_PERSON,
            'group_reference' => null,
            'title' => $data['title'],
            'context' => $data['context'],
            'category' => $data['category'],
            'collaboration_mode' => $data['collaboration_mode'],
            'visibility' => $data['visibility'],
        ], $settings);

        return redirect()->route('missions.show', $mission)
            ->with('status', 'Le besoin « '.$need->title.' » est créé et lié à ce blocage.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
