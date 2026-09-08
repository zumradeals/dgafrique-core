<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

final class LandingController
{
    /**
     * Compatibilité de l’ancienne URL publique `/decouvrir`.
     *
     * La page d’accueil est désormais l’unique entrée publique de GAMAD. Le regroupement
     * « Découvrir » reste une fonction du réseau pour les membres, pas une seconde page
     * marketing avant la création du compte.
     */
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('gateway', status: 301);
    }
}
