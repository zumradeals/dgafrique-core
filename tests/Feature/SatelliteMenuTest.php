<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Satellite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre CAP-049 : le menu « Mes outils » (topbar et Mon espace) reflète le registre CAP-048 sans
 * qu'aucun contrôleur existant n'ait besoin d'être modifié — un satellite actif apparaît, un
 * satellite désactivé disparaît, sans déploiement.
 */
final class SatelliteMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_declared_active_satellite_appears_in_the_tools_menu(): void
    {
        Satellite::query()->create([
            'product_reference' => 'PRD-GAMAD-020', 'slug' => 'wasplex', 'display_name' => 'Wasplex',
            'callback_url' => 'https://wasplex.dgafrique.com/federation/callback', 'is_active' => true,
            'created_by_core_reference' => 'IDN-ADMIN',
        ]);
        $this->signIn('IDN-MEMBER');

        $this->get('/activite')->assertOk()
            ->assertSee('GamaDrive')
            ->assertSee('Wasplex')
            ->assertSee(route('federation.continue', 'wasplex'), false);
    }

    public function test_a_disabled_satellite_disappears_from_the_tools_menu(): void
    {
        Satellite::query()->where('slug', 'gamadrive')->update(['is_active' => false]);
        $this->signIn('IDN-MEMBER');

        $this->get('/activite')->assertOk()->assertDontSee('GamaDrive');
    }

    public function test_mon_espace_lists_the_same_active_satellites(): void
    {
        Satellite::query()->create([
            'product_reference' => 'PRD-GAMAD-021', 'slug' => 'g-market', 'display_name' => 'G-Market',
            'description' => 'Ouvrir la marketplace.', 'callback_url' => 'https://gmarket.dgafrique.com/federation/callback',
            'is_active' => true, 'created_by_core_reference' => 'IDN-ADMIN',
        ]);
        $this->signIn('IDN-MEMBER');

        $this->get('/espace')->assertOk()
            ->assertSee('G-Market')
            ->assertSee('Ouvrir la marketplace.');
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => function ($request) {
                $entite = $request['identifiant'] ?? $request['entite'];

                return Http::response(['jeton' => 'bearer-'.$entite, 'entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201);
            },
            'core.test/api/v1/identites/*' => function ($request) {
                $entite = Str::afterLast($request->url(), '/');

                return Http::response(['reference' => $entite, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            },
            'core.test/api/v1/sessions/current' => function ($request) {
                $entite = Str::after((string) $request->header('Authorization')[0], 'bearer-');

                return Http::response(['entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']);
            },
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
