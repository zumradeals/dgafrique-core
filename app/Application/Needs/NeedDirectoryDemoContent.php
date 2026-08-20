<?php

declare(strict_types=1);

namespace App\Application\Needs;

use App\Models\Need;
use Illuminate\Support\Collection;

/**
 * Contenu de démonstration du portail Besoins (docs/design/DESIGN-INVARIANTS.md §21) : cartes de
 * besoin d'exemple, chargées depuis resources/design-reference/needs-demo.json, jamais depuis
 * dg_needs.
 *
 * Règle DEMO-FIRST, REAL-DATA-TAKES-OVER (déjà appliquée à /projets, §18) : une carte de
 * démonstration ne s'affiche, catégorie par catégorie, qu'en première page et tant qu'aucun besoin
 * réel visible n'existe pour cette catégorie — dès qu'un besoin réel existe pour cette catégorie,
 * la carte de démonstration correspondante disparaît d'elle-même.
 */
final class NeedDirectoryDemoContent
{
    private ?array $fixture = null;

    /**
     * @param  Collection<int, Need>  $realVisibleNeeds  Besoins réels visibles, non paginés.
     * @return Collection<int, array>
     */
    public function demoCards(Collection $realVisibleNeeds, int $page, ?string $categoryFilter): Collection
    {
        if ($page !== 1) {
            return collect();
        }

        $realCategories = $realVisibleNeeds->pluck('category')->all();

        return collect($this->load()['cards'])
            ->reject(static fn (array $card): bool => in_array($card['category'], $realCategories, true))
            ->when($categoryFilter !== null && $categoryFilter !== '', static fn (Collection $cards) => $cards->where('category', $categoryFilter))
            ->values();
    }

    private function load(): array
    {
        return $this->fixture ??= json_decode(
            file_get_contents(resource_path('design-reference/needs-demo.json')),
            true,
        );
    }
}
