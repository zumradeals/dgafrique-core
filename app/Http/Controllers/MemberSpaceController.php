<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MemberSpaceController
{
    public function __invoke(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return view('member.space', ['identity' => $identity]);
    }
}
