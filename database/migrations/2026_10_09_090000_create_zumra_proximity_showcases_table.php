<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UIUX-010 — vitrine de démonstration pour le bloc « Près de vous » du carrefour ZUMRA. Aucune
 * coordonnée géographique n'est collectée nulle part dans le produit aujourd'hui : cette table
 * n'invente pas un moteur de proximité, elle plante honnêtement le décor visuel validé par le
 * mandat en attendant un vrai rapprochement géographique (frontière documentée dans
 * EXPERIENCE-PRODUIT-CANONIQUE.md). `distance_label` reste un texte libre affiché tel quel —
 * jamais un calcul présenté comme réel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zumra_proximity_showcases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 140);
            $table->string('activity_label', 140);
            $table->string('distance_label', 40);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_proximity_showcases');
    }
};
