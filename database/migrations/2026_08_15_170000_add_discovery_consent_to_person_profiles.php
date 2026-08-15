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
            $table->uuid('discovery_reference')->nullable()->unique();
            $table->string('discovery_display_name', 120)->nullable();
            $table->text('discovery_bio')->nullable();
            $table->boolean('discovery_consent')->default(false)->index();
            $table->timestampTz('discovery_consented_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dg_person_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'discovery_reference', 'discovery_display_name', 'discovery_bio',
                'discovery_consent', 'discovery_consented_at',
            ]);
        });
    }
};
