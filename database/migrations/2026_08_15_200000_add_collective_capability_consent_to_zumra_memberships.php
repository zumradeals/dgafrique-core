<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_zumra_group_memberships', function (Blueprint $table): void {
            $table->boolean('collective_capability_consent')->default(false)->index();
            $table->timestampTz('collective_capability_consented_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dg_zumra_group_memberships', function (Blueprint $table): void {
            $table->dropColumn(['collective_capability_consent', 'collective_capability_consented_at']);
        });
    }
};
