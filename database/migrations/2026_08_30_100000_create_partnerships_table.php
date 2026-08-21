<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_partnerships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('provider_type', 16)->index();
            $table->string('provider_reference', 64)->index();
            $table->string('context_type', 16)->index();
            $table->string('context_reference', 64)->index();
            $table->foreignUuid('capability_statement_id')->nullable()->constrained('dg_capability_statements')->nullOnDelete();
            $table->string('capability_label', 200);
            $table->text('description')->nullable();
            $table->string('status', 16)->default('PROPOSED')->index();
            $table->string('visibility', 16)->default('PRIVATE')->index();
            $table->string('initiated_by_core_reference', 64)->index();
            $table->string('decided_by_core_reference', 64)->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->string('ended_by_core_reference', 64)->nullable();
            $table->timestampTz('paused_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->index(['context_type', 'context_reference']);
            $table->index(['provider_type', 'provider_reference']);
        });

        Schema::create('dg_partnership_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('partnership_id')->constrained('dg_partnerships')->cascadeOnDelete();
            $table->string('event', 48)->index();
            $table->string('actor_core_reference', 64);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_partnership_events');
        Schema::dropIfExists('dg_partnerships');
    }
};
