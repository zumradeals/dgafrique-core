<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ZUMRA-HUMAN-BIRTH-001 — modèle minimal pour les activités secondaires et sous-activités
 * d'une ZUMRA. `relation_to_principal` porte la filiation explicite exigée par le mandat
 * (« toute activité secondaire/sous-activité doit dériver, spécialiser ou appliquer l'activité
 * principale ») — un texte humain obligatoire, jamais une validation automatique ou une
 * taxonomie globale rigide. Aucune hiérarchie parent/enfant entre activités dérivées elles-mêmes :
 * chacune se rattache uniquement à l'activité principale de sa ZUMRA (`ZumraGroup::domain`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zumra_group_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('zumra_group_id')->constrained('dg_zumra_groups')->cascadeOnDelete();
            $table->string('label', 140);
            $table->string('normalized_label', 140)->index();
            $table->text('relation_to_principal');
            $table->string('added_by_core_reference', 64);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_group_activities');
    }
};
