<?php

declare(strict_types=1);

use App\Http\Controllers\MissionAssignmentController;
use App\Http\Controllers\MissionChecklistController;
use App\Http\Controllers\MissionContributionController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionDependencyController;
use App\Http\Controllers\MissionMatchingController;
use App\Http\Controllers\MissionNeedLinkController;
use App\Http\Controllers\MissionRecurrenceController;
use App\Http\Controllers\MissionRequirementController;
use App\Http\Controllers\MissionWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/missions', [MissionController::class, 'index'])
        ->middleware(['core.member', 'throttle:mission-read'])->name('missions.index');

    Route::get('/missions/{mission}', [MissionController::class, 'show'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-read'])->name('missions.show');

    // Création contextuelle — un même moteur, jamais de logique dupliquée par contexte.
    Route::get('/projets/{project}/missions/creer', [MissionController::class, 'createForProject'])
        ->whereUuid('project')->middleware(['core.member', 'throttle:mission-read'])->name('projects.missions.create');
    Route::post('/projets/{project}/missions', [MissionController::class, 'storeForProject'])
        ->whereUuid('project')->middleware(['core.member', 'throttle:mission-write'])->name('projects.missions.store');

    Route::get('/zumra/groupes/{group}/missions/creer', [MissionController::class, 'createForZumraGroup'])
        ->whereUuid('group')->middleware(['core.member', 'throttle:mission-read'])->name('zumra.groups.missions.create');
    Route::post('/zumra/groupes/{group}/missions', [MissionController::class, 'storeForZumraGroup'])
        ->whereUuid('group')->middleware(['core.member', 'throttle:mission-write'])->name('zumra.groups.missions.store');

    Route::get('/besoins/{need}/missions/creer', [MissionController::class, 'createForNeed'])
        ->whereUuid('need')->middleware(['core.member', 'throttle:mission-read'])->name('needs.missions.create');
    Route::post('/besoins/{need}/missions', [MissionController::class, 'storeForNeed'])
        ->whereUuid('need')->middleware(['core.member', 'throttle:mission-write'])->name('needs.missions.store');

    // Sous-Missions (profondeur maximale 3, même contexte que le parent).
    Route::get('/missions/{mission}/sous-missions/creer', [MissionController::class, 'createChild'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-read'])->name('missions.children.create');
    Route::post('/missions/{mission}/sous-missions', [MissionController::class, 'storeChild'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-write'])->name('missions.children.store');

    // Machine d'états.
    Route::post('/missions/{mission}/proposer', [MissionWorkflowController::class, 'propose'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.propose');
    Route::post('/missions/{mission}/demander-modifications', [MissionWorkflowController::class, 'requestChanges'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.request-changes');
    Route::post('/missions/{mission}/proposer-a-nouveau', [MissionWorkflowController::class, 'resubmitProposal'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.resubmit');
    Route::post('/missions/{mission}/rejeter', [MissionWorkflowController::class, 'reject'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.reject');
    Route::post('/missions/{mission}/officialiser', [MissionWorkflowController::class, 'officialize'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.officialize');
    Route::post('/missions/{mission}/demarrer', [MissionWorkflowController::class, 'start'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.start');
    Route::post('/missions/{mission}/bloquer', [MissionWorkflowController::class, 'block'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.block');
    Route::post('/missions/{mission}/blocages/{blocker}/resoudre', [MissionWorkflowController::class, 'resolveBlocker'])
        ->whereUuid('mission')->whereUuid('blocker')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.blockers.resolve');
    Route::get('/missions/{mission}/blocages/{blocker}/exprimer-besoin', [MissionNeedLinkController::class, 'create'])
        ->whereUuid('mission')->whereUuid('blocker')->middleware(['core.member', 'throttle:mission-read'])->name('missions.blockers.express-need.create');
    Route::post('/missions/{mission}/blocages/{blocker}/exprimer-besoin', [MissionNeedLinkController::class, 'store'])
        ->whereUuid('mission')->whereUuid('blocker')->middleware(['core.member', 'throttle:mission-write'])->name('missions.blockers.express-need');
    Route::post('/missions/{mission}/soumettre', [MissionWorkflowController::class, 'submit'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.submit');
    Route::post('/missions/{mission}/demander-correction', [MissionWorkflowController::class, 'requestCorrection'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.request-correction');
    Route::post('/missions/{mission}/valider', [MissionWorkflowController::class, 'validateSubmission'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.validate');
    Route::post('/missions/{mission}/annuler', [MissionWorkflowController::class, 'cancel'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.cancel');
    Route::post('/missions/{mission}/reouvrir', [MissionWorkflowController::class, 'reopen'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-transition'])->name('missions.reopen');

    // Participation.
    Route::post('/missions/{mission}/me-proposer', [MissionAssignmentController::class, 'offer'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.offer');
    Route::post('/missions/{mission}/propositions/{assignment}/accepter', [MissionAssignmentController::class, 'acceptOffer'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.offer.accept');
    Route::post('/missions/{mission}/propositions/{assignment}/refuser', [MissionAssignmentController::class, 'declineOffer'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.offer.decline');
    Route::post('/missions/{mission}/propositions/{assignment}/retirer', [MissionAssignmentController::class, 'withdrawOffer'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.offer.withdraw');
    Route::post('/missions/{mission}/inviter', [MissionAssignmentController::class, 'invite'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.invite');
    Route::post('/missions/{mission}/invitations/{assignment}/accepter', [MissionAssignmentController::class, 'acceptInvitation'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.invitation.accept');
    Route::post('/missions/{mission}/invitations/{assignment}/refuser', [MissionAssignmentController::class, 'declineInvitation'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.invitation.decline');
    Route::post('/missions/{mission}/assignments/{assignment}/liberer', [MissionAssignmentController::class, 'release'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.release');
    Route::post('/missions/{mission}/assignments/{assignment}/retirer', [MissionAssignmentController::class, 'remove'])
        ->whereUuid('mission')->whereUuid('assignment')->middleware(['core.member', 'throttle:mission-assignment'])->name('missions.assignments.remove');

    // Checklist.
    Route::post('/missions/{mission}/checklist', [MissionChecklistController::class, 'store'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-checklist'])->name('missions.checklist.store');
    Route::put('/missions/{mission}/checklist/{item}', [MissionChecklistController::class, 'update'])
        ->whereUuid('mission')->whereUuid('item')->middleware(['core.member', 'throttle:mission-checklist'])->name('missions.checklist.update');

    // Dépendances.
    Route::post('/missions/{mission}/dependances', [MissionDependencyController::class, 'store'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-write'])->name('missions.dependencies.store');
    Route::delete('/missions/{mission}/dependances/{dependency}', [MissionDependencyController::class, 'destroy'])
        ->whereUuid('mission')->whereUuid('dependency')->middleware(['core.member', 'throttle:mission-write'])->name('missions.dependencies.destroy');

    // Capacités et ressources requises.
    Route::post('/missions/{mission}/capacites-requises', [MissionRequirementController::class, 'storeCapability'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-write'])->name('missions.requirements.capability.store');
    Route::post('/missions/{mission}/ressources-requises', [MissionRequirementController::class, 'storeResource'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-write'])->name('missions.requirements.resource.store');

    // Contributions.
    Route::post('/missions/{mission}/contributions', [MissionContributionController::class, 'store'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-contribution'])->name('missions.contributions.store');

    // Correspondances (matching).
    Route::get('/missions/{mission}/correspondances', [MissionMatchingController::class, 'index'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-matching'])->name('missions.matching');
    Route::post('/missions/{mission}/correspondances/{subject}/masquer', [MissionMatchingController::class, 'hide'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-matching'])->name('missions.matching.hide');

    // Récurrence.
    Route::post('/missions/{mission}/recurrence', [MissionRecurrenceController::class, 'store'])
        ->whereUuid('mission')->middleware(['core.member', 'throttle:mission-recurrence'])->name('missions.recurrence.store');
    Route::put('/missions/{mission}/recurrence/{recurrence}/pause', [MissionRecurrenceController::class, 'pause'])
        ->whereUuid('mission')->whereUuid('recurrence')->middleware(['core.member', 'throttle:mission-recurrence'])->name('missions.recurrence.pause');
    Route::put('/missions/{mission}/recurrence/{recurrence}/reprendre', [MissionRecurrenceController::class, 'resume'])
        ->whereUuid('mission')->whereUuid('recurrence')->middleware(['core.member', 'throttle:mission-recurrence'])->name('missions.recurrence.resume');
    Route::put('/missions/{mission}/recurrence/{recurrence}/arreter', [MissionRecurrenceController::class, 'stop'])
        ->whereUuid('mission')->whereUuid('recurrence')->middleware(['core.member', 'throttle:mission-recurrence'])->name('missions.recurrence.stop');
});
