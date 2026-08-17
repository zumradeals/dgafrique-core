<?php

declare(strict_types=1);

use App\Http\Controllers\ContextCommentController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/commentaires/besoins/{need}', [ContextCommentController::class, 'need'])
        ->whereUuid('need')
        ->middleware(['core.member', 'throttle:comments-read'])
        ->name('comments.need');

    Route::post('/commentaires/besoins/{need}', [ContextCommentController::class, 'storeNeed'])
        ->whereUuid('need')
        ->middleware(['core.member', 'throttle:comments-write'])
        ->name('comments.need.store');

    Route::get('/commentaires/projets/{project}', [ContextCommentController::class, 'project'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:comments-read'])
        ->name('comments.project');

    Route::post('/commentaires/projets/{project}', [ContextCommentController::class, 'storeProject'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:comments-write'])
        ->name('comments.project.store');

    Route::get('/commentaires/activite/zumra/{group}', [ContextCommentController::class, 'zumraActivity'])
        ->whereUuid('group')
        ->middleware(['core.member', 'throttle:comments-read'])
        ->name('comments.zumra-activity');

    Route::post('/commentaires/activite/zumra/{group}', [ContextCommentController::class, 'storeZumraActivity'])
        ->whereUuid('group')
        ->middleware(['core.member', 'throttle:comments-write'])
        ->name('comments.zumra-activity.store');

    Route::get('/commentaires/missions/{mission}', [ContextCommentController::class, 'mission'])
        ->whereUuid('mission')
        ->middleware(['core.member', 'throttle:comments-read'])
        ->name('comments.mission');

    Route::post('/commentaires/missions/{mission}', [ContextCommentController::class, 'storeMission'])
        ->whereUuid('mission')
        ->middleware(['core.member', 'throttle:comments-write'])
        ->name('comments.mission.store');
});
