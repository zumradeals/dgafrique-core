<?php

declare(strict_types=1);

namespace App\Application\Contributions;

use App\Models\PortalSetting;

/**
 * CAP-061 — art. 6.5 : « Les règles de répartition sont configurables, versionnées et auditées.
 * Elles ne doivent pas être enfouies dans le code. » Montants administrables (500/2 500 XOF
 * canoniques par défaut), jamais réutilisés depuis payments.membership.* (CAP-007B, valeur
 * distincte malgré la coïncidence numérique du montant individuel).
 */
final class ContributionConfiguration
{
    public const KEY = 'contributions.configuration';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'individual_enabled' => false,
            'individual_amount' => 500,
            'collective_enabled' => false,
            'collective_amount' => 2500,
            'currency' => 'XOF',
        ];
    }
}
