<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraGroup;
use Database\Seeders\NeedsDirectoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UX-HARMONY-BESOINS-001 — la maquette Besoins raccordée aux vraies données. Ne re-teste jamais la
 * logique métier Need déjà couverte ailleurs (NeedTest, NeedsDirectoryDemoTest) : vérifie seulement
 * que le nouvel habillage affiche des chiffres réels, que le seeder est idempotent, et que les
 * emplacements « financement » restent honnêtement désactivés plutôt que fabriqués.
 */
final class NeedsDirectoryHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_covers_six_categories_and_three_owner_types(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $firstRunCount = Need::query()->where('public_reference', 'like', '93000000-%')->count();
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $secondRunCount = Need::query()->where('public_reference', 'like', '93000000-%')->count();

        self::assertSame($firstRunCount, $secondRunCount);
        self::assertGreaterThanOrEqual(20, $firstRunCount);
        self::assertLessThanOrEqual(30, $firstRunCount);
        self::assertSame(6, Need::query()->where('public_reference', 'like', '93000000-%')->distinct('category')->count('category'));
        self::assertGreaterThanOrEqual(10, Need::query()->where('public_reference', 'like', '93000000-%')->distinct('location')->count('location'));
        self::assertSame(2, ZumraGroup::query()->whereIn('name', ['ZUMRA Ateliers Numériques Bouaké', 'ZUMRA Couture Solidaire Yamoussoukro'])->count());
        self::assertSame(2, Project::query()->whereIn('name', ['Centre d’initiation informatique de Bouaké', 'Réseau d’irrigation goutte-à-goutte de Ségou'])->count());
    }

    public function test_directory_shows_real_counts_never_the_mockup_reference_numbers(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $this->signIn();

        $total = Need::query()->where('status', '!=', Need::STATUS_ARCHIVED)->where('visibility', Need::VISIBILITY_PUBLIC)->count();
        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertStringContainsString((string) $total, $content);
        self::assertStringNotContainsString('245', $content);
        self::assertStringNotContainsString('1,24 M', $content);
        self::assertStringNotContainsString('1 24 M', $content);
    }

    public function test_financing_slots_stay_honestly_disabled_never_a_fabricated_amount(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $this->signIn();

        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertStringContainsString('Besoins financés', $content);
        self::assertStringContainsString('Montant engagé', $content);
        self::assertStringContainsString('moteur à construire', $content);
        self::assertStringContainsString('Finançables', $content);
        // Les deux emplacements financiers n'affichent jamais de chiffre : seule une puce neutre.
        self::assertMatchesRegularExpression('/<b>Besoins financés<\/b><strong>—<\/strong>/', $content);
        self::assertMatchesRegularExpression('/<b>Montant engagé<\/b><strong>—<\/strong>/', $content);
    }

    public function test_urgent_tab_only_lists_needs_open_for_more_than_thirty_days(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $this->signIn();

        $expectedUrgent = Need::query()
            ->whereIn('status', [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS])
            ->where('visibility', Need::VISIBILITY_PUBLIC)
            ->where('created_at', '<=', now()->subDays(30))
            ->count();

        $response = $this->get('/besoins?urgent=1')->assertOk();
        $response->assertSee((string) $expectedUrgent);

        foreach ($response->viewData('needs') as $need) {
            self::assertContains($need->status, [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS]);
            self::assertTrue($need->created_at->lte(now()->subDays(30)));
        }
    }

    public function test_category_and_location_breakdowns_reflect_real_distinct_data(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $this->signIn();

        $content = $this->get('/besoins')->assertOk()->getContent();

        foreach (['Compétence recherchée', 'Ressource nécessaire', 'Appui technique'] as $label) {
            self::assertStringContainsString($label, $content);
        }
        self::assertStringContainsString('Bouaké', $content);
        self::assertStringContainsString('Ségou', $content);
    }

    public function test_mine_filter_shows_only_the_authenticated_authors_own_needs(): void
    {
        $this->seed(NeedsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-NEED-PERSON-01');

        $mine = Need::query()->where('author_core_reference', 'DEMO-NEED-PERSON-01')->count();
        $response = $this->get('/besoins?mine=1')->assertOk();

        foreach ($response->viewData('needs') as $need) {
            self::assertSame('DEMO-NEED-PERSON-01', $need->author_core_reference);
        }
        self::assertGreaterThanOrEqual(1, $mine);
    }

    private function signIn(string $reference = 'DEMO-IDN-VIEWER'): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Identité démo', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
