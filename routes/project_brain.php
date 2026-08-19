<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectBrainController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'core.member'])->group(function (): void {
    Route::get('/projets/{project}/cerveau', [ProjectBrainController::class, 'show'])
        ->whereUuid('project')->name('projects.brain.show');
    Route::post('/projets/{project}/cerveau/besoins/preparer', [ProjectBrainController::class, 'prepareNeed'])
        ->whereUuid('project')->middleware('throttle:need-write')->name('projects.brain.needs.prepare');
    Route::post('/projets/{project}/cerveau/besoins/{draft}/confirmer', [ProjectBrainController::class, 'confirmNeed'])
        ->whereUuid('project')->whereUuid('draft')->middleware('throttle:need-write')->name('projects.brain.needs.confirm');
    Route::post('/projets/{project}/cerveau/brouillons/{draft}/abandonner', [ProjectBrainController::class, 'cancelDraft'])
        ->whereUuid('project')->whereUuid('draft')->middleware('throttle:need-write')->name('projects.brain.drafts.cancel');
});
