<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\ProjectBrainIntent;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UX-HARMONY-CLEANUP-001 — non-régression des trois anomalies identifiées par
 * UX-HARMONY-AUDIT-001 dans le domaine Projet. N'introduit ni ne modifie aucun moteur métier :
 * vérifie seulement (1) qu'une seule navigation DG Afrique reste rendue sur le flux Cerveau Projet
 * (plus jamais de doublon .dg-global-nav), (2) que projects.overview ne peut plus exposer de
 * données de financement fictives, (3) que projects.autonomy.show est bien rattaché au shell
 * officiel tout en conservant ses fonctions réelles (ouverture/fermeture du parcours).
 */
final class UxHarmonyCleanup001Test extends TestCase
{
    use RefreshDatabase;

    public function test_project_brain_start_renders_a_single_dg_navigation_never_the_legacy_duplicate(): void
    {
        $this->activateProgram('IDN-CLEAN-BSTART');
        $this->signIn('IDN-CLEAN-BSTART');

        $content = $this->get(route('projects.brain.start'))->assertOk()->getContent();

        self::assertStringContainsString('dg-topbar', $content, 'Le shell officiel doit rester la seule navigation rendue.');
        self::assertStringNotContainsString('dg-global-nav', $content, 'La bannière de navigation legacy ne doit plus jamais apparaître.');
    }

    public function test_project_brain_start_show_renders_a_single_dg_navigation_never_the_legacy_duplicate(): void
    {
        $this->activateProgram('IDN-CLEAN-BSHOW');
        $this->signIn('IDN-CLEAN-BSHOW');
        $intent = $this->readyBrainIntent('IDN-CLEAN-BSHOW');

        $content = $this->get(route('projects.brain.start.show', $intent))->assertOk()->getContent();

        self::assertStringContainsString('dg-topbar', $content);
        self::assertStringNotContainsString('dg-global-nav', $content);
    }

    public function test_project_brain_start_zumra_create_renders_a_single_dg_navigation_never_the_legacy_duplicate(): void
    {
        $this->activateProgram('IDN-CLEAN-BZUMRA');
        $this->signIn('IDN-CLEAN-BZUMRA');
        $intent = $this->readyBrainIntent('IDN-CLEAN-BZUMRA');

        $content = $this->get(route('projects.brain.start.zumra.create', $intent))->assertOk()->getContent();

        self::assertStringContainsString('dg-topbar', $content);
        self::assertStringNotContainsString('dg-global-nav', $content);
    }

