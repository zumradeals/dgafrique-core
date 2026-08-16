<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_context_shares', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('source_type', 24)->index();
            $table->string('source_reference', 64)->index();
            $table->string('sharer_core_reference', 64)->index();
            $table->string('target_type', 16)->index();
            $table->string('target_reference', 64)->index();
            $table->text('context_note');
            $table->timestampTz('shared_at')->index();
            $table->timestampsTz();

            $table->index(
                ['target_type', 'target_reference', 'shared_at'],
                'dg_context_shares_target_time_idx'
            );
            $table->unique(
                ['source_type', 'source_reference', 'sharer_core_reference', 'target_type', 'target_reference'],
                'dg_context_shares_unique_delivery'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_context_shares');
    }
};
