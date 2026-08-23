<?php

declare(strict_types=1);

namespace App\Application\Opportunities;

use App\Application\Missions\MissionVisibilityService;
use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\PersonProfile;

/**
 * CAP-064 — transforme les objets métier déjà existants en possibilités d'action
 * explicables. Une opportunité est une projection : elle n'assigne personne,
 * ne mute aucun objet métier et ne produit aucun score de valeur humaine.
 *
 * La visibilité déclarée d'une Mission (PUBLIC/PROGRAM) n'est jamais suffisante à elle
 * seule : chaque candidate est revalidée par MissionVisibilityService::canViewMission(),
 * la même intersection contexte×visibilité que le reste du domaine Missions.
 */
final class OpportunityEngine
{
    private const MAX_RESULTS = 20;

    public function __construct(private readonly MissionVisibilityService $visibility) {}

    /**
     * @return list<array{
     *   type: string,
     *   reference: string,
     *   title: string,
     *   reasons: list<string>,
     *   action: array{kind: string, context_type: string, context_reference: string},
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

        // Les capacités PRIVATE consenties (matching_consent) restent utilisables pour les
        // opportunités de la personne elle-même : VISIBILITY_DISCOVERABLE ne gouverne que la
        // découvrabilité PAR AUTRUI (cf. PersonRecommendationEngine sur les capacités du viewer).
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

        // UIUX-008 — audit Phase A : une Mission à laquelle la personne a déjà une relation
        // active (offerte, invitée ou acceptée) ne doit plus se présenter comme « ce que vous
        // pourriez rejoindre ». Une relation résolue (déclinée, retirée, libérée, retirée par
        // l'autorité) ne l'exclut jamais : la Mission redevient une possibilité réelle une fois
        // la personne effectivement libre d'y répondre à nouveau.
        $activeMissionIds = MissionAssignment::query()
            ->where('core_identity_reference', $identityReference)
            ->whereIn('status', MissionAssignment::CURRENT_STATUSES)
            ->pluck('mission_id');

        $missions = Mission::query()
            ->where('status', Mission::STATUS_OPEN)
            ->whereIn('visibility', [Mission::VISIBILITY_PUBLIC, Mission::VISIBILITY_PROGRAM])
            ->whereNotIn('id', $activeMissionIds)
            ->whereHas('capabilityRequirements', static fn ($query) => $query->whereIn('normalized_label', $labels))
            ->with(['capabilityRequirements' => static fn ($query) => $query->whereIn('normalized_label', $labels)])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(self::MAX_RESULTS * 2)
            ->get();

        $results = [];
        foreach ($missions as $mission) {
            if (! $this->visibility->canViewMission($mission, $identityReference)) {
                continue;
            }

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

            $reasons = array_values(array_unique($reasons));

            $results[] = [
                'type' => 'MISSION',
                'reference' => (string) $mission->public_reference,
                'title' => (string) $mission->title,
                'reasons' => $reasons,
                'action' => [
                    'kind' => 'OPEN_MISSION_CONTEXT',
                    'context_type' => (string) $mission->context_type,
                    'context_reference' => (string) $mission->context_reference,
                ],
                // Priorité d'action, jamais valeur humaine : plus de raisons métier
                // rapprochent l'objet du contexte de la personne.
                'priority' => count($reasons),
            ];
        }

        usort($results, static function (array $left, array $right): int {
            $priority = $right['priority'] <=> $left['priority'];

            return $priority !== 0
                ? $priority
                : strcasecmp($left['title'], $right['title']);
        });

        return array_slice($results, 0, self::MAX_RESULTS);
    }
}
