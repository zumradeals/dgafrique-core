<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionMatchingEngine;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TransmissionMatchingController
{
    public function index(Request $request, Transmission $transmission, TransmissionMatchingEngine $matching): View
    {
        $identity = $this->identity($request);
        $wantedRole = $request->string('role')->toString() ?: TransmissionParticipant::ROLE_LEARNER;

        return view('transmissions.matching', [
            'identity' => $identity,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'transmission' => $transmission,
            'wantedRole' => $wantedRole,
            'recommendations' => $matching->recommend($transmission, $identity->reference, $wantedRole),
        ]);
    }

    public function hide(Request $request, Transmission $transmission, string $discoveryReference, TransmissionMatchingEngine $matching): RedirectResponse
    {
        $matching->hide($transmission, $this->identity($request)->reference, $discoveryReference);

        return back()->with('status', 'Cette suggestion est masquée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
