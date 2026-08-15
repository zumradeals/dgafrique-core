<?php

declare(strict_types=1);

namespace App\Application\Needs;

use App\Models\PortalSetting;

final class NeedConfiguration
{
    public const KEY = 'needs.configuration';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'directory_title' => 'Ce qui manque peut devenir un point de rencontre.',
            'directory_intro' => 'Un besoin décrit un manque réel dans son contexte. Il ne constitue ni une collecte, ni une promesse d’aide.',
            'creation_title' => 'Exprimer un besoin clairement',
            'categories' => [
                'SKILL' => 'Compétence recherchée',
                'PARTNER' => 'Partenaire recherché',
                'TRAINING' => 'Formation souhaitée',
                'RESOURCE' => 'Ressource nécessaire',
                'TECHNICAL' => 'Appui technique',
                'LOGISTICS' => 'Appui logistique',
            ],
            'max_open_personal_needs' => 12,
            'max_open_group_needs' => 30,
            'directory_page_size' => 18,
        ];
    }
}
