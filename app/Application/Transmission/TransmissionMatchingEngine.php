<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\Transmission;
use App\Models\TransmissionMatchingHide;
use App\Models\TransmissionParticipant;
use Illuminate\Support\Collection;

/**
 * Matching Transmission ↔ personnes (fiche §14). Réutilise la même logique d'appariement
 * LEARNING/TRANSMISSION que PersonRecommendationEngine (CAP-010) via
 * CapabilityStatementSynchronizer::matchingLabels() — jamais une seconde implémentation.
 * Une recommandation propose et explique ; elle ne décide jamais, n'assigne personne et ne
 * produit aucun score de valeur humaine.
 */
final class TransmissionMatchingEngine
{
    private const MAX_RESULTS = 20;

    public function __construct(private readonly TransmissionWorkflow $workflow) {}

    /**
     * $wantedRole désigne le rôle recherché (compléter les transmetteurs ou les apprenants).
     *
     * @return list<array{profile: PersonProfile, reasons: list<string>}>
     */
    public function recommend(Transmission $transmission, string $viewer, string $wantedRole): array
    {
        abort_unless(in_array($wantedRole, TransmissionParticipant::ROLES, true), 422, 'Rôle recherché invalide.');
        abort_unless($this->workflow->isAcceptedParticipant($transmission, $viewer), 403);

        $searchedKind = $wantedRole === TransmissionParticipant::ROLE_TRANSMITTER
            ? CapabilityStatement::KIND_TRANSMISSION
            : CapabilityStatement::KIND_LEARNING;

        $engaged = $transmission->participants()
            ->whereIn('status', TransmissionParticipant::CURRENT_STATUSES)
            ->pluck('core_identity_reference');

        $hidden = TransmissionMatchingHide::query()
            ->where('transmission_id', $transmission->id)
            ->where('viewer_core_reference', $viewer)
            ->pluck('subject_core_reference');

        $statements = CapabilityStatement::query()
            ->where('kind', $searchedKind)
            ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
            ->where('matching_consent', true)
            ->whereNull('archived_at')
            ->where('normalized_label', $transmission->normalized_label)
            ->whereNotIn('core_identity_reference', $engaged->merge($hidden))
            ->get()
            ->groupBy('core_identity_reference');

        $results = [];
        foreach ($statements as $reference => $ownStatements) {
            /** @var Collection<int, CapabilityStatement> $ownStatements */
            $profile = PersonProfile::query()
                ->where('core_identity_reference', $reference)
                ->where('orientation_consent', true)
                ->where('discovery_consent', true)
                ->whereNotNull('discovery_display_name')
                ->first();

            if ($profile === null) {
                continue;
            }

            $label = (string) $ownStatements->first()->label;
            $reasons = [$wantedRole === TransmissionParticipant::ROLE_TRANSMITTER
                ? 'Cette personne propose de transmettre « '.$label.' ».'
                : 'Cette personne souhaite apprendre « '.$label.' ».'];

            $results[] = ['profile' => $profile, 'reasons' => $reasons];
            if (count($results) >= self::MAX_RESULTS) {
                break;
            }
        }

        return $results;
    }

    public function hide(Transmission $transmission, string $viewer, string $subjectDiscoveryReference): void
    {
        abort_unless($this->workflow->isAcceptedParticipant($transmission, $viewer), 403);

        $profile = PersonProfile::query()
            ->where('discovery_reference', $subjectDiscoveryReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first();
        abort_unless($profile !== null, 422, 'Cette personne n’est pas disponible.');

        TransmissionMatchingHide::query()->firstOrCreate([
            'transmission_id' => $transmission->id,
            'viewer_core_reference' => $viewer,
            'subject_core_reference' => $profile->core_identity_reference,
        ], ['created_at' => now()]);
    }
}
