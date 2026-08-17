<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_proofs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('owner_type', 20)->index();
            $table->string('owner_reference', 191)->index();
            $table->string('title', 200);
            $table->text('description');
            $table->string('capability_label', 200)->nullable();
            $table->string('normalized_label', 200)->nullable()->index();
            $table->foreignId('catalog_item_id')->nullable()->constrained('dg_capability_catalog')->nullOnDelete();
            $table->string('origin_type', 20)->index();
            $table->string('origin_reference', 191)->nullable();
            $table->timestampTz('occurred_at');
            $table->string('submitted_by_core_reference', 191)->index();
            $table->timestampTz('submitted_at');
            $table->string('visibility', 20)->index();
            $table->string('status', 20)->index();
            $table->string('context_acknowledged_by_core_reference', 191)->nullable();
            $table->timestampTz('context_acknowledged_at')->nullable();
            $table->string('disputed_by_core_reference', 191)->nullable();
            $table->timestampTz('disputed_at')->nullable();
            $table->text('dispute_note')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['origin_type', 'origin_reference']);
        });

        Schema::create('dg_proof_references', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('proof_id')->constrained('dg_proofs')->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('value');
            $table->string('label', 200)->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_proof_witnesses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('proof_id')->constrained('dg_proofs')->cascadeOnDelete();
            $table->string('core_identity_reference', 191)->index();
            $table->string('status', 20)->index();
            $table->string('invited_by_core_reference', 191);
            $table->timestampTz('responded_at')->nullable();
            $table->timestampsTz();

            $table->unique(['proof_id', 'core_identity_reference']);
        });

        Schema::create('dg_proof_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('proof_id')->constrained('dg_proofs')->cascadeOnDelete();
            $table->string('event', 80)->index();
            $table->string('actor_core_reference', 191);
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['proof_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_proof_events');
        Schema::dropIfExists('dg_proof_witnesses');
        Schema::dropIfExists('dg_proof_references');
        Schema::dropIfExists('dg_proofs');
    }
};
