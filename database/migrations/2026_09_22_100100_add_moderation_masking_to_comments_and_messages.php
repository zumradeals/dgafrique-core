<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODERATION-COMP-001 — masquage préventif non destructif (art. 19 : « masquage préventif en cas de
 * danger » ; art. 24.7 : « une suppression d'interface ne détruit pas une preuve soumise à
 * conservation »). Plus petit changement suffisant : un seul horodatage nullable par table. Sa
 * présence signifie masqué ; le contenu reste physiquement en base et reste lisible par la voie
 * disciplinaire/preuve, jamais exposé par la circulation ordinaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_context_comments', function (Blueprint $table): void {
            $table->timestampTz('hidden_at')->nullable()->after('posted_at');
        });

        Schema::table('dg_message_entries', function (Blueprint $table): void {
            $table->timestampTz('hidden_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('dg_context_comments', function (Blueprint $table): void {
            $table->dropColumn('hidden_at');
        });

        Schema::table('dg_message_entries', function (Blueprint $table): void {
            $table->dropColumn('hidden_at');
        });
    }
};
