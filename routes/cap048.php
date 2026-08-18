<?php

declare(strict_types=1);

use App\Http\Controllers\Administration\SatelliteController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
        Route::get('/satellites', [SatelliteController::class, 'index'])
            ->name('administration.satellites.index');
        Route::post('/satellites', [SatelliteController::class, 'store'])
            ->middleware('throttle:satellites-admin')->name('administration.satellites.store');
        Route::put('/satellites/{satellite}', [SatelliteController::class, 'update'])
            ->whereUuid('satellite')->middleware('throttle:satellites-admin')->name('administration.satellites.update');
        Route::put('/satellites/{satellite}/bascule', [SatelliteController::class, 'toggle'])
            ->whereUuid('satellite')->middleware('throttle:satellites-admin')->name('administration.satellites.toggle');
    });
});
