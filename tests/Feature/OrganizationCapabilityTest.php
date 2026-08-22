<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationCapabilityService;
use App\Application\Organizations\OrganizationService;
use App\Models\CapabilityStatement;
use App\Models\Organization;
use App\Models\Partnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-067 — les capacités d'une Organisation. Un fait métier explicite, déclaré volontairement
 * par un manager habilité, jamais déduit d'un Partnership, d'un Projet, d'un événement ni des
 * capacités personnelles du manager qui le déclare. Réutilise le moteur de capacités existant
 * (CAP-016, `CapabilityStatement`) — pas de second système, pas de score, pas de matching.
 */
final class OrganizationCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_declare_an_explicit_capability(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $statement = app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', [
            'label' => 'Formation en gestion comptable associative',
        ]);

        self::assertSame(CapabilityStatement::HOLDER_ORGANIZATION, $statement->holder_type);
        self::assertSame($organization->id, $statement->organization_id);
        self::assertNull($statement->core_identity_reference);
        self::assertSame(CapabilityStatement::KIND_POSSESSED, $statement->kind);
        self::assertFalse($statement->matching_consent, 'Aucun raccordement au moteur de matching dans ce chantier.');
    }

    public function test_a_non_manager_member_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        app(OrganizationService::class)->invite($organization, 'IDN-FOUNDER', 'IDN-MEMBER');
        app(OrganizationService::class)->acceptInvitation($organization, 'IDN-MEMBER');

        $this->assertAborts(403, fn () => app(OrganizationCapabilityService::class)->declare($organization, 'IDN-MEMBER', [
            'label' => 'Une capacité non autorisée',
        ]));
    }

    public function test_a_stranger_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        $this->assertAborts(403, fn () => app(OrganizationCapabilityService::class)->declare($organization, 'IDN-STRANGER', [
            'label' => 'Une capacité non autorisée',
        ]));
    }

    public function test_a_personal_capability_is_never_implicitly_attributed_to_the_organization(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        CapabilityStatement::query()->create([
            'holder_type' => CapabilityStatement::HOLDER_PERSON,
            'core_identity_reference' => 'IDN-FOUNDER',
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => 'Comptabilité personnelle',
            'normalized_label' => 'comptabilite personnelle',
            'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
            'matching_consent' => true,
            'source' => 'PROFILE',
        ]);

        $capabilities = app(OrganizationCapabilityService::class)->list($organization);

        self::assertCount(0, $capabilities, 'La capacité personnelle du fondateur ne doit jamais apparaître comme une capacité de son Organisation.');
    }

    public function test_a_duplicate_capability_is_refused(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', ['label' => 'Formation en gestion comptable']);

        $this->assertAborts(409, fn () => app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', [
            'label' => 'Formation en Gestion Comptable',
        ]));
    }

    public function test_a_manager_can_archive_a_capability(): void
    {
        $organization = $this->organization('IDN-FOUNDER');
        $statement = app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', ['label' => 'Formation en gestion comptable']);

        app(OrganizationCapabilityService::class)->archive($organization, 'IDN-FOUNDER', $statement);

        self::assertCount(0, app(OrganizationCapabilityService::class)->list($organization));
        self::assertNotNull($statement->refresh()->archived_at);
    }

    public function test_capabilities_are_visible_on_the_organization_page_when_public(): void
    {
        $organization = $this->organization('IDN-FOUNDER', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', ['label' => 'Formation en gestion comptable']);
        $this->signIn('IDN-STRANGER');

        $this->get('/organisations/'.$organization->public_reference)
            ->assertOk()
            ->assertSee('Formation en gestion comptable');
    }

    public function test_capabilities_stay_isolated_between_two_organizations(): void
    {
        $first = $this->organization('IDN-FOUNDER-A');
        $second = $this->organization('IDN-FOUNDER-B');
        app(OrganizationCapabilityService::class)->declare($first, 'IDN-FOUNDER-A', ['label' => 'Capacité de la première organisation']);

        self::assertCount(1, app(OrganizationCapabilityService::class)->list($first));
        self::assertCount(0, app(OrganizationCapabilityService::class)->list($second));
    }

    public function test_declaring_a_capability_never_touches_partnerships(): void
    {
        $organization = $this->organization('IDN-FOUNDER');

        app(OrganizationCapabilityService::class)->declare($organization, 'IDN-FOUNDER', ['label' => 'Formation en gestion comptable']);

        self::assertSame(0, Partnership::query()->count(), 'Déclarer une capacité Organisation ne doit jamais créer ou modifier un Partnership.');
    }

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Atelier numérique coopératif',
            'description' => 'Une structure durable qui porte des responsabilités et des ressources dans la durée.',
            'type' => 'COOPERATIVE',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ], $overrides));
    }

    /**
     * CAP-067 — voir OrganizationTest::fakeCoreOrganizationProvisioning() pour la justification
     * complète : une fermeture globale, sensible au corps de la requête, qui ne répond qu'aux
     * appels de session PRODUIT (PRD-GAMAD-005) sans jamais intercepter la session MEMBRE.
     */
    private function fakeCoreOrganizationProvisioning(): void
    {
        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_ends_with($url, '/sessions') && ($request['entite'] ?? null) === 'PRD-GAMAD-005') {
                return Http::response([
                    'jeton' => 'product-bearer-'.Str::random(8), 'entite' => 'PRD-GAMAD-005',
                    'assurance' => 'A1', 'expire_le' => '2026-08-16T23:59:00+00:00',
                ], 201);
            }
            if (str_ends_with($url, '/identites')) {
                return Http::response([
                    'identite' => ['reference' => 'IDN-CORE-ORG-'.Str::random(12), 'etat' => 'ACTIVE', 'assurance' => 'A1'],
                ], 201);
            }
            if (str_ends_with($url, '/organisations')) {
                return Http::response([
                    'resultat' => [
                        'reference' => 'ORG-GAMAD-'.Str::random(8), 'identite_reference' => 'IDN-CORE-ORG-'.Str::random(12),
                        'etat' => 'PREPARATION', 'type_organisation_reference' => 'INDETERMINE',
                    ],
                ], 201);
            }

            return null;
        });
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
