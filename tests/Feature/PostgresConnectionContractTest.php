<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PostgresConnectionContractTest extends TestCase
{
    public function test_the_application_forces_every_postgresql_session_to_utc(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Ce contrat est vérifié par ENGINE-RUNTIME-001 sur PostgreSQL.');
        }

        $row = DB::selectOne("select current_setting('TIMEZONE') as timezone");

        self::assertSame('UTC', $row->timezone ?? null);
        self::assertSame('UTC', config('database.connections.pgsql.timezone'));
    }
}
