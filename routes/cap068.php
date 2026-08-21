<?php

declare(strict_types=1);

use App\Http\Controllers\CommunityEventController;
use Illuminate\Support\Facades\Route;

/** CAP-068 — Événement. Routes propres à un objet nouveau (motif routes/cap063.php). */
Route::middleware('web')->group(function (): void {
    Route::get('/zumra/groupes/{group}/evenements', [CommunityEventController::class, 'indexForZumraGroup'])
        ->whereUuid('group')->middleware('core.member')->name('community-events.zumra.index');
    Route::post('/zumra/groupes/{group}/evenements', [CommunityEventController::class, 'storeForZumraGroup'])
        ->whereUuid('group')->middleware(['core.member', 'throttle:community-event-write'])->name('community-events.zumra.store');

    Route::get('/organisations/{organization}/evenements', [CommunityEventController::class, 'indexForOrganization'])
        ->whereUuid('organization')->middleware('core.member')->name('community-events.organization.index');
    Route::post('/organisations/{organization}/evenements', [CommunityEventController::class, 'storeForOrganization'])
        ->whereUuid('organization')->middleware(['core.member', 'throttle:community-event-write'])->name('community-events.organization.store');

    Route::get('/evenements/{event}', [CommunityEventController::class, 'show'])
        ->whereUuid('event')->middleware('core.member')->name('community-events.show');
    Route::patch('/evenements/{event}', [CommunityEventController::class, 'update'])
        ->whereUuid('event')->middleware(['core.member', 'throttle:community-event-write'])->name('community-events.update');
    Route::post('/evenements/{event}/annuler', [CommunityEventController::class, 'cancel'])
        ->whereUuid('event')->middleware(['core.member', 'throttle:community-event-write'])->name('community-events.cancel');
    Route::post('/evenements/{event}/tenue', [CommunityEventController::class, 'markCompleted'])
        ->whereUuid('event')->middleware(['core.member', 'throttle:community-event-write'])->name('community-events.complete');
    Route::post('/evenements/{event}/inscription', [CommunityEventController::class, 'register'])
        ->whereUuid('event')->middleware(['core.member', 'throttle:community-event-participation'])->name('community-events.register');
    Route::post('/evenements/{event}/desinscription', [CommunityEventController::class, 'unregister'])
        ->whereUuid('event')->middleware(['core.member', 'throttle:community-event-participation'])->name('community-events.unregister');
    Route::get('/evenements/{event}/participants', [CommunityEventController::class, 'participants'])
        ->whereUuid('event')->middleware('core.member')->name('community-events.participants');
});
