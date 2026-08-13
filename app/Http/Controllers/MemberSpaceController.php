<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MemberSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $profile = PersonProfile::query()->find($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('member.space', compact('identity', 'profile', 'isAdministrator'));
    }
}
