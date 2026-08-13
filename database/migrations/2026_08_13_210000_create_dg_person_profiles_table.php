<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_person_profiles', function (Blueprint $table): void {
            $table->string('core_identity_reference', 64)->primary();
            $table->string('country_code', 2)->nullable();
            $table->string('city', 160)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('current_activity', 256)->nullable();
            $table->string('education_level', 160)->nullable();
            $table->json('existing_skills')->default('[]');
            $table->boolean('starts_without_skill')->default(false);
            $table->json('learning_goals')->default('[]');
            $table->json('interest_domains')->default('[]');
            $table->json('intentions')->default('[]');
            $table->string('participation_mode', 80)->nullable();
            $table->boolean('orientation_consent')->default(false);
            $table->timestampTz('orientation_consented_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_person_profiles');
    }
};
