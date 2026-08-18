<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Models\PortalSetting;

/**
 * Paramètres administrables sans déploiement (doctrine L.675), même mécanisme que
 * RecommendationConfiguration. Aucun écran d'administration dédié en v1 (fiche §14/§16.5) :
 * la valeur reste modifiable directement via `dg_portal_settings`.
 */
final class NotificationConfiguration
{
    public const KEY = 'capabilities.notifications';

    public function get(): array
    {
        $defaults = $this->defaults();
        $stored = PortalSetting::query()->find(self::KEY)?->value;

        return is_array($stored) ? array_replace($defaults, array_intersect_key($stored, $defaults)) : $defaults;
    }

    public function defaults(): array
    {
        return [
            // Fenêtre de rétroaction pour les événements FYI (jours).
            'lookback_days' => 30,
            // Limites d'affichage par section, jamais un mur de statistiques.
            'max_actionable' => 50,
            'max_recent' => 30,
            // Bassin maximal d'événements bruts scannés par source avant filtrage.
            'scan_limit' => 200,
            // Types FYI activés — un interrupteur par domaine, jamais un réglage par événement isolé.
            'mission_fyi_enabled' => true,
            'transmission_fyi_enabled' => true,
            'proof_fyi_enabled' => true,
        ];
    }
}
