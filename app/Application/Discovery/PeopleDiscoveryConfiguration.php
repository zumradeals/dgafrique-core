<?php

declare(strict_types=1);

namespace App\Application\Discovery;

use App\Models\PortalSetting;

final class PeopleDiscoveryConfiguration
{
    public const KEY = 'capabilities.people.discovery';

    public function get(): array
    {
        $defaults = $this->defaults();
        $stored = PortalSetting::query()->find(self::KEY)?->value;

        return is_array($stored) ? array_replace($defaults, array_intersect_key($stored, $defaults)) : $defaults;
    }

    public function defaults(): array
    {
        return [
            'title' => 'Découvrir des personnes par ce qu’elles peuvent apporter.',
            'introduction' => 'Cherchez une capacité, une transmission ou un objectif d’apprentissage. Chaque résultat repose sur un consentement réel.',
            'privacy_notice' => 'DG Afrique n’affiche ni téléphone, ni preuve privée, ni référence d’identité. Aucun score humain n’est calculé.',
            'empty_title' => 'Aucun profil ne correspond encore.',
            'empty_text' => 'Essayez une autre capacité ou revenez lorsque davantage de membres auront choisi d’être visibles.',
            'detail_button' => 'Voir le profil utile',
            'page_size' => 12,
            'country_filter' => true,
            'mode_filter' => true,
        ];
    }
}
