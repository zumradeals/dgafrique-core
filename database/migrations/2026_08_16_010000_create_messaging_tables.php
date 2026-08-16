<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_message_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('kind', 16)->index();
            $table->string('subject', 180)->nullable();
            $table->string('context_type', 24)->nullable()->index();
            $table->string('context_reference', 64)->nullable()->index();
            $table->string('created_by_core_reference', 64)->index();
            $table->char('dedupe_key', 64)->unique();
            $table->timestampTz('last_message_at')->nullable()->index();
            $table->timestampsTz();
            $table->index(['context_type', 'context_reference']);
        });

        Schema::create('dg_message_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('dg_message_conversations')->cascadeOnDelete();
            $table->string('core_identity_reference', 64)->index();
            $table->timestampTz('joined_at');
            $table->timestampTz('last_read_at')->nullable();
            $table->timestampsTz();
            $table->unique(['conversation_id', 'core_identity_reference']);
            $table->index(['core_identity_reference', 'last_read_at']);
        });

        Schema::create('dg_message_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('dg_message_conversations')->cascadeOnDelete();
            $table->string('sender_core_reference', 64)->index();
            $table->text('body');
            $table->timestampTz('sent_at')->index();
            $table->timestampsTz();
            $table->index(['conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_message_entries');
        Schema::dropIfExists('dg_message_participants');
        Schema::dropIfExists('dg_message_conversations');
    }
};
