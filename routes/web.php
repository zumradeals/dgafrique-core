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
    ->middleware('throttle:5,1')->name('login.store');
Route::post('/deconnexion', [MemberSessionController::class, 'destroy'])
    ->middleware('throttle:10,1')->name('logout');

Route::get('/espace', MemberSpaceController::class)
    ->middleware('core.member')->name('member.space');
