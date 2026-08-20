<?php

declare(strict_types=1);
use App\Http\Controllers\ProjectBrainController; use App\Http\Controllers\ProjectBrainStartController; use Illuminate\Support\Facades\Route;
Route::middleware(['web','core.member'])->group(function():void{
 Route::get('/projets/cerveau/nouveau',[ProjectBrainStartController::class,'create'])->name('projects.brain.start');
 Route::post('/projets/cerveau/nouveau',[ProjectBrainStartController::class,'store'])->middleware('throttle:project-write')->name('projects.brain.start.store');
 Route::get('/projets/cerveau/preparation/{intent}',[ProjectBrainStartController::class,'show'])->whereUuid('intent')->name('projects.brain.start.show');
 Route::post('/projets/cerveau/preparation/{intent}',[ProjectBrainStartController::class,'reply'])->whereUuid('intent')->middleware('throttle:project-write')->name('projects.brain.start.reply');
 Route::post('/projets/cerveau/preparation/{intent}/confirmer',[ProjectBrainStartController::class,'confirm'])->whereUuid('intent')->middleware('throttle:project-write')->name('projects.brain.start.confirm');
 Route::get('/projets/{project}/vue-ensemble',[ProjectBrainController::class,'overview'])->whereUuid('project')->name('projects.overview');
 Route::get('/projets/{project}/cerveau',[ProjectBrainController::class,'show'])->whereUuid('project')->name('projects.brain.show');
 Route::post('/projets/{project}/cerveau/besoins/preparer',[ProjectBrainController::class,'prepareNeed'])->whereUuid('project')->middleware('throttle:need-write')->name('projects.brain.needs.prepare');
 Route::post('/projets/{project}/cerveau/besoins/{draft}/confirmer',[ProjectBrainController::class,'confirmNeed'])->whereUuid('project')->whereUuid('draft')->middleware('throttle:need-write')->name('projects.brain.needs.confirm');
 Route::post('/projets/{project}/cerveau/brouillons/{draft}/abandonner',[ProjectBrainController::class,'cancelDraft'])->whereUuid('project')->whereUuid('draft')->middleware('throttle:need-write')->name('projects.brain.drafts.cancel');
});
