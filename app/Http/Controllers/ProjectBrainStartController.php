<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\ProjectBrain\ProjectBrainAiProvider;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use App\Models\ProjectBrainIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

final class ProjectBrainStartController
{
    public function create(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('projects.brain-start', compact('identity', 'isAdministrator'));
    }

    public function store(Request $request, ProjectBrainAiProvider $brain): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate(['message' => ['required', 'string', 'min:12', 'max:3000']]);
        $message = trim($data['message']);
        $messages = [['role' => 'user', 'content' => $message, 'at' => now()->toIso8601String()]];
        $context = ['initial_intent' => $message];

        $result = $this->askBrain($brain, $messages, $context);
        $messages[] = ['role' => 'brain', 'content' => $result['reply'], 'at' => now()->toIso8601String()];
        $context = $this->mergeBrainContext($context, $result);

        $intent = ProjectBrainIntent::query()->create([
            'actor_core_reference' => $identity->reference,
            'title' => Str::limit($message, 72, '…'),
            'messages' => $messages,
            'context' => $context,
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

    public function reply(Request $request, ProjectBrainIntent $intent, ProjectBrainAiProvider $brain): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($intent->actor_core_reference === $identity->reference, 404);
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:3000']]);

        $messages = $intent->messages ?? [];
        $messages[] = ['role' => 'user', 'content' => trim($data['message']), 'at' => now()->toIso8601String()];
        $context = $intent->context ?? [];
        $result = $this->askBrain($brain, $messages, $context);
        $messages[] = ['role' => 'brain', 'content' => $result['reply'], 'at' => now()->toIso8601String()];
        $context = $this->mergeBrainContext($context, $result);
        $intent->update(['messages' => $messages, 'context' => $context]);

        return redirect()->route('projects.brain.start.show', $intent);
    }

    /** @return array{reply:string,project_state:array<string,mixed>,suggested_next_action:?string,confidence:?float} */
    private function askBrain(ProjectBrainAiProvider $brain, array $messages, array $context): array
    {
        try {
            return $brain->respond($messages, $context['project_state'] ?? []);
        } catch (Throwable $exception) {
            Log::warning('Project Brain AI unavailable; deterministic fallback used.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'reply' => 'Je garde bien votre message. Mon intelligence d’analyse est momentanément indisponible, mais votre brouillon reste sauvegardé. Vous pouvez continuer à préciser votre idée.',
                'project_state' => is_array($context['project_state'] ?? null) ? $context['project_state'] : [],
                'suggested_next_action' => null,
                'confidence' => null,
            ];
        }
    }

    private function mergeBrainContext(array $context, array $result): array
    {
        $context['project_state'] = $result['project_state'];
        $context['suggested_next_action'] = $result['suggested_next_action'];
        $context['ai_confidence'] = $result['confidence'];
        $context['ai_provider'] = 'deepseek';
        $context['ai_model'] = (string) config('services.deepseek.model', 'deepseek-v4-flash');

        return $context;
    }
}
