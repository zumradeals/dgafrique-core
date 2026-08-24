<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ZAHAB-002 — trace CHAQUE tentative d'acquisition de ZAHAB via un paiement externe GeniusPay.
 * Patron identique à `dg_contribution_payments`/`dg_zumra_payments` (mêmes noms de colonnes, même
 * vocabulaire de statuts) : ce n'est jamais qu'un paiement de plus au sens de GeniusPay, réutilisant
 * `GeniusPayClient::createContributionPayment()`/`payment()` (déjà génériques, jamais un deuxième
 * client). `amount` représente indifféremment le montant FCFA payé ET le montant ZAHAB à créditer —
 * la parité actuelle (1 ZAHAB = 1 FCFA, docs/architecture/ARCHITECTURE-PRODUIT-V2.md §15) rend une
 * deuxième colonne inutile ; si la parité change un jour, ce sera un arbitrage documenté séparé.
 *
 * Cette table ne stocke JAMAIS un solde : le crédit réel est une écriture `dg_ledger_entries`
 * (CAP-062), postée par `ZahabWalletService::credit()` uniquement après confirmation server-to-
 * server (`credited_at`, distinct de `completed_at` — le moment où GeniusPay confirme le paiement
 * externe n'est pas forcément identique en pratique à celui où le crédit Wallet est effectivement
 * posté, même si les deux surviennent dans la même transaction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zahab_acquisitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('person_core_reference', 64);
            $table->string('provider', 40);
            $table->string('reference', 160)->unique();
            $table->string('provider_id', 160)->nullable();
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->string('environment', 20);
            $table->string('status', 24);
            $table->text('checkout_url')->nullable();
            $table->unsignedInteger('fees')->nullable();
            $table->unsignedInteger('net_amount')->nullable();
            $table->json('provider_snapshot')->nullable();
            $table->string('provider_snapshot_hash', 64)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('credited_at')->nullable();
            $table->timestampTz('last_verified_at')->nullable();
            $table->timestampsTz();
            $table->index(['person_core_reference', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zahab_acquisitions');
    }
};