    public function test_project_brain_show_still_renders_a_single_dg_navigation(): void
    {
        // Non-régression : projects.brain.show était déjà exclu du bandeau legacy avant ce lot,
        // il doit rester ainsi après la suppression complète du mécanisme.
        $this->activateProgram('IDN-CLEAN-BRAINSHOW');
        $this->signIn('IDN-CLEAN-BRAINSHOW');
        $zumra = $this->zumraFor('IDN-CLEAN-BRAINSHOW');
        $project = app(ProjectService::class)->create('IDN-CLEAN-BRAINSHOW', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());

        $content = $this->get(route('projects.brain.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('dg-topbar', $content);
        self::assertStringNotContainsString('dg-global-nav', $content);
    }

    public function test_projects_overview_no_longer_exposes_fictional_funding_figures_and_redirects_to_the_real_project_sheet(): void
    {
        $this->activateProgram('IDN-CLEAN-OVERVIEW');
        $this->signIn('IDN-CLEAN-OVERVIEW');
        $zumra = $this->zumraFor('IDN-CLEAN-OVERVIEW');
        $project = app(ProjectService::class)->create('IDN-CLEAN-OVERVIEW', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());

        $response = $this->get(route('projects.overview', $project));

        $response->assertRedirect(route('projects.show', $project));

        // Preuve directe de la doctrine : même en suivant la redirection, aucune des figures
        // fictives de l'ancienne vue-ensemble n'est jamais exposée à l'utilisateur.
        $followed = $this->get(route('projects.overview', $project))->assertRedirect();
        $shown = $this->get(route('projects.show', $project))->assertOk()->getContent();
        self::assertStringNotContainsString('1 250 000 F', $shown);
        self::assertStringNotContainsString('3 600 000 F', $shown);
        self::assertStringNotContainsString('Projection métier', $shown);
    }

    public function test_projects_overview_view_file_no_longer_exists(): void
    {
        self::assertFileDoesNotExist(resource_path('views/projects/overview-v2.blade.php'));
    }

    public function test_projects_autonomy_show_is_wrapped_in_the_official_shell_and_keeps_its_real_functions(): void
    {
        $this->activateProgram('IDN-CLEAN-AUTONOMY');
        $this->signIn('IDN-CLEAN-AUTONOMY');
        $zumra = $this->zumraFor('IDN-CLEAN-AUTONOMY');
        $project = app(ProjectService::class)->create('IDN-CLEAN-AUTONOMY', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());
        $project->forceFill(['maturity' => 'POTENTIAL_STRUCTURE'])->save();

        $content = $this->get(route('projects.autonomy.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('dg-topbar', $content, 'projects.autonomy.show doit désormais utiliser le shell officiel DG Afrique.');
        self::assertStringNotContainsString('dg-global-nav', $content);
        // Les fonctions réelles du parcours d'autonomie (CAP-018) restent intactes.
        self::assertStringContainsString(route('projects.autonomy.open', $project), $content, 'Le formulaire d’ouverture du parcours reste fonctionnel.');
        self::assertStringContainsString('Ouvrir le parcours d’autonomie', $content);
    }

    public function test_projects_autonomy_close_action_still_works_after_the_shell_integration(): void
    {
        $this->activateProgram('IDN-CLEAN-AUTOCLOSE');
        $this->signIn('IDN-CLEAN-AUTOCLOSE');
        $zumra = $this->zumraFor('IDN-CLEAN-AUTOCLOSE');
        $project = app(ProjectService::class)->create('IDN-CLEAN-AUTOCLOSE', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());
        $project->forceFill(['maturity' => 'POTENTIAL_STRUCTURE'])->save();

        $this->post(route('projects.autonomy.open', $project), ['target_form' => 'ASSOCIATION'])
            ->assertRedirect(route('projects.autonomy.show', $project));

        $content = $this->get(route('projects.autonomy.show', $project))->assertOk()->getContent();
        self::assertStringContainsString('Parcours actif', $content);

        $this->post(route('projects.autonomy.close', $project), ['_method' => 'PUT'])
            ->assertRedirect(route('projects.autonomy.show', $project));
    }

    // ===== Helpers (repris de ProjectZumraInvariantTest pour rester cohérent avec la suite existante) =====

    /** @return array<string,mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier '.Str::random(6),
            'summary' => str_repeat('Résumé suffisamment long pour la contrainte de validation ici. ', 2),
            'problem' => str_repeat('Problème suffisamment détaillé pour la contrainte de validation ici. ', 2),
            'proposed_solution' => str_repeat('Solution suffisamment détaillée pour la contrainte de validation ici. ', 2),
            'beneficiaries' => 'Des bénéficiaires suffisamment décrits pour ce test.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire un résultat",
            'required_capabilities' => '', 'required_resources' => '', 'risks' => '',
            'milestones' => '', 'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    /** @return array<string,mixed> */
    private function zumraFields(string $actor): array
    {
        return [
            'name' => 'ZUMRA '.$actor.' '.Str::random(6), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ];
    }

    private function zumraFor(string $actor): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($actor, $this->zumraFields($actor));
    }

    private function readyBrainIntent(string $actor): ProjectBrainIntent
    {
        return ProjectBrainIntent::query()->create([
            'actor_core_reference' => $actor, 'title' => 'Idée test', 'messages' => [],
            'context' => ['project_state' => ['name' => 'Idée en cours']], 'status' => 'DRAFT',
        ]);
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $reference],
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
