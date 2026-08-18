<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Profile\ProfileConfiguration;
use App\Application\Profile\ProfileList;
use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class MemberProfileController
{
    public function edit(Request $request, ProfileConfiguration $configuration): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);

        $profileConfiguration = $configuration->get();
        $capabilitySummary = CapabilityStatement::query()
            ->where('core_identity_reference', $identity->reference)
            ->whereNull('archived_at')
            ->selectRaw('kind, count(*) as aggregate')
            ->groupBy('kind')
            ->pluck('aggregate', 'kind')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('member.profile', compact('identity', 'profile', 'profileConfiguration', 'capabilitySummary', 'isAdministrator'));
    }

    public function update(
        Request $request,
        ProfileConfiguration $configuration,
        CapabilityStatementSynchronizer $statementSynchronizer,
    ): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profileConfiguration = $configuration->get();
        $allowedModes = array_column($profileConfiguration['participation_modes'] ?? [], 'value');
        $fieldSections = [
            'country_code' => 'situation', 'city' => 'situation', 'phone' => 'situation',
            'current_activity' => 'situation', 'education_level' => 'situation',
            'existing_skills_text' => 'skills', 'transmission_offers_text' => 'transmission',
            'learning_goals_text' => 'learning', 'experience_highlights_text' => 'experience',
            'experience_proofs_text' => 'experience', 'declared_needs_text' => 'needs',
            'interest_domains_text' => 'needs', 'intentions_text' => 'needs',
            'participation_mode' => 'collaboration',
            'collaboration_preferences_text' => 'collaboration',
            'discovery_display_name' => 'collaboration', 'discovery_bio' => 'collaboration',
            'availability_status' => 'collaboration', 'availability_note' => 'collaboration',
        ];
        $presence = static function (string $field) use ($profileConfiguration, $fieldSections): string {
            $section = $fieldSections[$field];

            return ($profileConfiguration['sections'][$section]['enabled'] ?? false)
                && ($profileConfiguration['required_fields'][$field] ?? false) ? 'required' : 'nullable';
        };
        $data = $request->validate([
            'country_code' => [$presence('country_code'), 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'city' => [$presence('city'), 'string', 'max:160'],
            'phone' => [$presence('phone'), 'string', 'max:40'],
            'current_activity' => [$presence('current_activity'), 'string', 'max:256'],
            'education_level' => [$presence('education_level'), 'string', 'max:160'],
            'existing_skills_text' => [$presence('existing_skills_text'), 'string', 'max:3000'],
            'starts_without_skill' => ['nullable', 'boolean'],
            'transmission_offers_text' => [$presence('transmission_offers_text'), 'string', 'max:3000'],
            'learning_goals_text' => [$presence('learning_goals_text'), 'string', 'max:3000'],
            'experience_highlights_text' => [$presence('experience_highlights_text'), 'string', 'max:3000'],
            'experience_proofs_text' => [$presence('experience_proofs_text'), 'string', 'max:3000'],
            'declared_needs_text' => [$presence('declared_needs_text'), 'string', 'max:3000'],
            'interest_domains_text' => [$presence('interest_domains_text'), 'string', 'max:3000'],
            'intentions_text' => [$presence('intentions_text'), 'string', 'max:3000'],
            'participation_mode' => [$presence('participation_mode'), Rule::in($allowedModes)],
            'collaboration_preferences_text' => [$presence('collaboration_preferences_text'), 'string', 'max:3000'],
            'orientation_consent' => ['nullable', 'boolean'],
            'discovery_display_name' => ['nullable', 'string', 'max:120'],
            'discovery_bio' => ['nullable', 'string', 'max:600'],
            'discovery_consent' => ['nullable', 'boolean'],
            'availability_status' => ['nullable', Rule::in(array_keys(PersonProfile::AVAILABILITY_LABELS))],
            'availability_note' => ['nullable', 'string', 'max:300'],
        ]);

        $discoveryConsent = $request->boolean('orientation_consent') && $request->boolean('discovery_consent');
        $discoveryErrors = [];
        if ($discoveryConsent && trim((string) ($data['discovery_display_name'] ?? '')) === '') {
            $discoveryErrors['discovery_display_name'] = 'Choisissez le nom sous lequel les autres membres vous découvriront.';
        }
        if ($discoveryErrors !== []) {
            throw ValidationException::withMessages($discoveryErrors);
        }

        $attributes = [];
        $enabled = static fn (string $section): bool => (bool) ($profileConfiguration['sections'][$section]['enabled'] ?? false);
        if ($enabled('situation')) {
            $attributes += [
                'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'city' => $data['city'] ?? null,
                'phone' => $data['phone'] ?? null,
                'current_activity' => $data['current_activity'] ?? null,
                'education_level' => $data['education_level'] ?? null,
            ];
        }
        if ($enabled('skills')) {
            $skills = ProfileList::fromText($data['existing_skills_text'] ?? null);
            $attributes += [
                'existing_skills' => $skills,
                'starts_without_skill' => $skills === [] && $request->boolean('starts_without_skill'),
            ];
        }
        if ($enabled('transmission')) {
            $attributes['transmission_offers'] = ProfileList::fromText($data['transmission_offers_text'] ?? null);
        }
        if ($enabled('learning')) {
            $attributes['learning_goals'] = ProfileList::fromText($data['learning_goals_text'] ?? null);
        }
        if ($enabled('experience')) {
            $attributes += [
                'experience_highlights' => ProfileList::fromText($data['experience_highlights_text'] ?? null),
                'experience_proofs' => ProfileList::fromText($data['experience_proofs_text'] ?? null),
            ];
        }
        if ($enabled('needs')) {
            $attributes += [
                'declared_needs' => ProfileList::fromText($data['declared_needs_text'] ?? null),
                'interest_domains' => ProfileList::fromText($data['interest_domains_text'] ?? null),
                'intentions' => ProfileList::fromText($data['intentions_text'] ?? null),
            ];
        }
        if ($enabled('collaboration')) {
            $consent = $request->boolean('orientation_consent');
            $attributes += [
                'participation_mode' => $data['participation_mode'] ?? null,
                'collaboration_preferences' => ProfileList::fromText($data['collaboration_preferences_text'] ?? null),
                'orientation_consent' => $consent,
                'discovery_display_name' => $data['discovery_display_name'] ?? null,
                'discovery_bio' => $data['discovery_bio'] ?? null,
                'discovery_consent' => $discoveryConsent,
                'availability_status' => $data['availability_status'] ?? null,
                'availability_note' => $data['availability_note'] ?? null,
            ];
        }

        $capabilityDimensions = [];
        if ($enabled('skills')) {
            $capabilityDimensions[CapabilityStatement::KIND_POSSESSED] = $attributes['existing_skills'];
        }
        if ($enabled('learning')) {
            $capabilityDimensions[CapabilityStatement::KIND_LEARNING] = $attributes['learning_goals'];
        }
        if ($enabled('transmission')) {
            $capabilityDimensions[CapabilityStatement::KIND_TRANSMISSION] = $attributes['transmission_offers'];
        }

        DB::transaction(function () use ($identity, $attributes, $capabilityDimensions, $statementSynchronizer): void {
            $profile = PersonProfile::query()->firstOrNew(['core_identity_reference' => $identity->reference]);
            if (array_key_exists('orientation_consent', $attributes)) {
                $attributes['orientation_consented_at'] = $attributes['orientation_consent']
                    ? ($profile->orientation_consented_at ?? now()) : null;
            }
            if (array_key_exists('discovery_consent', $attributes)) {
                $attributes['discovery_consented_at'] = $attributes['discovery_consent']
                    ? ($profile->discovery_consented_at ?? now()) : null;
                if ($attributes['discovery_consent'] && ! $profile->discovery_reference) {
                    $attributes['discovery_reference'] = (string) Str::uuid();
                }
            }
            if (array_key_exists('availability_status', $attributes) && $attributes['availability_status'] !== $profile->availability_status) {
                $attributes['availability_updated_at'] = $attributes['availability_status'] ? now() : null;
            }
            $profile->fill($attributes);
            $profile->save();

            $statementSynchronizer->sync(
                $identity->reference,
                $capabilityDimensions,
                (bool) $profile->orientation_consent,
                (bool) $profile->discovery_consent,
            );
        });

        return redirect()->route('member.profile.edit')->with('status', 'Votre profil de capacités est enregistré.');
    }
}
