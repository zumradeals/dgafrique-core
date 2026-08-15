<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_autonomy_pathways', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->unique()->constrained('dg_projects')->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->string('target_form', 32);
            $table->string('other_form_label', 180)->nullable();
            $table->string('opened_by_core_reference', 64);
            $table->timestampTz('opened_at');
            $table->string('closed_by_core_reference', 64)->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_autonomy_pathways');
    }
};
