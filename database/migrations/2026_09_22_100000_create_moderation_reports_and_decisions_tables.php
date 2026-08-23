<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MODERATION-COMP-001 — Modération, discipline et recours (art. 19 ZUMRA-DOCTRINE-INVARIANTE.md).
 * Ce n'est pas une CAP officielle (docs/roadmap/ROADMAP-METIER-CANONIQUE.md — ROADMAP-003) : aucun
 * numéro CAP-085 n'est créé, aucune ligne CAPABILITY-INDEX/COVERAGE n'est modifiée par ce chantier.
 *
 * dg_moderation_reports — signalement transversal (target limité en V1 à CONTEXT_COMMENT,
 * MESSAGE_ENTRY, ZUMRA_MEMBERSHIP). context_type/context_reference (nullable) ne portent PAS le
 * contexte du contenu ciblé mais la portée d'autorité niveau 2 : 'ZUMRA' + id de groupe lorsque le
 * signalement relève de la gouvernance d'une ZUMRA précise ; null lorsque seule DG Afrique (niveau 3)
 * est compétente (messages directs, contenus hors ZUMRA). escalated_at retire définitivement le
 * signalement des mains du niveau 2 (art. 19 : « une ZUMRA ne peut empêcher un membre de signaler
 * directement un abus à GAMAD »).
 *
 * dg_moderation_decisions — décision disciplinaire VIVANTE (art. 19 : motif, autorité, date, éléments
 * concernés, durée éventuelle, voie de recours). Le recours n'est pas suspensif (appeal_requested_at
 * ne modifie jamais status) ; seule une décision de recours explicite (appeal_outcome) fait évoluer
 * l'état. Deux index uniques partiels Postgres empêchent une double décision active sur un même
 * signalement ou une même cible (motif déjà établi par dg_project_fundings_active_per_project_unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_moderation_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reporter_core_reference', 64)->index();
            $table->string('target_type', 24)->index();
            $table->string('target_reference', 64)->index();
            $table->string('context_type', 16)->nullable();
            $table->string('context_reference', 64)->nullable();
            $table->string('reason_code', 32)->index();
            $table->text('reason_details')->nullable();
            $table->string('status', 16)->default('PENDING')->index();
            $table->timestampTz('escalated_at')->nullable();
            $table->timestampTz('reported_at');
            $table->timestampsTz();
            $table->index(['context_type', 'context_reference', 'status']);
        });

        Schema::create('dg_moderation_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('moderation_report_id')->nullable()->constrained('dg_moderation_reports')->nullOnDelete();
            $table->string('target_type', 24)->index();
            $table->string('target_reference', 64)->index();
            $table->string('action_type', 24)->index();
            $table->string('reason_code', 32);
            $table->text('reason_details')->nullable();
            $table->string('decided_by_core_reference', 64);
            $table->unsignedTinyInteger('authority_level');
            $table->timestampTz('effective_at');
            $table->timestampTz('expires_at')->nullable();
            $table->string('status', 16)->default('ACTIVE')->index();
            $table->timestampTz('appeal_requested_at')->nullable();
            $table->text('appeal_reason')->nullable();
            $table->timestampTz('appeal_decided_at')->nullable();
            $table->string('appeal_decided_by_core_reference', 64)->nullable();
            $table->string('appeal_outcome', 16)->nullable();
            $table->text('appeal_explanation')->nullable();
            $table->timestampsTz();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX dg_moderation_decisions_active_per_report_unique '
                .'ON dg_moderation_decisions (moderation_report_id) '
                .'WHERE status = \'ACTIVE\' AND moderation_report_id IS NOT NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX dg_moderation_decisions_active_per_target_unique '
                .'ON dg_moderation_decisions (target_type, target_reference) WHERE status = \'ACTIVE\''
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_moderation_decisions');
        Schema::dropIfExists('dg_moderation_reports');
    }
};
