<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraWorldSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hub_renders_without_seeded_product_data(): void
    {
        $this->programMember('IDN-SMOKE-EMPTY');
        $this->signIn('IDN-SMOKE-EMPTY');
        $this->get('/zumra')->assertOk();
    }

    public function test_the_legacy_directory_preserves_its_redirect_contract(): void
    {
        $this->programMember('IDN-SMOKE-REDIRECT');
        $this->signIn('IDN-SMOKE-REDIRECT');
        $this->get('/zumra/groupes?mode=PHYSICAL&location=Yamoussoukro')
            ->assertRedirect(route('zumra.index', ['mode' => 'PHYSICAL', 'location' => 'Yamoussoukro']));
    }

    private function programMember(string $identity): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $identity],
            ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()],
        );
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
