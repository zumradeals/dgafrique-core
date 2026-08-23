<?php

declare(strict_types=1);

use App\Http\Controllers\Administration\ProjectAutonomyPathwayController;
use App\Http\Controllers\ProjectAutonomyController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/projets/{project}/autonomie', [ProjectAutonomyController::class, 'show'])
        ->whereUuid('project')
        ->middleware('core.member')
        ->name('projects.autonomy.show');

    Route::post('/projets/{project}/autonomie', [ProjectAutonomyController::class, 'open'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-autonomy'])
        ->name('projects.autonomy.open');

    Route::put('/projets/{project}/autonomie/fin', [ProjectAutonomyController::class, 'close'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-autonomy'])
        ->name('projects.autonomy.close');

    Route::get('/administration/parcours-autonomie', [ProjectAutonomyPathwayController::class, 'index'])
        ->middleware(['core.member', 'portal.admin'])
        ->name('administration.project-autonomy-pathway.index');
});
