<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_transmissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('capability_label', 200);
            $table->string('normalized_label', 200)->index();
            $table->foreignId('catalog_item_id')->nullable()->constrained('dg_capability_catalog')->nullOnDelete();
            $table->text('learning_objective');
            $table->string('origin_type', 20)->index();
            $table->string('origin_reference', 191)->nullable();
            $table->string('context_type', 20)->nullable()->index();
            $table->string('context_reference', 191)->nullable();
            $table->string('visibility', 20)->index();
            $table->string('status', 30)->index();
            $table->text('availability_note')->nullable();
            $table->string('proposed_by_core_reference', 191)->index();
            $table->timestampTz('proposed_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('completion_summary')->nullable();
            $table->string('context_officialized_by_core_reference', 191)->nullable();
            $table->timestampTz('context_officialized_at')->nullable();
            $table->string('context_validated_by_core_reference', 191)->nullable();
            $table->timestampTz('context_validated_at')->nullable();
            $table->json('evidence_context')->nullable();
            $table->timestampsTz();

            $table->index(['context_type', 'context_reference']);
        });

        Schema::create('dg_transmission_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transmission_id')->constrained('dg_transmissions')->cascadeOnDelete();
            $table->string('core_identity_reference', 191)->index();
            $table->string('role', 20);
            $table->string('status', 20)->index();
            $table->string('invited_by_core_reference', 191)->nullable();
            $table->timestampTz('responded_at')->nullable();
            $table->timestampTz('declared_done_at')->nullable();
            $table->text('declared_done_note')->nullable();
            $table->timestampsTz();

            $table->unique(['transmission_id', 'core_identity_reference']);
        });

        Schema::create('dg_transmission_milestones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transmission_id')->constrained('dg_transmissions')->cascadeOnDelete();
            $table->string('label', 300);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_required')->default(false);
            $table->string('completed_by_core_reference', 191)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_transmission_contributions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transmission_id')->constrained('dg_transmissions')->cascadeOnDelete();
            $table->string('core_identity_reference', 191);
            $table->text('note');
            $table->json('evidence_context')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });

        Schema::create('dg_transmission_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transmission_id')->constrained('dg_transmissions')->cascadeOnDelete();
            $table->string('event', 80)->index();
            $table->string('actor_core_reference', 191);
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['transmission_id', 'occurred_at']);
        });

        Schema::create('dg_transmission_matching_hides', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transmission_id')->constrained('dg_transmissions')->cascadeOnDelete();
            $table->string('viewer_core_reference', 191);
            $table->string('subject_core_reference', 191);
            $table->timestampTz('created_at');

            $table->unique(['transmission_id', 'viewer_core_reference', 'subject_core_reference'], 'dg_transmission_matching_hides_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_transmission_matching_hides');
        Schema::dropIfExists('dg_transmission_events');
        Schema::dropIfExists('dg_transmission_contributions');
        Schema::dropIfExists('dg_transmission_milestones');
        Schema::dropIfExists('dg_transmission_participants');
        Schema::dropIfExists('dg_transmissions');
    }
};
