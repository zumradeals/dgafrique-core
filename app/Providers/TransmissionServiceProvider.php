<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class TransmissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('transmission-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('transmission-write', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('transmission-transition', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('transmission-participation', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('transmission-milestone', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('transmission-contribution', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('transmission-matching', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/cap006.php'));
    }
}
