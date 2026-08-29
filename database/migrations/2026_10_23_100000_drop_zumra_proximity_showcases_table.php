<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PRODUCTION-TRUTH-001 — retire la table exclusivement dédiée au décor de proximité fictif. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dg_zumra_proximity_showcases');
    }

    public function down(): void
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
};
