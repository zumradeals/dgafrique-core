<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use Database\Seeders\PeopleDirectoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PeopleDirectoryHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_produces_real_metrics(): void
    {
        $this->seed(PeopleDirectoryDemoSeeder::class);$this->seed(PeopleDirectoryDemoSeeder::class);
        self::assertSame(24,PersonProfile::query()->where('core_identity_reference','like','DEMO-PEOPLE-%')->count());
        self::assertGreaterThan(20,CapabilityStatement::query()->where('core_identity_reference','like','DEMO-PEOPLE-%')->whereNull('archived_at')->count());
        $this->signIn();$this->get('/personnes')->assertOk()->assertSee('Les bonnes personnes')->assertSee('24')->assertSee('Personnes recommandées pour vous')->assertDontSee('1 842');
    }

    public function test_private_profiles_stay_out_of_metrics_and_cards(): void
    {
        PersonProfile::query()->create(['core_identity_reference'=>'PRIVATE','discovery_reference'=>'92000000-0000-4000-8000-000000000001','discovery_display_name'=>'Profil secret','orientation_consent'=>true,'discovery_consent'=>false]);
        $this->signIn();$this->get('/personnes')->assertOk()->assertDontSee('Profil secret')->assertSeeInOrder(['Toutes les personnes','0','profils découvrables']);
    }

    private function signIn(): void
    {
        Http::fake(['core.test/api/v1/sessions'=>Http::response(['jeton'=>'bearer-demo','entite'=>'DEMO-IDN-VIEWER','assurance'=>'AS1','expire_le'=>'2026-12-01T00:00:00+00:00'],201),'core.test/api/v1/identites/*'=>Http::response(['reference'=>'DEMO-IDN-VIEWER','type'=>'personne','libelle'=>'Identité démo','etat'=>'ACTIF','source'=>'CORE','regime'=>'INSCRIT_AU_REGISTRE']),'core.test/api/v1/sessions/current'=>Http::response(['entite'=>'DEMO-IDN-VIEWER','assurance'=>'AS1','expire_le'=>'2026-12-01T00:00:00+00:00'])]);
        $this->post('/connexion',['identifier'=>'DEMO-IDN-VIEWER','secret'=>'secret'])->assertRedirect('/espace');
    }
}
