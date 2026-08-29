<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\OperationalReadinessService;
use Illuminate\Http\JsonResponse;

final class ReadinessController
{
    public function __invoke(OperationalReadinessService $readiness): JsonResponse
    {
        $result = $readiness->inspect();

        return response()->json([
            'status' => $result['ready'] ? 'ready' : 'not_ready',
            'checks' => $result['checks'],
            'checked_at' => $result['checked_at'],
        ], $result['ready'] ? 200 : 503, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
