<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_satellites', function (Blueprint $table): void {
            $table->string('slug', 80)->nullable()->unique()->after('product_reference');
        });

        // Préserve l'URL déjà en production pour le seul satellite existant (CAP-048).
        DB::table('dg_satellites')
            ->where('product_reference', config('federation.gamadrive.product_reference', 'PRD-GAMAD-002'))
            ->update(['slug' => 'gamadrive']);
    }

    public function down(): void
    {
        Schema::table('dg_satellites', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }
};
