<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Missions\Contexts\NeedMissionContext;
use App\Application\Missions\Contexts\ProjectMissionContext;
use App\Application\Missions\Contexts\ZumraMissionContext;
use App\Application\Missions\MissionContextRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Le registre de production initial de MISSIONS contient exactement PROJECT, ZUMRA, NEED
 * (v0.5 §2). Un nouveau contexte exige une autorité explicitement définie et une revue —
 * il ne s'ajoute jamais ici simplement parce qu'un modèle voisin existe.
 */
final class MissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MissionContextRegistry::class, static fn (Application $app): MissionContextRegistry => new MissionContextRegistry([
            $app->make(ProjectMissionContext::class),
            $app->make(ZumraMissionContext::class),
            $app->make(NeedMissionContext::class),
        ]));
    }

    public function boot(): void
    {
        RateLimiter::for('mission-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('mission-write', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('mission-transition', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('mission-assignment', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('mission-checklist', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('mission-contribution', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('mission-matching', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('mission-recurrence', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/cap069.php'));
    }
}
