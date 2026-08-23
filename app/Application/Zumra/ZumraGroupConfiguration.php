<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PortalSetting;

final class ZumraGroupConfiguration
{
    public const KEY = 'zumra.groups.configuration';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'directory_title' => 'Des équipes pour transformer les capacités en action.',
            'directory_intro' => 'Découvrez des ZUMRA réelles, organisées autour d’un domaine, d’un objectif et d’une charte.',
            'creation_title' => 'Faire naître une ZUMRA',
            'established_member_threshold' => 50,
            'max_simultaneous_founder_roles' => 3,
            'auto_validation_enabled' => false,
        ];
    }
}
