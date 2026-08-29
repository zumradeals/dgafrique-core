<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Application\ProjectBrain\ProjectBrainAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class DeepSeekProjectBrainProvider implements ProjectBrainAiProvider
{
    public function respond(array $messages, array $currentContext = []): array
    {
        if (! (bool) config('services.deepseek.enabled', false)) {
            throw new RuntimeException('DEEPSEEK_DISABLED');
        }
        $dataPolicyVersion = trim((string) config('services.deepseek.data_policy_version'));
        if ($dataPolicyVersion === '') {
            throw new RuntimeException('DEEPSEEK_DATA_POLICY_NOT_CONFIGURED');
        }
        $apiKey = (string) config('services.deepseek.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('DeepSeek API key is not configured.');
        }

        $system = <<<'PROMPT'
Vous êtes le Cerveau Projet permanent de DG Afrique. Vous accompagnez un projet vivant par conversation naturelle.

Règles impératives :
- Français simple, chaleureux, concret. Répondez d'abord à la personne, pas à un formulaire.
- Utilisez l'historique, l'état du projet et ses besoins Core déjà actifs.
- N'inventez ni partenaire, argent, ressource, personne, preuve ou résultat acquis.
- Vous pouvez conseiller, structurer, signaler un manque et proposer une prochaine action.
- Ne créez et ne modifiez jamais le Core vous-même.
- Si un manque réel est assez précis et mérite un Besoin DG Afrique, proposez UNE action NEED_CREATE dans proposed_actions. Sinon proposed_actions=[] et conversez normalement.
- Une suggestion ou une question générale ne doit pas devenir automatiquement un Besoin.
- Un NEED_CREATE doit contenir title, context, category, capability_label, collaboration_mode, location. category ∈ SKILL, PARTNER, TRAINING, RESOURCE, TECHNICAL, LOGISTICS. collaboration_mode ∈ LOCAL, REMOTE, ANY.
- Pour un lieu physique manquant (local, salle, espace, entrepôt), utilisez de préférence category=RESOURCE et collaboration_mode=LOCAL.
- La visibilité d'un besoin préparé depuis le Cerveau reste privée au projet jusqu'à confirmation et règles Core.
- Une seule question utile maximum par réponse.
- Retournez UNIQUEMENT un objet JSON valide, sans markdown ni bloc ```.

Format :
{
 "reply":"...",
 "project_state":{},
 "suggested_next_action":null,
 "confidence":0.0,
 "proposed_actions":[
   {"type":"NEED_CREATE","title":"...","context":"...","category":"RESOURCE","capability_label":null,"collaboration_mode":"LOCAL","location":"Bouaké","reason":"Pourquoi ce besoin mérite d'être préparé"}
 ]
}
PROMPT;

        $conversation = [['role' => 'system', 'content' => $system]];
        if ($currentContext !== []) $conversation[] = ['role' => 'system', 'content' => 'Contexte DG Afrique actuel (JSON) : '.json_encode($currentContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'brain' ? 'assistant' : ($message['role'] ?? 'user');
            if (in_array($role, ['user','assistant'], true)) $conversation[] = ['role'=>$role,'content'=>(string)($message['content'] ?? '')];
        }

        try {
            $response = Http::baseUrl((string) config('services.deepseek.base_url'))->withToken($apiKey)->acceptJson()
                ->withHeaders(['X-DG-Afrique-Data-Policy' => $dataPolicyVersion])
                ->connectTimeout((int) config('services.deepseek.connect_timeout', 3))
                ->timeout((int) config('services.deepseek.timeout', 30))->retry(1,250)->post('/chat/completions', [
                    'model'=>(string) config('services.deepseek.model','deepseek-v4-flash'), 'messages'=>$conversation,
                    'thinking'=>['type'=>'disabled'], 'response_format'=>['type'=>'json_object'],
                    'max_tokens'=>(int) config('services.deepseek.max_tokens',2000), 'stream'=>false,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('DeepSeek Project Brain transport failure.', ['exception' => $exception::class]);
            throw new RuntimeException('DeepSeek request failed at transport level.', previous: $exception);
        }

        if (! $response->successful()) {
            Log::warning('DeepSeek Project Brain HTTP failure.', ['status'=>$response->status()]);
            throw new RuntimeException('DeepSeek request failed with HTTP '.$response->status().'.');
        }

        $finishReason = $response->json('choices.0.finish_reason');
        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content)==='') {
            Log::warning('DeepSeek Project Brain empty response.', ['finish_reason'=>$finishReason]);
            throw new RuntimeException('DeepSeek returned an empty response.');
        }

        $decoded = $this->decodePayload($content);
        if (!is_array($decoded) || !is_string($decoded['reply'] ?? null)) {
            Log::warning('DeepSeek Project Brain invalid JSON payload.', [
                'finish_reason'=>$finishReason,
                'json_error'=>json_last_error_msg(),
                'content_length'=>strlen($content),
            ]);
            throw new RuntimeException('DeepSeek returned an invalid Project Brain payload.');
        }

        return [
            'reply'=>trim($decoded['reply']),
            'project_state'=>is_array($decoded['project_state']??null)?$decoded['project_state']:[],
            'suggested_next_action'=>is_string($decoded['suggested_next_action']??null)?$decoded['suggested_next_action']:null,
            'confidence'=>is_numeric($decoded['confidence']??null)?max(0.0,min(1.0,(float)$decoded['confidence'])):null,
            'proposed_actions'=>$this->normalizeActions($decoded['proposed_actions'] ?? []),
        ];
    }

    private function decodePayload(string $content): ?array
    {
        $content = trim($content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) return $decoded;

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $content, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) return $decoded;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
            if (is_array($decoded)) return $decoded;
        }

        return null;
    }

    private function normalizeActions(mixed $actions): array
    {
        if (!is_array($actions)) return [];
        if (($actions['type'] ?? null) !== null) $actions = [$actions];

        return array_values(array_filter($actions, static fn ($action): bool => is_array($action) && is_string($action['type'] ?? null)));
    }
}
