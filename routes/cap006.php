<?php

declare(strict_types=1);

use App\Http\Controllers\TransmissionContributionController;
use App\Http\Controllers\TransmissionController;
use App\Http\Controllers\TransmissionMatchingController;
use App\Http\Controllers\TransmissionMilestoneController;
use App\Http\Controllers\TransmissionParticipationController;
use App\Http\Controllers\TransmissionWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/transmissions', [TransmissionController::class, 'index'])
        ->middleware(['core.member', 'throttle:transmission-read'])->name('transmissions.index');

    Route::get('/transmissions/creer', [TransmissionController::class, 'create'])
        ->middleware(['core.member', 'throttle:transmission-read'])->name('transmissions.create');
    Route::post('/transmissions', [TransmissionController::class, 'store'])
        ->middleware(['core.member', 'throttle:transmission-write'])->name('transmissions.store');

    Route::get('/transmissions/{transmission}', [TransmissionController::class, 'show'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-read'])->name('transmissions.show');

    // Machine d'états.
    Route::post('/transmissions/{transmission}/demarrer', [TransmissionWorkflowController::class, 'start'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.start');
    Route::post('/transmissions/{transmission}/declarer-termine', [TransmissionWorkflowController::class, 'declareDone'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.declare-done');
    Route::post('/transmissions/{transmission}/confirmer-cloture', [TransmissionWorkflowController::class, 'confirmCompletion'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.confirm-completion');
    Route::post('/transmissions/{transmission}/valider-contexte', [TransmissionWorkflowController::class, 'validateByContext'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.validate-by-context');
    Route::post('/transmissions/{transmission}/officialiser-contexte', [TransmissionWorkflowController::class, 'officializeContext'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.officialize-context');
    Route::post('/transmissions/{transmission}/arreter', [TransmissionWorkflowController::class, 'end'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.end');
    Route::post('/transmissions/{transmission}/annuler', [TransmissionWorkflowController::class, 'cancel'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-transition'])->name('transmissions.cancel');

    // Participation.
    Route::post('/transmissions/{transmission}/me-proposer', [TransmissionParticipationController::class, 'offer'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.offer');
    Route::post('/transmissions/{transmission}/inviter', [TransmissionParticipationController::class, 'invite'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.invite');
    Route::post('/transmissions/{transmission}/propositions/{participant}/accepter', [TransmissionParticipationController::class, 'acceptOffer'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.offer.accept');
    Route::post('/transmissions/{transmission}/propositions/{participant}/refuser', [TransmissionParticipationController::class, 'declineOffer'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.offer.decline');
    Route::post('/transmissions/{transmission}/propositions/{participant}/retirer', [TransmissionParticipationController::class, 'withdraw'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.offer.withdraw');
    Route::post('/transmissions/{transmission}/invitations/{participant}/accepter', [TransmissionParticipationController::class, 'acceptInvitation'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.invitation.accept');
    Route::post('/transmissions/{transmission}/invitations/{participant}/refuser', [TransmissionParticipationController::class, 'declineInvitation'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.invitation.decline');
    Route::post('/transmissions/{transmission}/participants/{participant}/quitter', [TransmissionParticipationController::class, 'leave'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.leave');
    Route::post('/transmissions/{transmission}/participants/{participant}/retirer', [TransmissionParticipationController::class, 'remove'])
        ->whereUuid('transmission')->whereUuid('participant')->middleware(['core.member', 'throttle:transmission-participation'])->name('transmissions.participants.remove');

    // Jalons.
    Route::post('/transmissions/{transmission}/jalons', [TransmissionMilestoneController::class, 'store'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-milestone'])->name('transmissions.milestones.store');
    Route::put('/transmissions/{transmission}/jalons/{milestone}', [TransmissionMilestoneController::class, 'update'])
        ->whereUuid('transmission')->whereUuid('milestone')->middleware(['core.member', 'throttle:transmission-milestone'])->name('transmissions.milestones.update');

    // Contributions.
    Route::post('/transmissions/{transmission}/contributions', [TransmissionContributionController::class, 'store'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-contribution'])->name('transmissions.contributions.store');

    // Correspondances (matching).
    Route::get('/transmissions/{transmission}/correspondances', [TransmissionMatchingController::class, 'index'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-matching'])->name('transmissions.matching');
    Route::post('/transmissions/{transmission}/correspondances/{discoveryReference}/masquer', [TransmissionMatchingController::class, 'hide'])
        ->whereUuid('transmission')->middleware(['core.member', 'throttle:transmission-matching'])->name('transmissions.matching.hide');
});
