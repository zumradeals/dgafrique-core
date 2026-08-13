<?php

declare(strict_types=1);

use App\Http\Controllers\AccountRegistrationController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberSessionController;
use App\Http\Controllers\MemberSpaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('foundation');
});

Route::get('/connexion', [MemberSessionController::class, 'create'])->name('login');
Route::post('/connexion', [MemberSessionController::class, 'store'])
    ->middleware('throttle:member-login')->name('login.store');
Route::post('/deconnexion', [MemberSessionController::class, 'destroy'])
    ->middleware('throttle:member-logout')->name('logout');

Route::get('/creer-un-compte', [AccountRegistrationController::class, 'create'])->name('register');
Route::post('/creer-un-compte', [AccountRegistrationController::class, 'store'])->middleware('throttle:account-create')->name('register.store');
Route::get('/verifier-le-compte', [AccountRegistrationController::class, 'verification'])->name('register.verify');
Route::post('/verifier-le-compte', [AccountRegistrationController::class, 'verify'])->middleware('throttle:account-verify')->name('register.verify.store');
Route::post('/verifier-le-compte/renvoi', [AccountRegistrationController::class, 'resend'])->middleware('throttle:account-resend')->name('register.verify.resend');

Route::get('/espace', MemberSpaceController::class)
    ->middleware('core.member')->name('member.space');
Route::get('/espace/profil', [MemberProfileController::class, 'edit'])
    ->middleware('core.member')->name('member.profile.edit');
Route::put('/espace/profil', [MemberProfileController::class, 'update'])
    ->middleware(['core.member', 'throttle:profile-update'])->name('member.profile.update');
