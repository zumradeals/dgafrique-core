<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conserve le jour du mois nominal d'une récurrence MONTHLY (ex. 31), indépendamment d'un
 * éventuel clamp subi par une occurrence intermédiaire (ex. 28 en février) — sans cette
 * ancre, un mois plus court ferait dériver la récurrence de son intention d'origine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_mission_recurrences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('monthly_anchor_day')->nullable()->after('due_offset_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('dg_mission_recurrences', function (Blueprint $table): void {
            $table->dropColumn('monthly_anchor_day');
        });
    }
};
