<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_team_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete();
            $table->string('core_identity_reference', 64)->index();
            $table->string('role', 80)->nullable();
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
            $table->unique(['project_id', 'core_identity_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_team_members');
    }
};
