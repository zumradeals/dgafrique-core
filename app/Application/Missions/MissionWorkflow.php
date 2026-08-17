<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Machine d'états Mission (v0.4 §5, verrouillée v0.5 §3). Chaque transition sensible
 * vérifie état + autorité, s'exécute sous transaction avec verrouillage de ligne, écrit
 * l'événement append-only et ne déclenche que les effets secondaires autorisés.
 *
 * `applyTransition()` est le primitif partagé réutilisé par MissionBlockerService,
 * MissionSubmissionService et MissionAssignmentService pour les mutations de statut dont
 * l'autorité relève de leur propre domaine (ex. consolidation des soumissions) : ces
 * services valident leur propre autorité puis délèguent ici la transition verrouillée +
 * l'événement, pour ne jamais dupliquer la logique de verrouillage/audit.
 */
final class MissionWorkflow
{
    public function __construct(private readonly MissionContextRegistry $registry) {}

    public function create(string $actor, string $contextType, string $contextReference, array $data): Mission
    {
        $adapter = $this->registry->for($contextType);
        $context = $adapter->resolve($contextReference);
        abort_unless($adapter->canPropose($context, $actor), 403);

        $requestedVisibility = $data['visibility'] ?? Mission::VISIBILITY_CONTEXT;
        abort_unless(isset(Mission::VISIBILITY_ORDER[$requestedVisibility]), 422, 'Visibilité invalide.');

        $parent = null;
        if (! empty($data['parent_mission_id'])) {
            $parent = Mission::query()->where('public_reference', $data['parent_mission_id'])->firstOrFail();
            abort_unless(
                $parent->context_type === $contextType && $parent->context_reference === $contextReference,
                422,
                'Une sous-Mission conserve exactement le contexte de sa Mission parente.'
            );
            abort_unless(! in_array($parent->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission parente est déjà terminée.');
            abort_unless($this->depthOf($parent) < 3, 422, 'Profondeur maximale de sous-Missions (3) atteinte.');
            abort_unless(
                Mission::VISIBILITY_ORDER[$requestedVisibility] <= Mission::VISIBILITY_ORDER[$parent->visibility],
                422,
                'Une sous-Mission ne peut pas être plus visible que sa Mission parente.'
            );
        }

        $maxVisibility = $adapter->maxVisibility($context, $actor);
        abort_unless(
            Mission::VISIBILITY_ORDER[$requestedVisibility] <= Mission::VISIBILITY_ORDER[$maxVisibility],
            422,
            'La visibilité demandée dépasse ce que le contexte autorise.'
        );

        return DB::transaction(function () use ($actor, $contextType, $contextReference, $data, $parent, $requestedVisibility): Mission {
            $mission = Mission::query()->create([
                'public_reference' => (string) Str::uuid(),
                'context_type' => $contextType,
                'context_reference' => $contextReference,
                'parent_mission_id' => $parent?->id,
                'created_by_core_reference' => $actor,
                'title' => $data['title'],
                'description' => $data['description'],
                'expected_result' => $data['expected_result'] ?? null,
                'acceptance_criteria' => $data['acceptance_criteria'] ?? null,
                'participation_mode' => $data['participation_mode'] ?? null,
                'location' => $data['location'] ?? null,
                'visibility' => $requestedVisibility,
                'status' => Mission::STATUS_DRAFT,
                'min_executors' => $data['min_executors'] ?? 1,
                'max_executors' => $data['max_executors'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);
            $this->record($mission, 'MISSION_DRAFTED', $actor, null, Mission::STATUS_DRAFT);

            return $mission;
        });
    }

    public function propose(Mission $mission, string $actor): Mission
    {
        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_DRAFT], Mission::STATUS_PROPOSED, 'MISSION_PROPOSED',
            authorize: function (Mission $m, string $a): void {
                abort_unless($this->isCreatorOrOfficializer($m, $a), 403);
            },
            mutate: static fn (): array => ['proposed_at' => now()],
        );
    }

    public function requestChanges(Mission $mission, string $actor, string $reason): Mission
    {
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour demander des modifications.');

        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_PROPOSED], Mission::STATUS_CHANGES_REQUESTED, 'MISSION_CHANGES_REQUESTED',
            authorize: fn (Mission $m, string $a) => $this->assertOfficializer($m, $a),
            eventContext: ['reason' => $reason],
        );
    }

