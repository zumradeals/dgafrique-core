<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Models\PersonProfile;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use Illuminate\Support\Facades\DB;

/**
 * Participation Transmission (fiche §9/§18). Aucune acceptation silencieuse : chaque
 * participant accepte explicitement sa propre participation, quel que soit le rôle.
 * Convention de verrouillage : toujours la Transmission d'abord, puis le participant.
 */
final class TransmissionParticipationService
{
    private const OPEN_STATUSES = [Transmission::STATUS_PROPOSED, Transmission::STATUS_ACCEPTED];

    public function __construct(
        private readonly TransmissionVisibilityService $visibility,
        private readonly TransmissionWorkflow $workflow,
    ) {}

    public function offer(Transmission $transmission, string $actor, string $role): TransmissionParticipant
    {
        abort_unless(in_array($role, TransmissionParticipant::ROLES, true), 422, 'Rôle de participation invalide.');
        abort_unless($this->visibility->canView($transmission, $actor), 404);

        return DB::transaction(function () use ($transmission, $actor, $role): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, self::OPEN_STATUSES, true), 409, 'Cette Transmission ne reçoit pas de proposition de participation actuellement.');

            $participant = TransmissionParticipant::query()
                ->where('transmission_id', $locked->id)
                ->where('core_identity_reference', $actor)
                ->lockForUpdate()
                ->first();
            abort_if($participant?->status === TransmissionParticipant::STATUS_ACCEPTED, 409, 'Vous participez déjà à cette Transmission.');
            abort_if(in_array($participant?->status, [TransmissionParticipant::STATUS_OFFERED, TransmissionParticipant::STATUS_INVITED], true), 409, 'Une proposition est déjà en attente pour vous sur cette Transmission.');

            $participant ??= new TransmissionParticipant(['transmission_id' => $locked->id, 'core_identity_reference' => $actor]);
            $participant->fill(['role' => $role, 'status' => TransmissionParticipant::STATUS_OFFERED, 'responded_at' => null, 'declared_done_at' => null, 'declared_done_note' => null])->save();

            $this->workflow->record($locked, 'TRANSMISSION_PARTICIPANT_OFFERED', $actor, null, null);

