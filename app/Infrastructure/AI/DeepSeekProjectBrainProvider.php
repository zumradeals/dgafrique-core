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
        if ($apiKey === '') throw new RuntimeException('DeepSeek API key is not configured.');

        $system = <<<'PROMPT'
Vous êtes le Cerveau Projet de DG Afrique. Vous transformez une idée racontée naturellement en projet structuré sans imposer de formulaire.

Règles impératives :
- Français simple, chaleureux, concret.
- Comprenez les changements d'avis, demandes de suggestion et réponses multiples.
- N'inventez ni partenaire, argent, ressource, personne, preuve ou résultat déjà acquis.
- Vous pouvez proposer une formulation professionnelle pour le résumé, le problème, la solution, les objectifs, étapes, risques, capacités et ressources à partir des faits réellement exprimés.
- Distinguez faits exprimés et structuration proposée.
- Ne créez et ne modifiez jamais le Core vous-même.
- Une seule question utile maximum par réponse.
- Quand les éléments essentiels sont assez clairs, mettez ready_for_confirmation=true et dites que la première structure peut être confirmée.
- ready_for_confirmation exige au minimum : un nom ou nom proposé, activité, objectif, bénéficiaires, problème, solution, mode et premières étapes cohérentes. Le lieu peut rester vide si le projet est numérique.
- Retournez UNIQUEMENT un objet JSON valide.

Format :
{
 "reply":"...",
 "project_state":{
   "name":null,"activity":null,"goal":null,"beneficiaries":[],"location":null,"mode":null,
   "problem":null,"proposed_solution":null,"summary":null,"objectives":[],"milestones":[],
   "existing_people_or_skills":[],"existing_resources":[],"identified_needs":[],"constraints":[],
   "required_capabilities":[],"required_resources":[],"risks":[],"open_questions":[],
   "ready_for_confirmation":false
 },
 "suggested_next_action":null,
 "confidence":0.0
}

Conservez les informations fiables du contexte précédent lorsqu'elles ne sont pas contredites. La correction explicite la plus récente prime.
PROMPT;

        $conversation = [['role' => 'system', 'content' => $system]];
        if ($currentContext !== []) $conversation[] = ['role' => 'system', 'content' => 'Contexte structuré retenu (JSON non canonique) : '.json_encode($currentContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'brain' ? 'assistant' : ($message['role'] ?? 'user');
            if (in_array($role, ['user','assistant'], true)) $conversation[] = ['role'=>$role,'content'=>(string)($message['content'] ?? '')];
        }

        $response = Http::baseUrl((string) config('services.deepseek.base_url'))->withToken($apiKey)->acceptJson()
            ->timeout((int) config('services.deepseek.timeout', 30))->retry(1,250)->post('/chat/completions', [
                'model'=>(string) config('services.deepseek.model','deepseek-v4-flash'), 'messages'=>$conversation,
                'thinking'=>['type'=>'disabled'], 'response_format'=>['type'=>'json_object'],
                'max_tokens'=>(int) config('services.deepseek.max_tokens',900), 'stream'=>false,
            ]);
        if (! $response->successful()) throw new RuntimeException('DeepSeek request failed with HTTP '.$response->status().'.');
        $content=$response->json('choices.0.message.content');
        if (!is_string($content)||trim($content)==='') throw new RuntimeException('DeepSeek returned an empty response.');
        $decoded=json_decode($content,true);
        if (!is_array($decoded)||!is_string($decoded['reply']??null)) throw new RuntimeException('DeepSeek returned an invalid Project Brain payload.');
        return [
            'reply'=>trim($decoded['reply']),
            'project_state'=>is_array($decoded['project_state']??null)?$decoded['project_state']:[],
            'suggested_next_action'=>is_string($decoded['suggested_next_action']??null)?$decoded['suggested_next_action']:null,
            'confidence'=>is_numeric($decoded['confidence']??null)?max(0.0,min(1.0,(float)$decoded['confidence'])):null,
        ];
    }
}
