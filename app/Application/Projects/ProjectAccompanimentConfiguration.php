<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\PortalSetting;

final class ProjectAccompanimentConfiguration
{
    public const KEY = 'project_accompaniment.configuration';

    public function get(): array
    {
        $stored = PortalSetting::query()->find(self::KEY)?->value ?? [];
        $configuration = array_replace($this->defaults(), $stored);

        if (isset($stored['action_types']) && is_array($stored['action_types'])) {
            $configuration['action_types'] = $stored['action_types'];
        }

        return $configuration;
    }

    public function defaults(): array
    {
        return [
            'page_title' => 'Un accompagnement progressif pour franchir les prochaines étapes.',
            'page_intro' => 'DG Afrique peut apporter ou coordonner des appuis utiles au projet, directement ou avec des partenaires, sans devenir propriétaire ni prendre le contrôle du projet.',
            'action_types' => [
                'ORIENTATION' => 'Orientation',
                'EXPERTISE' => 'Expertise',
                'TRAINING' => 'Formation',
                'DIGITAL' => 'Appui numérique',
                'MATCHING' => 'Mise en relation',
                'STRUCTURING' => 'Structuration',
                'PARTNERS' => 'Partenaires',
                'FINANCING_MECHANISM' => 'Orientation vers un mécanisme de financement',
            ],
        ];
    }
}
