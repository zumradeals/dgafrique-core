<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_person_profiles', function (Blueprint $table): void {
            $table->json('transmission_offers')->default('[]');
            $table->json('experience_highlights')->default('[]');
            $table->json('experience_proofs')->default('[]');
            $table->json('declared_needs')->default('[]');
            $table->json('collaboration_preferences')->default('[]');
        });
    }

    public function down(): void
    {
        Schema::table('dg_person_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'transmission_offers',
                'experience_highlights',
                'experience_proofs',
                'declared_needs',
                'collaboration_preferences',
            ]);
        });
    }
};
