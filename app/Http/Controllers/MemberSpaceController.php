<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MemberSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $profile = PersonProfile::query()->find($identity->reference);
        $zumraMembership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $greetingName = preg_split('/\s+/u', trim($identity->label))[0] ?? $identity->label;
        $profileCompletion = $this->profileCompletion($profile);

        return view('member.space', compact(
            'identity', 'profile', 'zumraMembership', 'isAdministrator', 'greetingName', 'profileCompletion'
        ));
    }

    private function profileCompletion(?PersonProfile $profile): int
    {
        if (! $profile) {
            return 0;
        }

        $completed = [
            filled($profile->country_code) || filled($profile->city),
            filled($profile->phone),
            filled($profile->current_activity),
            filled($profile->education_level),
            filled($profile->existing_skills) || $profile->starts_without_skill,
            filled($profile->transmission_offers),
            filled($profile->learning_goals),
            filled($profile->experience_highlights) || filled($profile->experience_proofs),
            filled($profile->declared_needs),
            filled($profile->interest_domains),
            filled($profile->intentions),
            filled($profile->participation_mode),
            filled($profile->collaboration_preferences),
            $profile->orientation_consent,
        ];

        return (int) round((count(array_filter($completed)) / count($completed)) * 100);
    }
}
