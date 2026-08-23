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
use App\Models\Mission;
use App\Models\Need;
use App\Models\Organization;
use App\Models\Partnership;
use App\Models\PartnershipEvent;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-065 — un Partenariat relie un acteur déjà réel (Personne ou Organisation, CAP-066) à un
 * contexte déjà réel (Projet, ZUMRA, Besoin) sans dupliquer le domaine Capacité ni fabriquer
 * d'identité organisationnelle Core (CAP-067 reste hors périmètre, prouvé au test dédié).
 */
final class PartnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_person_proposes_a_partnership_with_a_real_capability_statement(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Formation numérique');

        $partnership = app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, ['capability_statement_id' => $statement->id]));

        self::assertSame(Partnership::STATUS_PROPOSED, $partnership->status);
        self::assertSame(Partnership::PROVIDER_PERSON, $partnership->provider_type);
        self::assertSame('IDN-PROVIDER', $partnership->provider_reference);
        self::assertSame($statement->id, $partnership->capability_statement_id);
        self::assertSame($statement->label, $partnership->capability_label);
    }

    public function test_a_person_without_a_capability_statement_id_is_refused(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');

        $this->assertAborts(422, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project)));
    }

    public function test_a_contradictory_capability_label_is_overridden_by_the_real_capability_statement(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Formation numérique');

        $partnership = app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, [
            'capability_statement_id' => $statement->id,
            'capability_label' => 'Une capacité totalement différente inventée par le client',
        ]));

        self::assertSame('Formation numérique', $partnership->capability_label);
    }

    public function test_creation_without_identity_is_refused_via_http(): void
    {
        $project = $this->project('IDN-OWNER');

        $this->post('/partenariats', $this->payload($project))
            ->assertRedirect('/connexion?next=%2Fpartenariats');

        self::assertSame(0, Partnership::query()->count());
    }

    public function test_a_capability_statement_belonging_to_someone_else_is_rejected(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-STRANGER');
        $statement = $this->capability('IDN-STRANGER', 'Comptabilité');

        $this->assertAborts(403, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, ['capability_statement_id' => $statement->id])));
    }

    public function test_a_capability_without_matching_consent_is_rejected(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Comptabilité', ['matching_consent' => false]);

        $this->assertAborts(422, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, ['capability_statement_id' => $statement->id])));
    }

    public function test_an_inaccessible_context_is_refused(): void
    {
        $privateProject = $this->project('IDN-OWNER', ['visibility' => 'PRIVATE']);

        $this->assertAborts(404, fn () => app(PartnershipService::class)->propose('IDN-STRANGER', $this->payload($privateProject)));
    }

    public function test_a_nonexistent_context_is_refused(): void
    {
        $fake = new Project(['public_reference' => (string) Str::uuid()]);

        $this->assertAborts(404, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($fake)));
    }

    public function test_paused_availability_blocks_a_new_person_proposal(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER', ['availability_status' => PersonProfile::AVAILABILITY_PAUSED]);
        $statement = $this->capability('IDN-PROVIDER', 'Formation numérique');

        $this->assertAborts(409, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, ['capability_statement_id' => $statement->id])));
    }

    public function test_activation_requires_context_authority(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');

        $this->assertAborts(403, fn () => app(PartnershipService::class)->activate($partnership, 'IDN-OUTSIDER'));
    }

    public function test_activation_by_context_authority_succeeds(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');

        $activated = app(PartnershipService::class)->activate($partnership, 'IDN-OWNER');

        self::assertSame(Partnership::STATUS_ACTIVE, $activated->status);
        self::assertSame('IDN-OWNER', $activated->decided_by_core_reference);
        self::assertNotNull($activated->decided_at);
    }

    public function test_activating_an_already_active_partnership_fails(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $service = app(PartnershipService::class);
        $service->activate($partnership, 'IDN-OWNER');

        $this->assertAborts(409, fn () => $service->activate($partnership, 'IDN-OWNER'));
    }

    public function test_provider_can_pause_and_resume_an_active_partnership(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $service = app(PartnershipService::class);
        $service->activate($partnership, 'IDN-OWNER');

        $paused = $service->pause($partnership, 'IDN-PROVIDER');
        self::assertSame(Partnership::STATUS_PAUSED, $paused->status);

        $resumed = $service->resume($partnership, 'IDN-PROVIDER');
        self::assertSame(Partnership::STATUS_ACTIVE, $resumed->status);
    }

    public function test_pausing_by_a_non_provider_is_refused(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $service = app(PartnershipService::class);
        $service->activate($partnership, 'IDN-OWNER');

        $this->assertAborts(403, fn () => $service->pause($partnership, 'IDN-OWNER'));
    }

    public function test_provider_can_withdraw_the_capability(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');

        $ended = app(PartnershipService::class)->withdraw($partnership, 'IDN-PROVIDER');

        self::assertSame(Partnership::STATUS_ENDED, $ended->status);
        self::assertTrue(PartnershipEvent::query()->where('partnership_id', $partnership->id)->where('event', 'CAPABILITY_WITHDRAWN')->exists());
    }

    public function test_context_authority_can_end_the_partnership(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');

        $ended = app(PartnershipService::class)->end($partnership, 'IDN-OWNER', 'Besoin couvert autrement.');

        self::assertSame(Partnership::STATUS_ENDED, $ended->status);
        self::assertTrue(PartnershipEvent::query()->where('partnership_id', $partnership->id)->where('event', 'PARTNERSHIP_ENDED')->exists());
    }

    public function test_ending_an_already_ended_partnership_fails(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $service = app(PartnershipService::class);
        $service->withdraw($partnership, 'IDN-PROVIDER');

        $this->assertAborts(409, fn () => $service->end($partnership, 'IDN-OWNER'));
    }

    public function test_organization_with_an_active_capability_can_be_proposed_as_provider(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');
        $capability = $this->organizationCapability($organization, 'IDN-FOUNDER', 'Salle de formation et matériel informatique');

        $partnership = app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_statement_id' => $capability->id,
        ]));

        self::assertSame(Partnership::PROVIDER_ORGANIZATION, $partnership->provider_type);
        self::assertSame($organization->id, $partnership->provider_reference);
        self::assertSame($capability->id, $partnership->capability_statement_id, 'CAP-067 : la capacité fournie référence une CapabilityStatement Organisation réellement déclarée.');
        self::assertSame('Salle de formation et matériel informatique', $partnership->capability_label, 'capability_label reste un snapshot dérivé de la capacité liée, jamais un texte libre.');
    }

    public function test_organization_without_any_capability_cannot_fabricate_one_from_a_free_label(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');

        $this->assertAborts(422, fn () => app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_label' => 'Une capacité fabriquée depuis le formulaire',
        ])));
    }

    public function test_a_capability_belonging_to_another_organization_is_refused(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');
        $otherOrganization = $this->organization('IDN-OTHER-FOUNDER');
        $foreignCapability = $this->organizationCapability($otherOrganization, 'IDN-OTHER-FOUNDER', 'Capacité de l’autre organisation');

        $this->assertAborts(403, fn () => app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_statement_id' => $foreignCapability->id,
        ])));
    }

    public function test_a_manager_personal_capability_never_counts_as_an_organization_capability(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');
        $personalCapability = $this->capability('IDN-FOUNDER', 'Comptabilité personnelle du fondateur');

        $this->assertAborts(403, fn () => app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_statement_id' => $personalCapability->id,
        ])));
    }

    public function test_an_archived_organization_capability_cannot_be_proposed_for_a_new_partnership(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');
        $capability = $this->organizationCapability($organization, 'IDN-FOUNDER', 'Formation ponctuelle');
        app(OrganizationCapabilityService::class)->archive($organization, 'IDN-FOUNDER', $capability);

        $this->assertAborts(422, fn () => app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_statement_id' => $capability->id,
        ])));
    }

    public function test_archiving_the_linked_capability_after_the_fact_never_retroactively_breaks_the_partnership(): void
    {
        $project = $this->project('IDN-OWNER');
        $organization = $this->organization('IDN-FOUNDER');
        $capability = $this->organizationCapability($organization, 'IDN-FOUNDER', 'Formation en gestion associative');

        $partnership = app(PartnershipService::class)->propose('IDN-FOUNDER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
            'capability_statement_id' => $capability->id,
        ]));
        $partnership = app(PartnershipService::class)->activate($partnership, 'IDN-OWNER');

        app(OrganizationCapabilityService::class)->archive($organization, 'IDN-FOUNDER', $capability);

        // Le Partenariat déjà conclu représente un fait historique réel : il reste intelligible et
        // pleinement gouvernable (pause/reprise/retrait), même si la capacité qui l'a justifié a
        // depuis été archivée par l'organisation. capability_label reste le snapshot pris au
        // moment de la proposition, distinct de la capacité actuelle du fournisseur.
        self::assertSame('Formation en gestion associative', $partnership->fresh()->capability_label);
        self::assertSame($capability->id, $partnership->fresh()->capability_statement_id);

        $paused = app(PartnershipService::class)->pause($partnership, 'IDN-FOUNDER');
        self::assertSame(Partnership::STATUS_PAUSED, $paused->status);
        $resumed = app(PartnershipService::class)->resume($paused, 'IDN-FOUNDER');
        self::assertSame(Partnership::STATUS_ACTIVE, $resumed->status);
        $ended = app(PartnershipService::class)->end($resumed, 'IDN-OWNER');
        self::assertSame(Partnership::STATUS_ENDED, $ended->status);
    }

    public function test_organization_provider_requires_a_real_manager(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->fakeCoreOrganizationProvisioning();
        $organization = app(OrganizationService::class)->create('IDN-FOUNDER', [
            'name' => 'Association locale',
            'description' => 'Une structure indépendante du projet testé.',
            'type' => 'ASSOCIATION',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ]);

        $this->assertAborts(403, fn () => app(PartnershipService::class)->propose('IDN-NOT-A-MEMBER', $this->payload($project, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $organization->public_reference,
        ])));
    }

    public function test_proposing_never_creates_a_new_capability_statement(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Logistique');
        $before = CapabilityStatement::query()->count();

        app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($project, ['capability_statement_id' => $statement->id]));

        self::assertSame($before, CapabilityStatement::query()->count());
    }

    public function test_no_automatic_organization_project_or_group_is_created(): void
    {
        $project = $this->project('IDN-OWNER');
        $organizationsBefore = Organization::query()->count();
        $projectsBefore = Project::query()->count();
        $needsBefore = Need::query()->count();
        $groupsBefore = ZumraGroup::query()->count();
        $missionsBefore = Mission::query()->count();

        $this->partnership($project, 'IDN-PROVIDER');

        self::assertSame($organizationsBefore, Organization::query()->count());
        self::assertSame($projectsBefore, Project::query()->count());
        self::assertSame($needsBefore, Need::query()->count());
        self::assertSame($groupsBefore, ZumraGroup::query()->count());
        self::assertSame($missionsBefore, Mission::query()->count());
    }

    public function test_isolation_between_organizations(): void
    {
        $projectA = $this->project('IDN-OWNER-A');
        $orgA = $this->organization('IDN-FOUNDER-A');
        $capabilityA = $this->organizationCapability($orgA, 'IDN-FOUNDER-A', 'Capacité de l’organisation A');
        $partnershipA = app(PartnershipService::class)->propose('IDN-FOUNDER-A', $this->payload($projectA, [
            'provider_type' => Partnership::PROVIDER_ORGANIZATION,
            'organization_reference' => $orgA->public_reference,
            'capability_statement_id' => $capabilityA->id,
        ]));
        $partnershipA = app(PartnershipService::class)->activate($partnershipA, 'IDN-OWNER-A');

        $orgB = $this->organization('IDN-FOUNDER-B');

        self::assertFalse(app(PartnershipService::class)->canView($partnershipA, 'IDN-FOUNDER-B'));
        $this->assertAborts(403, fn () => app(PartnershipService::class)->pause($partnershipA, 'IDN-FOUNDER-B'));
    }

    public function test_private_partnership_never_leaks_through_the_index_listing(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->partnership($project, 'IDN-PROVIDER', ['capability_label' => 'Capacité confidentielle']);
        $this->signIn('IDN-STRANGER');

        $this->get('/partenariats')
            ->assertOk()
            ->assertJsonMissing(['capability_label' => 'Capacité confidentielle']);
    }

    public function test_a_public_active_partnership_is_visible_to_any_member(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER', ['visibility' => Partnership::VISIBILITY_PUBLIC]);
        app(PartnershipService::class)->activate($partnership, 'IDN-OWNER');

        self::assertTrue(app(PartnershipService::class)->canView($partnership, 'IDN-ANY-MEMBER'));
    }

    public function test_no_mutation_happens_while_reading(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $updatedAtBefore = $partnership->updated_at;

        app(PartnershipService::class)->canView($partnership, 'IDN-STRANGER');

        self::assertEquals($updatedAtBefore, $partnership->refresh()->updated_at);
    }

    public function test_full_lifecycle_produces_the_expected_events(): void
    {
        $project = $this->project('IDN-OWNER');
        $partnership = $this->partnership($project, 'IDN-PROVIDER');
        $service = app(PartnershipService::class);

        $service->activate($partnership, 'IDN-OWNER');
        $service->pause($partnership, 'IDN-PROVIDER');
        $service->resume($partnership, 'IDN-PROVIDER');
        $service->withdraw($partnership, 'IDN-PROVIDER');

        $events = PartnershipEvent::query()->where('partnership_id', $partnership->id)->pluck('event')->all();
        self::assertSame([
            'PARTNERSHIP_PROPOSED', 'PARTNERSHIP_ACTIVATED', 'PARTNERSHIP_PAUSED', 'PARTNERSHIP_RESUMED', 'CAPABILITY_WITHDRAWN',
        ], $events);
    }

    public function test_a_person_can_hold_partnerships_in_several_contexts_at_once(): void
    {
        $projectOne = $this->project('IDN-OWNER-1', ['name' => 'Premier projet']);
        $projectTwo = $this->project('IDN-OWNER-2', ['name' => 'Second projet']);

        $this->partnership($projectOne, 'IDN-PROVIDER');
        $this->partnership($projectTwo, 'IDN-PROVIDER');

        self::assertSame(2, Partnership::query()->where('provider_reference', 'IDN-PROVIDER')->count());
    }

    public function test_a_zumra_context_partnership_is_gated_by_active_membership(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Formation numérique');

        $this->assertAborts(404, fn () => app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($group, ['capability_statement_id' => $statement->id])));

        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-PROVIDER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-PROVIDER', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        $partnership = app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($group, ['capability_statement_id' => $statement->id]));
        self::assertSame(Partnership::CONTEXT_ZUMRA, $partnership->context_type);

        $activated = app(PartnershipService::class)->activate($partnership, 'IDN-LEADER');
        self::assertSame(Partnership::STATUS_ACTIVE, $activated->status);
    }

    public function test_a_need_context_partnership_can_be_proposed_and_activated(): void
    {
        $need = $this->need('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Formation numérique');

        $partnership = app(PartnershipService::class)->propose('IDN-PROVIDER', $this->payload($need, ['capability_statement_id' => $statement->id]));
        self::assertSame(Partnership::CONTEXT_NEED, $partnership->context_type);

        $activated = app(PartnershipService::class)->activate($partnership, 'IDN-OWNER');
        self::assertSame(Partnership::STATUS_ACTIVE, $activated->status);
    }

    public function test_the_member_route_renders_a_real_created_partnership(): void
    {
        $project = $this->project('IDN-OWNER');
        $this->profile('IDN-PROVIDER');
        $statement = $this->capability('IDN-PROVIDER', 'Mentorat technique');
        $this->signIn('IDN-PROVIDER');

        $this->post('/partenariats', $this->payload($project, ['capability_statement_id' => $statement->id]))
            ->assertRedirect();

        self::assertSame(1, Partnership::query()->count());
        self::assertSame('Mentorat technique', Partnership::query()->first()->capability_label);
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

    private function partnership(Project|Need|ZumraGroup $context, string $provider, array $overrides = []): Partnership
    {
        $this->profile($provider);

        if (($overrides['provider_type'] ?? Partnership::PROVIDER_PERSON) === Partnership::PROVIDER_PERSON
            && ! isset($overrides['capability_statement_id'])) {
            $label = $overrides['capability_label'] ?? 'Formation numérique';
            unset($overrides['capability_label']);
            $statement = CapabilityStatement::query()->firstOrCreate(
                ['core_identity_reference' => $provider, 'kind' => CapabilityStatement::KIND_POSSESSED, 'normalized_label' => mb_strtolower($label)],
                ['label' => $label, 'matching_consent' => true, 'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE]
            );
            $overrides['capability_statement_id'] = $statement->id;
        }

        return app(PartnershipService::class)->propose($provider, $this->payload($context, $overrides));
    }

    private function payload(Project|Need|ZumraGroup $context, array $overrides = []): array
    {
        $contextType = match (true) {
            $context instanceof Project => Partnership::CONTEXT_PROJECT,
            $context instanceof Need => Partnership::CONTEXT_NEED,
            $context instanceof ZumraGroup => Partnership::CONTEXT_ZUMRA,
        };

        return array_replace([
            'provider_type' => Partnership::PROVIDER_PERSON,
            'context_type' => $contextType,
            'context_reference' => $context->public_reference,
            'capability_label' => 'Formation numérique',
            'description' => 'Un appui concret proposé dans ce contexte.',
            'visibility' => Partnership::VISIBILITY_PRIVATE,
        ], $overrides);
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

    private function capability(string $identity, string $label, array $overrides = []): CapabilityStatement
    {
        return CapabilityStatement::query()->create(array_replace([
            'core_identity_reference' => $identity,
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => true,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
        ], $overrides));
    }

    /** CAP-067 — une Organisation, raccordée à GAMAD Core (identités simulées). */
    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Organisation '.Str::random(6),
            'description' => 'Une structure durable indépendante, utilisée pour ce parcours de test.',
            'type' => 'ASSOCIATION',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ], $overrides));
    }

    /** CAP-065/CAP-067 — une capacité réellement déclarée par un manager habilité de l'Organisation. */
    private function organizationCapability(Organization $organization, string $manager, string $label): CapabilityStatement
    {
        return app(OrganizationCapabilityService::class)->declare($organization, $manager, ['label' => $label]);
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

    private function group(string $founder, array $overrides = []): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($founder, array_replace([
            'name' => 'ZUMRA partenariat '.Str::random(5),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes pour apprendre, transmettre et produire une action utile ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'assume_primary_lead' => true,
        ], $overrides));
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
