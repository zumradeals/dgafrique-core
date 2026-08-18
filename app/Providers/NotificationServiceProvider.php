<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('notifications-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/cap054.php'));
    }
}
