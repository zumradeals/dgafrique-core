<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionEvent;
use App\Models\MissionRecurrence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Récurrence complète (v0.5 §8). Créer/activer une récurrence est une autorisation
 * humaine permanente et explicite pour les occurrences futures, jusqu'à pause/arrêt.
 * Chaque occurrence est une Mission distincte et auditée, jamais un clone silencieux des
 * affectations, contributions, soumissions, blockers ou preuves de la précédente.
 */
final class MissionRecurrenceService
{
    public function __construct(
        private readonly MissionContextRegistry $registry,
        private readonly MissionWorkflow $workflow,
    ) {}

    public function create(Mission $sourceMission, string $actor, string $rrule, string $timezone): MissionRecurrence
    {
        $this->workflow->assertOfficializer($sourceMission, $actor);
        abort_if(trim($rrule) === '', 422, 'La règle de récurrence est requise.');

        return MissionRecurrence::query()->create([
            'source_mission_id' => $sourceMission->id,
            'rrule' => $rrule,
            'timezone' => $timezone,
            'is_active' => true,
            'status' => MissionRecurrence::STATUS_ACTIVE,
            'created_by_core_reference' => $actor,
        ]);
    }

    public function activate(MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $this->assertAuthority($recurrence, $actor);
        $recurrence->update(['status' => MissionRecurrence::STATUS_ACTIVE, 'is_active' => true]);

        return $recurrence;
    }

    public function pause(MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $this->assertAuthority($recurrence, $actor);
        $recurrence->update(['status' => MissionRecurrence::STATUS_PAUSED, 'is_active' => false]);

        return $recurrence;
    }

    public function stop(MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $this->assertAuthority($recurrence, $actor);
        $recurrence->update(['status' => MissionRecurrence::STATUS_STOPPED, 'is_active' => false]);

        return $recurrence;
    }

    /**
     * Idempotent par (recurrence_id, occurrence_key) — contrainte DB UNIQUE en dernier
     * recours. Ne copie jamais assignments/invitations/contributions/submissions/
     * blockers/commentaires/conversations/preuves/finance/état COMPLETED.
     */
    public function generateOccurrence(MissionRecurrence $recurrence, string $occurrenceKey): ?Mission
    {
        return DB::transaction(function () use ($recurrence, $occurrenceKey): ?Mission {
            $locked = MissionRecurrence::query()->whereKey($recurrence->id)->lockForUpdate()->firstOrFail();

            $existing = Mission::query()
                ->where('recurrence_id', $locked->id)
                ->where('occurrence_key', $occurrenceKey)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            if ($locked->status !== MissionRecurrence::STATUS_ACTIVE) {
                return null;
            }

            $source = Mission::query()->findOrFail($locked->source_mission_id);
            $adapter = $this->registry->for($source->context_type);
            $context = $adapter->resolve($source->context_reference);

            if (! $adapter->isOperational($context)) {
                $locked->update(['last_error_at' => now(), 'last_error_code' => 'CONTEXT_NOT_OPERATIONAL']);

                return null;
            }

            $maxVisibility = $adapter->maxVisibility($context, $locked->created_by_core_reference);
            if (Mission::VISIBILITY_ORDER[$source->visibility] > Mission::VISIBILITY_ORDER[$maxVisibility]) {
                $locked->update(['last_error_at' => now(), 'last_error_code' => 'VISIBILITY_NO_LONGER_ALLOWED']);

                return null;
            }

            $now = now();
            $occurrence = Mission::query()->create([
                'public_reference' => (string) Str::uuid(),
                'context_type' => $source->context_type,
                'context_reference' => $source->context_reference,
                'recurrence_id' => $locked->id,
                'occurrence_key' => $occurrenceKey,
                'created_by_core_reference' => $locked->created_by_core_reference,
                'title' => $source->title,
                'description' => $source->description,
                'expected_result' => $source->expected_result,
                'acceptance_criteria' => $source->acceptance_criteria,
                'participation_mode' => $source->participation_mode,
                'location' => $source->location,
                'visibility' => $source->visibility,
                'status' => Mission::STATUS_OPEN,
                'min_executors' => $source->min_executors,
                'max_executors' => $source->max_executors,
                'due_at' => $source->due_at,
                'proposed_at' => $now,
                'officialized_at' => $now,
            ]);

            foreach ($source->checklistItems as $item) {
                $occurrence->checklistItems()->create([
                    'label' => $item->label,
                    'position' => $item->position,
                    'is_required' => $item->is_required,
                ]);
            }
            foreach ($source->capabilityRequirements as $requirement) {
                $occurrence->capabilityRequirements()->create([
                    'catalog_item_id' => $requirement->catalog_item_id,
                    'label' => $requirement->label,
                    'normalized_label' => $requirement->normalized_label,
                    'requirement_level' => $requirement->requirement_level,
                    'quantity' => $requirement->quantity,
                    'context' => $requirement->context,
                ]);
            }
            foreach ($source->resourceRequirements as $requirement) {
                $occurrence->resourceRequirements()->create([
                    'type' => $requirement->type,
                    'label' => $requirement->label,
                    'quantity' => $requirement->quantity,
                    'unit' => $requirement->unit,
                    'is_required' => $requirement->is_required,
                    'context' => $requirement->context,
                    'external_reference' => $requirement->external_reference,
                ]);
            }

            MissionEvent::query()->create([
                'mission_id' => $occurrence->id,
                'event' => 'MISSION_OCCURRENCE_CREATED',
                'actor_core_reference' => MissionEvent::SYSTEM_RECURRENCE_ACTOR,
                'subject_type' => 'MISSION',
                'subject_reference' => $occurrence->public_reference,
                'to_state' => Mission::STATUS_OPEN,
                'context' => [
                    'authorized_by_core_reference' => $locked->created_by_core_reference,
                    'recurrence_id' => $locked->id,
                    'occurrence_key' => $occurrenceKey,
                ],
                'occurred_at' => $now,
            ]);

            $locked->update(['last_generated_at' => $now, 'last_error_at' => null, 'last_error_code' => null]);

            return $occurrence;
        });
    }

    private function assertAuthority(MissionRecurrence $recurrence, string $actor): void
    {
        $source = Mission::query()->findOrFail($recurrence->source_mission_id);
        $this->workflow->assertOfficializer($source, $actor);
    }
}
