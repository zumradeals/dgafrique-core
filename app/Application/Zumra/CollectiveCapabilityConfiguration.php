<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PortalSetting;

final class CollectiveCapabilityConfiguration
{
    public const KEY = 'zumra.collective_capabilities.configuration';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'section_title' => 'Ce que cette équipe peut mobiliser',
            'section_intro' => 'Ces forces sont agrégées à partir de capacités réelles volontairement rendues mobilisables par les membres.',
            'empty_text' => 'Aucune capacité collective n’est encore attestable à partir des consentements actifs.',
            'consent_label' => 'Autoriser cette ZUMRA à compter mes capacités découvrables dans son profil collectif.',
            'minimum_members' => 1,
            'max_capabilities' => 24,
        ];
    }
}
