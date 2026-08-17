<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Transmission;
use App\Models\TransmissionMilestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TransmissionMilestoneController
{
    public function store(Request $request, Transmission $transmission, TransmissionService $transmissions): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'min:2', 'max:300'],
            'is_required' => ['nullable', 'boolean'],
        ]);
        $transmissions->addMilestone($transmission, $this->identity($request)->reference, $data['label'], (bool) ($data['is_required'] ?? false));

        return back()->with('status', 'Le jalon est ajouté.');
    }

    public function update(Request $request, Transmission $transmission, TransmissionMilestone $milestone, TransmissionService $transmissions): RedirectResponse
    {
        $data = $request->validate(['completed' => ['required', 'boolean']]);
        $transmissions->setMilestoneCompletion($transmission, $this->identity($request)->reference, $milestone, (bool) $data['completed']);

        return back()->with('status', 'Le jalon est mis à jour.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
