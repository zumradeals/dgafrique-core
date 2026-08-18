<?php

declare(strict_types=1);

namespace App\Application\Recommendation;

use App\Models\PortalSetting;

final class RecommendationConfiguration
{
    public const KEY = 'capabilities.recommendations';

    public function get(): array
    {
        $defaults = $this->defaults();
        $stored = PortalSetting::query()->find(self::KEY)?->value;

        return is_array($stored) ? array_replace($defaults, array_intersect_key($stored, $defaults)) : $defaults;
    }

    public function defaults(): array
    {
        return [
            'title' => 'Des personnes à découvrir pour avancer.',
            'introduction' => 'Ces suggestions rapprochent vos objectifs des capacités que d’autres membres ont choisi de rendre visibles.',
            'privacy_notice' => 'Une recommandation propose une piste. Elle ne décide pas à votre place et ne mesure jamais la valeur d’une personne.',
            'empty_title' => 'Aucune recommandation suffisamment claire.',
            'empty_text' => 'Enrichissez vos capacités, apprentissages et transmissions pour faire apparaître des rapprochements explicables.',
            'detail_button' => 'Comprendre ce profil',
            'hide_button' => 'Masquer cette suggestion',
            'max_results' => 8,
            'candidate_pool' => 200,
            'max_reasons' => 4,
            'learning_transmission' => true,
            'transmission_learning' => true,
            'proof_evidence' => true,
            'shared_capability' => true,
            'shared_domain' => true,
            'location_context' => true,
            'participation_context' => true,
            'availability_declared' => true,
        ];
    }
}
