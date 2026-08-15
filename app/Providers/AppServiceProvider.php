<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            productReference: config('gamad-core.product_reference'),
            connectSecret: config('gamad-core.connect_secret'),
            timeoutSeconds: (int) config('gamad-core.timeout_seconds'),
            connectTimeoutSeconds: (int) config('gamad-core.connect_timeout_seconds'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ces limiteurs ne consultent jamais Request::user() : GAMAD Core reste
        // l'unique autorité d'identité et aucun guard Laravel local n'est requis.
        RateLimiter::for('member-login', static fn (Request $request): Limit =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('member-logout', static fn (Request $request): Limit =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('account-create', static fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('account-verify', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('account-resend', static fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('profile-update', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('people-discovery', static fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('discovery-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('profile-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('zumra-membership', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('zumra-payment', static fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('zumra-payment-status', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('zumra-card-verification', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('zumra-card-revoke', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('zumra-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('zumra-charter-publish', static fn (Request $request): Limit => Limit::perHour(10)->by($request->ip()));
    }
}
