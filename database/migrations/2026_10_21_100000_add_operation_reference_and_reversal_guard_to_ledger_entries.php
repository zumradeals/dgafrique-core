<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ZAHAB-001, passe corrective après contre-validation — corrige deux défauts financiers
 * bloquants sans toucher aux colonnes ni à la sémantique déjà utilisées par CAP-061/CAP-062
 * (`source_type`/`source_id` restent l'identité idempotente de CHAQUE mouvement/jambe, inchangée) :
 *
 * - `zahab_operation_reference` : colonne additive, nullable, qui distingue enfin l'OPÉRATION
 *   métier (ex. "contribution:123", pourra un jour relier plusieurs jambes d'une même opération —
 *   débit payeur + crédit bénéficiaire) de l'identité de CHAQUE mouvement individuel
 *   (`source_id`, inchangé). Aucune opération multi-jambes n'est implémentée dans cette passe :
 *   seule la colonne qui la rendra possible plus tard est ajoutée.
 * - Un index UNIQUE partiel sur `reverses_entry_id` (Postgres uniquement, `WHERE reverses_entry_id
 *   IS NOT NULL`) : garantit qu'une écriture d'origine ne peut jamais être compensée deux fois,
 *   même sous deux transactions concurrentes utilisant deux clés d'idempotence différentes — la
 *   vérification applicative (ZahabWalletService::reverse(), sous verrou Wallet) est la première
 *   ligne de défense, cette contrainte DB est le dernier rempart contre une course qui la
 *   contournerait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->string('zahab_operation_reference', 160)->nullable()->after('wallet_id');
            $table->index('zahab_operation_reference');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX dg_ledger_entries_reverses_entry_id_unique '
                .'ON dg_ledger_entries (reverses_entry_id) '
                .'WHERE reverses_entry_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS dg_ledger_entries_reverses_entry_id_unique');
        }

        Schema::table('dg_ledger_entries', function (Blueprint $table): void {
            $table->dropColumn('zahab_operation_reference');
        });
    }
};
