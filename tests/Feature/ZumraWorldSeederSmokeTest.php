<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraGroup;
use App\Models\ZumraGroupActivity;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProximityShowcase;
use Database\Seeders\ZumraWorldDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ZumraWorldSeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_without_error_and_produces_a_believable_network(): void
    {
        (new ZumraWorldDemoSeeder)->run();

        self::assertGreaterThan(20, ZumraGroup::query()->count());
        self::assertGreaterThan(50, ZumraGroupMembership::query()->where('status', ZumraGroupMembership::STATUS_ACTIVE)->count());
        self::assertSame(4, ZumraProximityShowcase::query()->count());
        self::assertGreaterThan(0, ZumraGroupActivity::query()->count());

        $rahman = ZumraGroup::query()->where('name', 'RAHMAN Technology')->sole();
        self::assertSame(15, $rahman->active_member_count);
        self::assertSame('Numériques', $rahman->domain);

        $viewer = 'DEMO-IDN-VIEWER';
        self::assertSame(
            ZumraGroupMembership::STATUS_INVITED,
            ZumraGroupMembership::query()->where('zumra_group_id', $rahman->id)->where('core_identity_reference', $viewer)->sole()->status,
        );
    }
}
