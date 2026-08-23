<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationCapabilityService;
use App\Application\Organizations\OrganizationService;
use App\Application\Partnerships\PartnershipService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Partnership;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-005 Phase B — couche Partnership (CAP-065) réellement supportée : collaborations sur la
 * fiche Organisation, Partenariats contextuels sur Besoin/Projet/ZUMRA, proposition d'une
 * Organisation habilitée depuis un contexte consultable. Aucune capacité intrinsèque
 * d'Organisation n'est déduite des Partnerships (décision produit explicite) : ces tests vérifient
 * autant l'absence de ce contenu que la présence des collaborations réelles.
 */
final class PartnershipHttpTest extends TestCase
{
    use RefreshDatabase;

    // ===== Proposition depuis un contexte, par une Organisation habilitée =====

    public function test_an_organization_manager_can_propose_from_a_need_and_it_appears_on_both_fiches(): void
    {
        $need = $this->need('IDN-PH-NEED-OWNER');
        $organization = $this->organization('IDN-PH-ORG-OWNER1');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER1', 'Formation en gestion associative');

        $this->signIn('IDN-PH-ORG-OWNER1');
        $this->get(route('needs.show', $need))->assertOk()->assertSee('Notre organisation peut apporter');

        $this->from(route('needs.show', $need))->post(route('partnerships.store'), [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ])->assertRedirect(route('needs.show', $need));

        self::assertSame(1, Partnership::query()->count());
        $this->get(route('needs.show', $need))->assertOk()->assertSee('Formation en gestion associative');
        $this->get(route('organizations.show', $organization))->assertOk()->assertSee('Formation en gestion associative');
    }

    public function test_an_organization_manager_can_propose_from_a_project(): void
    {
        $project = $this->project('IDN-PH-PROJ-OWNER');
        $organization = $this->organization('IDN-PH-ORG-OWNER2');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER2', 'Mentorat technique');

        $this->signIn('IDN-PH-ORG-OWNER2');
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Notre organisation peut apporter');

        $this->from(route('projects.show', $project))->post(route('partnerships.store'), [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'PROJECT',
            'context_reference' => $project->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ])->assertRedirect(route('projects.show', $project));

        $this->get(route('projects.show', $project))->assertOk()->assertSee('Mentorat technique');
    }

    public function test_an_organization_manager_who_is_also_a_zumra_member_can_propose_from_a_zumra(): void
    {
        $group = $this->group('IDN-PH-ZUMRA-LEADER');
        $organization = $this->organization('IDN-PH-ORG-OWNER3');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER3', 'Appui logistique');
        $this->membership($group, 'IDN-PH-ORG-OWNER3', ZumraGroupMembership::STATUS_ACTIVE);

        $this->signIn('IDN-PH-ORG-OWNER3');
        $this->get(route('zumra.groups.show', $group))->assertOk()->assertSee('Notre organisation peut apporter');

        $this->from(route('zumra.groups.show', $group))->post(route('partnerships.store'), [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'ZUMRA',
            'context_reference' => $group->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PRIVATE',
        ])->assertRedirect(route('zumra.groups.show', $group));

        $this->get(route('zumra.groups.show', $group))->assertOk()->assertSee('Appui logistique');
    }

    public function test_a_member_without_a_managed_organization_does_not_see_the_propose_form(): void
    {
        $need = $this->need('IDN-PH-NEED-OWNER2');
        $this->signIn('IDN-PH-NO-ORG');

        $this->get(route('needs.show', $need))->assertOk()->assertDontSee('Notre organisation peut apporter');
    }

    // ===== Le fournisseur PERSON reste intact =====

