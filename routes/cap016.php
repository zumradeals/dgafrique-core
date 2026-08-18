<?php

declare(strict_types=1);

use App\Http\Controllers\Administration\ProjectAccompanimentController as AdministrationProjectAccompanimentController;
use App\Http\Controllers\ProjectAccompanimentController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/projets/{project}/accompagnement', [ProjectAccompanimentController::class, 'show'])
        ->whereUuid('project')->middleware('core.member')->name('projects.accompaniment.show');
    Route::post('/projets/{project}/accompagnement', [ProjectAccompanimentController::class, 'activate'])
        ->whereUuid('project')->middleware(['core.member', 'throttle:project-accompaniment'])->name('projects.accompaniment.activate');
    Route::put('/projets/{project}/accompagnement/fin', [ProjectAccompanimentController::class, 'end'])
        ->whereUuid('project')->middleware(['core.member', 'throttle:project-accompaniment'])->name('projects.accompaniment.end');
    Route::post('/projets/{project}/accompagnement/demandes', [ProjectAccompanimentController::class, 'storeRequest'])
        ->whereUuid('project')->middleware(['core.member', 'throttle:project-accompaniment'])->name('projects.accompaniment.requests.store');

    Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
        Route::get('/accompagnement-projets', [AdministrationProjectAccompanimentController::class, 'edit'])
            ->name('administration.project-accompaniment.edit');
        Route::put('/accompagnement-projets', [AdministrationProjectAccompanimentController::class, 'update'])
            ->middleware('throttle:project-accompaniment-configuration')->name('administration.project-accompaniment.update');
        Route::post('/accompagnement-projets/{project}/interventions', [AdministrationProjectAccompanimentController::class, 'storeAction'])
            ->whereUuid('project')->middleware('throttle:project-accompaniment-admin')->name('administration.project-accompaniment.actions.store');
        Route::put('/accompagnement-projets/demandes/{accompanimentRequest}/prise-en-charge', [AdministrationProjectAccompanimentController::class, 'acknowledgeRequest'])
            ->whereUuid('accompanimentRequest')->middleware('throttle:project-accompaniment-admin')->name('administration.project-accompaniment.requests.acknowledge');
        Route::put('/accompagnement-projets/demandes/{accompanimentRequest}/clore', [AdministrationProjectAccompanimentController::class, 'closeRequest'])
            ->whereUuid('accompanimentRequest')->middleware('throttle:project-accompaniment-admin')->name('administration.project-accompaniment.requests.close');
    });
});
