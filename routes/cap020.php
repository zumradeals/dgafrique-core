<?php

declare(strict_types=1);

use App\Http\Controllers\MessagingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/messages', [MessagingController::class, 'index'])
        ->middleware(['core.member', 'throttle:messaging-read'])
        ->name('messages.index');

    Route::post('/messages/personnes/{reference}', [MessagingController::class, 'startDirect'])
        ->whereUuid('reference')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.direct');

    Route::post('/messages/besoins/{need}', [MessagingController::class, 'openNeed'])
        ->whereUuid('need')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.need');

    Route::post('/messages/zumra/{group}', [MessagingController::class, 'openZumra'])
        ->whereUuid('group')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.zumra');

    Route::post('/messages/zumra/{group}/invitation', [MessagingController::class, 'openInvitation'])
        ->whereUuid('group')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.invitation');

    Route::post('/messages/projets/{project}', [MessagingController::class, 'openProject'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.project');

    Route::post('/messages/missions/{mission}', [MessagingController::class, 'openMission'])
        ->whereUuid('mission')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.mission');

    Route::post('/messages/dg-afrique', [MessagingController::class, 'openSupport'])
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.support');

    Route::post('/messages/{conversation}/participants', [MessagingController::class, 'addProjectParticipant'])
        ->whereUuid('conversation')
        ->middleware(['core.member', 'throttle:messaging-open'])
        ->name('messages.participants.add');

    Route::get('/messages/{conversation}', [MessagingController::class, 'show'])
        ->whereUuid('conversation')
        ->middleware(['core.member', 'throttle:messaging-read'])
        ->name('messages.show');

    Route::post('/messages/{conversation}', [MessagingController::class, 'send'])
        ->whereUuid('conversation')
        ->middleware(['core.member', 'throttle:messaging-write'])
        ->name('messages.send');
});
