<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PortalSetting;

final class ZumraProgramConfiguration
{
    public const KEY = 'zumra.program.presentation';

    public function get(): array
    {
        return array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
    }

    public function defaults(): array
    {
        return [
            'title' => 'Rejoindre le Programme ZUMRA',
            'introduction' => 'Un engagement unique pour apprendre, transmettre et construire avec d’autres.',
            'commitment_notice' => 'L’adhésion est acquise une seule fois. Les contributions mensuelles restent entièrement facultatives.',
            'pending_payment_title' => 'Votre dossier est prêt',
            'pending_payment_help' => 'Le paiement sécurisé sera ouvert dès que le moyen de paiement officiel sera raccordé.',
            'accept_label' => 'J’ai lu et j’accepte cette charte.',
            'submit_label' => 'Préparer mon adhésion',
            'payment_enabled' => false,
            'payment_unavailable_notice' => 'Le paiement n’est pas encore disponible. Aucun débit ne sera effectué.',
        ];
    }
}
