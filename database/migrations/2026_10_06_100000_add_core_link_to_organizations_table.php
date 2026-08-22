<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAP-067 — raccordement canonique GAMAD Core. Toute nouvelle Organisation
 * est désormais créée avec une identité (CAP-CORE-001) et une fiche
 * organisationnelle (CAP-CORE-002) réelles côté Core, via la délégation
 * CORE-ORG-DELEGATION-001 (PRD-GAMAD-005). `core_link_status` reste
 * `UNLINKED` par défaut pour toute Organisation déjà existante : aucune
 * migration automatique par rapprochement de nom n'est tentée (aucune
 * preuve fiable ne le permettrait — voir le rapport de session CAP-067).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_organizations', function (Blueprint $table): void {
            $table->string('core_identity_reference', 64)->nullable()->unique()->after('founder_core_reference');
            $table->string('core_organization_reference', 64)->nullable()->unique()->after('core_identity_reference');
            $table->string('core_link_status', 16)->default('UNLINKED')->index()->after('core_organization_reference');
        });
    }

    public function down(): void
    {
        Schema::table('dg_organizations', function (Blueprint $table): void {
            $table->dropColumn(['core_identity_reference', 'core_organization_reference', 'core_link_status']);
        });
    }
};
