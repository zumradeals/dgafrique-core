<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ZUMRA-COMP-001 — les transitions au-delà de READY relèvent de l'autorité DG Afrique/GAMAD
 * (art. 10 : « DG Afrique, sous gouvernance GAMAD, peut avertir, limiter, suspendre... »),
 * jamais de isLeader(). Le middleware `portal.admin` du groupe de routes gate l'accès ;
 * ZumraGroupService revérifie PortalAdministrator en défense en profondeur.
 */
final class ZumraGroupLifecycleController
{
    public function validate(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->validate($group, $this->actor($request));

        return back()->with('status', 'ZUMRA validée.');
    }

    public function activate(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->activate($group, $this->actor($request));

        return back()->with('status', 'ZUMRA activée.');
    }

    public function warn(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->warn($group, $this->actor($request));

        return back()->with('status', 'ZUMRA avertie.');
    }

    public function suspend(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->suspend($group, $this->actor($request));

        return back()->with('status', 'ZUMRA suspendue.');
    }

    public function enterRehabilitation(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->enterRehabilitation($group, $this->actor($request));

        return back()->with('status', 'ZUMRA en réhabilitation.');
    }

    public function reactivate(Request $request, ZumraGroup $group, ZumraGroupService $service): RedirectResponse
    {
        $service->reactivate($group, $this->actor($request));

        return back()->with('status', 'ZUMRA réactivée.');
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
