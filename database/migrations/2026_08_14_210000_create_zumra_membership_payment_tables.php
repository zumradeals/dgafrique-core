<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_zumra_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('membership_id')->constrained('dg_zumra_program_memberships')->restrictOnDelete();
            $table->string('provider', 40);
            $table->string('purpose', 40);
            $table->string('reference', 160)->unique();
            $table->string('provider_id', 160)->nullable();
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->string('environment', 20);
            $table->string('status', 24);
            $table->text('checkout_url')->nullable();
            $table->unsignedInteger('fees')->nullable();
            $table->unsignedInteger('net_amount')->nullable();
            $table->json('provider_snapshot')->nullable();
            $table->string('provider_snapshot_hash', 64)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('last_verified_at')->nullable();
            $table->timestampsTz();
            $table->index(['membership_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('dg_zumra_payment_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->unique()->constrained('dg_zumra_payments')->restrictOnDelete();
            $table->foreignUuid('membership_id')->constrained('dg_zumra_program_memberships')->restrictOnDelete();
            $table->string('number', 64)->unique();
            $table->string('core_identity_reference', 64);
            $table->string('provider', 40);
            $table->string('provider_reference', 160);
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->string('purpose', 40);
            $table->timestampTz('issued_at');
            $table->string('integrity_hash', 64)->unique();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_payment_receipts');
        Schema::dropIfExists('dg_zumra_payments');
    }
};