    public function resubmitProposal(Mission $mission, string $actor, array $data = []): Mission
    {
        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_CHANGES_REQUESTED], Mission::STATUS_PROPOSED, 'MISSION_PROPOSAL_RESUBMITTED',
            authorize: function (Mission $m, string $a): void {
                abort_unless($this->isCreatorOrOfficializer($m, $a), 403);
            },
            mutate: static fn (Mission $m): array => [
                'title' => $data['title'] ?? $m->title,
                'description' => $data['description'] ?? $m->description,
                'expected_result' => $data['expected_result'] ?? $m->expected_result,
                'proposed_at' => now(),
            ],
        );
    }

    public function reject(Mission $mission, string $actor, string $reason): Mission
    {
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour rejeter cette proposition.');

        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_PROPOSED], Mission::STATUS_REJECTED, 'MISSION_REJECTED',
            authorize: fn (Mission $m, string $a) => $this->assertOfficializer($m, $a),
            eventContext: ['reason' => $reason],
        );
    }

    public function officialize(Mission $mission, string $actor, array $data = []): Mission
    {
        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_PROPOSED], Mission::STATUS_OPEN, 'MISSION_OFFICIALIZED',
            authorize: function (Mission $m, string $a) use ($data): void {
                $adapter = $this->registry->for($m->context_type);
                $context = $adapter->resolve($m->context_reference);
                abort_unless($adapter->canOfficialize($context, $a), 403);
                abort_unless($adapter->isOperational($context), 409, 'Ce contexte n’est plus opérationnel.');
                $expectedResult = $data['expected_result'] ?? $m->expected_result;
                abort_if(trim((string) $expectedResult) === '', 422, 'Le résultat attendu est obligatoire avant d’ouvrir la Mission.');
                abort_if(trim((string) $m->title) === '' || trim((string) $m->description) === '', 422, 'Le titre et la description sont obligatoires.');
            },
            mutate: static fn (Mission $m): array => [
                'expected_result' => $data['expected_result'] ?? $m->expected_result,
                'acceptance_criteria' => $data['acceptance_criteria'] ?? $m->acceptance_criteria,
                'officialized_at' => now(),
            ],
        );
    }

    public function start(Mission $mission, string $actor): Mission
    {
        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_OPEN], Mission::STATUS_IN_PROGRESS, 'MISSION_STARTED',
            authorize: function (Mission $m, string $a): void {
                abort_unless($this->isOfficializerOrAcceptedExecutor($m, $a), 403);
            },
            mutate: static fn (): array => ['started_at' => now()],
        );
    }

    public function cancel(Mission $mission, string $actor, string $reason): Mission
    {
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour annuler cette Mission.');
        abort_if(
            $mission->children()->whereNotIn('status', Mission::TERMINAL_STATUSES)->exists(),
            409,
            'Terminez ou annulez d’abord les sous-Missions encore actives.'
        );

        return $this->applyTransition(
            $mission, $actor,
            [Mission::STATUS_OPEN, Mission::STATUS_IN_PROGRESS, Mission::STATUS_BLOCKED, Mission::STATUS_SUBMITTED],
            Mission::STATUS_CANCELLED, 'MISSION_CANCELLED',
            authorize: fn (Mission $m, string $a) => $this->assertOfficializer($m, $a),
            mutate: static fn (): array => ['cancelled_at' => now()],
            eventContext: ['reason' => $reason],
        );
    }

    public function reopen(Mission $mission, string $actor, string $reason): Mission
    {
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour rouvrir cette Mission.');

        return $this->applyTransition(
            $mission, $actor, [Mission::STATUS_COMPLETED], Mission::STATUS_IN_PROGRESS, 'MISSION_REOPENED',
            authorize: function (Mission $m, string $a): void {
                $adapter = $this->registry->for($m->context_type);
                $context = $adapter->resolve($m->context_reference);
                abort_unless($adapter->canReopen($context, $a), 403);
            },
            mutate: static fn (): array => ['completed_at' => null],
            eventContext: ['reason' => $reason],
        );
    }

    /**
     * Primitif partagé : verrouille la Mission, vérifie l'état source, exécute l'autorité
     * fournie par l'appelant, applique la mutation et journalise l'événement. Utilisé
     * directement par ce workflow et par les services domaine (blockers, submissions,
     * assignments) qui possèdent leur propre règle d'autorité contextuelle.
     */
    public function applyTransition(
        Mission $mission,
        string $actor,
        array $allowedFrom,
        string $to,
        string $event,
        ?callable $authorize = null,
        ?callable $mutate = null,
        array $eventContext = [],
    ): Mission {
        return DB::transaction(function () use ($mission, $actor, $allowedFrom, $to, $event, $authorize, $mutate, $eventContext): Mission {
            $locked = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, $allowedFrom, true), 409, 'Cette transition de Mission n’est pas permise depuis cet état.');

            if ($authorize !== null) {
                $authorize($locked, $actor);
            }

            $from = $locked->status;
            $attributes = ['status' => $to];
            if ($mutate !== null) {
                $attributes = array_merge($attributes, $mutate($locked) ?? []);
            }
            $locked->update($attributes);
            $this->record($locked, $event, $actor, $from, $to, $eventContext);

            return $locked->fresh();
        });
    }

    public function depthOf(Mission $mission): int
    {
        $depth = 1;
        $current = $mission;
        $guard = 0;
        while ($current->parent_mission_id !== null && $guard < 10) {
            $current = Mission::query()->whereKey($current->parent_mission_id)->first();
            if ($current === null) {
                break;
            }
            $depth++;
            $guard++;
        }

        return $depth;
    }

    public function record(Mission $mission, string $event, string $actor, ?string $from, ?string $to, array $context = []): void
    {
        MissionEvent::query()->create([
            'mission_id' => $mission->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'subject_type' => 'MISSION',
            'subject_reference' => $mission->public_reference,
            'from_state' => $from,
            'to_state' => $to,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    public function assertOfficializer(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canOfficialize($context, $actor), 403);
    }

    private function isCreatorOrOfficializer(Mission $mission, string $actor): bool
    {
        if (hash_equals($mission->created_by_core_reference, $actor)) {
            return true;
        }
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        return $adapter->canOfficialize($context, $actor);
    }

    private function isOfficializerOrAcceptedExecutor(Mission $mission, string $actor): bool
    {
        if ($this->isCreatorOrOfficializer($mission, $actor)) {
            return true;
        }

        return $mission->assignments()
            ->where('core_identity_reference', $actor)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
            ->exists();
    }
}
