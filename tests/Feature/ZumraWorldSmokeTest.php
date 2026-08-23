<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Database\Seeders\ZumraWorldDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraWorldSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hub_renders_without_the_demo_seeder(): void
    {
        $this->programMember('IDN-SMOKE-EMPTY');
        $this->signIn('IDN-SMOKE-EMPTY');
        $this->get('/zumra')->assertOk();
    }

    public function test_the_hub_renders_with_the_demo_seeder(): void
    {
        (new ZumraWorldDemoSeeder)->run();
        $this->programMember('IDN-SMOKE-FULL');
        $this->signIn('IDN-SMOKE-FULL');
        $this->get('/zumra')->assertOk()
            ->assertSee('Le monde ZUMRA')
            ->assertSee('RAHMAN Technology');
    }

    public function test_the_directory_view_filters_work(): void
    {
        (new ZumraWorldDemoSeeder)->run();
        $this->programMember('IDN-SMOKE-DIR');
        $this->signIn('IDN-SMOKE-DIR');
        $this->get('/zumra/groupes?mode=PHYSICAL&location=Yamoussoukro')->assertOk()->assertSee('Atelier Bois');
        $this->get('/zumra/groupes?view=mine')->assertOk();
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
