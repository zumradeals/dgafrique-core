<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAP-068 — Événement (rencontre/atelier/activité calendaire organisée par une ZUMRA ou une
 * Organisation), distinct de Mission (action à accomplir avec responsabilité) et des journaux
 * append-only *Event (traçabilité technique, jamais un objet consultable/organisable). Nommé
 * CommunityEvent — jamais Event — pour éviter toute collision de vocabulaire avec ProjectEvent/
 * ZumraGroupEvent/etc.
 *
 * Organisateur V1 limité à ZUMRA_GROUP/ORGANIZATION (organizer_type/organizer_reference, motif
 * déjà établi par Need.owner_type/owner_reference). Aucune récurrence, aucun calendrier complexe,
 * aucune finance, aucun matching, aucun score, aucune émargement de présence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_community_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_reference')->unique();
            $table->string('organizer_type', 16)->index();
            $table->uuid('organizer_reference')->index();
            $table->string('organizer_core_reference', 64);
            $table->string('title', 160);
            $table->text('description');
            $table->string('location', 160)->nullable();
            $table->string('visibility', 16)->default('INTERNAL');
            $table->string('status', 16)->default('SCHEDULED')->index();
            $table->timestampTz('scheduled_at');
            $table->string('decided_by_core_reference', 64)->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();
            $table->index(['organizer_type', 'organizer_reference']);
        });

        Schema::create('dg_community_event_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('community_event_id')->constrained('dg_community_events')->cascadeOnDelete();
            $table->string('core_identity_reference', 64);
            $table->string('status', 16);
            $table->timestampTz('registered_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->timestampsTz();
            $table->unique(['community_event_id', 'core_identity_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_community_event_participants');
        Schema::dropIfExists('dg_community_events');
    }
};
