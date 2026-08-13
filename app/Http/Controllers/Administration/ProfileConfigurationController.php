<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Profile\ProfileConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ProfileConfigurationController
{
    public function edit(ProfileConfiguration $configuration): View
    {
        return view('administration.profile-configuration', ['configuration' => $configuration->get()]);
    }

    public function update(Request $request, ProfileConfiguration $configuration): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'introduction' => ['required', 'string', 'max:600'],
            'sections' => ['required', 'array'],
            'sections.*.enabled' => ['nullable', 'boolean'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:999'],
            'sections.*.title' => ['required', 'string', 'max:160'],
            'sections.*.help' => ['nullable', 'string', 'max:500'],
            'participation_modes_text' => ['nullable', 'string', 'max:2000'],
            'country_suggestions_text' => ['nullable', 'string', 'max:1000'],
            'field_labels' => ['required', 'array'],
            'field_labels.*' => ['required', 'string', 'max:200'],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['nullable', 'boolean'],
            'orientation_consent_label' => ['required', 'string', 'max:160'],
            'orientation_consent_help' => ['required', 'string', 'max:600'],
            'submit_label' => ['required', 'string', 'max:100'],
            'no_score_notice' => ['required', 'string', 'max:300'],
        ]);

        $defaults = $configuration->defaults();
        $sections = [];
        foreach (array_keys($defaults['sections']) as $key) {
            $section = Arr::get($data, "sections.{$key}", []);
            $sections[$key] = [
                'enabled' => filter_var($section['enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'order' => (int) ($section['order'] ?? 999),
                'title' => (string) ($section['title'] ?? ''),
                'help' => (string) ($section['help'] ?? ''),
            ];
        }

        $modes = $this->pairs((string) ($data['participation_modes_text'] ?? ''));
        $countries = array_values(array_unique(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[\r\n,;]+/', (string) ($data['country_suggestions_text'] ?? '')) ?: [],
        ), static fn (string $value): bool => preg_match('/^[A-Z]{2}$/', $value) === 1)));
        $fieldLabels = array_intersect_key($data['field_labels'], $defaults['field_labels']);
        $requiredFields = [];
        foreach (array_keys($defaults['required_fields']) as $field) {
            $requiredFields[$field] = filter_var($data['required_fields'][$field] ?? false, FILTER_VALIDATE_BOOL);
        }

        DB::transaction(static function () use ($data, $sections, $modes, $countries, $fieldLabels, $requiredFields, $identity): void {
            PortalSetting::query()->updateOrCreate(
                ['key' => ProfileConfiguration::KEY],
                ['value' => [
                    'title' => $data['title'],
                    'introduction' => $data['introduction'],
                    'sections' => $sections,
                    'participation_modes' => $modes,
                    'country_suggestions' => $countries,
                    'field_labels' => $fieldLabels,
                    'required_fields' => $requiredFields,
                    'orientation_consent_label' => $data['orientation_consent_label'],
                    'orientation_consent_help' => $data['orientation_consent_help'],
                    'submit_label' => $data['submit_label'],
                    'no_score_notice' => $data['no_score_notice'],
                ], 'updated_by_core_reference' => $identity->reference],
            );
        });

        return back()->with('status', 'Configuration CAP‑003 enregistrée.');
    }

    /** @return list<array{value: string, label: string}> */
    private function pairs(string $text): array
    {
        $pairs = [];
        foreach (preg_split('/\R+/', $text) ?: [] as $line) {
            [$value, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($value !== '' && $label !== '' && preg_match('/^[A-Z0-9_]{2,40}$/', $value)) {
                $pairs[] = ['value' => $value, 'label' => $label];
            }
        }

        return array_slice($pairs, 0, 20);
    }
}
