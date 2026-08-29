<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Models\Need;
use App\Models\PersonProfile;
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
        return view('foundation', [
            'realMoments' => $this->publicMoments($needs, $projects),
            'publicStats' => [
                'people' => PersonProfile::query()
                    ->where('discovery_consent', true)
                    ->whereNotNull('discovery_reference')
                    ->count(),
                'projects' => Project::query()
                    ->where('visibility', Project::VISIBILITY_PUBLIC)
                    ->whereNotIn('status', [Project::STATUS_PROPOSED, Project::STATUS_ARCHIVED])
                    ->count(),
                'countries' => PersonProfile::query()
                    ->where('discovery_consent', true)
                    ->whereNotNull('country_code')
                    ->distinct()
                    ->count('country_code'),
            ],
        ]);
    }

    /**
     * Derniers Besoins et Projets réellement publics. Une base vide produit un état vide honnête.
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
            ->whereNotIn('status', [Project::STATUS_PROPOSED, Project::STATUS_ARCHIVED])
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
