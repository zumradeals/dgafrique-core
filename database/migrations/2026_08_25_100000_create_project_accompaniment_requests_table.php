<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_accompaniment_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_accompaniment_id')->constrained('dg_project_accompaniments')->cascadeOnDelete();
            $table->string('requested_by_core_reference', 64);
            $table->string('subject', 180);
            $table->text('description');
            $table->string('status', 20)->index();
            $table->timestampTz('requested_at');
            $table->string('acknowledged_by_core_reference', 64)->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->string('closed_by_core_reference', 64)->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_accompaniment_requests');
    }
};