            return $participant;
        });
    }

    /**
     * L'initiateur ou tout TRANSMITTER accepté invite — jamais une core_identity_reference
     * technique saisie côté client : $subjectDiscoveryReference est résolue vers une
     * identité canonique consentante, même mécanisme que le partage (CAP-022).
     */
    public function invite(Transmission $transmission, string $actor, string $subjectDiscoveryReference, string $role): TransmissionParticipant
    {
        abort_unless(in_array($role, TransmissionParticipant::ROLES, true), 422, 'Rôle de participation invalide.');
        $this->assertCanManage($transmission, $actor);
        abort_unless(in_array($transmission->status, self::OPEN_STATUSES, true), 409, 'Cette Transmission ne reçoit pas d’invitation actuellement.');

        $subject = $this->resolveInvitableSubject($subjectDiscoveryReference);
        abort_if($subject === $actor, 422, 'Vous ne pouvez pas vous inviter vous-même.');

        return DB::transaction(function () use ($transmission, $actor, $subject, $role): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, self::OPEN_STATUSES, true), 409, 'Cette Transmission ne reçoit pas d’invitation actuellement.');

            $participant = TransmissionParticipant::query()
                ->where('transmission_id', $locked->id)
                ->where('core_identity_reference', $subject)
                ->lockForUpdate()
                ->first();
            abort_if($participant?->status === TransmissionParticipant::STATUS_ACCEPTED, 409, 'Cette personne participe déjà à cette Transmission.');

            $participant ??= new TransmissionParticipant(['transmission_id' => $locked->id, 'core_identity_reference' => $subject]);
            $participant->fill([
                'role' => $role, 'status' => TransmissionParticipant::STATUS_INVITED,
                'invited_by_core_reference' => $actor, 'responded_at' => null,
                'declared_done_at' => null, 'declared_done_note' => null,
            ])->save();

            $this->workflow->record($locked, 'TRANSMISSION_PARTICIPANT_INVITED', $actor, null, null);

            return $participant;
        });
    }

    public function acceptOffer(Transmission $transmission, string $actor, TransmissionParticipant $participant): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        $this->assertCanManage($transmission, $actor);

        return $this->accept($transmission, $participant, TransmissionParticipant::STATUS_OFFERED, $actor);
    }

    public function declineOffer(Transmission $transmission, string $actor, TransmissionParticipant $participant, ?string $reason = null): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        $this->assertCanManage($transmission, $actor);

        return $this->decline($transmission, $participant, TransmissionParticipant::STATUS_OFFERED, $actor);
    }

    public function acceptInvitation(Transmission $transmission, string $actor, TransmissionParticipant $participant): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        abort_unless(hash_equals($participant->core_identity_reference, $actor), 403);

        return $this->accept($transmission, $participant, TransmissionParticipant::STATUS_INVITED, $actor);
    }

    public function declineInvitation(Transmission $transmission, string $actor, TransmissionParticipant $participant): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        abort_unless(hash_equals($participant->core_identity_reference, $actor), 403);

        return $this->decline($transmission, $participant, TransmissionParticipant::STATUS_INVITED, $actor);
    }

    public function withdraw(Transmission $transmission, string $actor, TransmissionParticipant $participant): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        abort_unless(hash_equals($participant->core_identity_reference, $actor), 403);

        return DB::transaction(function () use ($transmission, $participant, $actor): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            $lockedParticipant = TransmissionParticipant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedParticipant->status === TransmissionParticipant::STATUS_OFFERED, 409, 'Cette proposition n’est plus en attente.');
            $lockedParticipant->update(['status' => TransmissionParticipant::STATUS_WITHDRAWN, 'responded_at' => now()]);
            $this->workflow->record($locked, 'TRANSMISSION_PARTICIPANT_WITHDRAWN', $actor, null, null);

            return $lockedParticipant;
        });
    }

    /** Un participant accepté quitte lui-même la Transmission, quel que soit son rôle. */
    public function leave(Transmission $transmission, string $actor, TransmissionParticipant $participant): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        abort_unless(hash_equals($participant->core_identity_reference, $actor), 403);

        return DB::transaction(function () use ($transmission, $participant, $actor): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status, Transmission::TERMINAL_STATUSES, true), 409, 'Cette Transmission est terminée.');
            $lockedParticipant = TransmissionParticipant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedParticipant->status === TransmissionParticipant::STATUS_ACCEPTED, 409, 'Cette participation n’est plus active.');
            $lockedParticipant->update(['status' => TransmissionParticipant::STATUS_WITHDRAWN, 'responded_at' => now()]);
            $this->workflow->record($locked, 'TRANSMISSION_PARTICIPANT_LEFT', $actor, null, null);

            return $lockedParticipant;
        });
    }

    /** Un TRANSMITTER accepté retire un autre participant — jamais soi-même via cette action, jamais l'autorité contextuelle (§18). */
    public function remove(Transmission $transmission, string $actor, TransmissionParticipant $participant, string $reason): TransmissionParticipant
    {
        $this->assertBelongs($transmission, $participant);
        abort_if(trim($reason) === '', 422, 'Une raison est requise pour retirer un participant.');
        abort_if(hash_equals($participant->core_identity_reference, $actor), 422, 'Utilisez « quitter » pour vous retirer vous-même.');
        abort_unless($this->workflow->isAcceptedParticipant($transmission, $actor, TransmissionParticipant::ROLE_TRANSMITTER), 403);

        return DB::transaction(function () use ($transmission, $participant, $actor, $reason): TransmissionParticipant {
            $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status, Transmission::TERMINAL_STATUSES, true), 409, 'Cette Transmission est terminée.');
            $lockedParticipant = TransmissionParticipant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedParticipant->status === TransmissionParticipant::STATUS_ACCEPTED, 409, 'Cette participation n’est plus active.');
            $lockedParticipant->update(['status' => TransmissionParticipant::STATUS_REMOVED, 'responded_at' => now()]);
            $this->workflow->record($locked, 'TRANSMISSION_PARTICIPANT_REMOVED', $actor, null, null, ['reason' => $reason]);

            return $lockedParticipant;
        });
    }

    private function accept(Transmission $transmission, TransmissionParticipant $participant, string $expectedFrom, string $actor): TransmissionParticipant
    {
        return DB::transaction(function () use ($transmission, $participant, $expectedFrom, $actor): TransmissionParticipant {
            $lockedTransmission = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            $locked = TransmissionParticipant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === $expectedFrom, 409, 'Cette proposition n’est plus en attente.');
            abort_unless(in_array($lockedTransmission->status, self::OPEN_STATUSES, true), 409, 'Cette Transmission n’accepte plus de nouvelle participation.');

            $locked->update(['status' => TransmissionParticipant::STATUS_ACCEPTED, 'responded_at' => now()]);
            $this->workflow->record($lockedTransmission, 'TRANSMISSION_PARTICIPANT_ACCEPTED', $actor, null, null);

            $this->maybePromoteToAccepted($lockedTransmission, $actor);

            return $locked;
        });
    }

    private function decline(Transmission $transmission, TransmissionParticipant $participant, string $expectedFrom, string $actor): TransmissionParticipant
    {
        return DB::transaction(function () use ($transmission, $participant, $expectedFrom, $actor): TransmissionParticipant {
            $lockedTransmission = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($lockedTransmission->status, Transmission::TERMINAL_STATUSES, true), 409, 'Cette Transmission est terminée.');
            $locked = TransmissionParticipant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === $expectedFrom, 409, 'Cette proposition n’est plus en attente.');
            $locked->update(['status' => TransmissionParticipant::STATUS_DECLINED, 'responded_at' => now()]);
            $this->workflow->record($lockedTransmission, 'TRANSMISSION_PARTICIPANT_DECLINED', $actor, null, null);

            return $locked;
        });
    }

    /** PROPOSED -> ACCEPTED dès qu'au moins un TRANSMITTER et un LEARNER sont acceptés (§9/§17). */
    private function maybePromoteToAccepted(Transmission $transmission, string $actor): void
    {
        if ($transmission->status !== Transmission::STATUS_PROPOSED) {
            return;
        }

        $accepted = TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->pluck('role');

        if ($accepted->contains(TransmissionParticipant::ROLE_TRANSMITTER) && $accepted->contains(TransmissionParticipant::ROLE_LEARNER)) {
            $this->workflow->applyTransition($transmission, $actor, [Transmission::STATUS_PROPOSED], Transmission::STATUS_ACCEPTED, 'TRANSMISSION_ACCEPTED', mutate: static fn (): array => ['accepted_at' => now()]);
        }
    }

    private function assertCanManage(Transmission $transmission, string $actor): void
    {
        abort_unless(
            hash_equals($transmission->proposed_by_core_reference, $actor) || $this->workflow->isAcceptedParticipant($transmission, $actor, TransmissionParticipant::ROLE_TRANSMITTER),
            403
        );
    }

    private function resolveInvitableSubject(string $discoveryReference): string
    {
        $profile = PersonProfile::query()
            ->where('discovery_reference', $discoveryReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first();

        abort_unless($profile !== null, 422, 'Cette personne n’est pas disponible pour une invitation.');

        return $profile->core_identity_reference;
    }

    private function assertBelongs(Transmission $transmission, TransmissionParticipant $participant): void
    {
        abort_unless($participant->transmission_id === $transmission->id, 404);
    }
}
