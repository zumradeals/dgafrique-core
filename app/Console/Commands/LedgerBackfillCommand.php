<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Ledger\LedgerService;
use App\Models\ContributionPayment;
use App\Models\ZumraPayment;
use Illuminate\Console\Command;

/**
 * CAP-062 — backfill des paiements CONFIRMÉS antérieurs au déploiement du ledger. Utilise
 * exactement le même `LedgerService` que le runtime (aucune logique de posting dupliquée),
 * déterministe et rejouable à volonté : l'idempotence vient de la contrainte
 * UNIQUE(source_type, source_id), jamais d'un état de progression suivi par la commande elle-même.
 * Ne modifie jamais les paiements source. Ordre de déploiement documenté dans
 * docs/capacites/specs/CAP-062-ledger-tracabilite.md.
 */
final class LedgerBackfillCommand extends Command
{
    protected $signature = 'ledger:backfill';

    protected $description = 'Projeter dans le ledger (CAP-062) les paiements CONFIRMÉS déjà existants (CAP-061 et CAP-007B), sans jamais créer de doublon';

    public function handle(LedgerService $ledger): int
    {
        $contributionsPosted = 0;
        $contributionsSkipped = 0;
        ContributionPayment::query()->where('status', ContributionPayment::STATUS_COMPLETED)
            ->orderBy('created_at')
            ->cursor()
            ->each(function (ContributionPayment $payment) use ($ledger, &$contributionsPosted, &$contributionsSkipped): void {
                $entry = $ledger->postContributionPayment($payment);
                if ($entry?->wasRecentlyCreated) {
                    $contributionsPosted++;
                } else {
                    $contributionsSkipped++;
                }
            });

        $membershipsPosted = 0;
        $membershipsSkipped = 0;
        ZumraPayment::query()->where('status', ZumraPayment::STATUS_COMPLETED)
            ->orderBy('created_at')
            ->cursor()
            ->each(function (ZumraPayment $payment) use ($ledger, &$membershipsPosted, &$membershipsSkipped): void {
                $entry = $ledger->postMembershipPayment($payment);
                if ($entry?->wasRecentlyCreated) {
                    $membershipsPosted++;
                } else {
                    $membershipsSkipped++;
                }
            });

        $this->info("Contributions (CAP-061) : {$contributionsPosted} écriture(s) créée(s), {$contributionsSkipped} déjà présente(s).");
        $this->info("Adhésions (CAP-007B) : {$membershipsPosted} écriture(s) créée(s), {$membershipsSkipped} déjà présente(s).");

        return self::SUCCESS;
    }
}
