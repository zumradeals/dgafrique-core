<?php

declare(strict_types=1);

use App\Http\Controllers\AccountRegistrationController;
use App\Http\Controllers\Administration\CollectiveCapabilityConfigurationController;
use App\Http\Controllers\Administration\NeedConfigurationController;
use App\Http\Controllers\Administration\PeopleDiscoveryConfigurationController;
use App\Http\Controllers\Administration\ProfileConfigurationController;
use App\Http\Controllers\Administration\ProjectConfigurationController;
use App\Http\Controllers\Administration\ProjectMatchingConfigurationController;
use App\Http\Controllers\Administration\RecommendationConfigurationController;
use App\Http\Controllers\Administration\ZumraCardController as AdministrationZumraCardController;
use App\Http\Controllers\Administration\ZumraGroupConfigurationController;
use App\Http\Controllers\Administration\ZumraProgramConfigurationController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberSessionController;
use App\Http\Controllers\MemberSpaceController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PeopleDiscoveryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMatchingController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ZumraCardController;
use App\Http\Controllers\ZumraCollectiveCapabilityController;
use App\Http\Controllers\ZumraGroupController;
use App\Http\Controllers\ZumraMembershipPaymentController;
use App\Http\Controllers\ZumraProgramMembershipController;
use App\Http\Controllers\ZumraSpaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', GatewayController::class)->name('gateway');
Route::get('/decouvrir', LandingController::class)->name('landing');

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
Route::get('/recommandations', [RecommendationController::class, 'index'])
    ->middleware(['core.member', 'throttle:recommendations'])->name('recommendations.index');
Route::post('/recommandations/personnes/{reference}/masquer', [RecommendationController::class, 'hide'])
    ->whereUuid('reference')->middleware(['core.member', 'throttle:recommendation-decisions'])->name('recommendations.hide');
Route::post('/recommandations/personnes/{reference}/restaurer', [RecommendationController::class, 'restore'])
    ->whereUuid('reference')->middleware(['core.member', 'throttle:recommendation-decisions'])->name('recommendations.restore');
Route::get('/opportunites', [OpportunityController::class, 'index'])
    ->middleware(['core.member', 'throttle:opportunities'])->name('opportunities.index');
Route::get('/besoins', [NeedController::class, 'index'])->middleware('core.member')->name('needs.index');
Route::get('/besoins/exprimer', [NeedController::class, 'create'])->middleware('core.member')->name('needs.create');
Route::post('/besoins', [NeedController::class, 'store'])->middleware(['core.member', 'throttle:need-write'])->name('needs.store');
Route::get('/besoins/{need}', [NeedController::class, 'show'])->whereUuid('need')->middleware('core.member')->name('needs.show');
Route::put('/besoins/{need}/etat', [NeedController::class, 'transition'])->whereUuid('need')->middleware(['core.member', 'throttle:need-transition'])->name('needs.transition');
Route::get('/projets', [ProjectController::class, 'index'])->middleware('core.member')->name('projects.index');
Route::get('/projets/proposer', [ProjectController::class, 'create'])->middleware('core.member')->name('projects.create');
Route::post('/projets', [ProjectController::class, 'store'])->middleware(['core.member', 'throttle:project-write'])->name('projects.store');
Route::get('/projets/{project}', [ProjectController::class, 'show'])->whereUuid('project')->middleware('core.member')->name('projects.show');
Route::put('/projets/{project}/etat', [ProjectController::class, 'transition'])->whereUuid('project')->middleware(['core.member', 'throttle:project-transition'])->name('projects.transition');
Route::get('/projets/{project}/correspondances', [ProjectMatchingController::class, 'index'])->whereUuid('project')->middleware(['core.member', 'throttle:project-matching'])->name('projects.matching');
Route::post('/projets/{project}/correspondances/{profile}/masquer', [ProjectMatchingController::class, 'hide'])->whereUuid('project')->whereUuid('profile')->middleware(['core.member', 'throttle:project-match-decisions'])->name('projects.matching.hide');
Route::get('/organisations', [OrganizationController::class, 'index'])->middleware('core.member')->name('organizations.index');
Route::get('/organisations/creer', [OrganizationController::class, 'create'])->middleware('core.member')->name('organizations.create');
Route::post('/organisations', [OrganizationController::class, 'store'])->middleware(['core.member', 'throttle:organization-write'])->name('organizations.store');
Route::get('/organisations/{organization}', [OrganizationController::class, 'show'])->whereUuid('organization')->middleware('core.member')->name('organizations.show');
Route::post('/organisations/{organization}/rejoindre', [OrganizationController::class, 'requestToJoin'])->whereUuid('organization')->middleware(['core.member', 'throttle:organization-membership'])->name('organizations.join');
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
Route::get('/zumra/groupes', [ZumraGroupController::class, 'index'])
    ->middleware('core.member')->name('zumra.groups.index');
