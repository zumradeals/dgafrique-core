<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Portail Projets : cartes de démonstration (addendum daté du 20 août 2026,
 * DESIGN-INVARIANTS.md §18). Même règle DEMO-FIRST, REAL-DATA-TAKES-OVER que le Fil V2
 * (FilV2Test), appliquée ici domaine par domaine plutôt qu'à l'échelle de tout l'écran.
 */
final class ProjectsDirectoryDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_cards_appear_marked_as_example_when_no_real_project_is_visible(): void
    {
        $this->signIn('IDN-PDIR-EMPTY');

        $content = $this->get('/projets')->assertOk()->getContent();

        self::assertStringContainsString('GAMAD Technology', $content);
        self::assertStringContainsString('Bibliothèque Solidaire', $content);
        self::assertStringContainsString('Ensemble pour la propreté', $content);
        self::assertStringContainsString('Numérique · Exemple', $content);
        self::assertStringContainsString('Objet de démonstration — aucune action réelle n’est rattachée.', $content);
    }

    public function test_a_real_project_hides_only_its_own_domains_demo_card(): void
    {
        $this->member('IDN-PDIR-OWNER');
        app(ProjectService::class)->create('IDN-PDIR-OWNER', $this->payload(), (new ProjectConfiguration)->defaults());
        $this->signIn('IDN-PDIR-VIEWER');

        $content = $this->get('/projets')->assertOk()->getContent();

        self::assertStringNotContainsString('GAMAD Technology', $content);
        self::assertStringContainsString('Bibliothèque Solidaire', $content);
        self::assertStringContainsString('Ensemble pour la propreté', $content);
        self::assertStringContainsString('Atelier numérique communautaire', $content);
    }

    public function test_demo_cards_never_appear_on_a_paginated_second_page(): void
    {
        $this->signIn('IDN-PDIR-PAGE2');

        $content = $this->get('/projets?page=2')->assertOk()->getContent();

        self::assertStringNotContainsString('GAMAD Technology', $content);
        self::assertStringContainsString('Aucun projet visible', $content);
    }

    private function zumraFor(string $actor): string
    {
        return app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.uniqid(), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON',
            'group_reference' => null,
            'zumra_group_reference' => $this->zumraFor('IDN-PDIR-OWNER'),
            'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => 'PUBLIC',
        ], $overrides);
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
