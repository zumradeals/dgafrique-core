<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\ContextComment;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectTeamMember;

/**
 * CAP-044 — panneau consultatif uniquement : chaque signal est une phrase factuelle attachée à un
 * objet métier réel, jamais un score ni un pourcentage (CAP-017 §Invariant, DESIGN-INVARIANTS §7/§8).
 * Aucune méthode ici n'écrit jamais sur Project.maturity — ProjectMaturityService::change() reste
 * l'unique chemin d'écriture, toujours décidé par le porteur.
 *
 * @return list<string>
 */
final class ProjectSignalsEngine
{
    public function forProject(Project $project): array
    {
        $signals = [];

        $milestonesTotal = $project->milestones()->count();
        if ($milestonesTotal > 0) {
            $milestonesDone = $project->milestones()->where('status', 'COMPLETED')->count();
            $signals[] = "{$milestonesDone} jalon(s) réalisé(s) sur {$milestonesTotal}.";
        }

        $activeTeam = ProjectTeamMember::query()->where('project_id', $project->id)->where('status', ProjectTeamMember::STATUS_ACTIVE)->count();
        if ($activeTeam > 0) {
            $signals[] = "{$activeTeam} membre(s) actif(s) dans l’équipe.";
        }

        $needsTotal = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)->where('status', '!=', Need::STATUS_ARCHIVED)->count();
        if ($needsTotal > 0) {
            $needsResolved = Need::query()->where('owner_type', Need::OWNER_PROJECT)->where('owner_reference', $project->id)->where('status', Need::STATUS_RESOLVED)->count();
            $signals[] = "{$needsResolved} besoin(s) résolu(s) sur {$needsTotal} exprimés.";
        }

        $comments = ContextComment::query()->where('context_type', ContextComment::CONTEXT_PROJECT)->where('context_reference', $project->id)->count();
        if ($comments > 0) {
            $signals[] = "{$comments} contribution(s) déposée(s).";
        }

        $lastEvent = ProjectEvent::query()->where('project_id', $project->id)->latest('occurred_at')->first();
        if ($lastEvent !== null) {
            $signals[] = 'Dernière activité observée le '.$lastEvent->occurred_at->format('d/m/Y').'.';
        }

        return $signals;
    }
}
