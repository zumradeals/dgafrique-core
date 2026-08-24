<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Zahab\ZahabWalletService;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectFunding;
use App\Models\ZahabWallet;
use App\Models\ZumraGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PROJECT-FUNDING-002 — relie la déclaration CAP-063 (`ProjectFunding`, purement déclarative,
 * inchangée) aux primitives ZAHAB déjà prouvées (ZAHAB-001/002) : un contributeur débite son
 * propre Wallet Personne, le Wallet de la ZUMRA porteuse du Projet (`Project::zumraGroup()`,
 * ancrage déjà canonique — art. « Cas A » du mandat) est crédité du même montant, dans la même
 * opération. AUCUN Wallet Projet n'est créé (interdiction art. 1 du mandat) : le Projet reste
 * l'objet financé, jamais un détenteur d'argent. AUCUNE table de mouvements parallèle : le
 * montant collecté et l'historique sont entièrement dérivés du Ledger, filtrés par le préfixe
 * déterministe de `zahab_operation_reference` propre à CETTE déclaration — le Ledger reste la
 * seule vérité financière (art. 13 du mandat).
 */
final class ProjectFundingContributionService
{
    public function __construct(private readonly ZahabWalletService $wallets) {}

    /** Wallet bénéficiaire déjà créé, sans jamais en créer un par simple consultation. */
    public function existingBeneficiaryWallet(Project $project): ?ZahabWallet
    {
        $group = $project->zumraGroup;
        if ($group === null) {
            return null;
        }

        return ZahabWallet::query()
            ->where('subject_type', ZahabWallet::SUBJECT_ZUMRA_GROUP)
            ->where('subject_reference', (string) $group->id)
            ->first();
    }

    public function collectedAmount(ProjectFunding $funding, Project $project): int
    {
        $wallet = $this->existingBeneficiaryWallet($project);
        if ($wallet === null) {
            return 0;
        }

        return (int) LedgerEntry::query()
            ->where('wallet_id', $wallet->id)
            ->where('direction', LedgerEntry::DIRECTION_CREDIT)
            ->where('zahab_operation_reference', 'like', $this->operationPrefix($funding).'%')
            ->sum('amount');
    }

    public function remainingAmount(ProjectFunding $funding, Project $project): int
    {
        return max(0, $funding->target_amount - $this->collectedAmount($funding, $project));
    }

    /**
     * Historique dérivé exclusivement des jambes DEBIT du contributeur (jamais une table à part) :
     * `subject_reference` de la jambe DEBIT EST déjà l'identité du contributeur (art. `LedgerService::
     * postWalletMovement()` — subject_type/subject_reference sont ceux du Wallet débité).
     *
     * @return Collection<int, LedgerEntry>
     */
    public function history(ProjectFunding $funding): Collection
    {
        return LedgerEntry::query()
            ->where('direction', LedgerEntry::DIRECTION_DEBIT)
            ->where('zahab_operation_reference', 'like', $this->operationPrefix($funding).'%')
            ->orderByDesc('occurred_at')
            ->get();
    }

    /**
     * Débite le Wallet Personne du contributeur, crédite le Wallet de la ZUMRA porteuse du Projet,
     * dans une transaction unique (art. 6 du mandat) : les deux jambes réussissent ensemble ou
     * échouent ensemble — `ZahabWalletService::debit()` refuse déjà tout solde insuffisant AVANT
     * qu'aucune écriture ne soit produite (art. 11).
     *
     * `$contributionToken` : jeton généré côté formulaire (un par affichage de page, jamais par
     * clic) — un double clic/retry HTTP soumet le MÊME jeton, donc la MÊME `zahab_operation_
     * reference`, donc percute l'idempotence déjà prouvée de `postWalletMovement()` (une seule
     * jambe de chaque sens, jamais deux). Une nouvelle contribution légitime (nouveau chargement
     * de page) reçoit un jeton frais — jamais confondue avec la précédente (art. 7/8/19.13).
     */
    public function contribute(ProjectFunding $funding, Project $project, string $actor, int $amount, string $contributionToken): void
    {
        abort_unless($funding->project_id === $project->id, 404);
        abort_unless($amount > 0, 422, 'Le montant doit être un entier strictement positif.');
        abort_unless($funding->currency === ZahabWalletService::CURRENCY, 422, 'Cette déclaration n’est pas exprimée en XOF : le financement ZAHAB n’est pas disponible pour elle.');
        abort_unless(in_array($project->status, [Project::STATUS_ADOPTED, Project::STATUS_IN_PROGRESS], true), 409, 'Ce projet n’est plus ouvert au financement.');
        abort_unless((bool) preg_match('/^[A-Za-z0-9\-]{8,80}$/', $contributionToken), 422, 'Jeton de contribution invalide.');

        $group = $project->zumraGroup;
        abort_if($group === null, 409, 'Ce projet n’a pas d’ancrage ZUMRA identifiable : le financement ZAHAB n’est pas disponible pour lui.');
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 409, 'Une ZUMRA suspendue ne peut pas recevoir de financement.');

        DB::transaction(function () use ($funding, $project, $actor, $amount, $contributionToken, $group): void {
            $lockedFunding = ProjectFunding::query()->whereKey($funding->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedFunding->status === ProjectFunding::STATUS_OPEN, 409, 'Cette déclaration financière n’est plus ouverte.');

            $beneficiaryWallet = $this->wallets->walletFor(ZahabWallet::SUBJECT_ZUMRA_GROUP, (string) $group->id, $actor);
            $collected = (int) LedgerEntry::query()
                ->where('wallet_id', $beneficiaryWallet->id)
                ->where('direction', LedgerEntry::DIRECTION_CREDIT)
                ->where('zahab_operation_reference', 'like', $this->operationPrefix($lockedFunding).'%')
                ->sum('amount');
            $remaining = $lockedFunding->target_amount - $collected;
            abort_if($remaining <= 0, 409, 'Cette déclaration a déjà atteint sa cible.');
            abort_if($amount > $remaining, 422, "Le montant dépasse ce qu'il reste à financer ({$remaining} ZAHAB).");

            $payerWallet = $this->wallets->walletFor(ZahabWallet::SUBJECT_PERSON, $actor, $actor);
            $operationReference = $this->operationPrefix($lockedFunding).$contributionToken;

            $this->wallets->debit($payerWallet, $amount, ZahabWalletService::REASON_PROJECT_FUNDING, $operationReference.':contributor-debit', $actor, $operationReference);
            $this->wallets->credit($beneficiaryWallet, $amount, ZahabWalletService::REASON_PROJECT_FUNDING, $operationReference.':carrier-credit', $actor, $operationReference);

            $this->event($project, 'FUNDING_CONTRIBUTION_RECEIVED', $actor, ['amount' => $amount, 'funding_id' => $lockedFunding->id]);

            if ($collected + $amount === $lockedFunding->target_amount) {
                $lockedFunding->update(['status' => ProjectFunding::STATUS_FUNDED, 'decided_by_core_reference' => $actor, 'closed_at' => now()]);
                $this->event($project, 'FUNDING_DECLARATION_FUNDED', $actor, ['funding_id' => $lockedFunding->id]);
            }
        });
    }

    /** Préfixe déterministe distinguant les jambes de CETTE déclaration de toute autre. */
    private function operationPrefix(ProjectFunding $funding): string
    {
        return 'project-funding:'.$funding->id.':';
    }

    private function event(Project $project, string $event, string $actor, array $context = []): void
    {
        ProjectEvent::query()->create(['project_id' => $project->id, 'event' => $event, 'actor_core_reference' => $actor, 'context' => $context, 'occurred_at' => now()]);
    }
}
