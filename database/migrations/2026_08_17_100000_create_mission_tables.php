<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_missions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('context_type', 40)->index();
            $table->string('context_reference', 191)->index();
            $table->uuid('parent_mission_id')->nullable()->index();
            $table->uuid('recurrence_id')->nullable()->index();
            $table->string('occurrence_key', 100)->nullable()->index();
            $table->string('created_by_core_reference', 191)->index();
            $table->string('title', 180);
            $table->text('description');
            $table->text('expected_result')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->string('participation_mode', 20)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('visibility', 20)->index();
            $table->string('status', 30)->index();
            $table->unsignedSmallInteger('min_executors')->default(1);
            $table->unsignedSmallInteger('max_executors')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('due_at')->nullable()->index();
            $table->timestampTz('proposed_at')->nullable();
            $table->timestampTz('officialized_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['context_type', 'context_reference']);
            $table->unique(['recurrence_id', 'occurrence_key']);
        });

        Schema::create('dg_mission_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('core_identity_reference', 191)->index();
            $table->string('role', 30);
            $table->string('status', 30)->index();
            $table->string('initiated_by_core_reference', 191);
            $table->string('accepted_by_core_reference', 191)->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('offered_at')->nullable();
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->timestampTz('removed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['mission_id', 'core_identity_reference']);
        });

        Schema::create('dg_mission_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('event', 80)->index();
            $table->string('actor_core_reference', 191);
            $table->string('subject_type', 40)->default('MISSION');
            $table->string('subject_reference', 191)->nullable();
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['mission_id', 'occurred_at']);
        });

        Schema::create('dg_mission_checklist_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('label', 300);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_required')->default(false);
            $table->string('completed_by_core_reference', 191)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_mission_capability_requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('dg_capability_catalog')->nullOnDelete();
            $table->string('label', 200);
            $table->string('normalized_label', 200);
            $table->string('requirement_level', 30)->default('REQUIRED');
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->text('context')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_mission_resource_requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('label', 200);
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit', 40)->nullable();
            $table->boolean('is_required')->default(true);
            $table->text('context')->nullable();
            $table->string('external_reference', 191)->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_mission_blockers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->text('description');
            $table->string('opened_by_core_reference', 191);
            $table->timestampTz('opened_at');
            $table->string('resolved_by_core_reference', 191)->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestampsTz();

            $table->index(['mission_id', 'resolved_at']);
        });

        Schema::create('dg_mission_dependencies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->foreignUuid('depends_on_mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('created_by_core_reference', 191);
            $table->timestampsTz();

            $table->unique(['mission_id', 'depends_on_mission_id']);
        });

        Schema::create('dg_mission_contributions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->foreignUuid('assignment_id')->constrained('dg_mission_assignments')->cascadeOnDelete();
            $table->text('summary');
            $table->json('evidence_context')->nullable();
            $table->timestampTz('submitted_at');
            $table->timestampsTz();
        });

        Schema::create('dg_mission_submissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('result_summary');
            $table->string('submitted_by_core_reference', 191);
            $table->timestampTz('submitted_at');
            $table->string('decision', 30)->nullable();
            $table->string('decided_by_core_reference', 191)->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->unique(['mission_id', 'version']);
        });

        Schema::create('dg_mission_recurrences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_mission_id')->constrained('dg_missions');
            $table->text('rrule');
            $table->string('timezone', 80);
            $table->boolean('is_active')->default(true);
            $table->string('status', 20)->default('ACTIVE')->index();
            $table->timestampTz('next_occurrence_at')->nullable();
            $table->timestampTz('last_generated_at')->nullable();
            $table->timestampTz('last_error_at')->nullable();
            $table->string('last_error_code', 80)->nullable();
            $table->string('created_by_core_reference', 191);
            $table->timestampsTz();
        });

        Schema::create('dg_mission_need_links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->uuid('blocker_id')->nullable();
            $table->foreignUuid('need_id')->constrained('dg_needs');
            $table->string('created_by_core_reference', 191);
            $table->timestampTz('created_at');
        });

        Schema::create('dg_mission_matching_hides', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('mission_id')->constrained('dg_missions')->cascadeOnDelete();
            $table->string('viewer_core_reference', 191);
            $table->string('subject_core_reference', 191);
            $table->timestampTz('created_at');

            $table->unique(['mission_id', 'viewer_core_reference', 'subject_core_reference'], 'dg_mission_matching_hides_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_mission_matching_hides');
        Schema::dropIfExists('dg_mission_need_links');
        Schema::dropIfExists('dg_mission_recurrences');
        Schema::dropIfExists('dg_mission_submissions');
        Schema::dropIfExists('dg_mission_contributions');
        Schema::dropIfExists('dg_mission_dependencies');
        Schema::dropIfExists('dg_mission_blockers');
        Schema::dropIfExists('dg_mission_resource_requirements');
        Schema::dropIfExists('dg_mission_capability_requirements');
        Schema::dropIfExists('dg_mission_checklist_items');
        Schema::dropIfExists('dg_mission_events');
        Schema::dropIfExists('dg_mission_assignments');
        Schema::dropIfExists('dg_missions');
    }
};