Route::get('/zumra/groupes/proposer', [ZumraGroupController::class, 'create'])
    ->middleware('core.member')->name('zumra.groups.create');
Route::post('/zumra/groupes', [ZumraGroupController::class, 'store'])
    ->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.store');
Route::get('/zumra/groupes/{group}', [ZumraGroupController::class, 'show'])
    ->whereUuid('group')->middleware('core.member')->name('zumra.groups.show');
Route::post('/zumra/groupes/{group}/demande', [ZumraGroupController::class, 'requestToJoin'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.request');
Route::post('/zumra/groupes/{group}/invitation', [ZumraGroupController::class, 'invite'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.invite');
Route::post('/zumra/groupes/{group}/invitation/accepter', [ZumraGroupController::class, 'acceptInvitation'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.invitation.accept');
Route::post('/zumra/groupes/{group}/demandes/{membership}/approuver', [ZumraGroupController::class, 'approveRequest'])
    ->whereUuid('group')->whereUuid('membership')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.requests.approve');
Route::post('/zumra/groupes/{group}/quitter', [ZumraGroupController::class, 'leave'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.leave');
Route::put('/zumra/groupes/{group}/capacites-collectives/consentement', [ZumraCollectiveCapabilityController::class, 'consent'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:collective-capability-consent'])->name('zumra.groups.collective-capabilities.consent');

Route::prefix('administration')->middleware(['core.member', 'portal.admin'])->group(function (): void {
    Route::get('/', [ProfileConfigurationController::class, 'edit'])->name('administration.profile.edit');
    Route::put('/profil-capacites', [ProfileConfigurationController::class, 'update'])
        ->middleware('throttle:profile-configuration')->name('administration.profile.update');
    Route::get('/decouverte-personnes', [PeopleDiscoveryConfigurationController::class, 'edit'])
        ->name('administration.discovery.edit');
    Route::put('/decouverte-personnes', [PeopleDiscoveryConfigurationController::class, 'update'])
        ->middleware('throttle:discovery-configuration')->name('administration.discovery.update');
    Route::get('/recommandations', [RecommendationConfigurationController::class, 'edit'])
        ->name('administration.recommendations.edit');
    Route::put('/recommandations', [RecommendationConfigurationController::class, 'update'])
        ->middleware('throttle:recommendation-configuration')->name('administration.recommendations.update');
    Route::get('/programme-zumra', [ZumraProgramConfigurationController::class, 'edit'])->name('administration.zumra.edit');
    Route::put('/programme-zumra', [ZumraProgramConfigurationController::class, 'update'])
        ->middleware('throttle:zumra-configuration')->name('administration.zumra.update');
    Route::post('/programme-zumra/charte', [ZumraProgramConfigurationController::class, 'publishCharter'])
        ->middleware('throttle:zumra-charter-publish')->name('administration.zumra.charter.publish');
    Route::post('/programme-zumra/cartes/{card}/revoquer', [AdministrationZumraCardController::class, 'revoke'])
        ->middleware('throttle:zumra-card-revoke')->name('administration.zumra.card.revoke');
    Route::get('/groupes-zumra', [ZumraGroupConfigurationController::class, 'edit'])->name('administration.zumra.groups.edit');
    Route::put('/groupes-zumra', [ZumraGroupConfigurationController::class, 'update'])
        ->middleware('throttle:zumra-group-configuration')->name('administration.zumra.groups.update');
    Route::get('/capacites-collectives', [CollectiveCapabilityConfigurationController::class, 'edit'])->name('administration.collective-capabilities.edit');
    Route::put('/capacites-collectives', [CollectiveCapabilityConfigurationController::class, 'update'])
        ->middleware('throttle:collective-capability-configuration')->name('administration.collective-capabilities.update');
    Route::get('/besoins', [NeedConfigurationController::class, 'edit'])->name('administration.needs.edit');
    Route::put('/besoins', [NeedConfigurationController::class, 'update'])
        ->middleware('throttle:need-configuration')->name('administration.needs.update');
    Route::get('/projets', [ProjectConfigurationController::class, 'edit'])->name('administration.projects.edit');
    Route::put('/projets', [ProjectConfigurationController::class, 'update'])->middleware('throttle:project-configuration')->name('administration.projects.update');
    Route::get('/correspondances-projets', [ProjectMatchingConfigurationController::class, 'edit'])->name('administration.project-matching.edit');
    Route::put('/correspondances-projets', [ProjectMatchingConfigurationController::class, 'update'])->middleware('throttle:project-matching-configuration')->name('administration.project-matching.update');
});
