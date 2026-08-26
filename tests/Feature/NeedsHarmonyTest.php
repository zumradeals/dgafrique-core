<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Need;
use Database\Seeders\NeedsHarmonyDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
final class NeedsHarmonyTest extends TestCase{use RefreshDatabase;
 public function test_seeder_is_idempotent_and_page_uses_real_need_metrics():void{$this->seed(NeedsHarmonyDemoSeeder::class);$this->seed(NeedsHarmonyDemoSeeder::class);self::assertSame(24,Need::query()->where('author_core_reference','like','DEMO-NEED-%')->count());self::assertTrue(Need::query()->where('owner_type',Need::OWNER_GROUP)->exists());self::assertTrue(Need::query()->where('owner_type',Need::OWNER_PROJECT)->exists());$this->signIn();$this->get('/besoins')->assertOk()->assertSee('Les bons besoins')->assertSee('24')->assertSee('UX présente — moteur métier à construire')->assertDontSee('1,24 M');}
 public function test_search_and_location_filters_remain_real():void{$this->seed(NeedsHarmonyDemoSeeder::class);$this->signIn();$this->get('/besoins?q=solaires&location=Tambacounda')->assertOk()->assertSee('Kits solaires')->assertDontSee('Forage d’un puits');}
 private function signIn():void{Http::fake(['core.test/api/v1/sessions'=>Http::response(['jeton'=>'bearer-demo','entite'=>'DEMO-IDN-VIEWER','assurance'=>'AS1','expire_le'=>'2026-12-01T00:00:00+00:00'],201),'core.test/api/v1/identites/*'=>Http::response(['reference'=>'DEMO-IDN-VIEWER','type'=>'personne','libelle'=>'Démo','etat'=>'ACTIF','source'=>'CORE','regime'=>'INSCRIT_AU_REGISTRE']),'core.test/api/v1/sessions/current'=>Http::response(['entite'=>'DEMO-IDN-VIEWER','assurance'=>'AS1','expire_le'=>'2026-12-01T00:00:00+00:00'])]);$this->post('/connexion',['identifier'=>'DEMO-IDN-VIEWER','secret'=>'secret'])->assertRedirect('/espace');}}
