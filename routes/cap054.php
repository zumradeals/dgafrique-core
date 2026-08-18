<?php

declare(strict_types=1);

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware(['core.member', 'throttle:notifications-read'])
        ->name('notifications.index');
});
