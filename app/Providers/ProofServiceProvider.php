<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class ProofServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('proof-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('proof-write', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('proof-transition', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('proof-witness', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/cap036.php'));
    }
}
