<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_recommendation_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('core_identity_reference', 64)->index();
            $table->string('subject_type', 32);
            $table->string('subject_reference', 64);
            $table->string('decision', 24)->index();
            $table->timestampsTz();

            $table->unique(
                ['core_identity_reference', 'subject_type', 'subject_reference'],
                'dg_recommendation_decision_identity_subject_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_recommendation_decisions');
    }
};
