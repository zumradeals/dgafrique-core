<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\ProjectEvent;
use Illuminate\Support\Facades\DB;

final class ProjectMaturityService
{
    public const STAGES = [
        'IDEA' => [
            'label' => 'Idée',
            'description' => 'Le projet existe comme intention, problème identifié ou proposition à explorer.',
        ],
        'EXPLORATION' => [
            'label' => 'Exploration',
            'description' => 'Le besoin, les bénéficiaires, les options et les premières hypothèses sont explorés.',
        ],
        'TEAM' => [
            'label' => 'Équipe',
            'description' => 'Des personnes commencent à se réunir autour du projet et à répartir des responsabilités.',
        ],
        'EXPERIMENT' => [
            'label' => 'Prototype / expérimentation',
            'description' => 'Une première forme, un prototype ou une expérimentation permet d’apprendre du réel.',
        ],
        'STRUCTURED' => [
            'label' => 'Projet structuré',
            'description' => 'Le projet possède une organisation, des objectifs et un chemin d’action suffisamment structurés.',
        ],
        'ACTIVITY' => [
            'label' => 'Activité',
            'description' => 'Le projet produit déjà une activité, un service, un usage ou des résultats observables.',
        ],
        'POTENTIAL_STRUCTURE' => [
            'label' => 'Structure potentielle',
            'description' => 'Le projet peut explorer une structure plus autonome, sans que cela constitue un statut juridique.',
        ],
        'AUTONOMY_READY' => [
            'label' => 'Autonomie à explorer',
            'description' => 'Le projet présente des éléments suffisants pour explorer une autonomie organisationnelle, économique ou juridique, sans transformation automatique de sa nature.',
        ],
    ];

    public function __construct(private readonly ProjectService $projects)
    {
    }

    public function change(Project $project, string $actor, string $toMaturity, ?string $note = null): void
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
        abort_unless(array_key_exists($toMaturity, self::STAGES), 422, 'Repère de maturité inconnu.');

        $fromMaturity = (string) $project->maturity;
        abort_if($fromMaturity === $toMaturity, 409, 'Le projet est déjà situé à ce repère de maturité.');

        $normalizedNote = trim((string) $note);

        DB::transaction(function () use ($project, $actor, $fromMaturity, $toMaturity, $normalizedNote): void {
            $project->update(['maturity' => $toMaturity]);

            ProjectEvent::query()->create([
                'project_id' => $project->id,
                'event' => 'PROJECT_MATURITY_CHANGED',
                'actor_core_reference' => $actor,
                'context' => [
                    'from' => $fromMaturity,
                    'to' => $toMaturity,
                    'note' => $normalizedNote !== '' ? $normalizedNote : null,
                ],
                'occurred_at' => now(),
            ]);
        });
    }
}
