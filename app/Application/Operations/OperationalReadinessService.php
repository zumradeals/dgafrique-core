<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Models\OperationalHeartbeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class OperationalReadinessService
{
    /**
     * @return array{ready: bool, checks: array{postgresql: bool, redis_default: bool, redis_cache: bool, scheduler: bool}, checked_at: string}
     */
    public function inspect(): array
    {
        $checks = [
            'postgresql' => $this->postgresqlIsReady(),
            'redis_default' => $this->redisIsReady('default'),
            'redis_cache' => $this->redisIsReady('cache'),
            'scheduler' => $this->schedulerIsReady(),
        ];

        return [
            'ready' => ! in_array(false, $checks, true),
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function postgresqlIsReady(): bool
    {
        try {
            return (int) (DB::selectOne('select 1 as ready')?->ready ?? 0) === 1;
        } catch (Throwable) {
            return false;
        }
    }

    private function redisIsReady(string $connection): bool
    {
        try {
            Redis::connection($connection)->ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function schedulerIsReady(): bool
    {
        try {
            $heartbeat = OperationalHeartbeat::query()->find('scheduler');
            $maxAge = max(1, (int) config('operations.scheduler_heartbeat_max_age_seconds', 180));
            $currentTime = now();

            return $heartbeat?->last_succeeded_at !== null
                && $heartbeat->last_succeeded_at->greaterThanOrEqualTo($currentTime->copy()->subSeconds($maxAge))
                && $heartbeat->last_succeeded_at->lessThanOrEqualTo($currentTime->copy()->addSeconds(30));
        } catch (Throwable) {
            return false;
        }
    }
}
