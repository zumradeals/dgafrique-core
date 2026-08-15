<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_zumra_cards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('membership_id')->constrained('dg_zumra_program_memberships')->restrictOnDelete();
            $table->string('public_reference', 40)->unique();
            $table->string('core_identity_reference', 64)->index();
            $table->string('holder_label', 200);
            $table->string('country_code', 8)->nullable();
            $table->string('status', 24)->default('ACTIVE')->index();
            $table->timestampTz('issued_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revocation_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['membership_id', 'status', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_zumra_cards');
    }
};
