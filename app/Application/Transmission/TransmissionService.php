<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Models\PersonProfile;
use App\Models\Transmission;
use App\Models\TransmissionContribution;
use App\Models\TransmissionMilestone;
use App\Models\TransmissionParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Couche de lecture, sections « Mes Transmissions » (fiche §20) et petites mutations non
 * sensibles (jalons, contributions). Les transitions de statut restent exclusivement dans
 * TransmissionWorkflow / TransmissionParticipationService.
 */
final class TransmissionService
{
    private const POOL_LIMIT = 200;
    private const CANDIDATE_LIMIT = 100;

    public function __construct(
        private readonly TransmissionVisibilityService $visibility,
        private readonly TransmissionWorkflow $workflow,
    ) {}

    public function find(string $publicReference, string $actor): Transmission
    {
        $transmission = Transmission::query()->where('public_reference', $publicReference)->firstOrFail();
        abort_unless($this->visibility->canView($transmission, $actor), 404);

        return $transmission;
    }

    /**
     * Personnes proposables à l'invitation : même mécanisme de découverte consentie que
     * Missions/le partage — jamais une saisie libre de référence Core technique.
     *
     * @return list<array{reference: string, label: string}>
     */
    public function invitationCandidates(Transmission $transmission, string $actor): array
    {
        $alreadyEngaged = $transmission->participants()
            ->whereIn('status', TransmissionParticipant::CURRENT_STATUSES)
            ->pluck('core_identity_reference');

        return PersonProfile::query()
            ->where('core_identity_reference', '!=', $actor)
            ->whereNotIn('core_identity_reference', $alreadyEngaged)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_reference')
            ->whereNotNull('discovery_display_name')
            ->orderBy('discovery_display_name')
            ->limit(self::CANDIDATE_LIMIT)
            ->get(['core_identity_reference', 'discovery_reference', 'discovery_display_name'])
            ->map(static fn (PersonProfile $profile): array => [
                'reference' => $profile->discovery_reference,
                'label' => $profile->discovery_display_name,
            ])
            ->values()
            ->all();
    }

    public function addMilestone(Transmission $transmission, string $actor, string $label, bool $isRequired): TransmissionMilestone
    {
        abort_if(trim($label) === '', 422, 'Le libellé est requis.');
        $this->assertAcceptedParticipant($transmission, $actor);

        return DB::transaction(function () use ($transmission, $label, $isRequired): TransmissionMilestone {
            $locked = $this->lockAndAssertNotTerminal($transmission);
            $position = (int) ($locked->milestones()->max('position') ?? 0) + 1;

            return $locked->milestones()->create(['label' => trim($label), 'position' => $position, 'is_required' => $isRequired]);
        });
    }

    /** 100 % de jalons complétés ne clôture jamais automatiquement la Transmission (§10). */
    public function setMilestoneCompletion(Transmission $transmission, string $actor, TransmissionMilestone $milestone, bool $completed): TransmissionMilestone
    {
        abort_unless($milestone->transmission_id === $transmission->id, 404);
        $this->assertAcceptedParticipant($transmission, $actor);

        return DB::transaction(function () use ($transmission, $milestone, $actor, $completed): TransmissionMilestone {
            $this->lockAndAssertNotTerminal($transmission);
            $milestone->update($completed
                ? ['completed_by_core_reference' => $actor, 'completed_at' => now()]
                : ['completed_by_core_reference' => null, 'completed_at' => null]);

            return $milestone;
        });
    }

