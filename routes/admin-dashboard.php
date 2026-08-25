<?php

declare(strict_types=1);

use App\Http\Controllers\Administration\AdminCommunityController;
use App\Http\Controllers\Administration\AdminConfigurationController;
use App\Http\Controllers\Administration\AdminDashboardController;
use App\Http\Controllers\Administration\AdminEnginesController;
use App\Http\Controllers\Administration\AdminFinanceController;
use App\Http\Controllers\Administration\AdminJournalController;
use App\Http\Controllers\Administration\AdminProjectsController;
use App\Http\Controllers\Administration\ModerationConfigurationController;
use App\Http\Controllers\Administration\NotificationConfigurationController;
use Illuminate\Support\Facades\Route;

/**
 * ADMIN-CONTROL-002 — Tour de contrôle : porte d'entrée /administration et nouvelles sections
 * de supervision. Reste dans le même groupe core.member+portal.admin que routes/web.php
 * (routes/web.php:216) — aucune nouvelle règle d'accès inventée ici.
 */
Route::middleware('web')->group(function (): void {
    Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->middleware('throttle:admin-read')->name('administration.dashboard');

        Route::get('/communaute/personnes', [AdminCommunityController::class, 'people'])->middleware('throttle:admin-read')->name('administration.community.people');
        Route::get('/communaute/zumra', [AdminCommunityController::class, 'zumra'])->middleware('throttle:admin-read')->name('administration.community.zumra');
        Route::get('/communaute/besoins', [AdminCommunityController::class, 'needs'])->middleware('throttle:admin-read')->name('administration.community.needs');
        Route::get('/communaute/organisations', [AdminCommunityController::class, 'organizations'])->middleware('throttle:admin-read')->name('administration.community.organizations');

        Route::get('/projets/liste', [AdminProjectsController::class, 'index'])->middleware('throttle:admin-read')->name('administration.projects.index');
        Route::get('/projets/missions', [AdminProjectsController::class, 'missions'])->middleware('throttle:admin-read')->name('administration.projects.missions');
        Route::get('/projets/financements', [AdminProjectsController::class, 'fundings'])->middleware('throttle:admin-read')->name('administration.projects.fundings');
        Route::get('/projets/preuves', [AdminProjectsController::class, 'proofs'])->middleware('throttle:admin-read')->name('administration.projects.proofs');

        Route::get('/finance', [AdminFinanceController::class, 'index'])->middleware('throttle:admin-read')->name('administration.finance.index');
        Route::get('/finance/acquisitions', [AdminFinanceController::class, 'acquisitions'])->middleware('throttle:admin-read')->name('administration.finance.acquisitions');
        Route::get('/finance/contributions', [AdminFinanceController::class, 'contributions'])->middleware('throttle:admin-read')->name('administration.finance.contributions');

        Route::get('/moteurs', [AdminEnginesController::class, 'index'])->middleware('throttle:admin-read')->name('administration.engines.index');

        Route::get('/configuration', [AdminConfigurationController::class, 'index'])->middleware('throttle:admin-read')->name('administration.configuration.index');
        Route::get('/configuration/moderation', [ModerationConfigurationController::class, 'edit'])->middleware('throttle:admin-read')->name('administration.moderation.configuration.edit');
        Route::put('/configuration/moderation', [ModerationConfigurationController::class, 'update'])->middleware('throttle:admin-configuration-write')->name('administration.configuration.moderation.update');
        Route::get('/configuration/notifications', [NotificationConfigurationController::class, 'edit'])->middleware('throttle:admin-read')->name('administration.notifications.edit');
        Route::put('/configuration/notifications', [NotificationConfigurationController::class, 'update'])->middleware('throttle:admin-configuration-write')->name('administration.configuration.notifications.update');

        Route::get('/journal', [AdminJournalController::class, 'index'])->middleware('throttle:admin-read')->name('administration.journal.index');
    });
});
