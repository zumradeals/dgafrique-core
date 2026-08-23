<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAP-062 — Ledger / traçabilité (art. 6.5, 23.1 ZUMRA-DOCTRINE-INVARIANTE.md). Journal financier
 * simple, immuable, additif : une écriture par paiement réellement CONFIRMÉ (jamais un wallet, un
 * solde stocké ni un moteur double-entry — aucune sortie d'argent réelle n'existe dans le dépôt).
 * dg_ledger_entries est une PROJECTION : elle ne modifie jamais dg_contribution_payments ni
 * dg_zumra_payments, qui restent seuls responsables de leur propre cycle de vie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_type', 32)->index();
            $table->uuid('source_id');
            $table->string('entry_type', 16)->default('PAYMENT');
            $table->uuid('reverses_entry_id')->nullable();
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->string('purpose_code', 40)->nullable();
            $table->char('period', 7)->nullable();
            $table->string('payer_core_reference', 64);
            $table->string('subject_type', 16);
            $table->string('subject_reference', 64);
            $table->timestampTz('occurred_at');
            $table->timestampTz('posted_at');
            $table->timestampsTz();
            // Idempotence absolue : un paiement source ne produit jamais plus d'une écriture
            // initiale, que ce soit via reconcile() ou via `ledger:backfill` rejoué à volonté.
            $table->unique(['source_type', 'source_id']);
            $table->index(['subject_type', 'subject_reference']);
            $table->index('period');
        });

        // Auto-référence : ajoutée après coup (précédent : id/primary doit exister avant qu'une
        // contrainte FK puisse la référencer dans le même lot de statements Postgres).
        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->foreign('reverses_entry_id')->references('id')->on('dg_ledger_entries')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_ledger_entries');
    }
};
