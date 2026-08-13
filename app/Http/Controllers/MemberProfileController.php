<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Profile\ProfileList;
use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class MemberProfileController
{
    public function edit(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);

        return view('member.profile', compact('identity', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'city' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'current_activity' => ['nullable', 'string', 'max:256'],
            'education_level' => ['nullable', 'string', 'max:160'],
            'existing_skills_text' => ['nullable', 'string', 'max:3000'],
            'starts_without_skill' => ['nullable', 'boolean'],
            'learning_goals_text' => ['nullable', 'string', 'max:3000'],
            'interest_domains_text' => ['nullable', 'string', 'max:3000'],
            'intentions_text' => ['nullable', 'string', 'max:3000'],
            'participation_mode' => ['nullable', Rule::in(['EN_LIGNE', 'PRESENTIEL', 'HYBRIDE'])],
            'orientation_consent' => ['nullable', 'boolean'],
        ]);

        $skills = ProfileList::fromText($data['existing_skills_text'] ?? null);
        $withoutSkill = $request->boolean('starts_without_skill');
        if ($skills !== []) {
            $withoutSkill = false;
        }

        DB::transaction(function () use ($identity, $data, $skills, $withoutSkill, $request): void {
            $profile = PersonProfile::query()->firstOrNew(['core_identity_reference' => $identity->reference]);
            $consent = $request->boolean('orientation_consent');
            $profile->fill([
                'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'city' => $data['city'] ?? null,
                'phone' => $data['phone'] ?? null,
                'current_activity' => $data['current_activity'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'existing_skills' => $skills,
                'starts_without_skill' => $withoutSkill,
                'learning_goals' => ProfileList::fromText($data['learning_goals_text'] ?? null),
                'interest_domains' => ProfileList::fromText($data['interest_domains_text'] ?? null),
                'intentions' => ProfileList::fromText($data['intentions_text'] ?? null),
                'participation_mode' => $data['participation_mode'] ?? null,
                'orientation_consent' => $consent,
                'orientation_consented_at' => $consent
                    ? ($profile->orientation_consented_at ?? now())
                    : null,
            ]);
            $profile->save();
        });

        return redirect()->route('member.profile.edit')->with('status', 'Votre profil de capacités est enregistré.');
    }
}
