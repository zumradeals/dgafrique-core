<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Messaging\MessagingService;
use App\Domain\Identity\CoreIdentity;
use App\Models\MessageConversation;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MessagingController
{
    public function index(Request $request, MessagingService $messaging): View
    {
        $identity = $this->identity($request);

        return view('messages.index', [
            'identity' => $identity,
            'conversations' => $messaging->inbox($identity->reference),
        ]);
    }

    public function show(Request $request, MessageConversation $conversation, MessagingService $messaging): View
    {
        $identity = $this->identity($request);

        return view('messages.show', $messaging->thread($conversation, $identity->reference) + [
            'identity' => $identity,
        ]);
    }

    public function send(Request $request, MessageConversation $conversation, MessagingService $messaging): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $messaging->send($conversation, $identity->reference, $data['body']);

        return redirect()->route('messages.show', $conversation);
    }

    public function startDirect(Request $request, string $reference, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->startDirect($this->identity($request)->reference, $reference);

        return redirect()->route('messages.show', $conversation);
    }

    public function openNeed(Request $request, Need $need, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openNeed($this->identity($request)->reference, $need);

        return redirect()->route('messages.show', $conversation);
    }

    public function openMission(Request $request, Mission $mission, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openMission($this->identity($request)->reference, $mission);

        return redirect()->route('messages.show', $conversation);
    }

    public function openZumra(Request $request, ZumraGroup $group, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openZumra($this->identity($request)->reference, $group);

        return redirect()->route('messages.show', $conversation);
    }

    public function openInvitation(Request $request, ZumraGroup $group, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openInvitation($this->identity($request)->reference, $group);

        return redirect()->route('messages.show', $conversation);
    }

    public function openProject(Request $request, Project $project, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openProject($this->identity($request)->reference, $project);

        return redirect()->route('messages.show', $conversation);
    }

    public function addProjectParticipant(Request $request, MessageConversation $conversation, MessagingService $messaging): RedirectResponse
    {
        $identity = $this->identity($request);
        $data = $request->validate(['person_reference' => ['required', 'uuid']]);
        $messaging->addProjectParticipant($conversation, $identity->reference, $data['person_reference']);

        return redirect()->route('messages.show', $conversation)
            ->with('status', 'Participant ajouté à cette conversation de coordination. Aucun statut de membre du projet n’a été créé.');
    }

    public function openSupport(Request $request, MessagingService $messaging): RedirectResponse
    {
        $conversation = $messaging->openSupport($this->identity($request)->reference);

        return redirect()->route('messages.show', $conversation);
    }

    private function identity(Request $request): CoreIdentity
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity;
    }
}
