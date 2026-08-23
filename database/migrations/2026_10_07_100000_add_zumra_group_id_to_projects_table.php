<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROJET-ZUMRA-INVARIANT-001 — ancrage ZUMRA orthogonal à owner_type/owner_reference (autorité de
 * gouvernance) : tout Projet appartient toujours à une ZUMRA, distinctement de qui le gouverne
 * (une Personne seule ou la ZUMRA collectivement). Nullable pour compatibilité historique
 * uniquement — aucun backfill, aucune ZUMRA créée pour les Projects existants. Voir audit
 * PROJET-ZUMRA-INVARIANT-001 et docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md §32.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_projects', function (Blueprint $table): void {
            $table->foreignUuid('zumra_group_id')->nullable()->after('owner_reference')->constrained('dg_zumra_groups')->nullOnDelete();
            $table->index(['zumra_group_id']);
        });
    }

    public function down(): void
    {
        Schema::table('dg_projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('zumra_group_id');
        });
    }
};
