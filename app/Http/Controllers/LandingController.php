<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LandingController
{
    public function __invoke(): View
    {
        // Fixtures d'exemple uniquement (voir resources/design-reference/README.md) :
        // jamais seedées, jamais affichées comme une donnée métier réelle.
        $demo = json_decode(
            file_get_contents(resource_path('design-reference/demo-content.json')),
            true,
        );
        $portalDemo = json_decode(
            file_get_contents(resource_path('design-reference/landing-portal-demo.json')),
            true,
        );

        return view('foundation', [
            'exampleNeed' => $demo['besoins'][0],
            'exampleProject' => $demo['projets'][0],
            'exampleStats' => $portalDemo['statistiques'],
            'exampleMoments' => $portalDemo['moments'],
        ]);
    }
}
