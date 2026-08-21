<?php

declare(strict_types=1);

use App\Http\Controllers\Administration\ModerationController;
use App\Http\Controllers\ModerationDecisionController;
use App\Http\Controllers\ModerationReportController;
use App\Http\Controllers\ZumraGroupModerationController;
use Illuminate\Support\Facades\Route;

/**
 * MODERATION-COMP-001 — routes propres à un chantier non-CAP (docs/roadmap/ROADMAP-METIER-CANONIQUE.md
 * — ROADMAP-003), même patron que routes/cap063.php pour un objet nouveau.
 */
Route::middleware('web')->group(function (): void {
    Route::post('/moderation/commentaires/{comment}/signalement', [ModerationReportController::class, 'storeContextComment'])
        ->whereUuid('comment')->middleware(['core.member', 'throttle:moderation-report'])->name('moderation.reports.context-comment');

    Route::post('/moderation/messages/{entry}/signalement', [ModerationReportController::class, 'storeMessageEntry'])
        ->whereUuid('entry')->middleware(['core.member', 'throttle:moderation-report'])->name('moderation.reports.message-entry');

    Route::post('/moderation/adhesions/{membership}/signalement', [ModerationReportController::class, 'storeZumraMembership'])
        ->whereUuid('membership')->middleware(['core.member', 'throttle:moderation-report'])->name('moderation.reports.zumra-membership');

    Route::get('/moderation/mes-signalements', [ModerationReportController::class, 'mine'])
        ->middleware('core.member')->name('moderation.reports.mine');

    Route::post('/moderation/signalements/{report}/escalade', [ModerationReportController::class, 'escalate'])
        ->whereUuid('report')->middleware(['core.member', 'throttle:moderation-report'])->name('moderation.reports.escalate');

    Route::get('/moderation/mes-decisions', [ModerationDecisionController::class, 'mine'])
        ->middleware('core.member')->name('moderation.decisions.mine');

    Route::post('/moderation/decisions/{decision}/recours', [ModerationDecisionController::class, 'requestAppeal'])
        ->whereUuid('decision')->middleware(['core.member', 'throttle:moderation-decision'])->name('moderation.decisions.appeal');

    Route::get('/zumra/groupes/{group}/moderation', [ZumraGroupModerationController::class, 'index'])
        ->whereUuid('group')->middleware('core.member')->name('zumra.groups.moderation.index');

    Route::post('/zumra/groupes/{group}/moderation/{report}/decision', [ZumraGroupModerationController::class, 'decide'])
        ->whereUuid('group')->whereUuid('report')->middleware(['core.member', 'throttle:moderation-decision'])->name('zumra.groups.moderation.decide');

    Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('administration.moderation.index');
        Route::post('/moderation/{report}/decision', [ModerationController::class, 'decide'])
            ->whereUuid('report')->middleware('throttle:moderation-decision')->name('administration.moderation.decide');
        Route::post('/moderation/decisions/{decision}/recours', [ModerationController::class, 'decideAppeal'])
            ->whereUuid('decision')->middleware('throttle:moderation-decision')->name('administration.moderation.appeal-decide');
    });
});
