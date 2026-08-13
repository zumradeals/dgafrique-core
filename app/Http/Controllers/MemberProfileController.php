<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Profile\ProfileList;
use App\Application\Profile\ProfileConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('member.profile', compact('identity', 'profile', 'profileConfiguration'));
    }

    public function update(Request $request, ProfileConfiguration $configuration): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profileConfiguration = $configuration->get();
        $allowedModes = array_column($profileConfiguration['participation_modes'] ?? [], 'value');
        $fieldSections = [
            'country_code' => 'situation', 'city' => 'situation', 'phone' => 'situation',
            'current_activity' => 'situation', 'education_level' => 'situation',
            'existing_skills_text' => 'skills', 'learning_goals_text' => 'learning',
            'interest_domains_text' => 'intentions', 'intentions_text' => 'intentions',
            'participation_mode' => 'intentions',
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
            'learning_goals_text' => [$presence('learning_goals_text'), 'string', 'max:3000'],
            'interest_domains_text' => [$presence('interest_domains_text'), 'string', 'max:3000'],
            'intentions_text' => [$presence('intentions_text'), 'string', 'max:3000'],
            'participation_mode' => [$presence('participation_mode'), Rule::in($allowedModes)],
            'orientation_consent' => ['nullable', 'boolean'],
        ]);

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
        if ($enabled('learning')) {
            $attributes['learning_goals'] = ProfileList::fromText($data['learning_goals_text'] ?? null);
        }
        if ($enabled('intentions')) {
            $consent = $request->boolean('orientation_consent');
            $attributes += [
                'interest_domains' => ProfileList::fromText($data['interest_domains_text'] ?? null),
                'intentions' => ProfileList::fromText($data['intentions_text'] ?? null),
                'participation_mode' => $data['participation_mode'] ?? null,
                'orientation_consent' => $consent,
            ];
        }

        DB::transaction(function () use ($identity, $attributes): void {
            $profile = PersonProfile::query()->firstOrNew(['core_identity_reference' => $identity->reference]);
            if (array_key_exists('orientation_consent', $attributes)) {
                $attributes['orientation_consented_at'] = $attributes['orientation_consent']
                    ? ($profile->orientation_consented_at ?? now()) : null;
            }
            $profile->fill($attributes);
            $profile->save();
        });

        return redirect()->route('member.profile.edit')->with('status', 'Votre profil de capacités est enregistré.');
    }
}
