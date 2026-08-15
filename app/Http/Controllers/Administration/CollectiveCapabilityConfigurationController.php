<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Zumra\CollectiveCapabilityConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CollectiveCapabilityConfigurationController
{
    public function edit(CollectiveCapabilityConfiguration $configuration): View
    {
        return view('administration.collective-capabilities', ['configuration' => $configuration->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'section_title' => ['required', 'string', 'max:180'],
            'section_intro' => ['required', 'string', 'max:600'],
            'empty_text' => ['required', 'string', 'max:500'],
            'consent_label' => ['required', 'string', 'max:300'],
            'minimum_members' => ['required', 'integer', 'min:1', 'max:50'],
            'max_capabilities' => ['required', 'integer', 'min:3', 'max:100'],
        ]);
        PortalSetting::query()->updateOrCreate(['key' => CollectiveCapabilityConfiguration::KEY], ['value' => $data, 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration CAP‑012 enregistrée.');
    }
}
