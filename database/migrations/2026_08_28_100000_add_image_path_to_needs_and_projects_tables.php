<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_needs', function (Blueprint $table): void {
            $table->string('image_path', 255)->nullable()->after('location');
        });
        Schema::table('dg_projects', function (Blueprint $table): void {
            $table->string('image_path', 255)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('dg_needs', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
        Schema::table('dg_projects', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
