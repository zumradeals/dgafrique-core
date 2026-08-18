<?php

declare(strict_types=1);

namespace App\Application\Recommendation;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\RecommendationDecision;
use Illuminate\Support\Collection;

final class PersonRecommendationEngine
{
    /**
     * @return array{profile: PersonProfile|null, recommendations: list<array{profile: PersonProfile, reasons: list<string>}>}
     */
    public function forIdentity(string $identityReference, array $configuration): array
    {
        $profile = PersonProfile::query()->find($identityReference);
        if (! $profile || ! $profile->orientation_consent) {
            return ['profile' => $profile, 'recommendations' => []];
        }

        $viewerStatements = CapabilityStatement::query()
            ->where('core_identity_reference', $identityReference)
            ->whereNull('archived_at')
            ->where('matching_consent', true)
            ->get();
        $hidden = RecommendationDecision::query()
            ->where('core_identity_reference', $identityReference)
            ->where('subject_type', RecommendationDecision::SUBJECT_PERSON)
            ->where('decision', RecommendationDecision::DECISION_HIDDEN)
            ->pluck('subject_reference');

        $candidates = PersonProfile::query()
            ->where('core_identity_reference', '!=', $identityReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_reference')
            ->whereNotNull('discovery_display_name')
            ->when($hidden->isNotEmpty(), static fn ($query) => $query->whereNotIn('discovery_reference', $hidden))
            ->with(['capabilityStatements' => static fn ($query) => $query
                ->whereNull('archived_at')
                ->where('matching_consent', true)
                ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)])
            ->orderByDesc('discovery_consented_at')
            ->limit((int) $configuration['candidate_pool'])
            ->get();

        // Carnet de preuves (CAP-036) : une preuve DISCOVERABLE ne produit jamais un score —
        // seulement une raison explicable de plus, chargée une seule fois pour tous les
        // candidats plutôt qu'une requête par candidat.
        $viewerLearningLabels = $viewerStatements->where('kind', CapabilityStatement::KIND_LEARNING)->pluck('normalized_label')->unique()->values();
        $candidateProofsByOwner = ($configuration['proof_evidence'] ?? false) && $viewerLearningLabels->isNotEmpty()
            ? Proof::query()
                ->where('owner_type', Proof::OWNER_PERSON)
                ->whereIn('owner_reference', $candidates->pluck('core_identity_reference'))
                ->where('visibility', Proof::VISIBILITY_DISCOVERABLE)
                ->whereNull('archived_at')
                ->whereIn('normalized_label', $viewerLearningLabels)
                ->get()
                ->groupBy('owner_reference')
            : collect();

        $recommendations = [];
        foreach ($candidates as $candidate) {
            [$reasons, $priority] = $this->reasons($profile, $viewerStatements, $candidate, $configuration, $candidateProofsByOwner->get($candidate->core_identity_reference, collect()));
            if ($reasons === [] || $priority > 40) {
                continue;
            }
            $recommendations[] = [
                'profile' => $candidate,
                'reasons' => array_slice($reasons, 0, (int) $configuration['max_reasons']),
                '_priority' => $priority,
            ];
        }

        usort($recommendations, static fn (array $left, array $right): int =>
            [$left['_priority'], -count($left['reasons']), mb_strtolower($left['profile']->discovery_display_name)]
            <=> [$right['_priority'], -count($right['reasons']), mb_strtolower($right['profile']->discovery_display_name)]
        );
        $recommendations = array_slice($recommendations, 0, (int) $configuration['max_results']);
        foreach ($recommendations as &$recommendation) {
            unset($recommendation['_priority']);
        }

        return ['profile' => $profile, 'recommendations' => $recommendations];
    }

