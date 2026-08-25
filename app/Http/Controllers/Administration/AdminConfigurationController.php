<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — porte d'entrée unique vers les 13 configurations PortalSetting déjà
 * existantes + les 2 nouvelles (Modération, Notifications). Ne réécrit AUCUNE d'entre elles :
 * chaque lien pointe vers son écran de modification propre, inchangé.
 */
final class AdminConfigurationController
{
    public function index(): View
    {
        $groups = [
            'Communauté' => [
                ['route' => 'administration.profile.edit', 'label' => 'Profil & capacités', 'description' => 'Sections du formulaire, libellés, consentements.'],
                ['route' => 'administration.discovery.edit', 'label' => 'Découverte des personnes', 'description' => 'Textes, pagination, filtres du répertoire.'],
                ['route' => 'administration.collective-capabilities.edit', 'label' => 'Capacités collectives ZUMRA', 'description' => 'Seuils de membres et de capacités déclarables.'],
                ['route' => 'administration.needs.edit', 'label' => 'Besoins', 'description' => 'Catégories, quotas d’ouverture, pagination.'],
                ['route' => 'administration.zumra.edit', 'label' => 'Programme ZUMRA', 'description' => 'Présentation, charte, cartes.'],
                ['route' => 'administration.zumra.groups.edit', 'label' => 'Seuils des groupes ZUMRA', 'description' => 'Seuil « établie », responsabilités simultanées, auto-validation.'],
            ],
            'Projets' => [
                ['route' => 'administration.projects.edit', 'label' => 'Projets', 'description' => 'Domaines, quotas de projets actifs.'],
                ['route' => 'administration.project-accompaniment.edit', 'label' => 'Accompagnement Projet', 'description' => 'Types d’intervention DG Afrique.'],
            ],
            'Finance' => [
                ['route' => 'administration.contributions.edit', 'label' => 'Contributions', 'description' => 'Montants, activation, finalités.'],
            ],
            'Moteurs' => [
                ['route' => 'administration.project-matching.edit', 'label' => 'Matching Projet', 'description' => 'Bassin de candidats, résultats et raisons maximum.'],
                ['route' => 'administration.recommendations.edit', 'label' => 'Recommandations de personnes', 'description' => 'Bassin, résultats, critères activables.'],
            ],
            'Pilotage & catalogues' => [
                ['route' => 'administration.moderation.configuration.edit', 'label' => 'Modération', 'description' => 'Durées par défaut des sanctions (avertissement, suspension).'],
                ['route' => 'administration.notifications.edit', 'label' => 'Notifications (FYI)', 'description' => 'Fenêtres, limites d’affichage, types activés.'],
                ['route' => 'administration.satellites.index', 'label' => 'Satellites', 'description' => 'Applications externes raccordées — création, modification, activation.'],
            ],
        ];

        return view('administration.configuration.index', ['groups' => $groups]);
    }
}
