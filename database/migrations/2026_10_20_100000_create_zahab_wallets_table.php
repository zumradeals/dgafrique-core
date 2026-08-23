<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ZAHAB-001 — Wallet ZAHAB (arbitrage produit : 1 ZAHAB = 1 FCFA, addendum daté dans
 * docs/architecture/ARCHITECTURE-PRODUIT-V2.md §8). Cette table porte UNIQUEMENT l'identité du
 * sujet propriétaire (Personne/ZUMRA/Organisation) — jamais un solde stocké : `App\Application\
 * Zahab\ZahabWalletService::balance()` le recalcule toujours depuis dg_ledger_entries, seule
 * vérité des mouvements (CAP-062). Un sujet ne possède jamais plus d'un Wallet actif — contrainte
 * UNIQUE(subject_type, subject_reference), même patron d'idempotence que dg_contributions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zahab_wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 16)->index();
            $table->string('subject_reference', 64)->index();
            $table->string('created_by_core_reference', 64);
            $table->timestampsTz();
            $table->unique(['subject_type', 'subject_reference']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE dg_zahab_wallets ADD CONSTRAINT dg_zahab_wallets_subject_type_check '
                ."CHECK (subject_type IN ('PERSON', 'ZUMRA_GROUP', 'ORGANIZATION'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zahab_wallets');
    }
};
