<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['dg_zumra_payments', 'dg_contribution_payments', 'dg_zahab_acquisitions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                // Le secret brut n'est présent que dans l'URL confiée au prestataire. La base ne
                // conserve qu'un hash non réversible, unique, qui lie le retour à UNE tentative.
                $table->string('return_token_hash', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (['dg_zumra_payments', 'dg_contribution_payments', 'dg_zahab_acquisitions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('return_token_hash');
            });
        }
    }
};
