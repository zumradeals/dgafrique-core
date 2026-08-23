<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_project_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_core_reference', 64)->index();
            $table->string('status', 16)->index();
            $table->string('current_step', 32);
            $table->json('payload')->default('{}');
            $table->foreignUuid('project_id')->nullable()->constrained('dg_projects')->nullOnDelete();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('abandoned_at')->nullable();
            $table->timestampsTz();

            $table->index(['actor_core_reference', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_drafts');
    }
};
