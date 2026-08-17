<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionBlocker;
use App\Models\MissionEvent;
use Illuminate\Support\Facades\DB;

/**
 * Participation et affectations (v0.4 §6, retrait final v0.5 §6). Aucune affectation
 * automatique, aucun silence ne vaut acceptation. Le retrait du dernier exécutant suit
 * exactement la règle v0.5 : OPEN reste OPEN, IN_PROGRESS bascule BLOCKED avec un blocker
 * PERSON_UNAVAILABLE — jamais de Need créé, jamais de retour arbitraire à OPEN.
 */
final class MissionAssignmentService
{
    public const ROLES = [
        MissionAssignment::ROLE_EXECUTOR,
        MissionAssignment::ROLE_CO_EXECUTOR,
        MissionAssignment::ROLE_COORDINATOR,
        MissionAssignment::ROLE_LEARNER,
        MissionAssignment::ROLE_OBSERVER,
    ];

    private const OFFERABLE_STATUSES = [Mission::STATUS_OPEN, Mission::STATUS_IN_PROGRESS, Mission::STATUS_BLOCKED];

    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionVisibilityService $visibility,
        private readonly MissionWorkflow $workflow,
    ) {}

    /** Je me propose -> OFFERED. */
    public function offer(Mission $mission, string $actor, string $role): MissionAssignment
    {
        abort_unless(in_array($role, self::ROLES, true), 422, 'Rôle de participation invalide.');
        abort_unless(in_array($mission->status, self::OFFERABLE_STATUSES, true), 409, 'Cette Mission ne reçoit pas de proposition de participation actuellement.');
        abort_unless($this->visibility->canViewMission($mission, $actor), 404);

        return DB::transaction(function () use ($mission, $actor, $role): MissionAssignment {
            $assignment = MissionAssignment::query()
                ->where('mission_id', $mission->id)
                ->where('core_identity_reference', $actor)
                ->lockForUpdate()
                ->first();

            abort_if($assignment?->status === MissionAssignment::STATUS_ACCEPTED, 409, 'Vous participez déjà à cette Mission.');
            abort_if(in_array($assignment?->status, [MissionAssignment::STATUS_OFFERED, MissionAssignment::STATUS_INVITED], true), 409, 'Une proposition est déjà en attente pour vous sur cette Mission.');

            $assignment ??= new MissionAssignment(['mission_id' => $mission->id, 'core_identity_reference' => $actor]);
            $assignment->fill([
                'role' => $role,
                'status' => MissionAssignment::STATUS_OFFERED,
                'initiated_by_core_reference' => $actor,
                'accepted_by_core_reference' => null,
                'reason' => null,
                'offered_at' => now(),
                'invited_at' => null,
                'accepted_at' => null,
                'declined_at' => null,
                'withdrawn_at' => null,
                'released_at' => null,
                'removed_at' => null,
            ])->save();

            $this->event($mission, 'ASSIGNMENT_OFFERED', $actor, $actor);

            return $assignment;
        });
    }

    /**
     * Une autorité invite directement -> INVITED. Une invitation ne fabrique jamais un
     * droit d'accès : la personne invitée doit déjà pouvoir accéder au contexte porteur
     * (ProjectService/NeedService/ZumraGroupService), sinon l'invitation est refusée.
     */
    public function invite(Mission $mission, string $actor, string $subject, string $role): MissionAssignment
    {
        abort_unless(in_array($role, self::ROLES, true), 422, 'Rôle de participation invalide.');
        abort_if($subject === $actor, 422, 'Vous ne pouvez pas vous inviter vous-même.');
        $this->assertManageAssignments($mission, $actor);
        abort_unless(in_array($mission->status, self::OFFERABLE_STATUSES, true), 409, 'Cette Mission ne reçoit pas d’invitation actuellement.');

        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canView($context, $subject), 422, 'Cette personne n’a pas accès au contexte de cette Mission : une invitation ne peut pas lui fabriquer ce droit.');

        return DB::transaction(function () use ($mission, $actor, $subject, $role): MissionAssignment {
            $assignment = MissionAssignment::query()
                ->where('mission_id', $mission->id)
                ->where('core_identity_reference', $subject)
                ->lockForUpdate()
                ->first();

            abort_if($assignment?->status === MissionAssignment::STATUS_ACCEPTED, 409, 'Cette personne participe déjà à cette Mission.');

            $assignment ??= new MissionAssignment(['mission_id' => $mission->id, 'core_identity_reference' => $subject]);
            $assignment->fill([
                'role' => $role,
                'status' => MissionAssignment::STATUS_INVITED,
                'initiated_by_core_reference' => $actor,
                'accepted_by_core_reference' => null,
                'reason' => null,
                'invited_at' => now(),
                'offered_at' => null,
                'accepted_at' => null,
                'declined_at' => null,
                'withdrawn_at' => null,
                'released_at' => null,
                'removed_at' => null,
            ])->save();

            $this->event($mission, 'ASSIGNMENT_INVITED', $actor, $subject);

            return $assignment;
        });
    }

    /** Une autorité accepte une offre. */
    public function acceptOffer(Mission $mission, string $actor, MissionAssignment $assignment): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        $this->assertManageAssignments($mission, $actor);

        return $this->accept($mission, $assignment, MissionAssignment::STATUS_OFFERED, $actor);
    }

    /** Une autorité décline une offre. */
    public function declineOffer(Mission $mission, string $actor, MissionAssignment $assignment, ?string $reason = null): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        $this->assertManageAssignments($mission, $actor);

        return $this->decline($mission, $assignment, MissionAssignment::STATUS_OFFERED, $actor, $reason);
    }

    /** La personne invitée accepte elle-même. */
    public function acceptInvitation(Mission $mission, string $actor, MissionAssignment $assignment): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        abort_unless(hash_equals($assignment->core_identity_reference, $actor), 403);

        return $this->accept($mission, $assignment, MissionAssignment::STATUS_INVITED, $actor);
    }

    /** La personne invitée décline elle-même. */
    public function declineInvitation(Mission $mission, string $actor, MissionAssignment $assignment, ?string $reason = null): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        abort_unless(hash_equals($assignment->core_identity_reference, $actor), 403);

        return $this->decline($mission, $assignment, MissionAssignment::STATUS_INVITED, $actor, $reason);
    }

    /** Retrait d'une offre avant décision, par la personne elle-même. */
    public function withdraw(Mission $mission, string $actor, MissionAssignment $assignment): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        abort_unless(hash_equals($assignment->core_identity_reference, $actor), 403);

        return DB::transaction(function () use ($mission, $assignment, $actor): MissionAssignment {
            abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');
            $locked = MissionAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === MissionAssignment::STATUS_OFFERED, 409, 'Cette proposition n’est plus en attente.');
            $locked->update(['status' => MissionAssignment::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);
            $this->event($mission, 'ASSIGNMENT_WITHDRAWN', $actor, $actor);

            return $locked;
        });
    }

    /** L'exécutant se libère lui-même d'une affectation acceptée. */
    public function release(Mission $mission, string $actor, MissionAssignment $assignment, ?string $reason = null): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        abort_unless(hash_equals($assignment->core_identity_reference, $actor), 403);

        return $this->withdrawAccepted($mission, $assignment, $actor, MissionAssignment::STATUS_RELEASED, 'ASSIGNMENT_RELEASED', $reason);
    }

    /** Une autorité retire une personne d'une affectation acceptée, avec raison obligatoire. */
    public function remove(Mission $mission, string $actor, MissionAssignment $assignment, string $reason): MissionAssignment
    {
        $this->assertBelongsToMission($mission, $assignment);
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour retirer un exécutant.');
        $this->assertManageAssignments($mission, $actor);

        return $this->withdrawAccepted($mission, $assignment, $actor, MissionAssignment::STATUS_REMOVED, 'ASSIGNMENT_REMOVED', $reason);
    }

    private function accept(Mission $mission, MissionAssignment $assignment, string $expectedFrom, string $actor): MissionAssignment
    {
        return DB::transaction(function () use ($mission, $assignment, $expectedFrom, $actor): MissionAssignment {
            // La Mission est verrouillée AVANT de lire son statut/sa capacité, pour que deux
            // acceptations concurrentes ne puissent jamais dépasser max_executors ensemble.
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            $locked = MissionAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === $expectedFrom, 409, 'Cette proposition n’est plus en attente.');
            abort_unless(in_array($lockedMission->status, self::OFFERABLE_STATUSES, true), 409, 'Cette Mission n’accepte plus de nouvelle participation.');

            if (in_array($locked->role, MissionAssignment::EXECUTION_ROLES, true) && $lockedMission->max_executors !== null) {
                $currentExecutors = MissionAssignment::query()
                    ->where('mission_id', $lockedMission->id)
                    ->where('status', MissionAssignment::STATUS_ACCEPTED)
                    ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
                    ->count();
                abort_if($currentExecutors >= $lockedMission->max_executors, 409, 'Le nombre maximal d’exécutants est atteint pour cette Mission.');
            }

            $locked->update(['status' => MissionAssignment::STATUS_ACCEPTED, 'accepted_by_core_reference' => $actor, 'accepted_at' => now()]);
            $this->event($lockedMission, 'ASSIGNMENT_ACCEPTED', $actor, $locked->core_identity_reference);

            return $locked;
        });
    }

    private function decline(Mission $mission, MissionAssignment $assignment, string $expectedFrom, string $actor, ?string $reason): MissionAssignment
    {
        return DB::transaction(function () use ($mission, $assignment, $expectedFrom, $actor, $reason): MissionAssignment {
            abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');
            $locked = MissionAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === $expectedFrom, 409, 'Cette proposition n’est plus en attente.');
            $locked->update(['status' => MissionAssignment::STATUS_DECLINED, 'declined_at' => now(), 'reason' => $reason]);
            $this->event($mission, 'ASSIGNMENT_DECLINED', $actor, $locked->core_identity_reference);

            return $locked;
        });
    }

    private function withdrawAccepted(Mission $mission, MissionAssignment $assignment, string $actor, string $status, string $eventName, ?string $reason): MissionAssignment
    {
        return DB::transaction(function () use ($mission, $assignment, $actor, $status, $eventName, $reason): MissionAssignment {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($lockedMission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

            $locked = MissionAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === MissionAssignment::STATUS_ACCEPTED, 409, 'Cette participation n’est plus active.');

            $field = $status === MissionAssignment::STATUS_RELEASED ? 'released_at' : 'removed_at';
            $locked->update(['status' => $status, $field => now(), 'reason' => $reason]);
            $this->event($lockedMission, $eventName, $actor, $locked->core_identity_reference);

            if (in_array($locked->role, MissionAssignment::EXECUTION_ROLES, true)) {
                $this->applyLastExecutorRule($lockedMission, $actor);
            }

            return $locked;
        });
    }

    /**
     * v0.5 §6 : OPEN reste OPEN ; IN_PROGRESS/BLOCKED bascule ou reste BLOCKED avec un
     * blocker PERSON_UNAVAILABLE (jamais dupliqué s'il en existe déjà un actif). Aucun
     * Need n'est jamais créé par cette conséquence.
     */
    private function applyLastExecutorRule(Mission $mission, string $actor): void
    {
        if (! in_array($mission->status, [Mission::STATUS_IN_PROGRESS, Mission::STATUS_BLOCKED], true)) {
            return;
        }

        $remainingExecutors = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
            ->exists();

        if ($remainingExecutors) {
            return;
        }

        $hasActivePersonUnavailable = MissionBlocker::query()
            ->where('mission_id', $mission->id)
            ->where('type', MissionBlocker::TYPE_PERSON_UNAVAILABLE)
            ->whereNull('resolved_at')
            ->exists();

        if (! $hasActivePersonUnavailable) {
            MissionBlocker::query()->create([
                'mission_id' => $mission->id,
                'type' => MissionBlocker::TYPE_PERSON_UNAVAILABLE,
                'description' => 'Le dernier exécutant accepté a quitté la Mission.',
                'opened_by_core_reference' => $actor,
                'opened_at' => now(),
            ]);
            $this->workflow->record($mission, 'MISSION_BLOCKER_OPENED', $actor, null, null, ['type' => MissionBlocker::TYPE_PERSON_UNAVAILABLE, 'automatic' => true]);
        }

        if ($mission->status === Mission::STATUS_IN_PROGRESS) {
            $this->workflow->applyTransition(
                $mission, $actor, [Mission::STATUS_IN_PROGRESS], Mission::STATUS_BLOCKED, 'MISSION_BLOCKED',
                eventContext: ['type' => MissionBlocker::TYPE_PERSON_UNAVAILABLE, 'automatic' => true],
            );
        }
    }

    private function assertManageAssignments(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canManageAssignments($context, $actor), 403);
    }

    /**
     * Garde-fou IDOR : une MissionAssignment reçue en paramètre doit réellement appartenir
     * à la Mission reçue en paramètre. Le binding de route seul ne le garantit pas —
     * cette vérification métier reste nécessaire même avec un scoped binding en complément.
     */
    private function assertBelongsToMission(Mission $mission, MissionAssignment $assignment): void
    {
        abort_unless($assignment->mission_id === $mission->id, 404);
    }

    private function event(Mission $mission, string $event, string $actor, string $subjectReference): void
    {
        MissionEvent::query()->create([
            'mission_id' => $mission->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'subject_type' => 'ASSIGNMENT',
            'subject_reference' => $subjectReference,
            'context' => [],
            'occurred_at' => now(),
        ]);
    }
}
