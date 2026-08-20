<?php

declare(strict_types=1);

namespace App\Application\Opportunities;

use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\PersonProfile;

/**
 * CAP-064 — transforme les objets métier déjà existants en possibilités d'action
 * explicables. Une opportunité est une projection : elle n'assigne personne,
 * ne mute aucun objet métier et ne produit aucun score de valeur humaine.
 */
final class OpportunityEngine
{
    private const MAX_RESULTS = 20;

    /**
     * @return list<array{
     *   type: string,
     *   reference: string,
     *   title: string,
     *   reasons: list<string>,
     *   action: array{kind: string, route_name: string, route_parameter: string},
     *   priority: int
     * }>
     */
    public function forIdentity(string $identityReference): array
    {
        $profile = PersonProfile::query()->find($identityReference);
        if ($profile === null || ! $profile->orientation_consent) {
            return [];
        }

        if ($profile->availability_status === PersonProfile::AVAILABILITY_PAUSED) {
            return [];
        }

        $statements = CapabilityStatement::query()
            ->where('core_identity_reference', $identityReference)
            ->whereNull('archived_at')
            ->where('matching_consent', true)
            ->get();

        if ($statements->isEmpty()) {
            return [];
        }

        $labels = $statements->pluck('normalized_label')->filter()->unique()->values();
        if ($labels->isEmpty()) {
            return [];
        }

        $missions = Mission::query()
            ->where('status', Mission::STATUS_OPEN)
            ->whereIn('visibility', [Mission::VISIBILITY_PUBLIC, Mission::VISIBILITY_PROGRAM])
            ->whereHas('capabilityRequirements', static fn ($query) => $query->whereIn('normalized_label', $labels))
            ->with(['capabilityRequirements' => static fn ($query) => $query->whereIn('normalized_label', $labels)])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(self::MAX_RESULTS * 2)
            ->get();

        $results = [];
        foreach ($missions as $mission) {
            $reasons = $mission->capabilityRequirements
                ->pluck('label')
                ->filter()
                ->unique()
                ->map(static fn (string $label): string => 'Votre capacité « '.$label.' » correspond à cette mission.')
                ->values()
                ->all();

            if ($mission->participation_mode !== null && $profile->participation_mode === $mission->participation_mode) {
                $reasons[] = 'Le mode de participation correspond à votre préférence déclarée.';
            }

            if ($mission->location !== null && $profile->city !== null
                && str_contains(mb_strtolower($mission->location), mb_strtolower($profile->city))) {
                $reasons[] = 'La localisation de la mission correspond à votre ville déclarée.';
            }

            if ($profile->availability_status === PersonProfile::AVAILABILITY_OPEN) {
                $reasons[] = 'Vous vous déclarez disponible pour de nouvelles sollicitations.';
            }

            $results[] = [
                'type' => 'MISSION',
                'reference' => (string) $mission->public_reference,
                'title' => (string) $mission->title,
                'reasons' => array_values(array_unique($reasons)),
                'action' => [
                    'kind' => 'VIEW_MISSION',
                    'route_name' => 'missions.show',
                    'route_parameter' => (string) $mission->public_reference,
                ],
                // Priorité d'action, jamais valeur humaine : plus de raisons métier
                // rapprochent l'objet du contexte de la personne.
                'priority' => count(array_unique($reasons)),
            ];
        }

        usort($results, static fn (array $left, array $right): int =>
            [$right['priority'], mb_strtolower($left['title'])]
            <=> [$left['priority'], mb_strtolower($right['title'])]
        );

        return array_slice($results, 0, self::MAX_RESULTS);
    }
}
