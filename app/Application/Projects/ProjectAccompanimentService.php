<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectAccompanimentAction;
use App\Models\ProjectEvent;
use Illuminate\Support\Facades\DB;

final class ProjectAccompanimentService
{
    public function __construct(private readonly ProjectService $projects)
    {
    }

    public function activate(Project $project, string $actor): ProjectAccompaniment
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
        abort_if($project->status === Project::STATUS_ARCHIVED, 409, 'Un projet archivé ne peut pas ouvrir un accompagnement.');

        return DB::transaction(function () use ($project, $actor): ProjectAccompaniment {
            $accompaniment = ProjectAccompaniment::query()->firstOrNew(['project_id' => $project->id]);

            if ($accompaniment->exists && $accompaniment->status === ProjectAccompaniment::STATUS_ACTIVE) {
                return $accompaniment;
            }

            $accompaniment->fill([
                'status' => ProjectAccompaniment::STATUS_ACTIVE,
                'activated_by_core_reference' => $actor,
                'activated_at' => now(),
                'ended_by_core_reference' => null,
                'ended_at' => null,
            ])->save();

            $this->event($project, 'ACCOMPANIMENT_ACTIVATED', $actor, [
                'accompaniment_id' => $accompaniment->id,
            ]);

            return $accompaniment;
        });
    }

    public function end(Project $project, string $actor): void
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);

        $accompaniment = ProjectAccompaniment::query()
            ->where('project_id', $project->id)
            ->where('status', ProjectAccompaniment::STATUS_ACTIVE)
            ->first();

        abort_unless($accompaniment, 409, 'Aucun accompagnement actif à terminer.');

        DB::transaction(function () use ($project, $actor, $accompaniment): void {
            $accompaniment->update([
                'status' => ProjectAccompaniment::STATUS_ENDED,
                'ended_by_core_reference' => $actor,
                'ended_at' => now(),
            ]);

            $this->event($project, 'ACCOMPANIMENT_ENDED', $actor, [
                'accompaniment_id' => $accompaniment->id,
            ]);
        });
    }

    public function recordAction(Project $project, string $actor, array $data, array $configuration): ProjectAccompanimentAction
    {
        abort_unless(
            PortalAdministrator::query()->where('core_identity_reference', $actor)->exists(),
            403
        );

        $accompaniment = ProjectAccompaniment::query()
            ->where('project_id', $project->id)
            ->where('status', ProjectAccompaniment::STATUS_ACTIVE)
            ->first();

        abort_unless($accompaniment, 409, 'Le porteur doit avoir activé un accompagnement avant toute intervention.');

        $types = $configuration['action_types'] ?? [];
        abort_unless(isset($types[$data['action_type']]), 422, 'Type d’intervention indisponible.');

        $source = (string) $data['delivery_source'];
        abort_unless(
            in_array($source, [ProjectAccompanimentAction::SOURCE_INTERNAL, ProjectAccompanimentAction::SOURCE_PARTNER], true),
            422,
            'Source d’intervention invalide.'
        );

        $providerLabel = trim((string) ($data['provider_label'] ?? ''));
        if ($source === ProjectAccompanimentAction::SOURCE_PARTNER) {
            abort_if($providerLabel === '', 422, 'Le partenaire doit être identifié.');
        } elseif ($providerLabel === '') {
            $providerLabel = 'DG Afrique';
        }

        return DB::transaction(function () use ($project, $actor, $data, $accompaniment, $source, $providerLabel): ProjectAccompanimentAction {
            $action = ProjectAccompanimentAction::query()->create([
                'project_accompaniment_id' => $accompaniment->id,
                'action_type' => $data['action_type'],
                'delivery_source' => $source,
                'provider_label' => $providerLabel,
                'summary' => trim((string) $data['summary']),
                'next_step' => isset($data['next_step']) && trim((string) $data['next_step']) !== ''
                    ? trim((string) $data['next_step'])
                    : null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'recorded_by_core_reference' => $actor,
            ]);

            $this->event($project, 'ACCOMPANIMENT_ACTION_RECORDED', $actor, [
                'accompaniment_id' => $accompaniment->id,
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'delivery_source' => $action->delivery_source,
            ]);

            return $action;
        });
    }

    private function event(Project $project, string $event, string $actor, array $context): void
    {
        ProjectEvent::query()->create([
            'project_id' => $project->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
