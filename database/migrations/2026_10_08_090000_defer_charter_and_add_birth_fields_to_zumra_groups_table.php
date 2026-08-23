<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ZUMRA-HUMAN-BIRTH-001 — la naissance d'une ZUMRA doit rester bien plus légère que sa
 * structuration : `internal_charter` devient différable après la naissance (mini-audit confirmé :
 * la doctrine ne l'exige qu'à l'étape READY — ZUMRA-DOCTRINE-INVARIANTE.md §10 — jamais à la
 * création — §7 ; `evaluateStructuralReadiness()` traite déjà une charte vide comme non
 * satisfaite, aucune nouvelle logique n'est nécessaire). Aucune ZUMRA existante n'est affectée :
 * toutes ont déjà une charte non vide, cette contrainte n'était retirée que pour l'avenir.
 *
 * `welcome_capacity` et `location` sont de nouveaux signaux humains, tous deux nullable et
 * différables, jamais rétro-remplis pour les ZUMRA historiques.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_zumra_groups', function (Blueprint $table): void {
            $table->text('internal_charter')->nullable()->change();
            $table->string('welcome_capacity', 24)->nullable()->after('participation_mode');
            $table->string('location', 160)->nullable()->after('welcome_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('dg_zumra_groups', function (Blueprint $table): void {
            $table->dropColumn(['welcome_capacity', 'location']);
            $table->text('internal_charter')->nullable(false)->change();
        });
    }
};
