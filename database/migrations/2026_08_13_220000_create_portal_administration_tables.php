<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_administrators', function (Blueprint $table): void {
            $table->string('core_identity_reference', 64)->primary();
            $table->string('granted_by_core_reference', 64)->nullable();
            $table->timestampsTz();
        });

        Schema::create('portal_settings', function (Blueprint $table): void {
            $table->string('key', 160)->primary();
            $table->json('value');
            $table->string('updated_by_core_reference', 64)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_settings');
        Schema::dropIfExists('portal_administrators');
    }
};
