<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PortalSetting;

final class ZumraProgramConfiguration
{
    public const KEY = 'zumra.program.presentation';

    public function get(): array
    {
        $configuration = array_replace($this->defaults(), PortalSetting::query()->find(self::KEY)?->value ?? []);
        // Ce commutateur est opérationnel et secret : l’administration éditoriale
        // ne peut ni ouvrir ni fermer un prestataire financier.
        $configuration['payment_enabled'] = (bool) config('payments.membership.enabled');

        return $configuration;
    }

    public function defaults(): array
    {
        return [
            'title' => 'Rejoindre le Programme ZUMRA',
            'introduction' => 'Un engagement unique pour apprendre, transmettre et construire avec d’autres.',
            'commitment_notice' => 'L’adhésion est acquise une seule fois. Les contributions mensuelles restent entièrement facultatives.',
            'pending_payment_title' => 'Votre dossier est prêt',
            'pending_payment_help' => 'Finalisez le paiement unique pour activer votre adhésion.',
            'accept_label' => 'J’ai lu et j’accepte cette charte.',
            'submit_label' => 'Préparer mon adhésion',
            'payment_enabled' => (bool) config('payments.membership.enabled'),
            'payment_unavailable_notice' => 'Le paiement est temporairement indisponible. Aucun débit ne sera effectué.',
        ];
    }
}
