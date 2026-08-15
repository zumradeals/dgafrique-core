<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_needs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('owner_type', 16)->index();
            $table->string('owner_reference', 64)->index();
            $table->string('author_core_reference', 64)->index();
            $table->string('title', 180);
            $table->text('context');
            $table->string('category', 40)->index();
            $table->string('capability_label', 200)->nullable()->index();
            $table->string('collaboration_mode', 24)->index();
            $table->string('location', 160)->nullable();
            $table->string('visibility', 20)->index();
            $table->string('status', 24)->index();
            $table->string('decided_by_core_reference', 64)->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->index(['owner_type', 'owner_reference', 'status']);
        });

        Schema::create('dg_need_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('need_id')->constrained('dg_needs')->cascadeOnDelete();
            $table->string('event', 40)->index();
            $table->string('actor_core_reference', 64);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_need_events');
        Schema::dropIfExists('dg_needs');
    }
};