    public function test_a_person_provided_partnership_still_renders_correctly_on_the_context_fiche(): void
    {
        $need = $this->need('IDN-PH-NEED-OWNER3');
        $this->profile('IDN-PH-PERSON-PROVIDER');
        $statement = $this->capability('IDN-PH-PERSON-PROVIDER', 'Comptabilité associative');
        app(PartnershipService::class)->propose('IDN-PH-PERSON-PROVIDER', [
            'provider_type' => 'PERSON',
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $statement->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->signIn('IDN-PH-NEED-OWNER3');
        $this->get(route('needs.show', $need))
            ->assertOk()
            ->assertSee('Comptabilité associative')
            ->assertSee('Membre '.'IDN-PH-PERSON-PROVIDER');
    }

    public function test_the_person_provider_sees_themselves_as_vous_on_the_context_fiche(): void
    {
        $need = $this->need('IDN-PH-NEED-OWNER4');
        $this->profile('IDN-PH-PERSON-PROVIDER2');
        $statement = $this->capability('IDN-PH-PERSON-PROVIDER2', 'Traduction');
        app(PartnershipService::class)->propose('IDN-PH-PERSON-PROVIDER2', [
            'provider_type' => 'PERSON',
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $statement->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->signIn('IDN-PH-PERSON-PROVIDER2');
        $content = $this->get(route('needs.show', $need))->assertOk()->getContent();
        self::assertMatchesRegularExpression('/Vous\s+apporte/', $content);
    }

    // ===== Activation / fin — autorité de contexte =====

    public function test_the_context_authority_can_activate_and_then_end_a_partnership(): void
    {
        $need = $this->need('IDN-PH-NEED-AUTH');
        $organization = $this->organization('IDN-PH-ORG-OWNER4');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER4', 'Suivi budgétaire');
        $partnership = app(PartnershipService::class)->propose('IDN-PH-ORG-OWNER4', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->signIn('IDN-PH-NEED-AUTH');
        $this->from(route('needs.show', $need))
            ->post(route('partnerships.activate', $partnership))
            ->assertRedirect(route('needs.show', $need));
        self::assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);

        $this->from(route('needs.show', $need))
            ->post(route('partnerships.end', $partnership), ['reason' => 'Collaboration achevée.'])
            ->assertRedirect(route('needs.show', $need));
        self::assertSame(Partnership::STATUS_ENDED, $partnership->fresh()->status);
    }

    public function test_a_non_authority_cannot_activate_or_end_a_partnership(): void
    {
        $need = $this->need('IDN-PH-NEED-AUTH2');
        $organization = $this->organization('IDN-PH-ORG-OWNER5');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER5', 'Suivi budgétaire');
        $partnership = app(PartnershipService::class)->propose('IDN-PH-ORG-OWNER5', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->signIn('IDN-PH-OUTSIDER');
        $this->post(route('partnerships.activate', $partnership))->assertForbidden();
        self::assertSame(Partnership::STATUS_PROPOSED, $partnership->fresh()->status);
    }

    // ===== Pause / reprise / retrait — fournisseur =====

    public function test_the_provider_can_pause_resume_and_withdraw(): void
    {
        $need = $this->need('IDN-PH-NEED-PROV');
        $organization = $this->organization('IDN-PH-ORG-OWNER6');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER6', 'Appui numérique');
        $service = app(PartnershipService::class);
        $partnership = $service->propose('IDN-PH-ORG-OWNER6', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ]);
        $service->activate($partnership, 'IDN-PH-NEED-PROV');

        $this->signIn('IDN-PH-ORG-OWNER6');
        $this->from(route('organizations.show', $organization))
            ->post(route('partnerships.pause', $partnership))
            ->assertRedirect(route('organizations.show', $organization));
        self::assertSame(Partnership::STATUS_PAUSED, $partnership->fresh()->status);

        $this->post(route('partnerships.resume', $partnership))->assertRedirect();
        self::assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);

        $this->post(route('partnerships.withdraw', $partnership))->assertRedirect();
        self::assertSame(Partnership::STATUS_ENDED, $partnership->fresh()->status);
    }

    public function test_a_non_provider_cannot_pause_or_withdraw(): void
    {
        $need = $this->need('IDN-PH-NEED-PROV2');
        $organization = $this->organization('IDN-PH-ORG-OWNER7');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER7', 'Appui numérique');
        $service = app(PartnershipService::class);
        $partnership = $service->propose('IDN-PH-ORG-OWNER7', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ]);
        $service->activate($partnership, 'IDN-PH-NEED-PROV2');

        $this->signIn('IDN-PH-OUTSIDER2');
        $this->post(route('partnerships.pause', $partnership))->assertForbidden();
        $this->post(route('partnerships.withdraw', $partnership))->assertForbidden();
        self::assertSame(Partnership::STATUS_ACTIVE, $partnership->fresh()->status);
    }

    // ===== Confidentialité =====

    public function test_a_private_partnership_never_appears_to_an_outsider(): void
    {
        $need = $this->need('IDN-PH-NEED-PRIV');
        $organization = $this->organization('IDN-PH-ORG-OWNER8');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER8', 'Capacité confidentielle');
        app(PartnershipService::class)->propose('IDN-PH-ORG-OWNER8', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PRIVATE',
        ]);

        $this->signIn('IDN-PH-OUTSIDER3');
        $this->get(route('needs.show', $need))->assertOk()->assertDontSee('Capacité confidentielle');
    }

    // ===== Aucune capacité Organisation fabriquée, aucun catalogue/G-POS =====

    public function test_no_organization_capability_catalog_is_fabricated_from_partnerships(): void
    {
        $need = $this->need('IDN-PH-NEED-NOFAB');
        $organization = $this->organization('IDN-PH-ORG-OWNER9');
        $capability = $this->organizationCapability($organization, 'IDN-PH-ORG-OWNER9', 'Une capacité quelconque');
        app(PartnershipService::class)->propose('IDN-PH-ORG-OWNER9', [
            'provider_type' => 'ORGANIZATION',
            'organization_reference' => $organization->public_reference,
            'context_type' => 'NEED',
            'context_reference' => $need->public_reference,
            'capability_statement_id' => $capability->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->signIn('IDN-PH-ORG-OWNER9');
        $content = $this->get(route('organizations.show', $organization))->assertOk()->getContent();

        self::assertStringContainsString('Collaborations', $content);
        // UIUX-006 — la section « Ce que cette organisation peut apporter » présente désormais ce
        // vocabulaire humain, mais le Partnership ne doit jamais y avoir fait fabriquer une
        // capacité supplémentaire : une seule capacité réellement déclarée doit apparaître, pas un
        // catalogue reconstitué depuis la collaboration.
        self::assertSame(1, CapabilityStatement::query()->where('organization_id', $organization->id)->count());
        self::assertStringContainsString('1 capacité', $content);
    }

    public function test_no_gpos_or_commercial_catalog_content_is_fabricated(): void
    {
        $need = $this->need('IDN-PH-NEED-NOGPOS');
        $organization = $this->organization('IDN-PH-ORG-OWNER10');

        $this->signIn('IDN-PH-ORG-OWNER10');
        foreach ([route('needs.show', $need), route('organizations.show', $organization)] as $url) {
            $content = $this->get($url)->assertOk()->getContent();
            self::assertStringNotContainsString('G-POS', $content);
            self::assertStringNotContainsStringIgnoringCase('catalogue', $content);
            self::assertStringNotContainsStringIgnoringCase('prix', $content);
            self::assertStringNotContainsStringIgnoringCase('stock', $content);
        }
    }

    // ===== Navigation : aucun catalogue global =====

    public function test_no_global_partnerships_navigation_entry_exists(): void
    {
        $this->signIn('IDN-PH-NAV');
        $content = $this->get(route('member.space'))->assertOk()->getContent();

        self::assertStringNotContainsString('/partenariats"', $content);
        self::assertStringNotContainsString('>Partenariats<', $content);
    }

    // ===== Helpers =====

    /** CAP-065/CAP-067 — une capacité réellement déclarée par un manager habilité de l'Organisation. */
    private function organizationCapability(Organization $organization, string $manager, string $label): CapabilityStatement
    {
        return app(OrganizationCapabilityService::class)->declare($organization, $manager, ['label' => $label]);
    }

    private function organization(string $founder): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        $organization = app(OrganizationService::class)->create($founder, [
            'name' => 'Organisation Partenariat '.Str::random(6),
            'description' => 'Une structure durable qui organise des activités réelles.',
            'type' => 'COOPERATIVE',
            'visibility' => Organization::VISIBILITY_PUBLIC,
        ]);
        self::assertSame(OrganizationMembership::ROLE_OWNER, $organization->memberships()->sole()->role);

        return $organization;
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

    private function need(string $owner, array $overrides = []): Need
    {
        return Need::query()->create(array_replace([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $owner,
            'author_core_reference' => $owner,
            'title' => 'Besoin d’appui pour un atelier',
            'context' => 'Un contexte suffisamment précis pour rendre ce besoin utile dans le réseau.',
            'category' => 'PARTNER',
            'capability_label' => 'Formation numérique',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $owner,
            'published_at' => now(),
        ], $overrides));
    }

    /** PROJET-ZUMRA-INVARIANT-001 — mémoïsé par acteur pour éviter une ZUMRA par appel. */
    private array $zumraByActor = [];

    private function zumraFor(string $actor): string
    {
        return $this->zumraByActor[$actor] ??= app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.Str::random(6), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function project(string $owner, array $overrides = []): Project
    {
        $this->activateProgram($owner);

        return app(ProjectService::class)->create($owner, array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor($owner), 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides), (new ProjectConfiguration)->defaults());
    }

    private function group(string $founder): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($founder, [
            'name' => 'ZUMRA Partenariat '.Str::random(6),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes pour apprendre, transmettre et produire une action utile ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'assume_primary_lead' => true,
        ]);
    }

    private function membership(ZumraGroup $group, string $identity, string $status): void
    {
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => $identity, 'status' => $status,
            'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $identity,
            'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function profile(string $identity, array $overrides = []): PersonProfile
    {
        return PersonProfile::query()->firstOrCreate(
            ['core_identity_reference' => $identity],
            array_replace([
                'discovery_reference' => (string) Str::uuid(),
                'discovery_display_name' => 'Membre '.$identity,
                'orientation_consent' => true,
                'orientation_consented_at' => now(),
                'discovery_consent' => true,
                'discovery_consented_at' => now(),
                'availability_status' => PersonProfile::AVAILABILITY_OPEN,
            ], $overrides)
        );
    }

    private function capability(string $identity, string $label): CapabilityStatement
    {
        return CapabilityStatement::query()->create([
            'core_identity_reference' => $identity,
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => true,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
        ]);
    }

    private function activateProgram(string $reference): void
    {
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists()) {
            return;
        }

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
                'jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
