<?php

declare(strict_types=1);

use App\Http\Controllers\FederationContinuationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/federation/continue/gamadrive', FederationContinuationController::class)
        ->middleware(['core.member', 'throttle:10,1'])
        ->name('federation.continue.gamadrive');
});
