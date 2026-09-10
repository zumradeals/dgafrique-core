<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Comments\ContextCommentService;
use App\Domain\Identity\CoreIdentity;
use App\Models\ContextComment;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ZumraDiscussionController
{
    public function show(Request $request, ZumraGroup $group, ContextCommentService $comments): View
    {
        $thread = $comments->zumraActivityThread($group, $this->identity($request)->reference);

        return view('zumra.groups.discussion', ['group' => $group] + $thread);
    }

    public function store(Request $request, ZumraGroup $group, ContextCommentService $comments): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', Rule::in(array_keys(ContextComment::PURPOSES))],
            'body' => ['required', 'string', 'min:2', 'max:'.ContextCommentService::MAX_BODY_LENGTH],
        ]);

        $comments->addZumraActivity(
            $group,
            $this->identity($request)->reference,
            $data['purpose'],
            $data['body'],
        );

        return redirect()
            ->route('zumra.groups.discussion', $group)
            ->with('status', 'Message publié dans la discussion.');
    }

    private function identity(Request $request): CoreIdentity
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity;
    }
}
