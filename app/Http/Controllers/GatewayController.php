<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\PortalMemberSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class GatewayController
{
    public function __construct(private PortalMemberSession $portalSession) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($this->portalSession->has($request->session())) {
            return redirect()->route('activity.index');
        }

        // Fixture d'exemple uniquement (voir resources/design-reference/README.md) : jamais
        // seedée, jamais affichée comme une donnée métier réelle — même source que
        // LandingController (le portail long à /decouvrir affiche déjà ces mêmes « moments »).
        $portalDemo = json_decode(
            file_get_contents(resource_path('design-reference/landing-portal-demo.json')),
            true,
        );

        return view('gateway', ['exampleMoment' => $portalDemo['moments'][0]]);
    }
}
