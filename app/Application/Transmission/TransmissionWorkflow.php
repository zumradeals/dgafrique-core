<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Models\Transmission;
use App\Models\TransmissionEvent;
use App\Models\TransmissionParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Machine d'états Transmission (fiche §17). Chaque transition sensible vérifie état +
 * autorité, s'exécute sous transaction avec verrouillage de ligne, écrit l'événement
 * append-only. `applyTransition()` est le primitif partagé, même forme que
 * MissionWorkflow::applyTransition() — jamais dupliquée ni réinventée.
 *
 * Convention de verrouillage : toujours la Transmission d'abord, puis l'objet enfant
 * (participant), dans cet ordre.
 */
final class TransmissionWorkflow
{
    public function __construct(
        private readonly TransmissionContextService $contexts,
        private readonly TransmissionVisibilityService $visibility,
    ) {}

    public function create(string $actor, string $initiatorRole, array $data): Transmission
    {
        abort_unless(in_array($initiatorRole, TransmissionParticipant::ROLES, true), 422, 'Rôle de participation invalide.');
        abort_unless(in_array($data['origin_type'] ?? null, Transmission::ORIGIN_TYPES, true), 422, 'Origine de la Transmission invalide.');

        $capabilityLabel = trim((string) ($data['capability_label'] ?? ''));
        abort_if($capabilityLabel === '', 422, 'Le libellé de la capacité est requis.');
        $learningObjective = trim((string) ($data['learning_objective'] ?? ''));
        abort_if($learningObjective === '', 422, 'L’objectif d’apprentissage est requis.');

        $contextType = $data['context_type'] ?? null;
        $contextReference = $data['context_reference'] ?? null;
        if ($contextType !== null) {
            abort_unless(in_array($contextType, Transmission::CONTEXT_TYPES, true), 422, 'Ce contexte de Transmission n’est pas pris en charge.');
            $context = $this->contexts->resolve($contextType, $contextReference);
            abort_unless($this->contexts->canView($contextType, $context, $actor), 422, 'Vous n’avez pas accès à ce contexte : un rattachement ne peut jamais fabriquer ce droit.');
        }

        $visibility = $data['visibility'] ?? Transmission::VISIBILITY_PRIVATE;
        abort_unless(isset(Transmission::VISIBILITY_ORDER[$visibility]), 422, 'Visibilité invalide.');

        return DB::transaction(function () use ($actor, $initiatorRole, $data, $capabilityLabel, $learningObjective, $contextType, $contextReference, $visibility): Transmission {
            $transmission = Transmission::query()->create([
                'public_reference' => (string) Str::uuid(),
                'capability_label' => $capabilityLabel,
                'normalized_label' => CapabilityStatementSynchronizer::normalize($capabilityLabel),
                'learning_objective' => $learningObjective,
                'origin_type' => $data['origin_type'],
                'origin_reference' => $data['origin_reference'] ?? null,
                'context_type' => $contextType,
                'context_reference' => $contextType !== null ? $contextReference : null,
                'visibility' => $visibility,
                'status' => Transmission::STATUS_PROPOSED,
                'availability_note' => $data['availability_note'] ?? null,
                'proposed_by_core_reference' => $actor,
                'proposed_at' => now(),
            ]);

            TransmissionParticipant::query()->create([
                'transmission_id' => $transmission->id,
                'core_identity_reference' => $actor,
                'role' => $initiatorRole,
                'status' => TransmissionParticipant::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            $this->record($transmission, 'TRANSMISSION_PROPOSED', $actor, null, Transmission::STATUS_PROPOSED);

            return $transmission;
        });
    }

    /** Officialise le rattachement contextuel — jamais une acceptation à la place d'un participant (§5.A). */
    public function officializeContext(Transmission $transmission, string $actor, ?string $visibility = null): Transmission
    {
        abort_unless(in_array($transmission->context_type, Transmission::OFFICIALIZABLE_CONTEXTS, true), 422, 'Ce contexte n’admet pas d’officialisation.');
        $context = $this->contexts->resolve($transmission->context_type, $transmission->context_reference);
        abort_unless($this->contexts->canOfficialize($transmission->context_type, $context, $actor), 403);

        if ($visibility !== null) {
            abort_unless(isset(Transmission::VISIBILITY_ORDER[$visibility]), 422, 'Visibilité invalide.');
        }

        return DB::transaction(function () use ($transmission, $actor, $visibility): Transmission {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            $locked->update(array_filter([
                'context_officialized_by_core_reference' => $actor,
                'context_officialized_at' => now(),
                'visibility' => $visibility,
            ], static fn ($value): bool => $value !== null));

            $this->record($locked, 'TRANSMISSION_CONTEXT_OFFICIALIZED', $actor, null, null);

            return $locked->fresh();
        });
    }

    public function start(Transmission $transmission, string $actor): Transmission
    {
        return $this->applyTransition(
            $transmission, $actor, [Transmission::STATUS_ACCEPTED], Transmission::STATUS_IN_PROGRESS, 'TRANSMISSION_STARTED',
            authorize: function (Transmission $t, string $a): void {
                $this->assertCurrentAccess($t, $a);
            },
            mutate: static fn (): array => ['started_at' => now()],
        );
    }

    public function end(Transmission $transmission, string $actor, ?string $reason = null): Transmission
    {
        return $this->applyTransition(
            $transmission, $actor, [Transmission::STATUS_ACCEPTED, Transmission::STATUS_IN_PROGRESS], Transmission::STATUS_ENDED, 'TRANSMISSION_ENDED',
            authorize: function (Transmission $t, string $a): void {
                abort_unless($this->isAcceptedParticipant($t, $a), 403);
            },
            mutate: static fn (): array => ['ended_at' => now()],
            eventContext: $reason !== null ? ['reason' => $reason] : [],
        );
    }

    public function cancel(Transmission $transmission, string $actor, ?string $reason = null): Transmission
    {
        return $this->applyTransition(
            $transmission, $actor, [Transmission::STATUS_PROPOSED, Transmission::STATUS_ACCEPTED], Transmission::STATUS_CANCELLED, 'TRANSMISSION_CANCELLED',
            authorize: function (Transmission $t, string $a): void {
                abort_unless(
                    hash_equals($t->proposed_by_core_reference, $a) || $this->isAcceptedParticipant($t, $a, TransmissionParticipant::ROLE_TRANSMITTER),
                    403
                );
            },
            mutate: static fn (): array => ['cancelled_at' => now()],
            eventContext: $reason !== null ? ['reason' => $reason] : [],
        );
    }

    /** Déclarer sa part terminée ne clôture jamais seul la Transmission (§10, §5.E). */
    public function declareDone(Transmission $transmission, string $actor, ?string $note = null): TransmissionParticipant
    {
        $this->assertCurrentAccess($transmission, $actor);

        return DB::transaction(function () use ($transmission, $actor, $note): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === Transmission::STATUS_IN_PROGRESS, 409, 'Cette Transmission n’est pas en cours.');

            $participant = TransmissionParticipant::query()
                ->where('transmission_id', $locked->id)
                ->where('core_identity_reference', $actor)
                ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
                ->lockForUpdate()
                ->firstOrFail();

            $participant->update(['declared_done_at' => now(), 'declared_done_note' => $note]);
            $this->record($locked, 'TRANSMISSION_PARTICIPANT_DECLARED_DONE', $actor, null, null);

            return $participant;
        });
    }

