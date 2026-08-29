<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;

/** Projection de présentation exclusivement dérivée des données persistées du Projet. */
final class ProjectHubPresentation
{
    public function for(Project $project, int $realMembers): array
    {
        $project->loadMissing('milestones');
        $milestoneCount = $project->milestones->count();
        $completedMilestoneCount = $project->milestones
            ->where('status', \App\Models\ProjectMilestone::STATUS_COMPLETED)
            ->count();
        $progress = $project->milestoneProgressPercentage();

        return [
            'location' => $project->location,
            'members' => $realMembers,
            'progress' => $progress,
            'progress_label' => $milestoneCount > 0
                ? $completedMilestoneCount.' jalon'.($completedMilestoneCount > 1 ? 's' : '').' sur '.$milestoneCount
                : 'Aucun jalon défini',
            'display_status' => match ($project->status) {
                Project::STATUS_PROPOSED => 'Nouveau',
                Project::STATUS_ADOPTED => 'Adopté',
                Project::STATUS_IN_PROGRESS => 'En cours',
                Project::STATUS_COMPLETED => 'Terminé',
                default => $project->status,
            },
        ];
    }
}
