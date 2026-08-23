<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;

/**
 * PROJECT-HUB-001 — projection de présentation du décor de référence.
 * Ces attributs ne sont ni une taxonomie, ni un moteur de priorité, ni une mesure Core.
 */
final class ProjectHubPresentation
{
    public const PROJECTS = [
        'Plateforme numérique pour artisans d’Abobo' => ['zumra' => 'RAHMAN Technology', 'domain' => 'DIGITAL', 'category' => 'Innovation numérique', 'location' => 'Abidjan, Côte d’Ivoire', 'members' => 12, 'progress' => 65, 'progress_label' => 'En avance', 'display_status' => 'En cours', 'priority' => 'Priorité haute', 'image' => 'digital-artisan.svg'],
        'Installation solaire pour écoles rurales' => ['zumra' => 'ZUMRA Bamtaré', 'domain' => 'ENVIRONMENT', 'category' => 'Infrastructure', 'location' => 'Kayes, Mali', 'members' => 18, 'progress' => 40, 'progress_label' => 'Dans les délais', 'display_status' => 'En cours', 'priority' => 'Priorité haute', 'image' => 'solar-schools.svg'],
        'Centre de formation couture et design' => ['zumra' => 'Excellence ZUMRA', 'domain' => 'CRAFT', 'category' => 'Éducation & Formation', 'location' => 'Dakar, Sénégal', 'members' => 15, 'progress' => 55, 'progress_label' => 'En avance', 'display_status' => 'En cours', 'priority' => 'Priorité moyenne', 'image' => 'couture-training.svg'],
        'Reboisement communautaire de Tambacounda' => ['zumra' => 'ZUMRA Vert Demain', 'domain' => 'ENVIRONMENT', 'category' => 'Environnement', 'location' => 'Tambacounda, Sénégal', 'members' => 8, 'progress' => 10, 'progress_label' => 'Débuté', 'display_status' => 'Nouveau', 'priority' => 'Priorité moyenne', 'image' => 'reforestation.svg'],
        'Bibliothèque numérique mobile' => ['zumra' => 'Savoir pour Tous', 'domain' => 'EDUCATION', 'category' => 'Éducation & Formation', 'location' => 'Korhogo, Côte d’Ivoire', 'members' => 6, 'progress' => 30, 'progress_label' => 'Financement', 'display_status' => 'À soutenir', 'priority' => 'Priorité haute', 'image' => 'mobile-library.svg'],
        'Marché en ligne des producteurs' => ['zumra' => 'AgriZUMRA', 'domain' => 'AGRICULTURE', 'category' => 'Agriculture', 'location' => 'Ouagadougou, Burkina Faso', 'members' => 14, 'progress' => 70, 'progress_label' => 'En avance', 'display_status' => 'En cours', 'priority' => 'Priorité moyenne', 'image' => 'farm-market.svg'],
        'Accès à l’eau potable' => ['zumra' => 'ZUMRA Eau Vie', 'domain' => 'HEALTH', 'category' => 'Infrastructure', 'location' => 'Niamey, Niger', 'members' => 10, 'progress' => 5, 'progress_label' => 'Débuté', 'display_status' => 'Nouveau', 'priority' => 'Priorité basse', 'image' => 'clean-water.svg'],
        'Incubateur de talents numériques' => ['zumra' => 'Code & Impact', 'domain' => 'DIGITAL', 'category' => 'Entrepreneuriat', 'location' => 'Lomé, Togo', 'members' => 22, 'progress' => 60, 'progress_label' => 'En avance', 'display_status' => 'En cours', 'priority' => 'Priorité haute', 'image' => 'digital-talents.svg'],
    ];

    public function for(Project $project, int $realMembers): array
    {
        $demo = self::PROJECTS[$project->name] ?? null;

        return $demo ?? [
            'zumra' => null, 'domain' => $project->domain,
            'category' => $project->domain, 'location' => $project->location ?: 'Lieu à préciser',
            'members' => $realMembers, 'progress' => $project->progressionSeed(),
            'progress_label' => 'En progression',
            'display_status' => match ($project->status) { Project::STATUS_COMPLETED => 'Terminé', Project::STATUS_PROPOSED => 'Nouveau', default => 'En cours' },
            'priority' => 'Projet du réseau', 'image' => 'digital-artisan.svg',
        ];
    }

    public function networkStats(): array
    {
        return ['projects' => 128, 'groups' => 47, 'countries' => 11, 'members' => '1 842', 'beneficiaries' => '315 000+'];
    }
}
