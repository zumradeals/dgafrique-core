<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Moderation\ModerationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — première interface d'administration pour ModerationConfiguration
 * (MODERATION-COMP-001 documentait explicitement son absence en V1 : « Aucune interface
 * d'administration dédiée n'existe encore »). Réutilise strictement la classe Configuration
 * existante — mêmes deux paramètres déjà consommés par ModerationDecisionService, aucun paramètre
 * ajouté, aucune règle de modération réécrite.
 */
final class ModerationConfigurationController
{
    public function edit(ModerationConfiguration $configuration): View
    {
        return view('administration.configuration.moderation', [
            'configuration' => $configuration->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'warning_default_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'suspension_default_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);
        PortalSetting::query()->updateOrCreate(['key' => ModerationConfiguration::KEY], ['value' => $data, 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration de modération enregistrée.');
    }
}
