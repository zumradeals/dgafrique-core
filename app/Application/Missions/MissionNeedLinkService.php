<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Application\Needs\NeedService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionBlocker;
use App\Models\MissionEvent;
use App\Models\MissionNeedLink;
use App\Models\Need;
use Illuminate\Support\Facades\DB;

/**
 * « Exprimer ce manque comme besoin » (v0.4 §10, relation finale v0.5 §9). Le Need n'est
 * jamais créé automatiquement par un blocker : il faut une confirmation humaine explicite
 * (le contrôleur exige un `confirm=1`), puis passage strict par NeedService. Résoudre le
 * Need lié ne débloque jamais automatiquement la Mission.
 */
final class MissionNeedLinkService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly MissionContextRegistry $registry,
    ) {}

    public function expressBlockerAsNeed(Mission $mission, MissionBlocker $blocker, string $actor, array $needData, array $needConfiguration): Need
    {
        abort_unless($blocker->mission_id === $mission->id, 404);
        abort_unless($blocker->resolved_at === null, 409, 'Ce blocage est déjà résolu.');
        $this->assertCanAct($mission, $actor);

        return DB::transaction(function () use ($mission, $blocker, $actor, $needData, $needConfiguration): Need {
            $need = $this->needs->create($actor, $needData, $needConfiguration);

            MissionNeedLink::query()->create([
                'mission_id' => $mission->id,
                'blocker_id' => $blocker->id,
                'need_id' => $need->id,
                'created_by_core_reference' => $actor,
                'created_at' => now(),
            ]);

            MissionEvent::query()->create([
                'mission_id' => $mission->id,
                'event' => 'MISSION_NEED_LINK_CREATED',
                'actor_core_reference' => $actor,
                'subject_type' => 'NEED',
                'subject_reference' => $need->public_reference,
                'context' => ['blocker_id' => $blocker->id],
                'occurred_at' => now(),
            ]);

            return $need;
        });
    }

    private function assertCanAct(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        if ($adapter->canManageAssignments($context, $actor)) {
            return;
        }

        abort_unless(
            $mission->assignments()
                ->where('core_identity_reference', $actor)
                ->where('status', MissionAssignment::STATUS_ACCEPTED)
                ->whereIn('role', MissionAssignment::EXECUTION_ROLES)
                ->exists(),
            403
        );
    }
}
