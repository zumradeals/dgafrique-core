<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Administration\AdminJournalAggregator;
use App\Models\Contribution;
use App\Models\ContributionPayment;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\ModerationDecision;
use App\Models\ModerationReport;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectAccompanimentRequest;
use App\Models\ProjectFunding;
use App\Models\ZahabAcquisition;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\View\View;

/**
 * ADMIN-CONTROL-002 — porte d'entrée de la tour de contrôle. Chaque métrique vient d'une requête
 * directe sur les modèles réels (jamais une donnée fabriquée) — voir le rapport de livraison pour
 * la source exacte de chacune. Aucune logique métier ici : uniquement de la lecture agrégée.
 */
final class AdminDashboardController
{
    public function index(AdminJournalAggregator $journal): View
    {
        $zumraByState = ZumraGroup::query()->selectRaw('state, count(*) as total')->groupBy('state')->pluck('total', 'state');
        $needsByStatus = Need::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $projectsByStatus = Project::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $missionsActive = Mission::query()->whereNotIn('status', Mission::TERMINAL_STATUSES)->count();
        $missionsBlocked = Mission::query()->where('status', Mission::STATUS_BLOCKED)->count();

        $acquisitionsRecent = ZahabAcquisition::query()->latest('created_at')->limit(6)->get();
        $acquisitionsFailed24h = ZahabAcquisition::query()->where('status', ZahabAcquisition::STATUS_FAILED)->where('updated_at', '>=', now()->subDay())->count();
        $acquisitionsCompletedTotal = (int) ZahabAcquisition::query()->where('status', ZahabAcquisition::STATUS_COMPLETED)->sum('amount');

        $contributionPaymentsRecent = ContributionPayment::query()->latest('created_at')->limit(6)->get();
        $contributionsActive = Contribution::query()->where('status', Contribution::STATUS_ACTIVE)->count();
        $contributionFailures7d = ContributionPayment::query()->where('status', ContributionPayment::STATUS_FAILED)->where('updated_at', '>=', now()->subDays(7))->count();

        // Masse ZAHAB en circulation : jamais un solde stocké — toujours la somme dérivée du Ledger
        // (art. mandat Finance), identique au principe déjà appliqué par ZahabWalletService::balance().
        $massZahab = (int) LedgerEntry::query()->whereNotNull('wallet_id')->where('direction', LedgerEntry::DIRECTION_CREDIT)->sum('amount')
            - (int) LedgerEntry::query()->whereNotNull('wallet_id')->where('direction', LedgerEntry::DIRECTION_DEBIT)->sum('amount');
        $ledgerMovements7d = LedgerEntry::query()->where('occurred_at', '>=', now()->subDays(7))->count();

        $projectFundingOpen = ProjectFunding::query()->where('status', ProjectFunding::STATUS_OPEN)->count();
        $projectFundingTargetOpen = (int) ProjectFunding::query()->where('status', ProjectFunding::STATUS_OPEN)->sum('target_amount');

        $accompanimentPending = ProjectAccompanimentRequest::query()->where('status', ProjectAccompanimentRequest::STATUS_PENDING)->count();
        $moderationPending = ModerationReport::query()->where('status', ModerationReport::STATUS_PENDING)->count();
        $moderationAppealsPending = ModerationDecision::query()->whereNotNull('appeal_requested_at')->whereNull('appeal_decided_at')->count();
        $rolesProposedPending = ZumraGroupRole::query()->where('status', ZumraGroupRole::STATUS_PROPOSED)->count();

        return view('administration.dashboard', [
            'zumraByState' => $zumraByState,
            'needsByStatus' => $needsByStatus,
            'projectsByStatus' => $projectsByStatus,
            'missionsActive' => $missionsActive,
            'missionsBlocked' => $missionsBlocked,
            'acquisitionsRecent' => $acquisitionsRecent,
            'acquisitionsFailed24h' => $acquisitionsFailed24h,
            'acquisitionsCompletedTotal' => $acquisitionsCompletedTotal,
            'contributionPaymentsRecent' => $contributionPaymentsRecent,
            'contributionsActive' => $contributionsActive,
            'contributionFailures7d' => $contributionFailures7d,
            'massZahab' => $massZahab,
            'ledgerMovements7d' => $ledgerMovements7d,
            'projectFundingOpen' => $projectFundingOpen,
            'projectFundingTargetOpen' => $projectFundingTargetOpen,
            'accompanimentPending' => $accompanimentPending,
            'moderationPending' => $moderationPending,
            'moderationAppealsPending' => $moderationAppealsPending,
            'rolesProposedPending' => $rolesProposedPending,
            'recentJournal' => $journal->recent(10),
        ]);
    }
}
