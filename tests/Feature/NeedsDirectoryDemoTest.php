<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\Need;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Portail Besoins : cartes de démonstration (addendum daté du 20 août 2026,
 * DESIGN-INVARIANTS.md §21). Même règle DEMO-FIRST, REAL-DATA-TAKES-OVER que /projets (§18),
 * appliquée ici catégorie par catégorie.
 */
final class NeedsDirectoryDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_card_appears_marked_as_example_when_no_real_need_is_visible(): void
    {
        $this->signIn('IDN-NDIR-EMPTY');

        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertStringContainsString('Apprendre le forex', $content);
        self::assertStringContainsString('Formation souhaitée · Exemple', $content);
        self::assertStringContainsString('Objet de démonstration — aucune action réelle n’est rattachée.', $content);
    }

    public function test_a_real_need_hides_only_its_own_categorys_demo_card(): void
    {
        $this->member('IDN-NDIR-OWNER');
        $this->createNeed('IDN-NDIR-OWNER', ['category' => 'TRAINING', 'title' => 'Formation réelle en comptabilité']);
        $this->signIn('IDN-NDIR-VIEWER');

        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertStringNotContainsString('Apprendre le forex', $content);
        self::assertStringContainsString('Formation réelle en comptabilité', $content);
    }

    public function test_demo_card_never_appears_on_a_paginated_second_page(): void
    {
        $this->signIn('IDN-NDIR-PAGE2');

        $content = $this->get('/besoins?page=2')->assertOk()->getContent();

        self::assertStringNotContainsString('Apprendre le forex', $content);
        self::assertStringContainsString('Aucun besoin visible ici', $content);
    }

    public function test_the_needs_overview_panel_reflects_real_counts_not_a_projection(): void
    {
        $this->member('IDN-NDIR-STATS');
        $need = $this->createNeed('IDN-NDIR-STATS', ['category' => 'RESOURCE']);
        app(NeedService::class)->transition($need, 'IDN-NDIR-STATS', 'RESOLVED', 'Résolu pour le test.');
        $this->createNeed('IDN-NDIR-STATS', ['category' => 'TECHNICAL']);
        $this->signIn('IDN-NDIR-STATS');

        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertMatchesRegularExpression('/<span class="dg-needs-stat__value">1<\/span>\s*<div class="dg-needs-stat__label">Besoins ouverts/', $content);
        self::assertMatchesRegularExpression('/<span class="dg-needs-stat__value">1<\/span>\s*<div class="dg-needs-stat__label">Pourvus/', $content);
    }

    public function test_a_need_linked_to_a_real_project_shows_the_projects_name(): void
    {
        $this->member('IDN-NDIR-PROJECT');
        $project = app(ProjectService::class)->create('IDN-NDIR-PROJECT', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        app(NeedService::class)->create('IDN-NDIR-PROJECT', [
            'owner_type' => 'PROJECT', 'project_reference' => $project->public_reference, 'group_reference' => null,
            'title' => 'Local pour centre d’initiation informatique à Bouaké',
            'context' => 'Un local suffisamment grand pour accueillir les premières sessions de formation numérique à Bouaké.',
            'category' => 'RESOURCE', 'collaboration_mode' => 'LOCAL', 'location' => 'Bouaké, Côte d’Ivoire',
            'visibility' => 'PUBLIC',
        ], (new NeedConfiguration)->defaults());
        $this->signIn('IDN-NDIR-PROJECT');

        $content = $this->get('/besoins')->assertOk()->getContent();

        self::assertStringContainsString('Local pour centre d’initiation informatique à Bouaké', $content);
        self::assertStringContainsString($project->name, $content);
    }

    private function createNeed(string $owner, array $overrides = []): Need
    {
        return app(NeedService::class)->create($owner, array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'project_reference' => null,
            'title' => 'Besoin réel de test suffisamment décrit',
            'context' => 'Un contexte suffisamment détaillé pour respecter la validation métier existante.',
            'category' => 'SKILL', 'collaboration_mode' => 'ANY', 'location' => null,
            'visibility' => 'PUBLIC',
        ], $overrides), (new NeedConfiguration)->defaults());
    }

    private function projectPayload(): array
    {
        return [
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'GAMAD Technology',
            'summary' => 'Centre d’initiation informatique à Bouaké pour former les jeunes.',
            'problem' => 'Les jeunes de Bouaké manquent de compétences numériques suffisamment détaillées pour ce test.',
            'proposed_solution' => 'Créer un centre d’initiation offrant des formations pratiques suffisamment détaillées.',
            'beneficiaries' => 'Jeunes étudiants et jeunes du secteur informel de Bouaké.',
            'domain' => 'DIGITAL', 'participation_mode' => 'PHYSICAL', 'location' => 'Bouaké, Côte d’Ivoire',
            'objectives' => 'Former une première cohorte', 'required_capabilities' => 'Formation numérique',
            'required_resources' => 'Local pour les formations', 'risks' => 'Disponibilité irrégulière',
            'milestones' => "Définir le programme\nTrouver un local",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ];
    }

    private function member(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference,
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference,
                'type' => 'personne',
                'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF',
                'source' => 'CORE',
                'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', [
            'identifier' => $reference,
            'secret' => 'secret',
        ])->assertRedirect('/espace');
    }
}
