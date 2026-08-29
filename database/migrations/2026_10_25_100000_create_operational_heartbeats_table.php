<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_operational_heartbeats', function (Blueprint $table): void {
            $table->string('name', 80)->primary();
            $table->string('source', 120);
            $table->timestampTz('last_succeeded_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_operational_heartbeats');
    }
};