    /** Confirmation conjointe (§5.E) : deux actions distinctes — déclarer n'est pas confirmer. */
    public function confirmCompletion(Transmission $transmission, string $actor, string $summary): Transmission
    {
        $summary = trim($summary);
        abort_if($summary === '', 422, 'Un résumé de clôture est requis.');

        return $this->applyTransition(
            $transmission, $actor, [Transmission::STATUS_IN_PROGRESS], Transmission::STATUS_COMPLETED_CONFIRMED, 'TRANSMISSION_COMPLETED',
            authorize: function (Transmission $t, string $a): void {
                $this->assertCurrentAccess($t, $a);
                abort_unless($this->hasCompletionQuorum($t), 409, 'Au moins un transmetteur et un apprenant doivent avoir déclaré leur part terminée avant de confirmer.');
            },
            mutate: static fn (): array => ['completed_at' => now(), 'completion_summary' => $summary],
        );
    }

    /**
     * Validation par le contexte (fiche §5.E) : exige au minimum une trace réelle — une
     * contribution déposée ou une preuve de contexte — sinon 409. Ne certifie jamais rien
     * automatiquement ; ceci reste une exigence de trace minimale, pas une preuve garantie.
     */
    public function validateByContext(Transmission $transmission, string $actor, ?string $note = null): Transmission
    {
        return $this->applyTransition(
            $transmission, $actor, [Transmission::STATUS_IN_PROGRESS], Transmission::STATUS_COMPLETED_BY_CONTEXT, 'TRANSMISSION_COMPLETED',
            authorize: function (Transmission $t, string $a): void {
                abort_unless(in_array($t->context_type, Transmission::OFFICIALIZABLE_CONTEXTS, true), 422, 'Cette Transmission n’a pas de contexte pouvant valider sa réalisation.');
                $context = $this->contexts->resolve($t->context_type, $t->context_reference);
                abort_unless($this->contexts->canOfficialize($t->context_type, $context, $a), 403);
                abort_unless(
                    $t->contributions()->exists() || ! empty($t->evidence_context),
                    409,
                    'Aucune trace n’existe encore pour cette Transmission : au moins une contribution ou une preuve de contexte est requise avant validation.'
                );
            },
            mutate: static fn (): array => [
                'completed_at' => now(),
                'context_validated_by_core_reference' => $actor,
                'context_validated_at' => now(),
                'completion_summary' => $note,
            ],
        );
    }

