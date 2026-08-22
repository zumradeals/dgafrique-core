<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dg_project_brain_drafts ALTER COLUMN actor_core_reference TYPE VARCHAR(255) USING actor_core_reference::text');
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: Core identity references such as
        // IDN-PER-000000003 are business references, not UUIDs.
    }
};
