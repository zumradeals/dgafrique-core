<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Projects\ProjectMatchingConfiguration;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Models\ProjectMatchDecision;
use App\Models\RecommendationDecision;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — surface unique « Moteurs ». Regroupe la LECTURE des deux configurations déjà
 * existantes (les formulaires de modification restent sur leurs écrans propres,
 * administration.project-matching.edit / administration.recommendations.edit — jamais reconstruits
 * ici). Seule métrique montrée : le volume de masquages (HIDDEN), la seule donnée réellement
 * tracée aujourd'hui — jamais un taux de succès fabriqué, jamais un score de personne.
 */
final class AdminEnginesController
{
    public function index(): View
    {
        return view('administration.engines.index', [
            'matchingConfiguration' => (new ProjectMatchingConfiguration)->get(),
            'recommendationConfiguration' => (new RecommendationConfiguration)->get(),
            'projectMatchHidden' => ProjectMatchDecision::query()->where('decision', ProjectMatchDecision::HIDDEN)->count(),
            'recommendationHidden' => RecommendationDecision::query()->where('decision', RecommendationDecision::DECISION_HIDDEN)->count(),
        ]);
    }
}
