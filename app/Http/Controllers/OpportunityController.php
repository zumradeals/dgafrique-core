<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Opportunities\OpportunityEngine;
use App\Domain\Identity\CoreIdentity;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CAP-064 — surface humaine dédiée (UIUX-002 décision #5). Réutilise `OpportunityEngine`
 * strictement tel quel : aucun recalcul, aucun second moteur. La vue n'affiche que
 * `title`/`reasons`/l'action vers la Mission — jamais `priority` (entier interne) ni aucun
 * identifiant technique.
 */
final class OpportunityController
{
    public function index(Request $request, OpportunityEngine $engine): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return view('opportunities.index', [
            'identity' => $identity,
            'opportunities' => $engine->forIdentity($identity->reference),
        ]);
    }
}
