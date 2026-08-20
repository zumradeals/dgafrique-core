<?php

declare(strict_types=1);

namespace App\Application\ProjectBrain;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectBrainConversation;
use App\Models\ProjectBrainDraft;
use App\Models\ProjectBrainMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ProjectBrainNeedDraftService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly NeedConfiguration $configuration,
        private readonly ProjectBrainAiProvider $ai,
    ) {}

    public function conversation(Project $project, string $actor): ProjectBrainConversation
    {
        return ProjectBrainConversation::query()->firstOrCreate(
            ['project_id' => $project->id, 'actor_core_reference' => $actor],
            ['title' => 'Cerveau — '.$project->name],
        );
    }

    public function prepare(Project $project, string $actor, string $message): ?ProjectBrainDraft
    {
        $conversation = $this->conversation($project, $actor);
        $text = trim($message);

        return DB::transaction(function () use ($conversation, $project, $actor, $text): ?ProjectBrainDraft {
            ProjectBrainMessage::query()->create(['conversation_id'=>$conversation->id,'role'=>ProjectBrainMessage::ROLE_USER,'content'=>$text]);

            $history = ProjectBrainMessage::query()->where('conversation_id',$conversation->id)->latest()->limit(24)->get()->reverse()->map(fn (ProjectBrainMessage $m): array => [
                'role'=>$m->role === ProjectBrainMessage::ROLE_ASSISTANT ? 'assistant' : 'user', 'content'=>$m->content,
            ])->values()->all();

            $activeNeeds = Need::query()->where('owner_type',Need::OWNER_PROJECT)->where('owner_reference',$project->id)
                ->whereIn('status',[Need::STATUS_PROPOSED,Need::STATUS_OPEN,Need::STATUS_IN_PROGRESS])->latest()->limit(20)->get(['title','category','location','status'])->toArray();

            try {
                $result = $this->ai->respond($history, [
                    'phase'=>'PERMANENT_PROJECT_BRAIN',
                    'project'=>array_merge($project->only(['name','summary','problem','proposed_solution','domain','mode','location','status']), ['public_reference'=>$project->public_reference]),
                    'active_needs'=>$activeNeeds,
                    'rule'=>'Conversation first. Core mutations require explicit human confirmation.',
                ]);
            } catch (Throwable $e) {
                Log::warning('Permanent Project Brain AI unavailable; conversation preserved.', ['project'=>$project->public_reference,'exception'=>$e::class,'message'=>$e->getMessage()]);
                ProjectBrainMessage::query()->create(['conversation_id'=>$conversation->id,'role'=>ProjectBrainMessage::ROLE_ASSISTANT,'content'=>'J’ai bien gardé votre message, mais mon analyse est momentanément indisponible. Rien n’a été créé dans le Core.']);
                return null;
            }

            $draft = null;
            $action = collect($result['proposed_actions'] ?? [])->first(fn ($candidate): bool => is_array($candidate) && strtoupper((string)($candidate['type'] ?? '')) === 'NEED_CREATE');
            if (is_array($action)) {
                $payload = $this->normalizeNeed($project, $action, $text);
                if ($payload !== null) {
                    $draft = ProjectBrainDraft::query()->create([
                        'conversation_id'=>$conversation->id,'project_id'=>$project->id,'actor_core_reference'=>$actor,
                        'kind'=>ProjectBrainDraft::KIND_NEED_CREATE,'status'=>ProjectBrainDraft::STATUS_PENDING,'payload'=>$payload,
                    ]);
                } else {
                    Log::notice('Permanent Project Brain ignored incomplete NEED_CREATE action.', [
                        'project'=>$project->public_reference,
                        'category'=>$action['category'] ?? null,
                        'has_title'=>trim((string)($action['title'] ?? '')) !== '',
                        'has_context'=>trim((string)($action['context'] ?? '')) !== '',
                    ]);
                }
            }

            ProjectBrainMessage::query()->create([
                'conversation_id'=>$conversation->id,'role'=>ProjectBrainMessage::ROLE_ASSISTANT,'content'=>$result['reply'],
                'meta'=>$draft ? ['draft_reference'=>$draft->id,'kind'=>$draft->kind,'ai_confidence'=>$result['confidence'] ?? null] : ['ai_confidence'=>$result['confidence'] ?? null],
            ]);
            return $draft;
        });
    }

    public function confirm(Project $project, ProjectBrainDraft $draft, string $actor): Need
    {
        abort_unless($draft->project_id === $project->id && $draft->actor_core_reference === $actor, 404);
        return DB::transaction(function () use ($project, $draft, $actor): Need {
            $locked = ProjectBrainDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if ($locked->status === ProjectBrainDraft::STATUS_CONFIRMED && $locked->result_reference !== null) return Need::query()->where('public_reference',$locked->result_reference)->firstOrFail();
            abort_unless($locked->status === ProjectBrainDraft::STATUS_PENDING,409,'Ce brouillon n’est plus confirmable.');
            $need=$this->needs->create($actor,$locked->payload,$this->configuration->get());
            $locked->update(['status'=>ProjectBrainDraft::STATUS_CONFIRMED,'result_reference'=>$need->public_reference,'confirmed_at'=>now()]);
            ProjectBrainMessage::query()->create(['conversation_id'=>$locked->conversation_id,'role'=>ProjectBrainMessage::ROLE_SYSTEM,'content'=>'Besoin créé dans le Core : '.$need->title,'meta'=>['need_reference'=>$need->public_reference]]);
            return $need;
        });
    }

    public function cancel(Project $project, ProjectBrainDraft $draft, string $actor): void
    {
        abort_unless($draft->project_id === $project->id && $draft->actor_core_reference === $actor,404);
        ProjectBrainDraft::query()->whereKey($draft->id)->where('status',ProjectBrainDraft::STATUS_PENDING)->update(['status'=>ProjectBrainDraft::STATUS_CANCELLED]);
    }

    private function normalizeNeed(Project $project, array $action, string $source): ?array
    {
        $categories=array_keys($this->configuration->get()['categories'] ?? []);
        $requestedCategory=strtoupper(trim((string)($action['category'] ?? '')));
        $category=in_array($requestedCategory,$categories,true)?$requestedCategory:null;
        $title=trim((string)($action['title'] ?? ''));
        $context=trim((string)($action['context'] ?? ''));
        if ($category===null || mb_strlen($title)<4 || mb_strlen($context)<8) return null;
        $requestedMode=strtoupper(trim((string)($action['collaboration_mode'] ?? 'ANY')));
        $mode=in_array($requestedMode,['LOCAL','REMOTE','ANY'],true)?$requestedMode:'ANY';
        $location=isset($action['location']) && is_string($action['location']) ? trim($action['location']) : null;
        return [
            'owner_type'=>Need::OWNER_PROJECT,'project_reference'=>$project->public_reference,
            'title'=>Str::limit($title,150,'…'),'context'=>Str::limit($context,3000,'…'),'category'=>$category,
            'capability_label'=>isset($action['capability_label']) && is_string($action['capability_label']) ? Str::limit(trim($action['capability_label']),200,'') : null,
            'collaboration_mode'=>$mode,'location'=>$location !== '' ? Str::limit($location,160,'') : null,
            'visibility'=>Need::VISIBILITY_PRIVATE,
            'brain_source'=>Str::limit($source,500,''),'brain_reason'=>isset($action['reason']) ? Str::limit((string)$action['reason'],500,'') : null,
        ];
    }
}
