<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Missions\MissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionCapabilityRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MissionRequirementController
{
    public function storeCapability(Request $request, Mission $mission, MissionService $missions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'label' => ['required', 'string', 'min:2', 'max:200'],
            'requirement_level' => ['required', Rule::in([MissionCapabilityRequirement::LEVEL_REQUIRED, MissionCapabilityRequirement::LEVEL_PREFERRED])],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'context' => ['nullable', 'string', 'max:600'],
        ]);
        $missions->addCapabilityRequirement($mission, $identity->reference, $data['label'], $data['requirement_level'], $data['quantity'] ?? null, $data['context'] ?? null);

        return back()->with('status', 'Capacité recherchée ajoutée.');
    }

    public function storeResource(Request $request, Mission $mission, MissionService $missions): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate([
            'type' => ['required', 'string', 'max:40'],
            'label' => ['required', 'string', 'min:2', 'max:200'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:40'],
            'is_required' => ['nullable', 'boolean'],
            'context' => ['nullable', 'string', 'max:600'],
        ]);
        $missions->addResourceRequirement(
            $mission, $identity->reference, $data['type'], $data['label'],
            isset($data['quantity']) ? (float) $data['quantity'] : null, $data['unit'] ?? null,
            (bool) ($data['is_required'] ?? true), $data['context'] ?? null,
        );

        return back()->with('status', 'Ressource nécessaire ajoutée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
