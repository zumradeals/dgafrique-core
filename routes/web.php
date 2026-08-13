<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberSessionController;
use App\Http\Controllers\MemberSpaceController;

Route::get('/', function () {
    return view('foundation');
});

Route::get('/connexion', [MemberSessionController::class, 'create'])->name('login');
Route::post('/connexion', [MemberSessionController::class, 'store'])
    ->middleware('throttle:member-login')->name('login.store');
Route::post('/deconnexion', [MemberSessionController::class, 'destroy'])
    ->middleware('throttle:member-logout')->name('logout');

Route::get('/espace', MemberSpaceController::class)
    ->middleware('core.member')->name('member.space');
