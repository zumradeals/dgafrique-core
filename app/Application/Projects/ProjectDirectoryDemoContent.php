<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Contenu de démonstration du portail Projets (docs/design/DESIGN-INVARIANTS.md §18) :
 * statistiques réseau et cartes de projet d'exemple, chargées depuis
 * resources/design-reference/projets-demo.json, jamais depuis dg_projects.
 *
 * Règle DEMO-FIRST, REAL-DATA-TAKES-OVER (déjà appliquée au Fil V2, §17) : une carte de
 * démonstration ne s'affiche, domaine par domaine, qu'en première page et tant qu'aucun projet
 * réel visible n'existe pour ce domaine — dès qu'un projet réel existe pour ce domaine, la carte
 * de démonstration correspondante disparaît d'elle-même.
 */
final class ProjectDirectoryDemoContent
{
    private ?array $fixture = null;

    public function communityStats(): array
    {
        return $this->load()['statistiques'];
    }

    /**
     * @param  Collection<int, Project>  $realVisibleProjects  Projets réels visibles, non paginés.
     * @return Collection<int, array>
     */
    public function demoCards(Collection $realVisibleProjects, int $page, ?string $domainFilter): Collection
    {
        if ($page !== 1) {
            return collect();
        }

        $realDomains = $realVisibleProjects->pluck('domain')->all();

        return collect($this->load()['cards'])
            ->reject(static fn (array $card): bool => in_array($card['domain_slug'], $realDomains, true))
            ->when($domainFilter !== null && $domainFilter !== '', static fn (Collection $cards) => $cards->where('domain_slug', $domainFilter))
            ->values();
    }

    private function load(): array
    {
        return $this->fixture ??= json_decode(
            file_get_contents(resource_path('design-reference/projets-demo.json')),
            true,
        );
    }
}
