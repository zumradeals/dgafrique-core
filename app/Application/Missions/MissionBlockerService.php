<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionBlocker;
use Illuminate\Support\Facades\DB;

/**
 * Une Mission reste BLOCKED tant qu'au moins un blocker actif subsiste (v0.4 §7). Le
 * blocage et la résolution restent ouverts à l'autorité contextuelle et aux exécutants
 * acceptés : ce sont eux qui vivent le travail réel et peuvent signaler ce qui manque.
 */
final class MissionBlockerService
{
    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionWorkflow $workflow,
    ) {}

    public function block(Mission $mission, string $actor, string $type, string $description): Mission
    {
        abort_unless(in_array($type, MissionBlocker::TYPES, true), 422, 'Type de blocage invalide.');
        abort_if(trim($description) === '', 422, 'Décrivez le blocage rencontré.');
        $this->assertCanReport($mission, $actor);

        return DB::transaction(function () use ($mission, $actor, $type, $description): Mission {
            $locked = $this->workflow->applyTransition(
                $mission, $actor, [Mission::STATUS_IN_PROGRESS], Mission::STATUS_BLOCKED, 'MISSION_BLOCKED',
                eventContext: ['type' => $type],
            );

            MissionBlocker::query()->create([
                'mission_id' => $locked->id,
                'type' => $type,
                'description' => trim($description),
                'opened_by_core_reference' => $actor,
                'opened_at' => now(),
            ]);

            return $locked;
        });
    }

    /** Ajoute un blocker supplémentaire sur une Mission déjà BLOCKED (plusieurs blockers actifs possibles). */
    public function reportAdditional(Mission $mission, string $actor, string $type, string $description): MissionBlocker
    {
        abort_unless(in_array($type, MissionBlocker::TYPES, true), 422, 'Type de blocage invalide.');
        abort_if(trim($description) === '', 422, 'Décrivez le blocage rencontré.');
        abort_unless($mission->status === Mission::STATUS_BLOCKED, 409, 'Cette Mission n’est pas bloquée.');
        $this->assertCanReport($mission, $actor);

        $blocker = MissionBlocker::query()->create([
            'mission_id' => $mission->id,
            'type' => $type,
            'description' => trim($description),
            'opened_by_core_reference' => $actor,
            'opened_at' => now(),
        ]);
        $this->workflow->record($mission, 'MISSION_BLOCKED', $actor, null, null, ['type' => $type, 'additional' => true]);

        return $blocker;
    }

    public function resolve(Mission $mission, string $actor, MissionBlocker $blocker, ?string $note = null): Mission
    {
        $this->assertCanReport($mission, $actor);

        return DB::transaction(function () use ($mission, $actor, $blocker, $note): Mission {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $lockedBlocker = MissionBlocker::query()->whereKey($blocker->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedBlocker->mission_id === $lockedMission->id, 404);
            abort_unless($lockedBlocker->resolved_at === null, 409, 'Ce blocage est déjà résolu.');

            $lockedBlocker->update([
                'resolved_by_core_reference' => $actor,
                'resolved_at' => now(),
                'resolution_note' => $note,
            ]);
            $this->workflow->record($lockedMission, 'MISSION_BLOCKER_RESOLVED', $actor, null, null, ['blocker_id' => $lockedBlocker->id, 'type' => $lockedBlocker->type]);

            $stillBlocked = MissionBlocker::query()
                ->where('mission_id', $lockedMission->id)
                ->whereNull('resolved_at')
                ->exists();

            if (! $stillBlocked && $lockedMission->status === Mission::STATUS_BLOCKED) {
                $lockedMission = $this->workflow->applyTransition(
                    $lockedMission, $actor, [Mission::STATUS_BLOCKED], Mission::STATUS_IN_PROGRESS, 'MISSION_UNBLOCKED',
                );
            }

            return $lockedMission;
        });
    }

    private function assertCanReport(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        if ($adapter->canManageAssignments($context, $actor)) {
            return;
        }

        $isAcceptedExecutor = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
            ->exists();

        abort_unless($isAcceptedExecutor, 403);
    }
}
