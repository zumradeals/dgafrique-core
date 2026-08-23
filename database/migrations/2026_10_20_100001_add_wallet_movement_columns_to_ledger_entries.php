<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ZAHAB-001 — Phase A (audit CORE-COMPLETION-001 / docs/audits/CORE-COMPLETION-001-ZAHAB.md §C) a
 * conclu que le Wallet ZAHAB peut se construire AU-DESSUS du Ledger existant sans y créer une
 * deuxième vérité : deux colonnes suffisent, jamais une deuxième table de mouvements.
 *
 * - `direction` : jusqu'ici dg_ledger_entries ne projetait que des paiements REÇUS (CAP-061/007B),
 *   toujours un crédit implicite pour le sujet. Un Wallet a aussi besoin de débits (dépense de
 *   ZAHAB) : cette colonne rend le sens explicite. Toute écriture déjà existante (CONTRIBUTION_
 *   PAYMENT / MEMBERSHIP_PAYMENT) est un CREDIT — la valeur par défaut le fixe sans backfill
 *   applicatif séparé.
 * - `wallet_id` : référence NULLABLE vers dg_zahab_wallets, renseignée uniquement pour les
 *   écritures qu'un Wallet ZAHAB a produites (source_type = ZAHAB_WALLET_MOVEMENT). Les écritures
 *   CAP-061/007B existantes n'en ont aucune — aucun Wallet ne leur correspond, et ce chantier ne
 *   les raccorde pas rétroactivement (ce sera CONTRIBUTION-ZAHAB-001).
 *
 * Ni l'une ni l'autre ne stocke un solde : `tests/Feature/LedgerTest.php::
 * test_ledger_table_has_no_wallet_balance_or_credit_column()` reste vrai à l'identique après cette
 * migration — la colonne interdite est un solde/crédit numérique, pas une relation ni un sens de
 * mouvement. Le solde d'un Wallet reste 100% dérivé, jamais stocké ici ni ailleurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->string('direction', 6)->default('CREDIT')->after('entry_type');
            $table->uuid('wallet_id')->nullable()->after('subject_reference');
        });

        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->foreign('wallet_id')->references('id')->on('dg_zahab_wallets')->restrictOnDelete();
            $table->index('wallet_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE dg_ledger_entries ADD CONSTRAINT dg_ledger_entries_direction_check '
                ."CHECK (direction IN ('CREDIT', 'DEBIT'))"
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dg_ledger_entries DROP CONSTRAINT IF EXISTS dg_ledger_entries_direction_check');
        }

        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['wallet_id']);
            $table->dropColumn(['direction', 'wallet_id']);
        });
    }
};
