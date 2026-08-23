<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dg_project_brain_intents ALTER COLUMN actor_core_reference TYPE VARCHAR(120) USING actor_core_reference::text');
        }
    }

    public function down(): void
    {
        // Core identity references are semantic identifiers such as IDN-PER-000000003,
        // not UUIDs. Reverting this column to uuid would be destructive/invalid.
    }
};
