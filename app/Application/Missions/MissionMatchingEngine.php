<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\MissionMatchingHide;
use App\Models\PersonProfile;
use Illuminate\Support\Collection;

/**
 * Matching Missions ↔ capacités (v0.4 §11, verrouillé v0.5 §10). Une recommandation
 * propose et explique ; elle ne décide jamais, n'assigne personne et ne produit aucun
 * score de valeur humaine. Seules les capacités DISCOVERABLE, consenties et non
 * archivées sont utilisables.
 */
final class MissionMatchingEngine
{
    private const MAX_RESULTS = 20;

    public function __construct(private readonly MissionContextRegistry $registry) {}

    /** @return list<array{profile: PersonProfile, reasons: list<string>}> */
    public function recommend(Mission $mission, string $viewer): array
    {
        $this->assertCanView($mission, $viewer);

        $requirements = $mission->capabilityRequirements()->get();
        if ($requirements->isEmpty()) {
            return [];
        }

        $hidden = MissionMatchingHide::query()
            ->where('mission_id', $mission->id)
            ->where('viewer_core_reference', $viewer)
            ->pluck('subject_core_reference');

        $normalizedLabels = $requirements->pluck('normalized_label')->unique()->values();

        $statements = CapabilityStatement::query()
            ->where('visibility', CapabilityStatement::VISIBILITY_DISCOVERABLE)
            ->where('matching_consent', true)
            ->whereNull('archived_at')
            ->whereIn('normalized_label', $normalizedLabels)
            ->whereNotIn('core_identity_reference', $hidden)
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

            $reasons = $ownStatements
                ->pluck('label')
                ->unique()
                ->map(static fn (string $label): string => 'Capacité correspondante : '.$label)
                ->values()
                ->all();

            if ($mission->participation_mode !== null && $profile->participation_mode === $mission->participation_mode) {
                $reasons[] = 'Mode de participation compatible';
            }
            if ($mission->location !== null && $profile->city !== null
                && str_contains(mb_strtolower($mission->location), mb_strtolower($profile->city))) {
                $reasons[] = 'Localisation consentie compatible';
            }

            $results[] = ['profile' => $profile, 'reasons' => $reasons];
            if (count($results) >= self::MAX_RESULTS) {
                break;
            }
        }

        return $results;
    }

    /**
     * $subjectDiscoveryReference est la référence de découverte publique du profil masqué
     * — jamais une core_identity_reference technique reçue depuis l'UI (celle-ci n'apparaît
     * dans aucune URL ni aucun champ de formulaire côté membre).
     */
    public function hide(Mission $mission, string $viewer, string $subjectDiscoveryReference): void
    {
        $this->assertCanView($mission, $viewer);

        $profile = PersonProfile::query()
            ->where('discovery_reference', $subjectDiscoveryReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first();
        abort_unless($profile !== null, 422, 'Cette personne n’est pas disponible.');

        MissionMatchingHide::query()->firstOrCreate([
            'mission_id' => $mission->id,
            'viewer_core_reference' => $viewer,
            'subject_core_reference' => $profile->core_identity_reference,
        ], ['created_at' => now()]);
    }

    private function assertCanView(Mission $mission, string $actor): void
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);
        abort_unless($adapter->canManageAssignments($context, $actor), 403);
    }
}
