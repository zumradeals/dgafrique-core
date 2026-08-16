<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\GamadCore\FederatedProductGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class FederationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FederatedProductGateway::class, static fn (): FederatedProductGateway => new FederatedProductGateway(
            baseUrl: (string) config('gamad-core.base_url'),
            timeoutSeconds: (int) config('gamad-core.timeout_seconds'),
            connectTimeoutSeconds: (int) config('gamad-core.connect_timeout_seconds'),
        ));
    }

    public function boot(): void
    {
        // DG Afrique n'utilise aucun guard Laravel local : la limitation doit
        // donc rester indépendante de Request::user() et se borner à l'IP.
        RateLimiter::for('federation-continue', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/federation.php'));
    }
}
