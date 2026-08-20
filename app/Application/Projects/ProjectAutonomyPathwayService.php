<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\ProjectAutonomyPathway;
use App\Models\ProjectEvent;
use Illuminate\Support\Facades\DB;

final class ProjectAutonomyPathwayService
{
    public const ELIGIBLE_MATURITIES = [
        'POTENTIAL_STRUCTURE',
        'AUTONOMY_READY',
    ];

    public const TARGET_FORMS = [
        'COMPANY' => 'Entreprise',
        'ASSOCIATION' => 'Association',
        'COOPERATIVE' => 'Coopérative',
        'STARTUP' => 'Startup',
        'PLATFORM' => 'Plateforme',
        'OTHER' => 'Autre forme pertinente',
    ];

    public function __construct(private readonly ProjectService $projects)
    {
    }

    public function isEligible(Project $project): bool
    {
        return $project->status !== Project::STATUS_ARCHIVED
            && in_array((string) $project->maturity, self::ELIGIBLE_MATURITIES, true);
    }

    public function open(Project $project, string $actor, array $data): ProjectAutonomyPathway
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
        abort_unless($this->isEligible($project), 409, 'Le projet doit d’abord atteindre un repère de maturité compatible avec l’exploration d’une structure autonome.');

        $targetForm = (string) ($data['target_form'] ?? '');
        abort_unless(isset(self::TARGET_FORMS[$targetForm]), 422, 'Forme envisagée inconnue.');

        $otherFormLabel = trim((string) ($data['other_form_label'] ?? ''));
        if ($targetForm === 'OTHER') {
            abort_if($otherFormLabel === '', 422, 'La forme envisagée doit être précisée.');
        } else {
            $otherFormLabel = '';
        }

        return DB::transaction(function () use ($project, $actor, $targetForm, $otherFormLabel): ProjectAutonomyPathway {
            $pathway = ProjectAutonomyPathway::query()->firstOrNew(['project_id' => $project->id]);

            abort_if(
                $pathway->exists && $pathway->status === ProjectAutonomyPathway::STATUS_ACTIVE,
                409,
                'Un parcours d’autonomie est déjà actif pour ce projet.'
            );

            $pathway->fill([
                'status' => ProjectAutonomyPathway::STATUS_ACTIVE,
                'target_form' => $targetForm,
                'other_form_label' => $otherFormLabel !== '' ? $otherFormLabel : null,
                'opened_by_core_reference' => $actor,
                'opened_at' => now(),
                'closed_by_core_reference' => null,
                'closed_at' => null,
            ])->save();

            $this->event($project, 'AUTONOMY_PATHWAY_OPENED', $actor, [
                'pathway_id' => $pathway->id,
                'target_form' => $targetForm,
                'other_form_label' => $pathway->other_form_label,
            ]);

            return $pathway;
        });
    }

    public function close(Project $project, string $actor): void
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);

        $pathway = ProjectAutonomyPathway::query()
            ->where('project_id', $project->id)
            ->where('status', ProjectAutonomyPathway::STATUS_ACTIVE)
            ->first();

        abort_unless($pathway, 409, 'Aucun parcours d’autonomie actif à fermer.');

        DB::transaction(function () use ($project, $actor, $pathway): void {
            $pathway->update([
                'status' => ProjectAutonomyPathway::STATUS_CLOSED,
                'closed_by_core_reference' => $actor,
                'closed_at' => now(),
            ]);

            $this->event($project, 'AUTONOMY_PATHWAY_CLOSED', $actor, [
                'pathway_id' => $pathway->id,
            ]);
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
