<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * CAP-061 — Contributions financières (art. 6 ZUMRA-DOCTRINE-INVARIANTE.md). Nouvelles tables
 * dédiées (Architecture A de la Phase A) : dg_zumra_payments/dg_zumra_payment_receipts (CAP-007B)
 * ne sont ni modifiées ni polymorphisées. dg_contributions porte l'ENGAGEMENT (jamais un
 * paiement) ; dg_contribution_payments porte chaque TENTATIVE mensuelle, jamais confondue avec
 * l'engagement. Les finalités (art. 6.5, « configurables, versionnées, auditées ») vivent dans
 * une vraie table, jamais un enum PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_contribution_purposes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('label', 160);
            $table->string('status', 16)->default('ACTIVE')->index();
            $table->string('created_by_core_reference', 64);
            $table->string('retired_by_core_reference', 64)->nullable();
            $table->timestampTz('retired_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('dg_contributions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('type', 16)->index();
            $table->string('subject_type', 16)->index();
            $table->string('subject_reference', 64)->index();
            $table->string('status', 16)->index();
            $table->string('initiated_by_core_reference', 64);
            $table->string('proposed_by_core_reference', 64)->nullable();
            $table->string('proposed_role', 24)->nullable();
            $table->timestampTz('proposed_at')->nullable();
            $table->string('approved_by_core_reference', 64)->nullable();
            $table->string('approved_role', 24)->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('paused_at')->nullable();
            $table->timestampTz('resumed_at')->nullable();
            $table->timestampTz('stopped_at')->nullable();
            $table->timestampsTz();
            // Un seul engagement, à vie, par sujet et par type : STOPPED se réactive (resume()),
            // il ne se recrée jamais en doublon (aucune perte d'historique possible).
            $table->unique(['type', 'subject_type', 'subject_reference']);
        });

        Schema::create('dg_contribution_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contribution_id')->constrained('dg_contributions')->restrictOnDelete();
            $table->char('period', 7);
            $table->foreignUuid('purpose_id')->constrained('dg_contribution_purposes')->restrictOnDelete();
            $table->string('initiated_by_core_reference', 64);
            $table->string('provider', 40);
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
            $table->index(['contribution_id', 'period']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('dg_contribution_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->unique()->constrained('dg_contribution_payments')->restrictOnDelete();
            $table->foreignUuid('contribution_id')->constrained('dg_contributions')->restrictOnDelete();
            $table->string('number', 64)->unique();
            $table->string('core_identity_reference', 64);
            $table->string('subject_type', 16);
            $table->string('subject_reference', 64);
            $table->string('provider', 40);
            $table->string('provider_reference', 160);
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->char('period', 7);
            $table->string('purpose_code', 40);
            $table->timestampTz('issued_at');
            $table->string('integrity_hash', 64)->unique();
            $table->timestampsTz();
        });

        Schema::create('dg_contribution_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contribution_id')->constrained('dg_contributions')->cascadeOnDelete();
            $table->string('event', 48)->index();
            $table->string('actor_core_reference', 64);
            $table->json('context')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
        });

        // Invariant réel (jamais un simple UNIQUE naïf) : jamais deux paiements actifs ou
        // confirmés concurrents pour le même engagement/période ; FAILED/CANCELLED autorise une
        // nouvelle tentative. Index unique partiel, patron déjà établi dans ce dépôt
        // (2026_08_18_000000_harden_mission_tables.php) pour les contraintes Postgres avancées ;
        // portable en test car doublé par le verrouillage applicatif (ContributionService).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX dg_contribution_payments_active_period_unique '
                .'ON dg_contribution_payments (contribution_id, period) '
                ."WHERE status IN ('PENDING', 'PROCESSING', 'COMPLETED')"
            );
        }

        // Codes canoniques de l'art. 6.5, migrés comme données réelles (patron déjà établi par
        // 2026_08_26_100000_create_satellites_table.php) — jamais une donnée fictive.
        $now = now();
        DB::table('dg_contribution_purposes')->insert(array_map(static fn (array $purpose): array => [
            'id' => (string) Str::uuid(),
            'code' => $purpose[0],
            'label' => $purpose[1],
            'status' => 'ACTIVE',
            'created_by_core_reference' => 'MIGRATION-CAP-061',
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            ['ECOSYSTEM_SUSTAINABILITY', 'Pérennité de l’écosystème'],
            ['TRAINING', 'Formation'],
            ['NEW_ZUMRA', 'Nouvelles ZUMRA'],
            ['VALIDATED_PROJECTS', 'Projets validés'],
            ['INFRASTRUCTURE', 'Infrastructures'],
            ['SOLIDARITY', 'Solidarité'],
            ['EMERGENCY', 'Urgences'],
            ['AUTHORIZED_FEES', 'Frais autorisés'],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_contribution_events');
        Schema::dropIfExists('dg_contribution_receipts');
        Schema::dropIfExists('dg_contribution_payments');
        Schema::dropIfExists('dg_contributions');
        Schema::dropIfExists('dg_contribution_purposes');
    }
};
