<?php

declare(strict_types=1);

namespace App\Application\Profile;

use App\Models\CapabilityStatement;
use Illuminate\Support\Str;

final class CapabilityStatementSynchronizer
{
    /**
     * @param array<string, list<string>> $dimensions
     */
    public function sync(string $identityReference, array $dimensions, bool $matchingConsent, bool $discoveryConsent): void
    {
        $visibility = $matchingConsent && $discoveryConsent
            ? CapabilityStatement::VISIBILITY_DISCOVERABLE
            : CapabilityStatement::VISIBILITY_PRIVATE;

        CapabilityStatement::query()
            ->where('core_identity_reference', $identityReference)
            ->update(['matching_consent' => $matchingConsent, 'visibility' => $visibility]);

        foreach ($dimensions as $kind => $labels) {
            $this->syncDimension($identityReference, $kind, $labels, $matchingConsent, $visibility);
        }
    }

    /** @param list<string> $labels */
    private function syncDimension(string $identityReference, string $kind, array $labels, bool $matchingConsent, string $visibility): void
    {
        if (! in_array($kind, [
            CapabilityStatement::KIND_POSSESSED,
            CapabilityStatement::KIND_LEARNING,
            CapabilityStatement::KIND_TRANSMISSION,
        ], true)) {
            throw new \InvalidArgumentException('Unsupported capability statement kind.');
        }

        $normalizedLabels = [];
        foreach ($labels as $label) {
            $cleanLabel = mb_substr(trim($label), 0, 200);
            $normalized = self::normalize($cleanLabel);
            if ($normalized === '') {
                continue;
            }

            $normalizedLabels[] = $normalized;
            CapabilityStatement::query()->updateOrCreate(
                [
                    'core_identity_reference' => $identityReference,
                    'kind' => $kind,
                    'normalized_label' => $normalized,
                ],
                [
                    'label' => $cleanLabel,
                    'matching_consent' => $matchingConsent,
                    'visibility' => $visibility,
                    'archived_at' => null,
                ],
            );
        }

        $query = CapabilityStatement::query()
            ->where('core_identity_reference', $identityReference)
            ->where('kind', $kind)
            ->where('source', 'PROFILE')
            ->whereNull('archived_at');

        if ($normalizedLabels !== []) {
            $query->whereNotIn('normalized_label', array_values(array_unique($normalizedLabels)));
        }

        $query->update(['archived_at' => now()]);
    }

    public static function normalize(string $label): string
    {
        return mb_substr(Str::of($label)->ascii()->lower()->squish()->toString(), 0, 200);
    }
}
