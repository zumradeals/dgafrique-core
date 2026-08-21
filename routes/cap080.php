<?php

declare(strict_types=1);

use App\Http\Controllers\ImpactMetricsController;
use Illuminate\Support\Facades\Route;

/** CAP-080 — Ce que devrait mesurer DG Afrique. Lecture seule, aucune mutation. */
Route::middleware('web')->group(function (): void {
    Route::get('/mesure', [ImpactMetricsController::class, 'portal'])
        ->middleware(['core.member', 'throttle:impact-metrics-read'])->name('impact-metrics.portal');
    Route::get('/zumra/groupes/{group}/mesure', [ImpactMetricsController::class, 'forZumraGroup'])
        ->whereUuid('group')->middleware(['core.member', 'throttle:impact-metrics-read'])->name('impact-metrics.zumra');
    Route::get('/organisations/{organization}/mesure', [ImpactMetricsController::class, 'forOrganization'])
        ->whereUuid('organization')->middleware(['core.member', 'throttle:impact-metrics-read'])->name('impact-metrics.organization');
});
