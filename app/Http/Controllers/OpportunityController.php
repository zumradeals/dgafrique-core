<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Opportunities\OpportunityEngine;
use App\Domain\Identity\CoreIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CAP-064 — exposition applicative minimale du moteur d'opportunités : une projection
 * en lecture seule, jamais une mutation. Aucune UI dédiée n'est construite ici ; ce
 * point d'entrée existe pour que le Cerveau du Projet ou un futur écran puisse
 * consommer la même projection sans dupliquer le moteur.
 */
final class OpportunityController
{
    public function index(Request $request, OpportunityEngine $engine): JsonResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return response()->json([
            'opportunities' => $engine->forIdentity($identity->reference),
        ]);
    }
}
