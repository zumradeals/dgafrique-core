<?php

declare(strict_types=1);

use App\Http\Controllers\FederationContinuationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/federation/continue/gamadrive', FederationContinuationController::class)
        ->middleware(['core.member', 'throttle:federation-continue'])
        ->name('federation.continue.gamadrive');
});
