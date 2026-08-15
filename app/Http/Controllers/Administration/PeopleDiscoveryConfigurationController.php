<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Discovery\PeopleDiscoveryConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PeopleDiscoveryConfigurationController
{
    public function edit(PeopleDiscoveryConfiguration $configuration): View
    {
        return view('administration.people-discovery', ['configuration' => $configuration->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'introduction' => ['required', 'string', 'max:600'],
            'privacy_notice' => ['required', 'string', 'max:500'],
            'empty_title' => ['required', 'string', 'max:160'],
            'empty_text' => ['required', 'string', 'max:500'],
            'detail_button' => ['required', 'string', 'max:80'],
            'page_size' => ['required', 'integer', 'min:6', 'max:24'],
            'country_filter' => ['nullable', 'boolean'],
            'mode_filter' => ['nullable', 'boolean'],
        ]);
        $data['country_filter'] = $request->boolean('country_filter');
        $data['mode_filter'] = $request->boolean('mode_filter');

        PortalSetting::query()->updateOrCreate(
            ['key' => PeopleDiscoveryConfiguration::KEY],
            ['value' => $data, 'updated_by_core_reference' => $identity->reference],
        );

        return back()->with('status', 'Configuration CAP‑009 enregistrée.');
    }
}
