<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CAP-067 — une Organisation peut désormais déclarer explicitement ses
 * propres capacités, en réutilisant le moteur de capacités existant
 * (CAP-016) plutôt qu'un second système parallèle. `holder_type` distingue
 * un porteur `PERSON` (comportement inchangé, `core_identity_reference`
 * toujours renseignée) d'un porteur `ORGANIZATION` (`organization_id`
 * renseigné, `core_identity_reference` toujours `NULL`) — jamais les deux à
 * la fois, jamais une capacité sans porteur.
 *
 * `core_identity_reference` devient nullable pour permettre les lignes
 * `ORGANIZATION` ; la contrainte d'unicité existante
 * (`core_identity_reference`, `kind`, `normalized_label`) reste intacte pour
 * les lignes `PERSON` (PostgreSQL ne considère jamais deux `NULL` comme
 * égaux, donc les lignes `ORGANIZATION` n'entrent jamais en conflit avec
 * elle). Un second index unique partiel couvre symétriquement les lignes
 * `ORGANIZATION`.
 *
 * Aucune ligne existante n'est modifiée : toutes restent `PERSON`, exactement
 * comme avant ce chantier — aucun consommateur existant (moteurs de
 * matching, recommandations, profil) n'a besoin d'être touché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_capability_statements', function (Blueprint $table): void {
            $table->string('holder_type', 16)->default('PERSON')->index()->after('id');
            $table->string('core_identity_reference', 64)->nullable()->change();
            $table->foreignUuid('organization_id')->nullable()->after('core_identity_reference')
                ->constrained('dg_organizations')->cascadeOnDelete();
        });

        DB::statement(
            'CREATE UNIQUE INDEX dg_capability_statement_organization_kind_label_unique '
            .'ON dg_capability_statements (organization_id, kind, normalized_label) '
            ."WHERE holder_type = 'ORGANIZATION'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS dg_capability_statement_organization_kind_label_unique');

        Schema::table('dg_capability_statements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('holder_type');
            $table->string('core_identity_reference', 64)->nullable(false)->change();
        });
    }
};
