<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityFeedController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/activite', [ActivityFeedController::class, 'index'])
        ->middleware(['core.member', 'throttle:activity-feed'])
        ->name('activity.index');
});
