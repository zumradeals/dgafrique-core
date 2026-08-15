<?php

declare(strict_types=1);

use App\Http\Controllers\AccountRegistrationController;
use App\Http\Controllers\Administration\ProfileConfigurationController;
use App\Http\Controllers\Administration\ZumraProgramConfigurationController;
use App\Http\Controllers\Administration\ZumraCardController as AdministrationZumraCardController;
use App\Http\Controllers\Administration\PeopleDiscoveryConfigurationController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberSessionController;
use App\Http\Controllers\MemberSpaceController;
use App\Http\Controllers\ZumraSpaceController;
use App\Http\Controllers\ZumraProgramMembershipController;
use App\Http\Controllers\ZumraMembershipPaymentController;
use App\Http\Controllers\ZumraCardController;
use App\Http\Controllers\PeopleDiscoveryController;
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
Route::get('/personnes', [PeopleDiscoveryController::class, 'index'])
    ->middleware(['core.member', 'throttle:people-discovery'])->name('people.index');
Route::get('/personnes/{reference}', [PeopleDiscoveryController::class, 'show'])
    ->whereUuid('reference')->middleware(['core.member', 'throttle:people-discovery'])->name('people.show');
Route::get('/zumra', ZumraSpaceController::class)
    ->middleware('core.member')->name('zumra.index');
Route::get('/zumra/adhesion', [ZumraProgramMembershipController::class, 'show'])
    ->middleware('core.member')->name('zumra.membership.show');
Route::post('/zumra/adhesion', [ZumraProgramMembershipController::class, 'store'])
    ->middleware(['core.member', 'throttle:zumra-membership'])->name('zumra.membership.store');
Route::post('/zumra/adhesion/paiement', [ZumraMembershipPaymentController::class, 'store'])
    ->middleware(['core.member', 'throttle:zumra-payment'])->name('zumra.payment.store');
Route::get('/zumra/adhesion/paiement/retour', [ZumraMembershipPaymentController::class, 'returned'])
    ->middleware(['core.member', 'throttle:zumra-payment-status'])->name('zumra.payment.return');
Route::get('/zumra/adhesion/recus/{receipt}', [ZumraMembershipPaymentController::class, 'receipt'])
    ->middleware('core.member')->name('zumra.payment.receipt');
Route::get('/zumra/carte', [ZumraCardController::class, 'show'])
    ->middleware('core.member')->name('zumra.card.show');
Route::get('/verifier/carte-zumra/{card}', [ZumraCardController::class, 'verify'])
    ->middleware(['signed', 'throttle:zumra-card-verification'])->name('zumra.card.verify');

Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
    Route::get('/', [ProfileConfigurationController::class, 'edit'])->name('administration.profile.edit');
    Route::put('/profil-capacites', [ProfileConfigurationController::class, 'update'])
        ->middleware('throttle:profile-configuration')->name('administration.profile.update');
    Route::get('/decouverte-personnes', [PeopleDiscoveryConfigurationController::class, 'edit'])
        ->name('administration.discovery.edit');
    Route::put('/decouverte-personnes', [PeopleDiscoveryConfigurationController::class, 'update'])
        ->middleware('throttle:discovery-configuration')->name('administration.discovery.update');
    Route::get('/programme-zumra', [ZumraProgramConfigurationController::class, 'edit'])->name('administration.zumra.edit');
    Route::put('/programme-zumra', [ZumraProgramConfigurationController::class, 'update'])
        ->middleware('throttle:zumra-configuration')->name('administration.zumra.update');
    Route::post('/programme-zumra/charte', [ZumraProgramConfigurationController::class, 'publishCharter'])
        ->middleware('throttle:zumra-charter-publish')->name('administration.zumra.charter.publish');
    Route::post('/programme-zumra/cartes/{card}/revoquer', [AdministrationZumraCardController::class, 'revoke'])
        ->middleware('throttle:zumra-card-revoke')->name('administration.zumra.card.revoke');
});
