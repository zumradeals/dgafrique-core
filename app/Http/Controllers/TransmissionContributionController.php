<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Transmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TransmissionContributionController
{
    public function store(Request $request, Transmission $transmission, TransmissionService $transmissions): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:2', 'max:2000']]);
        $transmissions->addContribution($transmission, $this->identity($request)->reference, $data['note']);

        return back()->with('status', 'La contribution est ajoutée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
