<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_satellites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('product_reference', 64)->unique();
            $table->string('display_name', 120);
            $table->text('description')->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by_core_reference', 64);
            $table->timestampsTz();
        });

        // GamaDrive migré comme premier enregistrement réel, reflétant l'état actuel
        // (mêmes valeurs que config/federation.php) — jamais une donnée fictive.
        DB::table('dg_satellites')->insert([
            'id' => (string) Str::uuid(),
            'product_reference' => config('federation.gamadrive.product_reference', 'PRD-GAMAD-002'),
            'display_name' => config('federation.gamadrive.display_name', 'GamaDrive'),
            'description' => null,
            'callback_url' => config('federation.gamadrive.callback_url'),
            'is_active' => true,
            'created_by_core_reference' => 'MIGRATION-CAP-048',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_satellites');
    }
};
