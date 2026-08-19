<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_brain_intents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_core_reference', 120)->index();
            $table->string('title', 180)->nullable();
            $table->json('messages')->default('[]');
            $table->json('context')->default('{}');
            $table->string('status', 24)->default('DRAFT')->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_brain_intents');
    }
};
