<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Application\Missions\Support\RecurrenceRule;
use App\Models\Mission;
use App\Models\MissionEvent;
use App\Models\MissionRecurrence;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Récurrence complète (v0.5 §8). Créer/activer une récurrence est une autorisation
 * humaine permanente et explicite pour les occurrences futures, jusqu'à pause/arrêt.
 * Chaque occurrence est une Mission distincte et auditée, jamais un clone silencieux des
 * affectations, contributions, soumissions, blockers ou preuves de la précédente.
 *
 * La règle RRULE prise en charge est un sous-ensemble volontairement borné — voir
 * Support\RecurrenceRule — suffisant pour DAILY/WEEKLY/MONTHLY avec INTERVAL/BYDAY/COUNT/
 * UNTIL, sans prétendre à une conformité RFC 5545 complète.
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
        abort_if($sourceMission->officialized_at === null, 409, 'Seule une Mission officialisée peut porter une récurrence.');
        abort_if(trim((string) $sourceMission->expected_result) === '', 422, 'La Mission source doit contenir un résultat attendu valide avant d’être rendue récurrente.');
        abort_unless(in_array($timezone, DateTimeZone::listIdentifiers(), true), 422, 'Fuseau horaire invalide.');
        abort_if(
            MissionRecurrence::query()->where('source_mission_id', $sourceMission->id)
                ->whereIn('status', [MissionRecurrence::STATUS_ACTIVE, MissionRecurrence::STATUS_PAUSED])->exists(),
            409,
            'Une récurrence est déjà active ou en pause pour cette Mission.'
        );

        $rule = $this->parseOrFail($rrule);
        $tz = new DateTimeZone($timezone);
        $nextOccurrence = $rule->nextOccurrenceAfter(CarbonImmutable::now($tz));

        // L'échéance d'une occurrence n'est jamais une copie éternelle du due_at de la
        // Mission source : on retient un décalage relatif à son officialisation, appliqué
        // à chaque occurrence à partir de sa propre date d'ouverture.
        $dueOffsetMinutes = null;
        if ($sourceMission->due_at !== null) {
            $dueOffsetMinutes = max(0, $sourceMission->officialized_at->diffInMinutes($sourceMission->due_at));
        }

        return DB::transaction(function () use ($sourceMission, $actor, $rrule, $timezone, $dueOffsetMinutes, $nextOccurrence): MissionRecurrence {
            $recurrence = MissionRecurrence::query()->create([
                'source_mission_id' => $sourceMission->id,
                'rrule' => $rrule,
                'timezone' => $timezone,
                'due_offset_minutes' => $dueOffsetMinutes,
                'is_active' => true,
                'status' => MissionRecurrence::STATUS_ACTIVE,
                'next_occurrence_at' => $nextOccurrence,
                'created_by_core_reference' => $actor,
            ]);

            $this->workflow->record($sourceMission, 'MISSION_RECURRENCE_CREATED', $actor, null, null, [
                'recurrence_id' => $recurrence->id, 'rrule' => $rrule, 'timezone' => $timezone,
            ]);

            return $recurrence;
        });
    }

    public function activate(Mission $mission, MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $source = $this->assertAuthority($mission, $recurrence, $actor);
        $rule = $this->parseOrFail($recurrence->rrule);
        $tz = new DateTimeZone($recurrence->timezone);

        // Recalcul sûr : reprendre ne rejoue jamais les occurrences manquées pendant la
        // pause — on repart de la prochaine occurrence réelle après maintenant.
        $next = $rule->nextOccurrenceAfter(CarbonImmutable::now($tz));

        $recurrence->update([
            'status' => MissionRecurrence::STATUS_ACTIVE, 'is_active' => true,
            'next_occurrence_at' => $next, 'last_error_at' => null, 'last_error_code' => null,
        ]);
        $this->workflow->record($source, 'MISSION_RECURRENCE_RESUMED', $actor, null, null, ['recurrence_id' => $recurrence->id]);

        return $recurrence;
    }

    public function pause(Mission $mission, MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $source = $this->assertAuthority($mission, $recurrence, $actor);
        $recurrence->update(['status' => MissionRecurrence::STATUS_PAUSED, 'is_active' => false]);
        $this->workflow->record($source, 'MISSION_RECURRENCE_PAUSED', $actor, null, null, ['recurrence_id' => $recurrence->id]);

        return $recurrence;
    }

    public function stop(Mission $mission, MissionRecurrence $recurrence, string $actor): MissionRecurrence
    {
        $source = $this->assertAuthority($mission, $recurrence, $actor);
        $recurrence->update(['status' => MissionRecurrence::STATUS_STOPPED, 'is_active' => false, 'next_occurrence_at' => null]);
        $this->workflow->record($source, 'MISSION_RECURRENCE_STOPPED', $actor, null, null, ['recurrence_id' => $recurrence->id]);

        return $recurrence;
    }

    /**
     * Point d'entrée du planificateur (commande Artisan `missions:generate-recurring-
     * occurrences`, appelée par le scheduler Laravel) : génère toutes les occurrences dues,
     * de façon idempotente, et avance chaque récurrence à sa prochaine échéance réelle.
     */
    public function generateDueOccurrences(): int
    {
        $dueIds = MissionRecurrence::query()
            ->where('status', MissionRecurrence::STATUS_ACTIVE)
            ->whereNotNull('next_occurrence_at')
            ->where('next_occurrence_at', '<=', now())
            ->pluck('id');

        $generated = 0;
        foreach ($dueIds as $id) {
            if ($this->generateNextDueOccurrence($id)) {
                $generated++;
            }
        }

        return $generated;
    }

    private function generateNextDueOccurrence(string $recurrenceId): bool
    {
        return DB::transaction(function () use ($recurrenceId): bool {
            $locked = MissionRecurrence::query()->whereKey($recurrenceId)->lockForUpdate()->first();
            if ($locked === null || $locked->status !== MissionRecurrence::STATUS_ACTIVE
                || $locked->next_occurrence_at === null || $locked->next_occurrence_at->isFuture()) {
                return false;
            }

            try {
                $rule = RecurrenceRule::parse($locked->rrule);
            } catch (InvalidArgumentException $e) {
                $locked->update(['status' => MissionRecurrence::STATUS_PAUSED, 'is_active' => false, 'last_error_at' => now(), 'last_error_code' => 'INVALID_RRULE']);

                return false;
            }

            $occurrenceAt = $locked->next_occurrence_at;
            $alreadyGenerated = Mission::query()->where('recurrence_id', $locked->id)->count();

            if ($rule->isExhaustedAfter($alreadyGenerated, $occurrenceAt)) {
                $locked->update(['status' => MissionRecurrence::STATUS_STOPPED, 'is_active' => false, 'next_occurrence_at' => null]);
                $source = Mission::query()->find($locked->source_mission_id);
                if ($source !== null) {
                    $this->workflow->record($source, 'MISSION_RECURRENCE_STOPPED', MissionEvent::SYSTEM_RECURRENCE_ACTOR, null, null, [
                        'recurrence_id' => $locked->id, 'reason' => 'COUNT_OU_UNTIL_ATTEINT',
                    ]);
                }

                return false;
            }

            $tz = new DateTimeZone($locked->timezone);
            $occurrenceKey = $occurrenceAt->setTimezone($tz)->format('Y-m-d\TH:i');
            $occurrence = $this->generateOccurrence($locked, $occurrenceKey);

            // On avance toujours à l'échéance suivante, y compris si cette occurrence n'a
            // pas pu être générée (contexte devenu non opérationnel...) : la récurrence ne
            // reste jamais bloquée à rejouer indéfiniment le même créneau manqué.
            $locked->update(['next_occurrence_at' => $rule->nextOccurrenceAfter($occurrenceAt)]);

            return $occurrence !== null;
        });
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
            $dueAt = $locked->due_offset_minutes !== null ? $now->clone()->addMinutes($locked->due_offset_minutes) : null;

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
                'due_at' => $dueAt,
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

    private function parseOrFail(string $rrule): RecurrenceRule
    {
        try {
            return RecurrenceRule::parse($rrule);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    /** Garde-fou IDOR : la récurrence reçue doit réellement porter sur la Mission reçue. */
    private function assertAuthority(Mission $mission, MissionRecurrence $recurrence, string $actor): Mission
    {
        abort_unless($recurrence->source_mission_id === $mission->id, 404);
        $this->workflow->assertOfficializer($mission, $actor);

        return $mission;
    }
}
