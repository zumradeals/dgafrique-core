<?php

declare(strict_types=1);

use App\Http\Controllers\AccountRegistrationController;
use App\Http\Controllers\Administration\CollectiveCapabilityConfigurationController;
use App\Http\Controllers\Administration\ContributionConfigurationController;
use App\Http\Controllers\Administration\LedgerController as AdministrationLedgerController;
use App\Http\Controllers\Administration\NeedConfigurationController;
use App\Http\Controllers\Administration\PeopleDiscoveryConfigurationController;
use App\Http\Controllers\Administration\ProfileConfigurationController;
use App\Http\Controllers\Administration\ProjectConfigurationController;
use App\Http\Controllers\Administration\ProjectMatchingConfigurationController;
use App\Http\Controllers\Administration\RecommendationConfigurationController;
use App\Http\Controllers\Administration\WalletController as AdministrationWalletController;
use App\Http\Controllers\Administration\ZumraCardController as AdministrationZumraCardController;
use App\Http\Controllers\Administration\ZumraGroupConfigurationController;
use App\Http\Controllers\Administration\ZumraGroupLifecycleController;
use App\Http\Controllers\Administration\ZumraProgramConfigurationController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberSessionController;
use App\Http\Controllers\MemberSpaceController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OrganizationCapabilityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PartnershipController;
use App\Http\Controllers\PeopleDiscoveryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDraftController;
use App\Http\Controllers\ProjectMatchingController;
use App\Http\Controllers\QuickCapabilityController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ZahabAcquisitionController;
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
Route::post('/espace/capacite-rapide', [QuickCapabilityController::class, 'store'])
    ->middleware(['core.member', 'throttle:profile-update'])->name('member.capability.quick');
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
// UIUX-009B — parcours progressif et sauvegardable, indépendant du Cerveau. Remplace l'ancien
// formulaire monolithique (ProjectController::create/store, supprimés) : « projects.create » garde
// son nom pour tous les liens existants (Mon espace, fiche ZUMRA, sheet Agir…) mais mène désormais
// à ce parcours plutôt qu'à un formulaire unique.
Route::get('/projets/proposer', [ProjectDraftController::class, 'entry'])->middleware('core.member')->name('projects.create');
Route::get('/projets/proposer/{draft}/{step}', [ProjectDraftController::class, 'show'])->whereUuid('draft')->where('step', '[a-z]+')->middleware('core.member')->name('projects.draft.show');
// Les routes nommées (abandonner/confirmer/zumra) doivent être déclarées avant le joker {step} :
// celui-ci accepte "[a-z]+" et intercepterait sinon silencieusement ces mots.
Route::post('/projets/proposer/{draft}/abandonner', [ProjectDraftController::class, 'abandon'])->whereUuid('draft')->middleware(['core.member', 'throttle:project-draft-write'])->name('projects.draft.abandon');
Route::post('/projets/proposer/{draft}/confirmer', [ProjectDraftController::class, 'confirm'])->whereUuid('draft')->middleware(['core.member', 'throttle:project-draft-write'])->name('projects.draft.confirm');
// PROJET-ZUMRA-INVARIANT-001 — naissance explicite d'une ZUMRA solo depuis le parcours Projet,
// jamais silencieuse : un vrai formulaire, puis retour au brouillon jamais perdu.
Route::get('/projets/proposer/{draft}/zumra/nouvelle', [ProjectDraftController::class, 'newZumra'])->whereUuid('draft')->middleware('core.member')->name('projects.draft.zumra.create');
Route::post('/projets/proposer/{draft}/zumra/nouvelle', [ProjectDraftController::class, 'storeZumra'])->whereUuid('draft')->middleware(['core.member', 'throttle:project-draft-write'])->name('projects.draft.zumra.store');
Route::post('/projets/proposer/{draft}/{step}', [ProjectDraftController::class, 'update'])->whereUuid('draft')->where('step', '[a-z]+')->middleware(['core.member', 'throttle:project-draft-write'])->name('projects.draft.update');
Route::get('/projets/{project}', [ProjectController::class, 'show'])->whereUuid('project')->middleware('core.member')->name('projects.show');
Route::put('/projets/{project}/etat', [ProjectController::class, 'transition'])->whereUuid('project')->middleware(['core.member', 'throttle:project-transition'])->name('projects.transition');
Route::put('/projets/{project}/jalons/{milestone}', [ProjectController::class, 'completeMilestone'])->whereUuid('project')->whereUuid('milestone')->middleware(['core.member', 'throttle:project-transition'])->name('projects.milestones.complete');
Route::get('/projets/{project}/correspondances', [ProjectMatchingController::class, 'index'])->whereUuid('project')->middleware(['core.member', 'throttle:project-matching'])->name('projects.matching');
Route::post('/projets/{project}/correspondances/{profile}/masquer', [ProjectMatchingController::class, 'hide'])->whereUuid('project')->whereUuid('profile')->middleware(['core.member', 'throttle:project-match-decisions'])->name('projects.matching.hide');
Route::get('/organisations', [OrganizationController::class, 'index'])->middleware('core.member')->name('organizations.index');
Route::get('/organisations/creer', [OrganizationController::class, 'create'])->middleware('core.member')->name('organizations.create');
Route::post('/organisations', [OrganizationController::class, 'store'])->middleware(['core.member', 'throttle:organization-write'])->name('organizations.store');
Route::get('/organisations/{organization}', [OrganizationController::class, 'show'])->whereUuid('organization')->middleware('core.member')->name('organizations.show');
Route::post('/organisations/{organization}/rejoindre', [OrganizationController::class, 'requestToJoin'])->whereUuid('organization')->middleware(['core.member', 'throttle:organization-membership'])->name('organizations.join');
Route::post('/organisations/{organization}/demandes/{membership}/approuver', [OrganizationController::class, 'approveRequest'])->whereUuid('organization')->whereUuid('membership')->middleware(['core.member', 'throttle:organization-membership'])->name('organizations.requests.approve');
Route::post('/organisations/{organization}/capacites', [OrganizationCapabilityController::class, 'store'])->whereUuid('organization')->middleware(['core.member', 'throttle:organization-capability'])->name('organizations.capabilities.store');
Route::delete('/organisations/{organization}/capacites/{capability}', [OrganizationCapabilityController::class, 'destroy'])->whereUuid('organization')->whereUuid('capability')->middleware(['core.member', 'throttle:organization-capability'])->name('organizations.capabilities.destroy');
Route::get('/partenariats', [PartnershipController::class, 'index'])->middleware('core.member')->name('partnerships.index');
Route::post('/partenariats', [PartnershipController::class, 'store'])->middleware(['core.member', 'throttle:partnership-write'])->name('partnerships.store');
Route::get('/partenariats/{partnership}', [PartnershipController::class, 'show'])->whereUuid('partnership')->middleware('core.member')->name('partnerships.show');
Route::post('/partenariats/{partnership}/activation', [PartnershipController::class, 'activate'])->whereUuid('partnership')->middleware(['core.member', 'throttle:partnership-decisions'])->name('partnerships.activate');
Route::post('/partenariats/{partnership}/pause', [PartnershipController::class, 'pause'])->whereUuid('partnership')->middleware(['core.member', 'throttle:partnership-decisions'])->name('partnerships.pause');
Route::post('/partenariats/{partnership}/reprise', [PartnershipController::class, 'resume'])->whereUuid('partnership')->middleware(['core.member', 'throttle:partnership-decisions'])->name('partnerships.resume');
Route::post('/partenariats/{partnership}/retrait', [PartnershipController::class, 'withdraw'])->whereUuid('partnership')->middleware(['core.member', 'throttle:partnership-decisions'])->name('partnerships.withdraw');
Route::post('/partenariats/{partnership}/fin', [PartnershipController::class, 'end'])->whereUuid('partnership')->middleware(['core.member', 'throttle:partnership-decisions'])->name('partnerships.end');
Route::get('/zumra', ZumraSpaceController::class)
    ->middleware('core.member')->name('zumra.index');
