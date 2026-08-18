<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\GamadCore\FederatedProductGateway;
use App\Models\Satellite;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        $this->loadRoutesFrom(base_path('routes/cap048.php'));

        // CAP-049 : le menu « Mes outils » reflète le registre CAP-048 — aucun contrôleur
        // existant n'a besoin d'être modifié pour qu'un satellite déclaré y apparaisse.
        View::composer(['components.dg.topbar', 'member.space'], static function ($view): void {
            $view->with('satellites', Satellite::query()->where('is_active', true)->orderBy('display_name')->get());
        });
    }
}
