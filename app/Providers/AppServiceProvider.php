<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GamadCoreClient::class, static fn (): GamadCoreClient => new GamadCoreClient(
            baseUrl: (string) config('gamad-core.base_url'),
            timeoutSeconds: (int) config('gamad-core.timeout_seconds'),
            connectTimeoutSeconds: (int) config('gamad-core.connect_timeout_seconds'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
