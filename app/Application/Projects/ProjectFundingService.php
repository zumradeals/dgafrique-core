<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectFunding;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * CAP-063 — art. 15.3 : un Projet peut déclarer un besoin financier (montant cible, justification,
 * usage prévu) uniquement une fois ADOPTED (précondition doctrinale « adoption »). Ce service ne
 * déplace jamais d'argent : aucun appel GeniusPay, aucune ContributionPayment, aucune LedgerEntry.
 * Réutilise strictement ProjectService::canDecide — aucune autorité parallèle, aucune double
 * approbation financière copiée de CAP-061 (aucun mouvement d'argent ne le justifie ici).
 */
final class ProjectFundingService
{
    private const ELIGIBLE_PROJECT_STATUSES = [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS];

    public function __construct(private readonly ProjectService $projects) {}

    public function create(Project $project, string $actor, array $data): ProjectFunding
    {
        $this->assertDecision($project, $actor);
        $this->assertProjectEligible($project);

        try {
            return DB::transaction(function () use ($project, $actor, $data): ProjectFunding {
                abort_if(
                    ProjectFunding::query()->where('project_id', $project->id)->where('status', ProjectFunding::STATUS_OPEN)->lockForUpdate()->exists(),
                    409,
                    'Ce projet a déjà une déclaration financière active.'
                );
                $funding = ProjectFunding::query()->create([
                    'project_id' => $project->id,
                    'status' => ProjectFunding::STATUS_OPEN,
                    'target_amount' => $data['target_amount'],
                    'currency' => $data['currency'],
                    'purpose' => $data['purpose'],
                    'intended_use' => $data['intended_use'],
                    'conditions' => $data['conditions'] ?? null,
                    'author_core_reference' => $actor,
                    'opened_at' => now(),
                ]);
                $this->event($project, 'FUNDING_DECLARATION_CREATED', $actor);

                return $funding;
            });
        } catch (QueryException $exception) {
            // Filet de sécurité de l'index unique partiel (concurrence réelle échappant à la
            // vérification applicative ci-dessus — le verrou de ligne ne protège rien tant
            // qu'aucune ligne OPEN n'existe encore) — jamais une 500 sur une simple course bénigne.
            if ((string) $exception->getCode() === '23505') {
                abort(409, 'Ce projet a déjà une déclaration financière active.');
            }
            throw $exception;
        }
    }

    public function update(ProjectFunding $funding, Project $project, string $actor, array $data): ProjectFunding
    {
        $this->assertDecision($project, $actor);
        $this->assertProjectEligible($project);

        return DB::transaction(function () use ($funding, $project, $actor, $data): ProjectFunding {
            $fresh = ProjectFunding::query()->whereKey($funding->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->status === ProjectFunding::STATUS_OPEN, 409, 'Seule une déclaration active peut être modifiée.');
            $fresh->fill([
                'target_amount' => $data['target_amount'],
                'currency' => $data['currency'],
                'purpose' => $data['purpose'],
                'intended_use' => $data['intended_use'],
                'conditions' => $data['conditions'] ?? null,
            ])->save();
            $this->event($project, 'FUNDING_DECLARATION_UPDATED', $actor);

            return $fresh;
        });
    }

    public function close(ProjectFunding $funding, Project $project, string $actor, ?string $note): ProjectFunding
    {
        $this->assertDecision($project, $actor);

        return DB::transaction(function () use ($funding, $project, $actor, $note): ProjectFunding {
            $fresh = ProjectFunding::query()->whereKey($funding->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->status === ProjectFunding::STATUS_OPEN, 409, 'Seule une déclaration active peut être clôturée.');
            $fresh->update(['status' => ProjectFunding::STATUS_CLOSED, 'decided_by_core_reference' => $actor, 'closing_note' => $note, 'closed_at' => now()]);
            $this->event($project, 'FUNDING_DECLARATION_CLOSED', $actor);

            return $fresh;
        });
    }

    public function cancel(ProjectFunding $funding, Project $project, string $actor, ?string $note): ProjectFunding
    {
        $this->assertDecision($project, $actor);

        return DB::transaction(function () use ($funding, $project, $actor, $note): ProjectFunding {
            $fresh = ProjectFunding::query()->whereKey($funding->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->status === ProjectFunding::STATUS_OPEN, 409, 'Seule une déclaration active peut être annulée.');
            $fresh->update(['status' => ProjectFunding::STATUS_CANCELLED, 'decided_by_core_reference' => $actor, 'closing_note' => $note, 'cancelled_at' => now()]);
            $this->event($project, 'FUNDING_DECLARATION_CANCELLED', $actor);

            return $fresh;
        });
    }

    public function canView(Project $project, string $actor): bool
    {
        return $this->projects->canView($project, $actor);
    }

    private function assertDecision(Project $project, string $actor): void
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
    }

    private function assertProjectEligible(Project $project): void
    {
        abort_unless(in_array($project->status, self::ELIGIBLE_PROJECT_STATUSES, true), 409, 'Ce projet doit être adopté (et non archivé ni terminé) pour déclarer un besoin financier.');
    }

    private function event(Project $project, string $event, string $actor): void
    {
        ProjectEvent::query()->create(['project_id' => $project->id, 'event' => $event, 'actor_core_reference' => $actor, 'context' => [], 'occurred_at' => now()]);
    }
}
