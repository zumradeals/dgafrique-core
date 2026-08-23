<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CAP-063 — Financement de projet, V1 strictement DÉCLARATIVE (art. 15.3 ZUMRA-DOCTRINE-INVARIANTE.md :
 * un paiement réel exige budget, gouvernance financière et règles de décaissement, absents du
 * runtime — voir docs/capacites/specs/CAP-063-financement-projet.md). dg_project_fundings décrit un
 * BESOIN financier déclaré par un Projet, jamais un mouvement d'argent : aucun montant collecté,
 * aucun solde, aucune écriture ledger, aucun appel GeniusPay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_project_fundings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete();
            $table->string('status', 16)->index();
            $table->unsignedInteger('target_amount');
            $table->char('currency', 3);
            $table->text('purpose');
            $table->text('intended_use');
            $table->text('conditions')->nullable();
            $table->string('author_core_reference', 64);
            $table->string('decided_by_core_reference', 64)->nullable();
            $table->text('closing_note')->nullable();
            $table->timestampTz('opened_at');
            $table->timestampTz('closed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
        });

        // Une seule déclaration active (OPEN) à la fois par Projet — un Projet peut en revanche
        // déclarer une nouvelle demande après clôture/annulation d'une précédente. Index unique
        // partiel Postgres, motif déjà établi (2026_09_01_100000_create_contribution_tables.php) ;
        // doublé par le verrouillage applicatif (ProjectFundingService), portable en test.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX dg_project_fundings_active_per_project_unique '
                .'ON dg_project_fundings (project_id) WHERE status = \'OPEN\''
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_fundings');
    }
};
