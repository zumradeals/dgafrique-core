<?php

declare(strict_types=1);

use App\Http\Controllers\ProofController;
use App\Http\Controllers\ProofWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/preuves', [ProofController::class, 'index'])
        ->middleware(['core.member', 'throttle:proof-read'])->name('proofs.index');

    Route::get('/preuves/creer', [ProofController::class, 'create'])
        ->middleware(['core.member', 'throttle:proof-read'])->name('proofs.create');
    Route::post('/preuves', [ProofController::class, 'store'])
        ->middleware(['core.member', 'throttle:proof-write'])->name('proofs.store');

    Route::get('/preuves/memoire', [ProofController::class, 'memory'])
        ->middleware(['core.member', 'throttle:proof-read'])->name('proofs.memory.self');
    Route::get('/preuves/memoire/{discoveryReference}', [ProofController::class, 'memory'])
        ->middleware(['core.member', 'throttle:proof-read'])->name('proofs.memory');

    Route::get('/preuves/{proof}', [ProofController::class, 'show'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-read'])->name('proofs.show');

    Route::post('/preuves/{proof}/inviter-temoin', [ProofWorkflowController::class, 'inviteWitness'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-witness'])->name('proofs.witnesses.invite');
    Route::post('/preuves/{proof}/temoins/{witness}/confirmer', [ProofWorkflowController::class, 'confirmWitness'])
        ->whereUuid('proof')->whereUuid('witness')->middleware(['core.member', 'throttle:proof-witness'])->name('proofs.witnesses.confirm');
    Route::post('/preuves/{proof}/temoins/{witness}/decliner', [ProofWorkflowController::class, 'declineWitness'])
        ->whereUuid('proof')->whereUuid('witness')->middleware(['core.member', 'throttle:proof-witness'])->name('proofs.witnesses.decline');

    Route::post('/preuves/{proof}/reconnaitre', [ProofWorkflowController::class, 'acknowledge'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-transition'])->name('proofs.acknowledge');
    Route::post('/preuves/{proof}/contester', [ProofWorkflowController::class, 'dispute'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-transition'])->name('proofs.dispute');
    Route::post('/preuves/{proof}/archiver', [ProofWorkflowController::class, 'archive'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-transition'])->name('proofs.archive');
    Route::post('/preuves/{proof}/restaurer', [ProofWorkflowController::class, 'restore'])
        ->whereUuid('proof')->middleware(['core.member', 'throttle:proof-transition'])->name('proofs.restore');
});
