<?php

declare(strict_types=1);

namespace App\Application\Recommendation;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
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

        $recommendations = [];
        foreach ($candidates as $candidate) {
            [$reasons, $priority] = $this->reasons($profile, $viewerStatements, $candidate, $configuration);
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

    /** @return array{list<string>, int} */
    private function reasons(PersonProfile $viewer, Collection $viewerStatements, PersonProfile $candidate, array $configuration): array
    {
        $viewerByKind = $viewerStatements->groupBy('kind');
        $candidateByKind = $candidate->capabilityStatements->groupBy('kind');
        $reasons = [];
        $priority = 999;

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
