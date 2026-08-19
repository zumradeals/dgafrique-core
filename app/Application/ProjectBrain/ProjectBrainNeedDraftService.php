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
use Illuminate\Support\Str;

final class ProjectBrainNeedDraftService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly NeedConfiguration $configuration,
    ) {}

    public function conversation(Project $project, string $actor): ProjectBrainConversation
    {
        return ProjectBrainConversation::query()->firstOrCreate(
            ['project_id' => $project->id, 'actor_core_reference' => $actor],
            ['title' => 'Cerveau — '.$project->name],
        );
    }

    public function prepare(Project $project, string $actor, string $message): ProjectBrainDraft
    {
        $conversation = $this->conversation($project, $actor);
        $text = trim($message);
        $payload = $this->inferNeed($project, $text);

        return DB::transaction(function () use ($conversation, $project, $actor, $text, $payload): ProjectBrainDraft {
            ProjectBrainMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => ProjectBrainMessage::ROLE_USER,
                'content' => $text,
            ]);

            $draft = ProjectBrainDraft::query()->create([
                'conversation_id' => $conversation->id,
                'project_id' => $project->id,
                'actor_core_reference' => $actor,
                'kind' => ProjectBrainDraft::KIND_NEED_CREATE,
                'status' => ProjectBrainDraft::STATUS_PENDING,
                'payload' => $payload,
            ]);

            ProjectBrainMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => ProjectBrainMessage::ROLE_ASSISTANT,
                'content' => 'J’ai préparé un besoin à partir de votre message. Vérifiez la carte puis confirmez pour créer le vrai besoin.',
                'meta' => ['draft_reference' => $draft->id, 'kind' => $draft->kind],
            ]);

            return $draft;
        });
    }

    public function confirm(Project $project, ProjectBrainDraft $draft, string $actor): Need
    {
        abort_unless($draft->project_id === $project->id && $draft->actor_core_reference === $actor, 404);

        return DB::transaction(function () use ($project, $draft, $actor): Need {
            /** @var ProjectBrainDraft $locked */
            $locked = ProjectBrainDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if ($locked->status === ProjectBrainDraft::STATUS_CONFIRMED && $locked->result_reference !== null) {
                return Need::query()->where('public_reference', $locked->result_reference)->firstOrFail();
            }
            abort_unless($locked->status === ProjectBrainDraft::STATUS_PENDING, 409, 'Ce brouillon n’est plus confirmable.');

            $need = $this->needs->create($actor, $locked->payload, $this->configuration->get());
            $locked->update([
                'status' => ProjectBrainDraft::STATUS_CONFIRMED,
                'result_reference' => $need->public_reference,
                'confirmed_at' => now(),
            ]);
            ProjectBrainMessage::query()->create([
                'conversation_id' => $locked->conversation_id,
                'role' => ProjectBrainMessage::ROLE_SYSTEM,
                'content' => 'Besoin créé dans le Core : '.$need->title,
                'meta' => ['need_reference' => $need->public_reference],
            ]);

            return $need;
        });
    }

    public function cancel(Project $project, ProjectBrainDraft $draft, string $actor): void
    {
        abort_unless($draft->project_id === $project->id && $draft->actor_core_reference === $actor, 404);
        ProjectBrainDraft::query()->whereKey($draft->id)->where('status', ProjectBrainDraft::STATUS_PENDING)->update(['status' => ProjectBrainDraft::STATUS_CANCELLED]);
    }

    private function inferNeed(Project $project, string $text): array
    {
        $lower = Str::lower($text);
        $category = str_contains($lower, 'formation') ? 'TRAINING'
            : (str_contains($lower, 'partenaire') ? 'PARTNER'
            : ((str_contains($lower, 'matériel') || str_contains($lower, 'materiel') || str_contains($lower, 'ressource')) ? 'RESOURCE'
            : ((str_contains($lower, 'logistique') || str_contains($lower, 'transport')) ? 'LOGISTICS'
            : ((str_contains($lower, 'technique') || str_contains($lower, 'dévelop') || str_contains($lower, 'develop')) ? 'TECHNICAL' : 'SKILL'))));

        $location = null;
        if (preg_match('/\b(?:à|a)\s+([\p{L}][\p{L}\- ]{1,50})/ui', $text, $matches) === 1) {
            $location = trim(preg_split('/[,.;]|\b(?:pour|afin|avec|qui)\b/ui', $matches[1])[0] ?? '');
            $location = $location !== '' ? Str::limit($location, 160, '') : null;
        }

        $title = Str::ucfirst(Str::limit(preg_replace('/\s+/u', ' ', $text) ?: $text, 150, '…'));
        $context = 'Besoin exprimé depuis le Cerveau du projet « '.$project->name.' » : '.$text;

        return [
            'owner_type' => Need::OWNER_PROJECT,
            'project_reference' => $project->public_reference,
            'title' => $title,
            'context' => $context,
            'category' => $category,
            'capability_label' => $category === 'SKILL' || $category === 'TECHNICAL' ? Str::limit($text, 200, '') : null,
            'collaboration_mode' => $location !== null ? 'LOCAL' : 'ANY',
            'location' => $location,
            'visibility' => Need::VISIBILITY_PRIVATE,
        ];
    }
}
