<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('name', 140);
            $table->text('description');
            $table->string('type', 24)->index();
            $table->string('other_type_label', 140)->nullable();
            $table->string('status', 16)->default('ACTIVE')->index();
            $table->string('visibility', 16)->default('PRIVATE')->index();
            $table->string('founder_core_reference', 64)->index();
            $table->unsignedInteger('active_member_count')->default(1);
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_organization_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('dg_organizations')->cascadeOnDelete();
            $table->string('core_identity_reference', 64)->index();
            $table->string('role', 16)->index();
            $table->string('status', 24)->index();
            $table->string('entry_mode', 20);
            $table->string('initiated_by_core_reference', 64);
            $table->text('motivation')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestampTz('requested_at')->nullable();
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();
            $table->unique(['organization_id', 'core_identity_reference']);
        });

        Schema::create('dg_organization_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('dg_organizations')->cascadeOnDelete();
            $table->string('event', 48)->index();
            $table->string('actor_core_reference', 64);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_organization_events');
        Schema::dropIfExists('dg_organization_memberships');
        Schema::dropIfExists('dg_organizations');
    }
};
