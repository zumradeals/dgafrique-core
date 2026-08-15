<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_projects', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('public_reference')->unique();
            $table->string('owner_type', 16)->index(); $table->string('owner_reference', 64)->index();
            $table->string('initiator_core_reference', 64)->index(); $table->foreignUuid('source_need_id')->nullable()->constrained('dg_needs')->nullOnDelete();
            $table->string('name', 180); $table->text('summary'); $table->text('problem'); $table->text('proposed_solution'); $table->text('beneficiaries');
            $table->string('domain', 120)->index(); $table->string('participation_mode', 20); $table->string('location', 160)->nullable();
            $table->json('objectives')->default('[]'); $table->json('required_capabilities')->default('[]'); $table->json('required_resources')->default('[]'); $table->json('risks')->default('[]');
            $table->string('property_regime', 32)->index(); $table->string('visibility', 20)->index(); $table->string('status', 24)->index(); $table->string('maturity', 24)->index();
            $table->string('decided_by_core_reference', 64)->nullable(); $table->timestampTz('adopted_at')->nullable(); $table->timestampTz('started_at')->nullable(); $table->timestampTz('completed_at')->nullable(); $table->timestampTz('archived_at')->nullable(); $table->timestampsTz();
            $table->index(['owner_type', 'owner_reference', 'status']);
        });
        Schema::create('dg_project_milestones', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete(); $table->string('title', 180); $table->unsignedSmallInteger('position'); $table->string('status', 20)->default('PLANNED'); $table->timestampTz('completed_at')->nullable(); $table->timestampsTz(); $table->unique(['project_id', 'position']);
        });
        Schema::create('dg_project_events', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete(); $table->string('event', 48)->index(); $table->string('actor_core_reference', 64); $table->json('context')->default('{}'); $table->timestampTz('occurred_at'); $table->timestampsTz();
        });
    }
    public function down(): void { Schema::dropIfExists('dg_project_events'); Schema::dropIfExists('dg_project_milestones'); Schema::dropIfExists('dg_projects'); }
};
