<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::put('/projets/{project}/maturite', [ProjectController::class, 'maturity'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-maturity'])
        ->name('projects.maturity.update');
});
