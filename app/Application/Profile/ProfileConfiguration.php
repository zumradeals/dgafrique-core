<?php

declare(strict_types=1);

namespace App\Application\Profile;

use App\Models\PortalSetting;

final class ProfileConfiguration
{
    public const KEY = 'capabilities.profile.form';

    public function get(): array
    {
        $defaults = $this->defaults();
        $stored = PortalSetting::query()->find(self::KEY)?->value;

        if (! is_array($stored)) {
            return $defaults;
        }

        foreach (['title', 'introduction', 'orientation_consent_label', 'orientation_consent_help', 'discovery_consent_label', 'discovery_consent_help', 'submit_label', 'no_score_notice'] as $key) {
            if (array_key_exists($key, $stored)) {
                $defaults[$key] = $stored[$key];
            }
        }
        foreach (array_keys($defaults['sections']) as $key) {
            if (is_array($stored['sections'][$key] ?? null)) {
                $defaults['sections'][$key] = array_replace($defaults['sections'][$key], $stored['sections'][$key]);
            }
        }
        foreach (['field_labels', 'required_fields'] as $key) {
            if (is_array($stored[$key] ?? null)) {
                $defaults[$key] = array_replace($defaults[$key], array_intersect_key($stored[$key], $defaults[$key]));
            }
        }
        foreach (['participation_modes', 'country_suggestions'] as $key) {
            if (is_array($stored[$key] ?? null)) {
                $defaults[$key] = $stored[$key];
            }
        }

        return $defaults;
    }

    public function defaults(): array
    {
        return [
            'title' => 'Ce que vous savez faire. Ce que vous voulez devenir.',
            'introduction' => 'Ce profil appartient à votre Compte DG Afrique. Il sert à mieux vous orienter, jamais à résumer votre valeur par un score.',
            'sections' => [
                'situation' => ['enabled' => true, 'order' => 10, 'title' => 'Votre situation aujourd’hui', 'help' => 'Les repères utiles pour comprendre votre contexte.'],
                'skills' => ['enabled' => true, 'order' => 20, 'title' => 'Ce que vous savez faire', 'help' => 'Les compétences formelles, pratiques et issues de votre expérience.'],
                'transmission' => ['enabled' => true, 'order' => 30, 'title' => 'Ce que vous pouvez transmettre', 'help' => 'Les connaissances ou gestes que vous pourriez partager avec d’autres.'],
                'learning' => ['enabled' => true, 'order' => 40, 'title' => 'Ce que vous voulez apprendre', 'help' => 'Les capacités que vous souhaitez développer.'],
                'experience' => ['enabled' => true, 'order' => 50, 'title' => 'Vos expériences et preuves', 'help' => 'Des réalisations concrètes, avec une preuve seulement si vous souhaitez la partager.'],
                'needs' => ['enabled' => true, 'order' => 60, 'title' => 'Vos besoins et objectifs', 'help' => 'Ce que vous cherchez à résoudre, obtenir ou accomplir.'],
                'collaboration' => ['enabled' => true, 'order' => 70, 'title' => 'Vos préférences de collaboration', 'help' => 'La manière dont vous êtes disponible pour apprendre, transmettre ou agir.'],
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
                'existing_skills' => 'Mes capacités actuelles',
                'transmission_offers' => 'Ce que je peux apprendre ou transmettre à quelqu’un',
                'learning_goals' => 'Ce que je veux apprendre ou renforcer',
                'experience_highlights' => 'Mes expériences, réalisations ou activités',
                'experience_proofs' => 'Liens ou références de preuves — facultatif',
                'declared_needs' => 'Mes besoins actuels',
                'interest_domains' => 'Domaines qui vous intéressent',
                'intentions' => 'Ce que vous cherchez à accomplir',
                'participation_mode' => 'Mode de participation préféré',
                'collaboration_preferences' => 'Types de collaboration qui vous conviennent',
                'discovery_display_name' => 'Nom visible dans la découverte',
                'discovery_bio' => 'Courte présentation visible',
            ],
            'required_fields' => [
                'country_code' => false, 'city' => false, 'current_activity' => false,
                'phone' => false, 'education_level' => false, 'existing_skills_text' => false,
                'transmission_offers_text' => false, 'learning_goals_text' => false,
                'experience_highlights_text' => false, 'experience_proofs_text' => false,
                'declared_needs_text' => false, 'interest_domains_text' => false,
                'intentions_text' => false, 'participation_mode' => false,
                'collaboration_preferences_text' => false,
                'discovery_display_name' => false, 'discovery_bio' => false,
            ],
            'orientation_consent_label' => 'Recevoir des orientations utiles',
            'orientation_consent_help' => 'J’accepte que DG Afrique utilise ce profil pour me proposer des apprentissages ou prochaines actions. Ce choix est révocable.',
            'discovery_consent_label' => 'Permettre aux membres de me découvrir',
            'discovery_consent_help' => 'Seuls votre nom public choisi, votre présentation, votre contexte et vos capacités autorisées seront visibles. Votre téléphone, vos preuves et votre référence d’identité restent privés.',
            'submit_label' => 'Enregistrer mon profil',
            'no_score_notice' => 'Aucun score de valeur personnelle ne sera calculé.',
        ];
    }
}