    public function applyTransition(
        Transmission $transmission,
        string $actor,
        array $allowedFrom,
        string $to,
        string $event,
        ?callable $authorize = null,
        ?callable $mutate = null,
        array $eventContext = [],
    ): Transmission {
        return DB::transaction(function () use ($transmission, $actor, $allowedFrom, $to, $event, $authorize, $mutate, $eventContext): Transmission {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, $allowedFrom, true), 409, 'Cette transition de Transmission n’est pas permise depuis cet état.');

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

    public function record(Transmission $transmission, string $event, string $actor, ?string $from, ?string $to, array $context = []): void
    {
        TransmissionEvent::query()->create([
            'transmission_id' => $transmission->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'from_state' => $from,
            'to_state' => $to,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Vérification d'accès courant centrale (fiche §16, revue post-implémentation) pour les
     * mutations opérationnelles principales : participation acceptée ET visibilité actuelle,
     * jamais l'une sans l'autre. Un participant qui a perdu l'accès au contexte porteur (ex.
     * a quitté la ZUMRA rattachée) ne peut plus agir normalement, même encore ACCEPTED en
     * base — mais reste libre de decline/withdraw/leave (jamais gated ici, fiche §16).
     */
    public function assertCurrentAccess(Transmission $transmission, string $actor, ?string $role = null): void
    {
        abort_unless($this->isAcceptedParticipant($transmission, $actor, $role), 403);
        abort_unless($this->visibility->canView($transmission, $actor), 403);
    }

    public function isAcceptedParticipant(Transmission $transmission, string $actor, ?string $role = null): bool
    {
        return TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->exists();
    }

    private function hasCompletionQuorum(Transmission $transmission): bool
    {
        $declared = TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->whereNotNull('declared_done_at')
            ->pluck('role');

        return $declared->contains(TransmissionParticipant::ROLE_TRANSMITTER) && $declared->contains(TransmissionParticipant::ROLE_LEARNER);
    }
}
