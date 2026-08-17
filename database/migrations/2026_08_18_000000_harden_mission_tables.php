<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrections d'intégrité référentielle demandées en revue externe sur CAP-069 :
 * - parent_mission_id et recurrence_id de dg_missions deviennent de vraies FK. La FK vers
 *   dg_mission_recurrences ne peut être ajoutée qu'ici (après coup) car dg_mission_recurrences
 *   n'existe pas encore au moment où dg_missions est créée dans la migration d'origine —
 *   dépendance circulaire résolue en deux temps plutôt qu'en abandonnant l'intégrité.
 * - dg_mission_need_links.blocker_id devient une vraie FK vers dg_mission_blockers.
 * - dg_mission_recurrences reçoit due_offset_minutes : l'échéance d'une occurrence générée
 *   est désormais relative à sa propre date d'ouverture, jamais une copie éternelle du
 *   due_at de la Mission source.
 * - CHECK (min_executors >= 1 AND (max_executors IS NULL OR max_executors >= min_executors))
 *   est ajoutée sur Postgres (moteur de production). SQLite ne permet pas d'ajouter une
 *   contrainte CHECK après coup via ALTER TABLE ; cette règle reste donc également imposée
 *   au niveau applicatif (MissionWorkflow::create()) pour rester portable en test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_mission_recurrences', function (Blueprint $table): void {
            $table->integer('due_offset_minutes')->nullable()->after('timezone');
        });

        Schema::table('dg_missions', function (Blueprint $table): void {
            $table->foreign('parent_mission_id')->references('id')->on('dg_missions')->restrictOnDelete();
            $table->foreign('recurrence_id')->references('id')->on('dg_mission_recurrences')->nullOnDelete();
        });

        Schema::table('dg_mission_need_links', function (Blueprint $table): void {
            $table->foreign('blocker_id')->references('id')->on('dg_mission_blockers')->nullOnDelete();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dg_missions ADD CONSTRAINT dg_missions_executor_bounds_check CHECK (min_executors >= 1 AND (max_executors IS NULL OR max_executors >= min_executors))');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dg_missions DROP CONSTRAINT IF EXISTS dg_missions_executor_bounds_check');
        }

        Schema::table('dg_mission_need_links', function (Blueprint $table): void {
            $table->dropForeign(['blocker_id']);
        });

        Schema::table('dg_missions', function (Blueprint $table): void {
            $table->dropForeign(['parent_mission_id']);
            $table->dropForeign(['recurrence_id']);
        });

        Schema::table('dg_mission_recurrences', function (Blueprint $table): void {
            $table->dropColumn('due_offset_minutes');
        });
    }
};
