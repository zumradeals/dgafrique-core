<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Models\Need;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class LandingController
{
    // UIUX-001 §5 « Découvrir » : un visiteur anonyme bénéficie d'une découverte publique limitée
    // à ce que les règles de visibilité existantes autorisent réellement (Need/Project::canView,
    // branche VISIBILITY_PUBLIC — aucune règle nouvelle). Ce repère ne correspond à aucune identité
    // réelle : chaque branche non publique de canView() (auteur, porteur, membre de groupe,
    // adhésion Programme ZUMRA…) échoue proprement dessus et seule la visibilité PUBLIC reste.
    private const ANONYMOUS_ACTOR = 'ANONYMOUS-VISITOR';

    public function __invoke(NeedService $needs, ProjectService $projects): View
    {
        // Fixtures d'exemple uniquement (voir resources/design-reference/README.md) :
        // jamais seedées, jamais affichées comme une donnée métier réelle.
        $portalDemo = json_decode(
            file_get_contents(resource_path('design-reference/landing-portal-demo.json')),
            true,
        );

        return view('foundation', [
            'exampleStats' => $portalDemo['statistiques'],
            'exampleMoments' => $portalDemo['moments'],
            'realMoments' => $this->publicMoments($needs, $projects),
        ]);
    }

    /**
     * Règle DEMO-FIRST, REAL-DATA-TAKES-OVER déjà établie (docs/design/DESIGN-INVARIANTS.md
     * §17/§18/§21), appliquée ici à la Landing : dès qu'un Besoin ou Projet réellement PUBLIC
     * existe, il remplace entièrement les cartes d'exemple — jamais mélangé avec elles.
     */
    private function publicMoments(NeedService $needs, ProjectService $projects): Collection
    {
        $realNeeds = Need::query()
            ->where('visibility', Need::VISIBILITY_PUBLIC)
            ->whereNotIn('status', [Need::STATUS_PROPOSED, Need::STATUS_ARCHIVED])
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->filter(fn (Need $need): bool => $needs->canView($need, self::ANONYMOUS_ACTOR))
            ->map(static fn (Need $need): array => [
                'type' => 'besoin',
                'titre' => $need->title,
                'lieu' => $need->location,
                'meta' => 'Besoin réel · '.$need->published_at?->diffForHumans(),
                'occurred_at' => $need->published_at ?? $need->created_at,
            ]);

        $realProjects = Project::query()
            ->where('visibility', Project::VISIBILITY_PUBLIC)
            ->where('status', '!=', Project::STATUS_ARCHIVED)
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->filter(fn (Project $project): bool => $projects->canView($project, self::ANONYMOUS_ACTOR))
            ->map(static fn (Project $project): array => [
                'type' => 'projet',
                'titre' => $project->name,
                'lieu' => null,
                'meta' => 'Projet réel · '.$project->created_at?->diffForHumans(),
                'occurred_at' => $project->created_at,
            ]);

        return $realNeeds->concat($realProjects)
            ->sortByDesc(fn (array $item): int => $item['occurred_at']?->getTimestamp() ?? 0)
            ->take(3)
            ->values();
    }
}
