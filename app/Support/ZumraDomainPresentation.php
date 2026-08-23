<?php

declare(strict_types=1);

namespace App\Support;

/**
 * UIUX-010 — décoration purement visuelle (icône + illustration + teinte) pour une activité
 * ZUMRA en texte libre. Ne remplace jamais `ZumraGroup::domain`, ne le normalise pas, ne crée
 * aucune taxonomie stockée : un domaine non reconnu retombe simplement sur la présentation
 * générique « Autres », sans jamais bloquer la création ou l'affichage d'une activité nouvelle.
 */
final class ZumraDomainPresentation
{
    private const MAP = [
        'numérique' => 'numerique', 'numériques' => 'numerique', 'numerique' => 'numerique', 'digital' => 'numerique', 'tech' => 'numerique', 'informatique' => 'numerique',
        'artisanat' => 'artisanat', 'menuiserie' => 'artisanat', 'couture' => 'artisanat', 'textile' => 'artisanat', 'cuir' => 'artisanat',
        'éducation' => 'education', 'education' => 'education', 'formation' => 'education', 'alphabétisation' => 'education',
        'agriculture' => 'agriculture', 'agroalimentaire' => 'agriculture', 'élevage' => 'agriculture',
        'santé' => 'sante', 'sante' => 'sante', 'médical' => 'sante', 'hygiène' => 'sante',
        'social' => 'social', 'solidarité' => 'social', 'entraide' => 'social',
        'culture' => 'culture', 'art' => 'culture', 'musique' => 'culture', 'artisanat d’art' => 'culture',
    ];

    private const LABELS = [
        'numerique' => 'Numériques', 'artisanat' => 'Artisanat', 'education' => 'Éducation', 'agriculture' => 'Agriculture',
        'sante' => 'Santé', 'social' => 'Social', 'culture' => 'Culture', 'autres' => 'Autres',
    ];

    private const TINTS = [
        'numerique' => '#0b4a30', 'artisanat' => '#8a4a1f', 'education' => '#123a63', 'agriculture' => '#2f5c1f',
        'sante' => '#7a1f2e', 'social' => '#6b3f14', 'culture' => '#4a2a63', 'autres' => '#1c1b17',
    ];

    public static function key(?string $domain): string
    {
        $normalized = mb_strtolower(trim((string) $domain));
        foreach (self::MAP as $needle => $key) {
            if ($normalized === $needle || str_contains($normalized, $needle)) {
                return $key;
            }
        }

        return 'autres';
    }

    public static function cover(?string $domain): string
    {
        return asset('images/zumra/carte-'.self::key($domain).'.svg');
    }

    public static function tint(?string $domain): string
    {
        return self::TINTS[self::key($domain)];
    }

    /** @return list<array{key: string, label: string}> Les huit familles de présentation, pour un domaine générique observé et pour la légende « Explorer par activité ». */
    public static function knownFamilies(): array
    {
        return array_map(
            static fn (string $key, string $label): array => ['key' => $key, 'label' => $label],
            array_keys(self::LABELS),
            self::LABELS,
        );
    }
}
