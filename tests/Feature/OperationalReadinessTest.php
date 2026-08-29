<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OperationalHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class OperationalReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Stage D runs against the two dedicated runtime Redis databases. A failed ping is
        // a failed engine dependency and must never be silently replaced by an in-memory fake.
        Redis::connection('default')->ping();
        Redis::connection('cache')->ping();
    }

    public function test_liveness_and_readiness_are_distinct_without_a_scheduler_heartbeat(): void
    {
        $this->get('/up')->assertOk();

        $this->getJson('/ready')
            ->assertStatus(503)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('status', 'not_ready')
            ->assertJsonPath('checks.postgresql', true)
            ->assertJsonPath('checks.redis_default', true)
            ->assertJsonPath('checks.redis_cache', true)
            ->assertJsonPath('checks.scheduler', false)
            ->assertJsonStructure(['status', 'checks', 'checked_at']);
    }

    public function test_a_recent_scheduler_heartbeat_makes_the_engine_ready(): void
    {
        $this->travelTo(now()->startOfSecond());

        self::assertSame(0, Artisan::call('ops:scheduler-heartbeat', ['--source' => 'stage-d-test']));

        $this->getJson('/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.postgresql', true)
            ->assertJsonPath('checks.redis_default', true)
            ->assertJsonPath('checks.redis_cache', true)
            ->assertJsonPath('checks.scheduler', true)
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('message');

        $heartbeat = OperationalHeartbeat::query()->findOrFail('scheduler');
        self::assertSame('stage-d-test', $heartbeat->source);
        self::assertTrue($heartbeat->last_succeeded_at->equalTo(now()));
    }

    public function test_a_stale_scheduler_heartbeat_refuses_traffic(): void
    {
        OperationalHeartbeat::query()->create([
            'name' => 'scheduler',
            'source' => 'stale-test',
            'last_succeeded_at' => now()->subSeconds(181),
        ]);

        $this->getJson('/ready')
            ->assertStatus(503)
            ->assertJsonPath('status', 'not_ready')
            ->assertJsonPath('checks.scheduler', false);
    }

    public function test_the_readiness_command_has_a_machine_readable_exit_contract(): void
    {
        self::assertSame(1, Artisan::call('ops:readiness', ['--json' => true]));

        Artisan::call('ops:scheduler-heartbeat', ['--source' => 'stage-d-cli']);

        self::assertSame(0, Artisan::call('ops:readiness', ['--json' => true]));
        $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('ready', $payload['status']);
        self::assertNotEmpty($payload['checked_at']);
        self::assertNotContains(false, $payload['checks']);
    }
}
