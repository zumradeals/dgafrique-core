<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionDependency;
use App\Models\MissionEvent;
use Illuminate\Support\Facades\DB;

/**
 * Dépendances explicites entre Missions (v0.4 §7). Aucun cycle n'est jamais accepté ;
 * chaque dépendance doit rester explicable à l'utilisateur — ce n'est pas un diagramme
 * de Gantt.
 */
final class MissionDependencyService
{
    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionVisibilityService $visibility,
    ) {}

    public function add(Mission $mission, string $actor, Mission $dependsOn, string $type): MissionDependency
    {
        abort_unless(in_array($type, MissionDependency::TYPES, true), 422, 'Type de dépendance invalide.');
        abort_if($mission->id === $dependsOn->id, 422, 'Une Mission ne peut pas dépendre d’elle-même.');
        abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');
        $this->assertAuthority($mission, $actor);
        abort_unless($this->visibility->canViewMission($dependsOn, $actor), 404, 'Cette Mission cible n’est pas accessible.');

        return DB::transaction(function () use ($mission, $actor, $dependsOn, $type): MissionDependency {
            // Verrouille les deux Missions (ordre déterministe pour éviter tout deadlock
            // croisé) avant de rejouer la détection de cycle : deux ajouts concurrents ne
            // doivent jamais pouvoir fermer un cycle ensemble alors qu'aucun des deux seul
            // ne le fermait au moment de son propre contrôle pré-transaction.
            $ids = [$mission->id, $dependsOn->id];
            sort($ids);
            $locked = Mission::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // Revalidation sous verrou : $mission a pu devenir terminale entre le contrôle
            // initial et l'acquisition du verrou.
            abort_if(in_array($locked->get($mission->id)?->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');
            abort_if($this->wouldCycle($mission, $dependsOn), 422, 'Cette dépendance créerait un cycle entre Missions.');

            $dependency = MissionDependency::query()->firstOrCreate([
                'mission_id' => $mission->id,
                'depends_on_mission_id' => $dependsOn->id,
            ], [
                'type' => $type,
                'created_by_core_reference' => $actor,
            ]);
            $this->event($mission, 'MISSION_DEPENDENCY_ADDED', $actor, ['depends_on_mission_id' => $dependsOn->id, 'type' => $type]);

            return $dependency;
        });
    }

    public function remove(Mission $mission, string $actor, MissionDependency $dependency): void
    {
        abort_unless($dependency->mission_id === $mission->id, 404);
        $this->assertAuthority($mission, $actor);

        DB::transaction(function () use ($mission, $actor, $dependency): void {
            $lockedMission = Mission::query()->whereKey($mission->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($lockedMission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

            $dependsOnId = $dependency->depends_on_mission_id;
            $dependency->delete();
            $this->event($lockedMission, 'MISSION_DEPENDENCY_REMOVED', $actor, ['depends_on_mission_id' => $dependsOnId]);
        });
    }

    private function event(Mission $mission, string $event, string $actor, array $context): void
    {
        MissionEvent::query()->create([
            'mission_id' => $mission->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'subject_type' => 'DEPENDENCY',
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    /** True si ajouter (mission -> dependsOn) fermerait un cycle, c'est-à-dire si mission est déjà un ancêtre de dependsOn. */
    private function wouldCycle(Mission $mission, Mission $dependsOn): bool
    {
        $visited = [];
        $stack = [$dependsOn->id];

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === $mission->id) {
                return true;
            }
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $next = MissionDependency::query()->where('mission_id', $current)->pluck('depends_on_mission_id');
            foreach ($next as $id) {
                $stack[] = $id;
            }
        }

        return false;
    }

    private function assertAuthority(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canManageAssignments($context, $actor), 403);
    }
}
