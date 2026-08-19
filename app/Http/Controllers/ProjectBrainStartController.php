<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ProjectBrainIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ProjectBrainStartController
{
    public function create(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.brain-start', compact('identity', 'isAdministrator'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['message' => ['required', 'string', 'min:12', 'max:3000']]);
        $message = trim($data['message']);

        $intent = ProjectBrainIntent::query()->create([
            'actor_core_reference' => $identity->reference,
            'title' => Str::limit($message, 72, '…'),
            'messages' => [
                ['role' => 'user', 'content' => $message, 'at' => now()->toIso8601String()],
                ['role' => 'brain', 'content' => 'J’ai retenu votre idée. On va la structurer ensemble, une question utile à la fois. Quel résultat concret voulez-vous obtenir en premier ?', 'at' => now()->toIso8601String()],
            ],
            'context' => ['initial_intent' => $message],
            'status' => 'DRAFT',
        ]);

        return redirect()->route('projects.brain.start.show', $intent);
    }

    public function show(Request $request, ProjectBrainIntent $intent): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($intent->actor_core_reference === $identity->reference, 404);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.brain-start', compact('identity', 'isAdministrator', 'intent'));
    }

    public function reply(Request $request, ProjectBrainIntent $intent): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($intent->actor_core_reference === $identity->reference, 404);
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:3000']]);

        $messages = $intent->messages ?? [];
        $messages[] = ['role' => 'user', 'content' => trim($data['message']), 'at' => now()->toIso8601String()];
        $userReplies = collect($messages)->where('role', 'user')->count();
        $prompt = match (true) {
            $userReplies <= 2 => 'Très bien. Qui doit principalement bénéficier de ce projet ?',
            $userReplies === 3 => 'Compris. Où voulez-vous commencer : dans une ville précise, en ligne, ou les deux ?',
            $userReplies === 4 => 'Bien. De quoi disposez-vous déjà pour commencer : personnes, compétences, matériel ou partenaires ?',
            default => 'Votre projet prend forme. Continuez naturellement : dites-moi ce qui manque, ce qui vous inquiète ou la prochaine action que vous imaginez.',
        };
        $messages[] = ['role' => 'brain', 'content' => $prompt, 'at' => now()->toIso8601String()];
        $context = $intent->context ?? [];
        $context['answers'][] = trim($data['message']);
        $intent->update(['messages' => $messages, 'context' => $context]);

        return redirect()->route('projects.brain.start.show', $intent);
    }
}
