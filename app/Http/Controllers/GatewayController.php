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

        return view('gateway');
    }
}