Route::get('/zumra/adhesion', [ZumraProgramMembershipController::class, 'show'])
    ->middleware('core.member')->name('zumra.membership.show');
Route::post('/zumra/adhesion', [ZumraProgramMembershipController::class, 'store'])
    ->middleware(['core.member', 'throttle:zumra-membership'])->name('zumra.membership.store');
Route::post('/zumra/adhesion/paiement', [ZumraMembershipPaymentController::class, 'store'])
    ->middleware(['core.member', 'throttle:zumra-payment'])->name('zumra.payment.store');
Route::post('/zumra/adhesion/paiement-zahab', [ZumraMembershipPaymentController::class, 'payWithZahab'])
    ->middleware(['core.member', 'throttle:zumra-payment'])->name('zumra.payment.zahab.store');
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
Route::post('/zumra/groupes/{group}/roles/{role}/proposer', [ZumraGroupController::class, 'proposeRole'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.roles.propose');
Route::post('/zumra/groupes/{group}/roles/{role}/accepter', [ZumraGroupController::class, 'acceptRole'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.roles.accept');
Route::put('/zumra/groupes/{group}/capacites-collectives/consentement', [ZumraCollectiveCapabilityController::class, 'consent'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:collective-capability-consent'])->name('zumra.groups.collective-capabilities.consent');
Route::post('/zumra/groupes/{group}/contribution', [ContributionController::class, 'proposeCollective'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:contribution-write'])->name('zumra.groups.contribution.propose');
Route::post('/zumra/groupes/{group}/contribution/approbation', [ContributionController::class, 'approveCollective'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:contribution-write'])->name('zumra.groups.contribution.approve');
Route::post('/zumra/groupes/{group}/charte', [ZumraGroupController::class, 'setCharter'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.charter.set');
Route::post('/zumra/groupes/{group}/activites', [ZumraGroupController::class, 'addActivity'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zumra-group-write'])->name('zumra.groups.activities.add');

Route::get('/contributions', [ContributionController::class, 'index'])
    ->middleware('core.member')->name('contributions.index');
Route::get('/contributions/tableau', [ContributionController::class, 'dashboard'])
    ->middleware('core.member')->name('contributions.dashboard');
Route::post('/contributions/individuelle', [ContributionController::class, 'startIndividual'])
    ->middleware(['core.member', 'throttle:contribution-write'])->name('contributions.individual.start');
Route::post('/contributions/{contribution}/pause', [ContributionController::class, 'pause'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-write'])->name('contributions.pause');
Route::post('/contributions/{contribution}/reprise', [ContributionController::class, 'resume'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-write'])->name('contributions.resume');
Route::post('/contributions/{contribution}/arret', [ContributionController::class, 'stop'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-write'])->name('contributions.stop');
Route::post('/contributions/{contribution}/paiement', [ContributionController::class, 'pay'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-payment'])->name('contributions.pay');
Route::post('/contributions/{contribution}/paiement-zahab', [ContributionController::class, 'payWithZahab'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-payment'])->name('contributions.pay.zahab');
Route::get('/contributions/{contribution}/paiements/retour', [ContributionController::class, 'returned'])
    ->whereUuid('contribution')->middleware(['core.member', 'throttle:contribution-payment-status'])->name('contributions.payment.return');
Route::get('/contributions/recus/{receipt}', [ContributionController::class, 'receipt'])
    ->middleware('core.member')->name('contributions.receipt');

Route::get('/finances/ledger', [LedgerController::class, 'index'])
    ->middleware(['core.member', 'throttle:ledger-read'])->name('ledger.index');
Route::get('/finances/ledger/{entry}', [LedgerController::class, 'show'])
    ->whereUuid('entry')->middleware(['core.member', 'throttle:ledger-read'])->name('ledger.show');

// ZAHAB-001 — lecture uniquement (art. 28 du mandat) : aucune route n'expose credit()/debit().
Route::get('/finances/zahab', [WalletController::class, 'person'])
    ->middleware(['core.member', 'throttle:zahab-read'])->name('zahab.wallet.person');
Route::get('/finances/zahab/tableau', [WalletController::class, 'dashboard'])
    ->middleware(['core.member', 'throttle:zahab-read'])->name('zahab.wallet.dashboard');
Route::post('/finances/zahab/acquisitions', [ZahabAcquisitionController::class, 'store'])
    ->middleware(['core.member', 'throttle:zahab-acquisition'])->name('zahab.acquisitions.store');
Route::get('/finances/zahab/acquisitions/retour', [ZahabAcquisitionController::class, 'returned'])
    ->middleware(['core.member', 'throttle:zahab-acquisition-status'])->name('zahab.acquisitions.return');
Route::get('/zumra/groupes/{group}/zahab', [WalletController::class, 'zumraGroup'])
    ->whereUuid('group')->middleware(['core.member', 'throttle:zahab-read'])->name('zahab.wallet.zumra-group');
Route::get('/organisations/{organization}/zahab', [WalletController::class, 'organization'])
    ->whereUuid('organization')->middleware(['core.member', 'throttle:zahab-read'])->name('zahab.wallet.organization');

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
    Route::post('/groupes-zumra/{group}/pret', [ZumraGroupLifecycleController::class, 'markReady'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.ready');
    Route::post('/groupes-zumra/{group}/validation', [ZumraGroupLifecycleController::class, 'validate'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.validate');
    Route::post('/groupes-zumra/{group}/activation', [ZumraGroupLifecycleController::class, 'activate'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.activate');
    Route::post('/groupes-zumra/{group}/avertissement', [ZumraGroupLifecycleController::class, 'warn'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.warn');
    Route::post('/groupes-zumra/{group}/suspension', [ZumraGroupLifecycleController::class, 'suspend'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.suspend');
    Route::post('/groupes-zumra/{group}/rehabilitation', [ZumraGroupLifecycleController::class, 'enterRehabilitation'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.rehabilitate');
    Route::post('/groupes-zumra/{group}/reactivation', [ZumraGroupLifecycleController::class, 'reactivate'])
        ->whereUuid('group')->middleware('throttle:zumra-group-lifecycle')->name('administration.zumra.groups.reactivate');
    Route::get('/contributions', [ContributionConfigurationController::class, 'edit'])->name('administration.contributions.edit');
    Route::put('/contributions', [ContributionConfigurationController::class, 'update'])
        ->middleware('throttle:contribution-configuration')->name('administration.contributions.update');
    Route::post('/contributions/finalites/{purpose}/retrait', [ContributionConfigurationController::class, 'retirePurpose'])
        ->middleware('throttle:contribution-configuration')->name('administration.contributions.purposes.retire');
    Route::post('/contributions/finalites/{purpose}/reactivation', [ContributionConfigurationController::class, 'reactivatePurpose'])
        ->middleware('throttle:contribution-configuration')->name('administration.contributions.purposes.reactivate');
    Route::get('/ledger', [AdministrationLedgerController::class, 'index'])
        ->middleware('throttle:ledger-read')->name('administration.ledger.index');
    Route::get('/zahab-wallets', [AdministrationWalletController::class, 'index'])
        ->middleware('throttle:zahab-read')->name('administration.zahab.wallets.index');
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
