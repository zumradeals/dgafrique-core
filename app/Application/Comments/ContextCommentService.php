<?php

declare(strict_types=1);

namespace App\Application\Comments;

use App\Application\Missions\MissionVisibilityService;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Models\ContextComment;
use App\Models\Mission;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\Transmission;
use App\Models\ZumraGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ContextCommentService
{
    public const MAX_BODY_LENGTH = 1200;
    private const THREAD_LIMIT = 100;

    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly MissionVisibilityService $missionVisibility,
        private readonly TransmissionVisibilityService $transmissionVisibility,
    ) {
    }

    public function needThread(Need $need, string $actor): array
    {
        abort_unless($this->needs->canView($need, $actor), 404);

        return $this->thread([
            'type' => ContextComment::CONTEXT_NEED,
            'reference' => $need->public_reference,
            'label' => 'Besoin',
            'title' => $need->title,
            'summary' => Str::limit(trim($need->context), 240),
            'back_url' => route('needs.show', $need),
            'back_label' => 'Retour au besoin',
            'can_comment' => $need->status !== Need::STATUS_ARCHIVED,
            'closed_text' => 'Ce besoin est archivé : les contributions existantes restent lisibles aux personnes encore autorisées, mais le fil est en lecture seule.',
        ], $actor);
    }

    public function projectThread(Project $project, string $actor): array
    {
        abort_unless($this->projects->canView($project, $actor), 404);

        return $this->thread([
            'type' => ContextComment::CONTEXT_PROJECT,
            'reference' => $project->public_reference,
            'label' => 'Projet',
            'title' => $project->name,
            'summary' => Str::limit(trim($project->summary), 240),
            'back_url' => route('projects.show', $project),
            'back_label' => 'Retour au projet',
            'can_comment' => $project->status !== Project::STATUS_ARCHIVED,
            'closed_text' => 'Ce projet est archivé : son fil de commentaires reste une trace contextuelle en lecture seule pour les personnes autorisées.',
        ], $actor);
    }

    public function zumraActivityThread(ZumraGroup $group, string $actor): array
    {
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 404);

        return $this->thread([
            'type' => ContextComment::CONTEXT_ZUMRA_ACTIVITY,
            'reference' => $group->public_reference,
            'label' => 'Activité ZUMRA',
            'title' => $group->name,
            'summary' => Str::limit(trim($group->founding_objective), 240),
            'back_url' => route('zumra.groups.show', $group),
            'back_label' => 'Retour à la ZUMRA',
            'can_comment' => true,
            'closed_text' => null,
        ], $actor);
    }

    public function missionThread(Mission $mission, string $actor): array
    {
        abort_unless($this->missionVisibility->canViewMission($mission, $actor), 404);

        return $this->thread([
            'type' => ContextComment::CONTEXT_MISSION,
            'reference' => $mission->public_reference,
            'label' => 'Mission',
            'title' => $mission->title,
            'summary' => Str::limit(trim($mission->description), 240),
            'back_url' => route('missions.show', $mission),
            'back_label' => 'Retour à la Mission',
            'can_comment' => ! in_array($mission->status, Mission::TERMINAL_STATUSES, true),
            'closed_text' => 'Cette Mission est terminée : les contributions existantes restent lisibles aux personnes encore autorisées, mais le fil est en lecture seule.',
        ], $actor);
    }

    public function addMission(Mission $mission, string $actor, string $purpose, string $body): ContextComment
    {
        $thread = $this->missionThread($mission, $actor);

        return $this->add($thread['context'], $actor, $purpose, $body);
    }

    public function transmissionThread(Transmission $transmission, string $actor): array
    {
        abort_unless($this->transmissionVisibility->canView($transmission, $actor), 404);

        return $this->thread([
            'type' => ContextComment::CONTEXT_TRANSMISSION,
            'reference' => $transmission->public_reference,
            'label' => 'Transmission',
            'title' => $transmission->capability_label,
            'summary' => Str::limit(trim($transmission->learning_objective), 240),
            'back_url' => route('transmissions.show', $transmission),
            'back_label' => 'Retour à la Transmission',
            'can_comment' => ! in_array($transmission->status, Transmission::TERMINAL_STATUSES, true),
            'closed_text' => 'Cette Transmission est terminée : les contributions existantes restent lisibles aux personnes encore autorisées, mais le fil est en lecture seule.',
        ], $actor);
    }

    public function addTransmission(Transmission $transmission, string $actor, string $purpose, string $body): ContextComment
    {
        $thread = $this->transmissionThread($transmission, $actor);

        return $this->add($thread['context'], $actor, $purpose, $body);
    }

    public function addNeed(Need $need, string $actor, string $purpose, string $body): ContextComment
    {
        $thread = $this->needThread($need, $actor);

        return $this->add($thread['context'], $actor, $purpose, $body);
    }

    public function addProject(Project $project, string $actor, string $purpose, string $body): ContextComment
    {
        $thread = $this->projectThread($project, $actor);

        return $this->add($thread['context'], $actor, $purpose, $body);
    }

    public function addZumraActivity(ZumraGroup $group, string $actor, string $purpose, string $body): ContextComment
    {
        $thread = $this->zumraActivityThread($group, $actor);

        return $this->add($thread['context'], $actor, $purpose, $body);
    }

    private function add(array $context, string $actor, string $purpose, string $body): ContextComment
    {
        abort_unless(isset(ContextComment::PURPOSES[$purpose]), 422, 'Le type de contribution est invalide.');
        abort_unless($context['can_comment'], 409, $context['closed_text'] ?? 'Ce contexte est en lecture seule.');

        $body = trim($body);
        abort_if($body === '' || mb_strlen($body) < 2, 422, 'La contribution est trop courte.');
        abort_if(mb_strlen($body) > self::MAX_BODY_LENGTH, 422, 'La contribution est trop longue.');

        return ContextComment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'context_type' => $context['type'],
            'context_reference' => $context['reference'],
            'author_core_reference' => $actor,
            'purpose' => $purpose,
            'body' => $body,
            'posted_at' => now(),
        ]);
    }

    private function thread(array $context, string $actor): array
    {
        $comments = ContextComment::query()
            ->where('context_type', $context['type'])
            ->where('context_reference', $context['reference'])
            ->latest('posted_at')
            ->limit(self::THREAD_LIMIT)
            ->get()
            ->reverse()
            ->values();

        $labels = $this->labelsFor($comments->pluck('author_core_reference')->unique(), $actor);

        return [
            'context' => $context,
            'purposeLabels' => ContextComment::PURPOSES,
            'comments' => $comments->map(static fn (ContextComment $comment): array => [
                'public_reference' => $comment->public_reference,
                'purpose' => $comment->purpose,
                'purpose_label' => ContextComment::PURPOSES[$comment->purpose] ?? $comment->purpose,
                'body' => $comment->body,
                'posted_at' => $comment->posted_at,
                'author_label' => $labels[$comment->author_core_reference] ?? 'Membre DG Afrique',
            ]),
        ];
    }

    private function labelsFor(Collection $references, string $actor): array
    {
        $references = $references->filter()->unique()->values();
        $profiles = PersonProfile::query()
            ->whereIn('core_identity_reference', $references)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->get()
            ->keyBy('core_identity_reference');

        $labels = [];
        foreach ($references as $reference) {
            $labels[$reference] = $reference === $actor
                ? 'Vous'
                : ($profiles->get($reference)?->discovery_display_name ?: 'Membre DG Afrique');
        }

        return $labels;
    }
}
