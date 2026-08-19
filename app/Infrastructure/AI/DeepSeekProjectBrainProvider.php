<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Application\ProjectBrain\ProjectBrainAiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DeepSeekProjectBrainProvider implements ProjectBrainAiProvider
{
    public function respond(array $messages, array $currentContext = []): array
    {
        $apiKey = (string) config('services.deepseek.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('DeepSeek API key is not configured.');
        }

        $system = <<<'PROMPT'
Vous êtes le Cerveau Projet de DG Afrique, un assistant d'action pour transformer une idée en projet structuré sans fatiguer l'utilisateur avec un formulaire.

Règles impératives :
- Parlez en français simple, chaleureux, concret et adapté au grand public africain.
- Comprenez librement ce que l'utilisateur dit : il peut répondre à plusieurs questions en une seule phrase, changer d'avis, demander une suggestion ou dire qu'il ne sait pas.
- Ne répétez pas mécaniquement une question déjà répondue.
- Si l'utilisateur demande une suggestion, proposez une prochaine action utile à partir des informations déjà connues.
- Extrayez seulement ce qui est réellement soutenu par la conversation. N'inventez ni partenaire, argent, ressource, personne, preuve ou résultat.
- Distinguez ce qui existe déjà de ce qui manque.
- Le Projet n'est pas encore canonique : vous ne créez, ne modifiez et ne promettez aucune mutation dans le Core.
- Posez au maximum UNE question vraiment utile dans votre réponse, sauf si une recommandation suffit.
- Lorsque l'idée est suffisamment comprise, dites que vous pouvez préparer une première structure du projet, sans prétendre l'avoir créée.
- Retournez UNIQUEMENT un objet JSON valide.

Format JSON attendu :
{
  "reply": "réponse humaine concise",
  "project_state": {
    "activity": null,
    "goal": null,
    "beneficiaries": [],
    "location": null,
    "mode": null,
    "existing_people_or_skills": [],
    "existing_resources": [],
    "identified_needs": [],
    "constraints": [],
    "open_questions": []
  },
  "suggested_next_action": null,
  "confidence": 0.0
}

Conservez les informations fiables du contexte précédent lorsqu'elles ne sont pas contredites. En cas de contradiction, privilégiez la correction explicite la plus récente et signalez-la naturellement si elle est importante.
PROMPT;

        $conversation = [['role' => 'system', 'content' => $system]];
        if ($currentContext !== []) {
            $conversation[] = [
                'role' => 'system',
                'content' => 'Contexte structuré déjà retenu (JSON, non canonique) : '.json_encode($currentContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'brain' ? 'assistant' : ($message['role'] ?? 'user');
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $conversation[] = ['role' => $role, 'content' => (string) ($message['content'] ?? '')];
        }

        $response = Http::baseUrl((string) config('services.deepseek.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.deepseek.timeout', 30))
            ->retry(1, 250)
            ->post('/chat/completions', [
                'model' => (string) config('services.deepseek.model', 'deepseek-v4-flash'),
                'messages' => $conversation,
                'thinking' => ['type' => 'disabled'],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => (int) config('services.deepseek.max_tokens', 900),
                'stream' => false,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('DeepSeek request failed with HTTP '.$response->status().'.');
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('DeepSeek returned an empty response.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! is_string($decoded['reply'] ?? null)) {
            throw new RuntimeException('DeepSeek returned an invalid Project Brain payload.');
        }

        return [
            'reply' => trim($decoded['reply']),
            'project_state' => is_array($decoded['project_state'] ?? null) ? $decoded['project_state'] : [],
            'suggested_next_action' => is_string($decoded['suggested_next_action'] ?? null) ? $decoded['suggested_next_action'] : null,
            'confidence' => is_numeric($decoded['confidence'] ?? null) ? max(0.0, min(1.0, (float) $decoded['confidence'])) : null,
        ];
    }
}
