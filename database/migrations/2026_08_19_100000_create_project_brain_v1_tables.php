<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_project_brain_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->uuid('actor_core_reference')->index();
            $table->string('title', 180)->default('Conversation principale');
            $table->timestamps();
            $table->unique(['project_id', 'actor_core_reference']);
        });

        Schema::create('dg_project_brain_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->index();
            $table->string('role', 20);
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('dg_project_brain_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->index();
            $table->uuid('project_id')->index();
            $table->uuid('actor_core_reference')->index();
            $table->string('kind', 50);
            $table->string('status', 30)->default('PENDING');
            $table->json('payload');
            $table->uuid('result_reference')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_project_brain_drafts');
        Schema::dropIfExists('dg_project_brain_messages');
        Schema::dropIfExists('dg_project_brain_conversations');
    }
};
