<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectTeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/projets/{project}/equipe/demande', [ProjectTeamController::class, 'requestToJoin'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.request');

    Route::post('/projets/{project}/equipe/invitation', [ProjectTeamController::class, 'invite'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.invite');

    Route::post('/projets/{project}/equipe/invitation/accepter', [ProjectTeamController::class, 'acceptInvitation'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.invitation.accept');

    Route::post('/projets/{project}/equipe/demandes/{member}/approuver', [ProjectTeamController::class, 'approveRequest'])
        ->whereUuid('project')
        ->whereUuid('member')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.requests.approve');

    Route::post('/projets/{project}/equipe/quitter', [ProjectTeamController::class, 'leave'])
        ->whereUuid('project')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.leave');

    Route::post('/projets/{project}/equipe/{member}/retirer', [ProjectTeamController::class, 'remove'])
        ->whereUuid('project')
        ->whereUuid('member')
        ->middleware(['core.member', 'throttle:project-team-write'])
        ->name('projects.team.remove');
});
