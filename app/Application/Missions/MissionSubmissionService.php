<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionContribution;
use App\Models\MissionSubmission;
use Illuminate\Support\Facades\DB;

/**
 * Sépare l'apport individuel (MissionContribution) de la soumission consolidée
 * (MissionSubmission). SUBMITTED != COMPLETED : seule une décision explicite de
 * l'autorité contextuelle transforme une soumission en résultat validé (v0.5 §7).
 */
final class MissionSubmissionService
{
    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionWorkflow $workflow,
    ) {}

    public function contribute(Mission $mission, string $actor, string $summary, ?array $evidenceContext = null): MissionContribution
    {
        abort_if(trim($summary) === '', 422, 'Décrivez votre apport.');
        abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

        $assignment = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->first();
        abort_unless($assignment !== null, 403, 'Seule une personne dont la participation est acceptée peut déposer une contribution.');

        $contribution = MissionContribution::query()->create([
            'mission_id' => $mission->id,
            'assignment_id' => $assignment->id,
            'summary' => trim($summary),
            'evidence_context' => $evidenceContext,
            'submitted_at' => now(),
        ]);
        $this->workflow->record($mission, 'MISSION_CONTRIBUTION_SUBMITTED', $actor, null, null, ['assignment_id' => $assignment->id]);

        return $contribution;
    }

    /**
     * Qui peut soumettre le résultat global (v0.5 §7.1) :
     *  1. s'il existe un COORDINATOR accepté, lui seul peut consolider ;
     *  2. sinon, tout EXECUTOR/CO_EXECUTOR accepté peut soumettre ;
     *  3. LEARNER/OBSERVER seuls ne peuvent jamais soumettre le résultat global.
     */
    public function submit(Mission $mission, string $actor, string $summary): MissionSubmission
    {
        abort_if(trim($summary) === '', 422, 'Décrivez le résultat produit.');
        $this->assertCanConsolidate($mission, $actor);

        return DB::transaction(function () use ($mission, $actor, $summary): MissionSubmission {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedMission->status === Mission::STATUS_IN_PROGRESS, 409, 'Cette Mission n’est pas en cours.');
            // Revalidation sous verrou : l'assignment du soumetteur a pu être retirée entre
            // le contrôle initial et l'acquisition du verrou (concurrence).
            $this->assertCanConsolidate($lockedMission, $actor);

            $nextVersion = (int) (MissionSubmission::query()->where('mission_id', $lockedMission->id)->max('version') ?? 0) + 1;
            $submission = MissionSubmission::query()->create([
                'mission_id' => $lockedMission->id,
                'version' => $nextVersion,
                'result_summary' => trim($summary),
                'submitted_by_core_reference' => $actor,
                'submitted_at' => now(),
            ]);

            $this->workflow->applyTransition(
                $lockedMission, $actor, [Mission::STATUS_IN_PROGRESS], Mission::STATUS_SUBMITTED, 'MISSION_RESULT_SUBMITTED',
                mutate: static fn (): array => ['submitted_at' => now()],
                eventContext: ['version' => $nextVersion],
            );

            return $submission;
        });
    }

    public function requestCorrection(Mission $mission, string $actor, string $note): Mission
    {
        abort_if(trim($note) === '', 422, 'Expliquez ce qui doit être corrigé.');
        $this->assertValidator($mission, $actor);

        return DB::transaction(function () use ($mission, $actor, $note): Mission {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $latest = MissionSubmission::query()->where('mission_id', $lockedMission->id)->latest('version')->lockForUpdate()->first();
            abort_unless($latest !== null, 404, 'Aucune soumission à corriger.');

            $latest->update([
                'decision' => MissionSubmission::DECISION_CHANGES_REQUESTED,
                'decided_by_core_reference' => $actor,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);

            return $this->workflow->applyTransition(
                $lockedMission, $actor, [Mission::STATUS_SUBMITTED], Mission::STATUS_IN_PROGRESS, 'MISSION_CORRECTION_REQUESTED',
                eventContext: ['version' => $latest->version, 'note' => $note],
            );
        });
    }

    public function accept(Mission $mission, string $actor, ?string $note = null): Mission
    {
        $this->assertValidator($mission, $actor);
        // Même invariant que cancel() : une Mission avec une sous-Mission encore active ne
        // peut jamais devenir COMPLETED tant que ces sous-Missions n'ont pas leur propre issue.
        abort_if(
            $mission->children()->whereNotIn('status', Mission::TERMINAL_STATUSES)->exists(),
            409,
            'Terminez ou annulez d’abord les sous-Missions encore actives.'
        );

        return DB::transaction(function () use ($mission, $actor, $note): Mission {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $latest = MissionSubmission::query()->where('mission_id', $lockedMission->id)->latest('version')->lockForUpdate()->first();
            abort_unless($latest !== null, 404, 'Aucune soumission à valider.');

            $latest->update([
                'decision' => MissionSubmission::DECISION_ACCEPTED,
                'decided_by_core_reference' => $actor,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);

            return $this->workflow->applyTransition(
                $lockedMission, $actor, [Mission::STATUS_SUBMITTED], Mission::STATUS_COMPLETED, 'MISSION_COMPLETED',
                mutate: static fn (): array => ['completed_at' => now()],
                eventContext: ['version' => $latest->version],
            );
        });
    }

    private function assertValidator(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canValidate($context, $actor), 403);
    }

    private function assertCanConsolidate(Mission $mission, string $actor): void
    {
        $hasAcceptedCoordinator = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->where('role', MissionAssignment::ROLE_COORDINATOR)
            ->exists();

        if ($hasAcceptedCoordinator) {
            $actorIsCoordinator = MissionAssignment::query()
                ->where('mission_id', $mission->id)
                ->where('core_identity_reference', $actor)
                ->where('status', MissionAssignment::STATUS_ACCEPTED)
                ->where('role', MissionAssignment::ROLE_COORDINATOR)
                ->exists();
            abort_unless($actorIsCoordinator, 403, 'Un coordinateur accepté doit consolider et soumettre le résultat de cette Mission.');

            return;
        }

        $actorIsExecutor = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('role', [MissionAssignment::ROLE_EXECUTOR, MissionAssignment::ROLE_CO_EXECUTOR])
            ->exists();

        abort_unless($actorIsExecutor, 403, 'Seul un exécutant accepté peut soumettre le résultat de cette Mission.');
    }
}
