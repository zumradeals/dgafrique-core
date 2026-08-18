<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_notification_reads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('core_identity_reference', 191)->index();
            $table->string('source_type', 20)->index();
            $table->string('source_reference', 191);
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('dismissed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['core_identity_reference', 'source_type', 'source_reference'], 'dg_notification_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_notification_reads');
    }
};
