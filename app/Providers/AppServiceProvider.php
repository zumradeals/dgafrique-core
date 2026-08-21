<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\ProjectBrain\ProjectBrainAiProvider;
use App\Infrastructure\AI\DeepSeekProjectBrainProvider;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GamadCoreClient::class, static fn (): GamadCoreClient => new GamadCoreClient(
            baseUrl: (string) config('gamad-core.base_url'),
            productReference: config('gamad-core.product_reference'),
            connectSecret: config('gamad-core.connect_secret'),
            timeoutSeconds: (int) config('gamad-core.timeout_seconds'),
            connectTimeoutSeconds: (int) config('gamad-core.connect_timeout_seconds'),
        ));
        $this->app->singleton(ProjectBrainAiProvider::class, DeepSeekProjectBrainProvider::class);
    }

    public function boot(): void
    {
        // Ces limiteurs ne consultent jamais Request::user() : GAMAD Core reste
        // l'unique autorité d'identité et aucun guard Laravel local n'est requis.
        RateLimiter::for('member-login', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('member-logout', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('account-create', static fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('account-verify', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('account-resend', static fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('profile-update', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('people-discovery', static fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('discovery-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('recommendations', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('recommendation-decisions', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('recommendation-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('opportunities', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('profile-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('zumra-membership', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('zumra-payment', static fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('zumra-payment-status', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('zumra-card-verification', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('zumra-card-revoke', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('zumra-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('zumra-charter-publish', static fn (Request $request): Limit => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('zumra-group-write', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('zumra-group-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('zumra-group-lifecycle', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('contribution-write', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('contribution-payment', static fn (Request $request): Limit => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('contribution-payment-status', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('contribution-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('ledger-read', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('collective-capability-consent', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('collective-capability-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('need-write', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('need-transition', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('need-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('project-write', static fn (Request $request): Limit => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('project-transition', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('project-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('project-matching', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('project-match-decisions', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('project-matching-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('project-accompaniment', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('project-accompaniment-admin', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('project-accompaniment-configuration', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('project-maturity', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('project-autonomy', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('project-team-write', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('project-funding-write', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('organization-write', static fn (Request $request): Limit => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('organization-membership', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('partnership-write', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('partnership-decisions', static fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('satellites-admin', static fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('activity-feed', static fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('messaging-read', static fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('messaging-open', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('messaging-write', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('comments-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('comments-write', static fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('shares-read', static fn (Request $request): Limit => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('shares-write', static fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));

        $this->loadRoutesFrom(base_path('routes/cap016.php'));
        $this->loadRoutesFrom(base_path('routes/cap017.php'));
        $this->loadRoutesFrom(base_path('routes/cap018.php'));
        $this->loadRoutesFrom(base_path('routes/cap019.php'));
        $this->loadRoutesFrom(base_path('routes/cap020.php'));
        $this->loadRoutesFrom(base_path('routes/cap021.php'));
        $this->loadRoutesFrom(base_path('routes/cap022.php'));
        $this->loadRoutesFrom(base_path('routes/cap041.php'));
        $this->loadRoutesFrom(base_path('routes/cap063.php'));
    }
}
