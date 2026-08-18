<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_person_profiles', function (Blueprint $table): void {
            $table->string('availability_status', 20)->nullable()->index();
            $table->text('availability_note')->nullable();
            $table->timestampTz('availability_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dg_person_profiles', function (Blueprint $table): void {
            $table->dropColumn(['availability_status', 'availability_note', 'availability_updated_at']);
        });
    }
};
