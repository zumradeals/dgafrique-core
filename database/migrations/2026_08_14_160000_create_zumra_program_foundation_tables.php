<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zumra_charters', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 40)->unique();
            $table->string('title', 200);
            $table->text('body');
            $table->string('content_hash', 64)->unique();
            $table->string('status', 24)->default('PUBLISHED')->index();
            $table->timestampTz('published_at');
            $table->string('published_by_core_reference', 64)->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_zumra_program_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('core_identity_reference', 64)->unique();
            $table->string('status', 32)->default('PENDING_PAYMENT')->index();
            $table->foreignId('accepted_charter_id')->constrained('dg_zumra_charters');
            $table->string('accepted_charter_version', 40);
            $table->string('accepted_charter_hash', 64);
            $table->timestampTz('charter_accepted_at');
            $table->timestampTz('submitted_at');
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_zumra_program_membership_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('membership_id')->constrained('dg_zumra_program_memberships')->cascadeOnDelete();
            $table->string('event', 40)->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('actor_core_reference', 64);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_program_membership_events');
        Schema::dropIfExists('dg_zumra_program_memberships');
        Schema::dropIfExists('dg_zumra_charters');
    }
};
