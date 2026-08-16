<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_context_comments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('context_type', 32)->index();
            $table->string('context_reference', 64)->index();
            $table->string('author_core_reference', 64)->index();
            $table->string('purpose', 24)->index();
            $table->text('body');
            $table->timestampTz('posted_at')->index();
            $table->timestampsTz();
            $table->index(['context_type', 'context_reference', 'posted_at'], 'dg_context_comments_context_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_context_comments');
    }
};
