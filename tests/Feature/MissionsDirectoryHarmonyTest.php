<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Project;
use App\Models\ZumraGroup;
use Database\Seeders\MissionsDirectoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UX-HARMONY-MISSIONS-001 — la maquette Missions raccordée aux vraies données. Ne re-teste jamais
 * la machine d'états Mission déjà couverte ailleurs (MissionWorkflowTest, MissionAuthorityVisibilityTest) :
 * vérifie seulement que le nouvel habillage affiche des chiffres réels, que le seeder est idempotent
 * et bâti exclusivement via les services métier réels, que le domaine dérivé du contexte reste honnête,
 * et que « Taux d'impact » / « Par priorité » / « Mes favoris » restent honnêtement désactivés plutôt
 * que fabriqués. Le tableau de bord personnel CAP-069 reste intact sous ?scope=mine.
 */
final class MissionsDirectoryHarmonyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_covers_four_statuses_and_three_context_types(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $actors = ['DEMO-MISSION-LEADER-01', 'DEMO-MISSION-LEADER-02', 'DEMO-MISSION-LEADER-03'];
        $firstRunCount = Mission::query()->whereIn('created_by_core_reference', $actors)->count();

        $this->seed(MissionsDirectoryDemoSeeder::class);
        $secondRunCount = Mission::query()->whereIn('created_by_core_reference', $actors)->count();

        self::assertSame($firstRunCount, $secondRunCount, 'Le second lancement du seeder ne doit créer aucune Mission supplémentaire.');
        self::assertSame(8, $firstRunCount);

        $statuses = Mission::query()->whereIn('created_by_core_reference', $actors)->pluck('status')->unique()->sort()->values();
        self::assertEqualsCanonicalizing(['OPEN', 'IN_PROGRESS', 'BLOCKED', 'COMPLETED'], $statuses->all());

        $contextTypes = Mission::query()->whereIn('created_by_core_reference', $actors)->pluck('context_type')->unique()->sort()->values();
        self::assertEqualsCanonicalizing(['PROJECT', 'ZUMRA', 'NEED'], $contextTypes->all());

        self::assertSame(2, Project::query()->whereIn('name', ['Centre de lecture communautaire de Kayes', 'Réseau de santé mobile de Kolda'])->count());
        self::assertSame(1, ZumraGroup::query()->where('name', 'ZUMRA Maraîchage Solidaire Ségou')->count());
    }

    public function test_directory_shows_real_status_counts_never_a_projected_number(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $open = Mission::query()->where('status', Mission::STATUS_OPEN)->count();
        $inProgress = Mission::query()->where('status', Mission::STATUS_IN_PROGRESS)->count();
        $completed = Mission::query()->where('status', Mission::STATUS_COMPLETED)->count();

        $content = $this->get('/missions')->assertOk()->getContent();

        self::assertStringContainsString((string) $open, $content);
        self::assertStringContainsString((string) $inProgress, $content);
        self::assertStringContainsString((string) $completed, $content);
    }

    public function test_impact_rate_and_priority_and_favorites_stay_honestly_disabled_never_fabricated(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $content = $this->get('/missions')->assertOk()->getContent();

        self::assertStringContainsString('Taux d’impact', $content);
        self::assertStringContainsString('moteur à construire', $content);
        self::assertStringContainsString('<b>Taux d’impact</b><strong>—</strong>', $content, 'Aucun pourcentage d’impact inventé : uniquement une puce neutre.');
        self::assertStringContainsString('Par priorité', $content);
        self::assertStringContainsString('Mes favoris', $content);
    }

    public function test_status_tabs_filter_to_real_missions_only(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $response = $this->get('/missions?status=BLOCKED')->assertOk();
        $expectedCount = Mission::query()->where('status', Mission::STATUS_BLOCKED)->count();

        self::assertGreaterThanOrEqual(1, $expectedCount);
        foreach ($response->viewData('missionsPage') as $mission) {
            self::assertSame(Mission::STATUS_BLOCKED, $mission->status);
        }
    }

    public function test_domain_breakdown_derives_honestly_from_context_and_buckets_need_missions_as_unclassified(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $content = $this->get('/missions')->assertOk()->getContent();

        self::assertStringContainsString('Éducation et formation', $content);
        self::assertStringContainsString('Santé', $content);
        self::assertStringContainsString('Sans domaine identifié', $content, 'Une Mission issue d’un Besoin n’a pas d’équivalent domaine : elle doit apparaître honnêtement non classée, jamais inventée.');
    }

    public function test_progression_reflects_real_checklist_completion_and_stays_honest_when_no_checklist_exists(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $withChecklist = Mission::query()->where('title', 'Constituer le premier fonds de livres')->firstOrFail();
        $withoutChecklist = Mission::query()->where('title', 'Préparer la parcelle collective de maraîchage')->firstOrFail();

        self::assertSame(2, $withChecklist->checklistItems()->whereNotNull('completed_at')->count());
        self::assertSame(4, $withChecklist->checklistItems()->count());
        self::assertSame(0, $withoutChecklist->checklistItems()->count());

        $content = $this->get('/missions?status=IN_PROGRESS')->assertOk()->getContent();
        self::assertStringContainsString('50%', $content, '2/4 étapes complétées doit afficher 50%, jamais un chiffre arrondi fabriqué.');
    }

    public function test_scope_mine_still_renders_the_intact_cap069_personal_dashboard(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn('DEMO-MISSION-LEADER-01');

        $content = $this->get('/missions?scope=mine')->assertOk()->getContent();

        self::assertStringContainsString('Mes Missions', $content);
        self::assertStringContainsString('Mes propositions', $content);
        self::assertStringNotContainsString('Des missions concrètes pour un impact réel.', $content, 'La vue personnelle CAP-069 reste distincte de l’annuaire public.');
    }

    public function test_default_view_never_shows_the_personal_dashboard_content(): void
    {
        $this->seed(MissionsDirectoryDemoSeeder::class);
        $this->signIn();

        $content = $this->get('/missions')->assertOk()->getContent();

        self::assertStringContainsString('Des missions concrètes pour un impact réel.', $content);
    }

    private function signIn(string $reference = 'DEMO-MISSION-VIEWER'): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Identité démo', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-12-01T00:00:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
