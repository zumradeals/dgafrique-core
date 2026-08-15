<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_accompaniments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->unique()->constrained('dg_projects')->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->string('activated_by_core_reference', 64);
            $table->timestampTz('activated_at');
            $table->string('ended_by_core_reference', 64)->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_project_accompaniment_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_accompaniment_id')->constrained('dg_project_accompaniments')->cascadeOnDelete();
            $table->string('action_type', 50)->index();
            $table->string('delivery_source', 20)->index();
            $table->string('provider_label', 180);
            $table->text('summary');
            $table->text('next_step')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->string('recorded_by_core_reference', 64);
            $table->timestampsTz();

            $table->index(['project_accompaniment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_accompaniment_actions');
        Schema::dropIfExists('dg_project_accompaniments');
    }
};
