<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_zumra_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('name', 140);
            $table->string('slug', 170)->unique();
            $table->string('domain', 140)->index();
            $table->text('founding_objective');
            $table->string('participation_mode', 16)->index();
            $table->text('internal_charter');
            $table->string('state', 32)->default('CONSTITUTING')->index();
            $table->string('maturity', 24)->default('EMERGING')->index();
            $table->string('proposer_core_reference', 64)->index();
            $table->unsignedInteger('active_member_count')->default(1);
            $table->timestampTz('ready_at')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_zumra_group_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('zumra_group_id')->constrained('dg_zumra_groups')->cascadeOnDelete();
            $table->string('core_identity_reference', 64)->index();
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
            $table->unique(['zumra_group_id', 'core_identity_reference']);
        });

        Schema::create('dg_zumra_group_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('zumra_group_id')->constrained('dg_zumra_groups')->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('core_identity_reference', 64)->nullable()->index();
            $table->string('status', 20)->default('VACANT')->index();
            $table->string('proposed_by_core_reference', 64)->nullable();
            $table->timestampTz('proposed_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['zumra_group_id', 'role']);
        });

        Schema::create('dg_zumra_group_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('zumra_group_id')->constrained('dg_zumra_groups')->cascadeOnDelete();
            $table->string('event', 48)->index();
            $table->string('actor_core_reference', 64);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_group_events');
        Schema::dropIfExists('dg_zumra_group_roles');
        Schema::dropIfExists('dg_zumra_group_memberships');
        Schema::dropIfExists('dg_zumra_groups');
    }
};
