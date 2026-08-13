<?php

declare(strict_types=1);

namespace App\Application\Profile;

use App\Models\PortalSetting;

final class ProfileConfiguration
{
    public const KEY = 'capabilities.profile.form';

    public function get(): array
    {
        return PortalSetting::query()->find(self::KEY)?->value ?? $this->defaults();
    }

    public function defaults(): array
    {
        return [
            'title' => 'Ce que vous savez faire. Ce que vous voulez devenir.',
            'introduction' => 'Ce profil appartient à votre Compte DG Afrique. Il sert à mieux vous orienter, jamais à résumer votre valeur par un score.',
            'sections' => [
                'situation' => ['enabled' => true, 'order' => 10, 'title' => 'Votre situation aujourd’hui', 'help' => 'Quelques repères concrets pour comprendre votre contexte.'],
                'skills' => ['enabled' => true, 'order' => 20, 'title' => 'Vos savoir-faire actuels', 'help' => 'Une compétence ou pratique par ligne. Les expériences informelles comptent aussi.'],
                'learning' => ['enabled' => true, 'order' => 30, 'title' => 'Ce que vous voulez apprendre', 'help' => 'Écrivez un objectif par ligne.'],
                'intentions' => ['enabled' => true, 'order' => 40, 'title' => 'Vos domaines et intentions', 'help' => 'Précisez ce qui vous intéresse et ce que vous cherchez à accomplir.'],
            ],
            'participation_modes' => [
                ['value' => 'EN_LIGNE', 'label' => 'En ligne'],
                ['value' => 'PRESENTIEL', 'label' => 'En présentiel'],
                ['value' => 'HYBRIDE', 'label' => 'Hybride'],
            ],
            'country_suggestions' => ['CI', 'SN', 'ML', 'GN', 'BF', 'TG', 'BJ'],
            'field_labels' => [
                'country_code' => 'Pays (code à 2 lettres)', 'city' => 'Ville ou localité',
                'current_activity' => 'Activité actuelle', 'phone' => 'Téléphone facultatif',
                'education_level' => 'Niveau ou parcours de formation',
                'starts_without_skill' => 'Je commence sans compétence particulière à déclarer pour le moment.',
                'interest_domains' => 'Domaines qui vous intéressent',
                'intentions' => 'Ce que vous cherchez à accomplir',
                'participation_mode' => 'Mode de participation préféré',
            ],
            'required_fields' => [
                'country_code' => false, 'city' => false, 'current_activity' => false,
                'phone' => false, 'education_level' => false, 'existing_skills_text' => false,
                'learning_goals_text' => false, 'interest_domains_text' => false,
                'intentions_text' => false, 'participation_mode' => false,
            ],
            'orientation_consent_label' => 'Recevoir des orientations utiles',
            'orientation_consent_help' => 'J’accepte que DG Afrique utilise ce profil pour me proposer des apprentissages ou prochaines actions. Ce choix est révocable.',
            'submit_label' => 'Enregistrer mon profil',
            'no_score_notice' => 'Aucun score de valeur personnelle ne sera calculé.',
        ];
    }
}
