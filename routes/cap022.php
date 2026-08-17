<?php

declare(strict_types=1);

use App\Http\Controllers\ContextShareController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/partages', [ContextShareController::class, 'index'])
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.index');

    Route::get('/partages/zumra/{group}', [ContextShareController::class, 'group'])
        ->whereUuid('group')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.group');

    Route::get('/partages/besoins/{need}', [ContextShareController::class, 'need'])
        ->whereUuid('need')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.need');

    Route::post('/partages/besoins/{need}', [ContextShareController::class, 'storeNeed'])
        ->whereUuid('need')
        ->middleware(['core.member', 'throttle:shares-write'])
        ->name('shares.need.store');

    Route::get('/partages/projets/{project}', [ContextShareController::class, 'project'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.project');

    Route::post('/partages/projets/{project}', [ContextShareController::class, 'storeProject'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:shares-write'])
        ->name('shares.project.store');

    Route::get('/partages/missions/{mission}', [ContextShareController::class, 'mission'])
        ->whereUuid('mission')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.mission');

    Route::post('/partages/missions/{mission}', [ContextShareController::class, 'storeMission'])
        ->whereUuid('mission')
        ->middleware(['core.member', 'throttle:shares-write'])
        ->name('shares.mission.store');

    Route::get('/partages/transmissions/{transmission}', [ContextShareController::class, 'transmission'])
        ->whereUuid('transmission')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.transmission');

    Route::post('/partages/transmissions/{transmission}', [ContextShareController::class, 'storeTransmission'])
        ->whereUuid('transmission')
        ->middleware(['core.member', 'throttle:shares-write'])
        ->name('shares.transmission.store');

    Route::get('/partages/preuves/{proof}', [ContextShareController::class, 'proof'])
        ->whereUuid('proof')
        ->middleware(['core.member', 'throttle:shares-read'])
        ->name('shares.proof');

    Route::post('/partages/preuves/{proof}', [ContextShareController::class, 'storeProof'])
        ->whereUuid('proof')
        ->middleware(['core.member', 'throttle:shares-write'])
        ->name('shares.proof.store');
});