    public function addContribution(Transmission $transmission, string $actor, string $note, ?array $evidenceContext = null): TransmissionContribution
    {
        abort_if(trim($note) === '', 422, 'Décrivez brièvement cette contribution.');
        $this->assertAcceptedParticipant($transmission, $actor);

        return DB::transaction(function () use ($transmission, $actor, $note, $evidenceContext): TransmissionContribution {
            $this->lockAndAssertNotTerminal($transmission);

            return $transmission->contributions()->create([
                'core_identity_reference' => $actor,
                'note' => trim($note),
                'evidence_context' => $evidenceContext,
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Sections « Mes Transmissions » (fiche §20) — jamais un mur de statistiques. Chaque
     * Transmission rendue est revalidée avec TransmissionVisibilityService::canView() au
     * moment de la lecture.
     */
    public function myTransmissionsSections(string $actor): array
    {
        $myParticipations = TransmissionParticipant::query()
            ->where('core_identity_reference', $actor)
            ->with('transmission')
            ->latest('updated_at')
            ->limit(self::POOL_LIMIT)
            ->get();

        $invitations = $myParticipations->filter(fn (TransmissionParticipant $p): bool => $p->status === TransmissionParticipant::STATUS_INVITED);
        $offered = $myParticipations->filter(fn (TransmissionParticipant $p): bool => $p->status === TransmissionParticipant::STATUS_OFFERED);
        $accepted = $myParticipations->filter(fn (TransmissionParticipant $p): bool => $p->status === TransmissionParticipant::STATUS_ACCEPTED);

        $mesDemandes = $accepted
            ->filter(fn (TransmissionParticipant $p): bool => $p->transmission && $p->transmission->status === Transmission::STATUS_PROPOSED)
            ->map(fn (TransmissionParticipant $p): Transmission => $p->transmission)
            ->unique('id')->values();

        $jeTransmets = $accepted
            ->filter(fn (TransmissionParticipant $p): bool => $p->role === TransmissionParticipant::ROLE_TRANSMITTER
                && $p->transmission && in_array($p->transmission->status, [Transmission::STATUS_ACCEPTED, Transmission::STATUS_IN_PROGRESS], true))
            ->map(fn (TransmissionParticipant $p): Transmission => $p->transmission)
            ->unique('id')->values();

        $jApprends = $accepted
            ->filter(fn (TransmissionParticipant $p): bool => $p->role === TransmissionParticipant::ROLE_LEARNER
                && $p->transmission && in_array($p->transmission->status, [Transmission::STATUS_ACCEPTED, Transmission::STATUS_IN_PROGRESS], true))
            ->map(fn (TransmissionParticipant $p): Transmission => $p->transmission)
            ->unique('id')->values();

        $terminees = $myParticipations
            ->filter(fn (TransmissionParticipant $p): bool => $p->transmission && in_array($p->transmission->status, Transmission::TERMINAL_STATUSES, true))
            ->map(fn (TransmissionParticipant $p): Transmission => $p->transmission)
            ->unique('id')
            ->sortByDesc(fn (Transmission $t) => $t->updated_at)
            ->values();

        return [
            'propositions_recues' => $this->visibleParticipants($invitations, $actor)->values(),
            'mes_demandes' => $this->visibleTransmissions($mesDemandes, $actor),
            'je_me_suis_propose' => $this->visibleParticipants($offered, $actor)->values(),
            'je_transmets' => $this->visibleTransmissions($jeTransmets, $actor),
            'j_apprends' => $this->visibleTransmissions($jApprends, $actor),
            'terminees' => $this->visibleTransmissions($terminees, $actor),
        ];
    }

    /**
     * Une seule prochaine action Transmission, explicable, pour Mon espace (fiche §20).
     * Retourne null si rien ne le concerne.
     */
    public function nextAction(string $actor): ?array
    {
        $sections = $this->myTransmissionsSections($actor);

        if ($sections['propositions_recues']->isNotEmpty()) {
            $transmission = $sections['propositions_recues']->first()->transmission;

            return [
                'heading' => 'Une proposition de Transmission « '.$transmission->capability_label.' » attend votre réponse.',
                'body' => 'Accepter ou décliner reste entièrement votre choix.',
                'primary' => ['label' => 'Répondre à l’invitation', 'href' => route('transmissions.show', $transmission)],
            ];
        }

        $toConfirm = $sections['je_transmets']->concat($sections['j_apprends'])
            ->unique('id')
            ->first(function (Transmission $transmission) use ($actor): bool {
                if ($transmission->status !== Transmission::STATUS_IN_PROGRESS) {
                    return false;
                }
                $declared = TransmissionParticipant::query()
                    ->where('transmission_id', $transmission->id)
                    ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
                    ->whereNotNull('declared_done_at')
                    ->pluck('role');

                return $declared->contains(TransmissionParticipant::ROLE_TRANSMITTER) && $declared->contains(TransmissionParticipant::ROLE_LEARNER);
            });
        if ($toConfirm) {
            return [
                'heading' => 'La Transmission « '.$toConfirm->capability_label.' » peut être confirmée comme terminée.',
                'body' => 'Les deux parts ont été déclarées terminées : une confirmation explicite reste nécessaire.',
                'primary' => ['label' => 'Confirmer la clôture', 'href' => route('transmissions.show', $toConfirm)],
            ];
        }

        return null;
    }

    /** @param Collection<int, Transmission> $transmissions */
    private function visibleTransmissions(Collection $transmissions, string $actor): Collection
    {
        return $transmissions->filter(fn (Transmission $t): bool => $this->visibility->canView($t, $actor))->values();
    }

    /** @param Collection<int, TransmissionParticipant> $participants */
    private function visibleParticipants(Collection $participants, string $actor): Collection
    {
        return $participants->filter(fn (TransmissionParticipant $p): bool => $p->transmission && $this->visibility->canView($p->transmission, $actor));
    }

    /** Participation acceptée ET visibilité courante (fiche §16) — jamais l'une sans l'autre. */
    private function assertAcceptedParticipant(Transmission $transmission, string $actor): void
    {
        $this->workflow->assertCurrentAccess($transmission, $actor);
    }

    private function lockAndAssertNotTerminal(Transmission $transmission): Transmission
    {
        $locked = Transmission::query()->whereKey($transmission->id)->lockForUpdate()->firstOrFail();
        abort_if(in_array($locked->status, Transmission::TERMINAL_STATUSES, true), 409, 'Cette Transmission est terminée : elle reste consultable mais n’est plus modifiable.');

        return $locked;
    }
}