    /** @param Collection<int, Proof> $candidateProofs @return array{list<string>, int} */
    private function reasons(PersonProfile $viewer, Collection $viewerStatements, PersonProfile $candidate, array $configuration, Collection $candidateProofs = new Collection()): array
    {
        $viewerByKind = $viewerStatements->groupBy('kind');
        $candidateByKind = $candidate->capabilityStatements->groupBy('kind');
        $reasons = [];
        $priority = 999;

        if (($configuration['proof_evidence'] ?? false) && $candidateProofs->isNotEmpty()) {
            foreach ($candidateProofs->pluck('capability_label')->filter()->unique() as $label) {
                $reasons[] = "Cette personne a enregistré une preuve pour « {$label} ».";
                $priority = min($priority, 15);
            }
        }

        if ($configuration['learning_transmission']) {
            foreach (CapabilityStatementSynchronizer::matchingLabels($viewerByKind->get(CapabilityStatement::KIND_LEARNING, collect()), $candidateByKind->get(CapabilityStatement::KIND_TRANSMISSION, collect())) as $label) {
                $reasons[] = "Vous souhaitez apprendre « {$label} » et cette personne propose de le transmettre.";
                $priority = min($priority, 10);
            }
        }
        if ($configuration['transmission_learning']) {
            foreach (CapabilityStatementSynchronizer::matchingLabels($viewerByKind->get(CapabilityStatement::KIND_TRANSMISSION, collect()), $candidateByKind->get(CapabilityStatement::KIND_LEARNING, collect())) as $label) {
                $reasons[] = "Vous proposez de transmettre « {$label} » et cette personne souhaite l’apprendre.";
                $priority = min($priority, 20);
            }
        }
        if ($configuration['shared_capability']) {
            foreach (CapabilityStatementSynchronizer::matchingLabels($viewerByKind->get(CapabilityStatement::KIND_POSSESSED, collect()), $candidateByKind->get(CapabilityStatement::KIND_POSSESSED, collect())) as $label) {
                $reasons[] = "Vous partagez la capacité « {$label} » et pourriez échanger vos expériences.";
                $priority = min($priority, 30);
            }
        }
        if ($configuration['shared_domain']) {
            foreach ($this->matchingFreeLabels($viewer->interest_domains ?? [], $candidate->interest_domains ?? []) as $label) {
                $reasons[] = "Vous vous intéressez tous les deux au domaine « {$label} ».";
                $priority = min($priority, 40);
            }
        }

        if ($priority <= 40 && $configuration['location_context'] && $viewer->city && $candidate->city
            && mb_strtolower(trim($viewer->city)) === mb_strtolower(trim($candidate->city))) {
            $reasons[] = "Vous êtes tous les deux à {$candidate->city}.";
        }
        if ($priority <= 40 && $configuration['participation_context'] && $viewer->participation_mode
            && $viewer->participation_mode === $candidate->participation_mode) {
            $mode = mb_strtolower(str_replace('_', ' ', $candidate->participation_mode));
            $reasons[] = "Vous privilégiez tous les deux un mode {$mode}.";
        }
        // CAP-025 : la disponibilité déclarée n'est jamais un critère de tri à elle seule — une
        // raison supplémentaire uniquement, ajoutée à une recommandation déjà qualifiée par un
        // vrai rapprochement de capacité (même patron que location_context/participation_context).
        if ($priority <= 40 && ($configuration['availability_declared'] ?? false)
            && $candidate->availability_status === PersonProfile::AVAILABILITY_OPEN) {
            $reasons[] = 'Cette personne se déclare disponible pour de nouvelles sollicitations.';
        }

        return [array_values(array_unique($reasons)), $priority];
    }

    /** @param list<string> $left @param list<string> $right @return list<string> */
    private function matchingFreeLabels(array $left, array $right): array
    {
        $rightLabels = [];
        foreach ($right as $label) {
            $rightLabels[CapabilityStatementSynchronizer::normalize((string) $label)] = (string) $label;
        }

        $matches = [];
        foreach ($left as $label) {
            $normalized = CapabilityStatementSynchronizer::normalize((string) $label);
            if ($normalized !== '' && isset($rightLabels[$normalized])) {
                $matches[] = $rightLabels[$normalized];
            }
        }

        return array_values(array_unique($matches));
    }
}
