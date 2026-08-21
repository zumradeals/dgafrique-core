<?php

declare(strict_types=1);

namespace App\Application\Moderation;

use App\Models\PortalSetting;

/**
 * MODERATION-COMP-001 (art. 23.2 : « durées d'avertissement et de réhabilitation »). Seuls les deux
 * paramètres réellement consommés par ModerationDecisionService sont créés ici — aucun paramètre
 * mort (§28 du mandat). Aucune interface d'administration dédiée n'existe encore en V1 (limitation
 * documentée) : la valeur reste modifiable via PortalSetting comme tout autre paramètre administrable
 * du dépôt.
 */
final class ModerationConfiguration
{
    public const KEY = 'moderation.configuration';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'warning_default_duration_days' => 90,
            'suspension_default_duration_days' => 30,
        ];
    }
}
