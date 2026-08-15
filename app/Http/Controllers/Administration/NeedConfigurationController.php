<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Needs\NeedConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NeedConfigurationController
{
    public function edit(NeedConfiguration $configuration): View
    {
        return view('administration.needs', ['configuration' => $configuration->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'directory_title' => ['required', 'string', 'max:180'],
            'directory_intro' => ['required', 'string', 'max:700'],
            'creation_title' => ['required', 'string', 'max:180'],
            'category_codes' => ['required', 'array', 'min:1', 'max:20'],
            'category_codes.*' => ['required', 'string', 'regex:/^[A-Z][A-Z0-9_]{1,39}$/', 'distinct'],
            'category_labels' => ['required', 'array'],
            'category_labels.*' => ['required', 'string', 'max:120'],
            'max_open_personal_needs' => ['required', 'integer', 'min:1', 'max:100'],
            'max_open_group_needs' => ['required', 'integer', 'min:1', 'max:300'],
            'directory_page_size' => ['required', 'integer', 'min:6', 'max:60'],
        ]);
        abort_unless(count($data['category_codes']) === count($data['category_labels']), 422);
        $categories = [];
        foreach ($data['category_codes'] as $index => $code) {
            $categories[$code] = $data['category_labels'][$index];
        }
        PortalSetting::query()->updateOrCreate(['key' => NeedConfiguration::KEY], ['value' => [
            'directory_title' => $data['directory_title'], 'directory_intro' => $data['directory_intro'],
            'creation_title' => $data['creation_title'], 'categories' => $categories,
            'max_open_personal_needs' => $data['max_open_personal_needs'],
            'max_open_group_needs' => $data['max_open_group_needs'], 'directory_page_size' => $data['directory_page_size'],
        ], 'updated_by_core_reference' => $identity->reference]);

        return back()->with('status', 'Configuration CAP‑013 enregistrée.');
    }
}
