<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PersonProfile;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ZumraSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $profile = PersonProfile::query()->find($identity->reference);
        $membership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();

        return view('zumra.index', compact('identity', 'profile', 'membership'));
    }
}
