<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Recommendation\RecommendationConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RecommendationConfigurationController
{
    public function edit(RecommendationConfiguration $configuration): View
    {
        return view('administration.recommendations', ['configuration' => $configuration->get()]);
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
            'hide_button' => ['required', 'string', 'max:80'],
            'max_results' => ['required', 'integer', 'min:3', 'max:24'],
            'candidate_pool' => ['required', 'integer', 'min:20', 'max:500'],
            'max_reasons' => ['required', 'integer', 'min:1', 'max:8'],
            'learning_transmission' => ['nullable', 'boolean'],
            'transmission_learning' => ['nullable', 'boolean'],
            'shared_capability' => ['nullable', 'boolean'],
            'shared_domain' => ['nullable', 'boolean'],
            'location_context' => ['nullable', 'boolean'],
            'participation_context' => ['nullable', 'boolean'],
        ]);
        foreach (['learning_transmission', 'transmission_learning', 'shared_capability', 'shared_domain', 'location_context', 'participation_context'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        PortalSetting::query()->updateOrCreate(
            ['key' => RecommendationConfiguration::KEY],
            ['value' => $data, 'updated_by_core_reference' => $identity->reference],
        );

        return back()->with('status', 'Configuration CAP‑010 enregistrée.');
    }
}
