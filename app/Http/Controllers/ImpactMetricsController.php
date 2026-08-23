<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Ecosystem\ImpactMetricsService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Organization;
use App\Models\ZumraGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CAP-080 — surface de lecture pure, JSON uniquement. Aucune mutation possible depuis ce
 * contrôleur (même patron que ModerationReportController pour la lecture).
 */
final class ImpactMetricsController
{
    public function portal(Request $request, ImpactMetricsService $metrics): JsonResponse
    {
        return response()->json(['metrics' => $metrics->portal()]);
    }

    public function forZumraGroup(Request $request, ZumraGroup $group, ImpactMetricsService $metrics): JsonResponse
    {
        return response()->json(['metrics' => $metrics->forZumraGroup($group, $this->actor($request))]);
    }

    public function forOrganization(Request $request, Organization $organization, ImpactMetricsService $metrics): JsonResponse
    {
        return response()->json(['metrics' => $metrics->forOrganization($organization, $this->actor($request))]);
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }
}
