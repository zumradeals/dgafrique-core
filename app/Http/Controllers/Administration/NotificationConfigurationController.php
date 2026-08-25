<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Notifications\NotificationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — première interface d'administration pour NotificationConfiguration
 * (« Aucun écran d'administration dédié en v1 » — docblock d'origine). Réutilise strictement la
 * classe Configuration existante, mêmes clés déjà consommées par le moteur de notifications FYI.
 */
final class NotificationConfigurationController
{
    public function edit(NotificationConfiguration $configuration): View
    {
        return view('administration.configuration.notifications', [
            'configuration' => $configuration->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'lookback_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_actionable' => ['required', 'integer', 'min:1', 'max:500'],
            'max_recent' => ['required', 'integer', 'min:1', 'max:500'],
            'scan_limit' => ['required', 'integer', 'min:1', 'max:2000'],
            'mission_fyi_enabled' => ['nullable', 'boolean'],
            'transmission_fyi_enabled' => ['nullable', 'boolean'],
            'proof_fyi_enabled' => ['nullable', 'boolean'],
        ]);
        $data['mission_fyi_enabled'] = $request->boolean('mission_fyi_enabled');
        $data['transmission_fyi_enabled'] = $request->boolean('transmission_fyi_enabled');
        $data['proof_fyi_enabled'] = $request->boolean('proof_fyi_enabled');
        PortalSetting::query()->updateOrCreate(['key' => NotificationConfiguration::KEY], ['value' => $data, 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration des notifications enregistrée.');
    }
}
