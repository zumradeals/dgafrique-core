<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectFundingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/projets/{project}/financement', [ProjectFundingController::class, 'show'])
        ->whereUuid('project')
        ->middleware('core.member')
        ->name('projects.funding.show');

    Route::post('/projets/{project}/financement', [ProjectFundingController::class, 'store'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-funding-write'])
        ->name('projects.funding.store');

    Route::patch('/projets/{project}/financement', [ProjectFundingController::class, 'update'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-funding-write'])
        ->name('projects.funding.update');

    Route::post('/projets/{project}/financement/cloturer', [ProjectFundingController::class, 'close'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-funding-write'])
        ->name('projects.funding.close');

    Route::post('/projets/{project}/financement/annuler', [ProjectFundingController::class, 'cancel'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-funding-write'])
        ->name('projects.funding.cancel');
});
