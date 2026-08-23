<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Zumra\ZumraGroupConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ZumraGroupConfigurationController
{
    public function edit(ZumraGroupConfiguration $configuration): View
    {
        return view('administration.zumra-groups', ['configuration' => $configuration->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'creation_title' => ['required', 'string', 'max:160'],
            'established_member_threshold' => ['required', 'integer', 'min:5', 'max:10000'],
            'max_simultaneous_founder_roles' => ['required', 'integer', 'min:1', 'max:20'],
            'auto_validation_enabled' => ['nullable', 'boolean'],
        ]);
        $data['auto_validation_enabled'] = $request->boolean('auto_validation_enabled');
        PortalSetting::query()->updateOrCreate(['key' => ZumraGroupConfiguration::KEY], ['value' => $data, 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration CAP‑011 enregistrée.');
    }
}
