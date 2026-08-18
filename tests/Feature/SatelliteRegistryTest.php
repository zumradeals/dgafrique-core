<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PortalAdministrator;
use App\Models\Satellite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Couvre CAP-048 : le registre remplace le codage en dur comme source de vérité administrable,
 * réservé à un administrateur provisionné, sans jamais exposer d'identifiant GAMAD/Core aux membres.
 */
final class SatelliteRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamadrive_is_migrated_as_a_real_first_record(): void
    {
        $gamadrive = Satellite::query()->where('product_reference', config('federation.gamadrive.product_reference'))->sole();

        self::assertSame('GamaDrive', $gamadrive->display_name);
        self::assertTrue($gamadrive->is_active);
        self::assertSame(config('federation.gamadrive.callback_url'), $gamadrive->callback_url);
    }

    public function test_a_non_administrator_cannot_reach_the_registry(): void
    {
        $this->signIn('IDN-NOT-ADMIN');

        $this->get(route('administration.satellites.index'))->assertForbidden();
    }

    public function test_an_administrator_declares_a_new_satellite(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');

        $this->post(route('administration.satellites.store'), [
            'product_reference' => 'PRD-GAMAD-010',
            'display_name' => 'Wasplex',
            'description' => 'Satellite logistique.',
            'callback_url' => 'https://wasplex.dgafrique.com/federation/callback',
        ])->assertRedirect();

        $satellite = Satellite::query()->where('product_reference', 'PRD-GAMAD-010')->sole();
        self::assertSame('Wasplex', $satellite->display_name);
        self::assertSame('IDN-ADMIN', $satellite->created_by_core_reference);
        self::assertTrue($satellite->is_active);
    }

    public function test_the_product_reference_must_stay_unique(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');
        $existing = config('federation.gamadrive.product_reference');

        $this->post(route('administration.satellites.store'), [
            'product_reference' => $existing,
            'display_name' => 'Doublon',
        ])->assertSessionHasErrors('product_reference');
    }

    public function test_an_administrator_edits_and_toggles_a_satellite(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');
        $satellite = Satellite::query()->where('product_reference', config('federation.gamadrive.product_reference'))->sole();

        $this->put(route('administration.satellites.update', $satellite), [
            'product_reference' => $satellite->product_reference,
            'display_name' => 'GamaDrive (renommé)',
            'description' => 'Nouvelle description.',
        ])->assertRedirect();
        self::assertSame('GamaDrive (renommé)', $satellite->refresh()->display_name);

        $this->put(route('administration.satellites.toggle', $satellite))->assertRedirect();
        self::assertFalse($satellite->refresh()->is_active);
    }

    public function test_a_non_https_callback_url_is_refused(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');

        $this->post(route('administration.satellites.store'), [
            'product_reference' => 'PRD-GAMAD-011',
            'display_name' => 'Satellite non sécurisé',
            'callback_url' => 'http://insecure.test/callback',
        ])->assertSessionHasErrors('callback_url');
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
