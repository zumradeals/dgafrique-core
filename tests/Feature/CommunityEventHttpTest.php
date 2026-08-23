<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Community\CommunityEventService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Organizations\OrganizationService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CommunityEvent;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Organization;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-003 Phase B — première interface humaine de CommunityEvent (CAP-068) et navigation
 * contextuelle (retour ZUMRA/Organisation sur les fiches Besoin/Projet, découverte des
 * Événements réels depuis leur organisateur). Aucune autorité nouvelle : ces tests vérifient
 * que la page n'affiche jamais plus que ce que CommunityEventService/NeedService/ProjectService
 * autorisent déjà, et qu'aucune relation métier n'a été inventée.
 */
final class CommunityEventHttpTest extends TestCase
{
    use RefreshDatabase;

    // ===== Autorisation / visibilité =====

    public function test_an_outsider_cannot_view_an_internal_zumra_event_fiche(): void
    {
        $group = $this->group('IDN-EVT-LEADER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER', $this->eventPayload());

        $this->signIn('IDN-EVT-OUTSIDER');
        $this->get(route('community-events.show', $event))->assertNotFound();
    }

    public function test_anyone_signed_in_can_view_a_public_event_without_membership(): void
    {
        $organization = $this->organization('IDN-EVT-FOUNDER', ['visibility' => Organization::VISIBILITY_PRIVATE]);
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-EVT-FOUNDER', $this->eventPayload(['visibility' => CommunityEvent::VISIBILITY_PUBLIC]));

        $this->signIn('IDN-EVT-STRANGER');
        $this->get(route('community-events.show', $event))->assertOk()->assertSee($event->title);
    }

    // ===== Fiche : où suis-je / qui organise / quand / puis-je participer =====

    public function test_an_active_member_sees_the_organizer_the_date_and_the_status_on_the_fiche(): void
    {
        $group = $this->group('IDN-EVT-LEADER2');
        $this->membership($group, 'IDN-EVT-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER2', $this->eventPayload());

        $this->signIn('IDN-EVT-MEMBER');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee($group->name)
            ->assertSee('À venir')
            ->assertSee($event->scheduled_at->translatedFormat('d F Y'));
    }

    public function test_the_fiche_links_back_to_its_real_zumra_organizer(): void
    {
        $group = $this->group('IDN-EVT-LEADER3');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER3', $this->eventPayload());

        $this->signIn('IDN-EVT-LEADER3');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee(route('zumra.groups.show', $group), false);
    }

    public function test_the_fiche_links_back_to_its_real_organization_organizer(): void
    {
        $organization = $this->organization('IDN-EVT-FOUNDER2');
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-EVT-FOUNDER2', $this->eventPayload());

        $this->signIn('IDN-EVT-FOUNDER2');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee(route('organizations.show', $organization), false);
    }

    // ===== Participation =====

    public function test_a_member_can_register_and_unregister_through_the_fiche(): void
    {
        $group = $this->group('IDN-EVT-LEADER4');
        $this->membership($group, 'IDN-EVT-MEMBER4', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER4', $this->eventPayload());

        $this->signIn('IDN-EVT-MEMBER4');

        $this->post(route('community-events.register', $event))->assertRedirect();
        $this->get(route('community-events.show', $event))->assertOk()->assertSee('Vous êtes inscrit');

        $this->post(route('community-events.unregister', $event))->assertRedirect();
        $this->get(route('community-events.show', $event))->assertOk()->assertSee("ouvert à l'inscription", false);
    }

    public function test_a_terminated_event_no_longer_offers_participation(): void
    {
        $group = $this->group('IDN-EVT-LEADER5');
        $this->membership($group, 'IDN-EVT-MEMBER5', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-EVT-LEADER5', $this->eventPayload());
        $service->cancel($event, 'IDN-EVT-LEADER5', null);

        $this->signIn('IDN-EVT-MEMBER5');
        $this->get(route('community-events.show', $event->fresh()))
            ->assertOk()
            ->assertSee('annulé')
            ->assertDontSee("M'inscrire", false);
    }

    // ===== Confidentialité =====

    public function test_the_fiche_never_leaks_another_participants_identity(): void
    {
        $group = $this->group('IDN-EVT-LEADER6');
        $this->membership($group, 'IDN-EVT-MEMBER6A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->membership($group, 'IDN-EVT-MEMBER6B', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-EVT-LEADER6', $this->eventPayload());
        $service->register($event, 'IDN-EVT-MEMBER6A');
        $service->register($event, 'IDN-EVT-MEMBER6B');

        $this->signIn('IDN-EVT-MEMBER6A');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertDontSee('IDN-EVT-MEMBER6B');
    }

    // ===== Découverte contextuelle depuis l'organisateur =====

    public function test_a_zumra_fiche_shows_its_real_event_to_an_active_member(): void
    {
        $group = $this->group('IDN-EVT-LEADER7');
        $this->membership($group, 'IDN-EVT-MEMBER7', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER7', $this->eventPayload());

        $this->signIn('IDN-EVT-MEMBER7');
        $this->get(route('zumra.groups.show', $group))
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee(route('community-events.show', $event), false);
    }

    public function test_a_zumra_fiche_shows_its_real_open_mission_to_an_active_member(): void
    {
        $group = $this->group('IDN-EVT-LEADER11');
        $this->membership($group, 'IDN-EVT-MEMBER11', ZumraGroupMembership::STATUS_ACTIVE);
        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create('IDN-EVT-LEADER11', 'ZUMRA', $group->public_reference, [
            'title' => 'Organiser la logistique de la ZUMRA',
            'description' => 'Une description suffisamment précise pour cette mission de test.',
            'visibility' => Mission::VISIBILITY_CONTEXT,
        ]);
        $mission = $workflow->propose($mission, 'IDN-EVT-LEADER11');
        $mission = $workflow->officialize($mission, 'IDN-EVT-LEADER11', ['expected_result' => 'Résultat attendu de test.']);

        $this->signIn('IDN-EVT-MEMBER11');
        $this->get(route('zumra.groups.show', $group))
            ->assertOk()
            ->assertSee($mission->title)
            ->assertSee(route('missions.show', $mission), false);
    }

    public function test_an_organization_fiche_shows_its_real_event_to_a_viewer(): void
    {
        $organization = $this->organization('IDN-EVT-FOUNDER3', ['visibility' => Organization::VISIBILITY_PUBLIC]);
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-EVT-FOUNDER3', $this->eventPayload());

        $this->signIn('IDN-EVT-VISITOR');
        $this->get(route('organizations.show', $organization))
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee(route('community-events.show', $event), false);
    }

    // ===== Besoin/Projet : retour réel vers le contexte porteur =====

    public function test_a_need_owned_by_a_zumra_links_back_to_the_real_group(): void
    {
        $group = $this->group('IDN-EVT-LEADER8');
        $this->membership($group, 'IDN-EVT-MEMBER8', ZumraGroupMembership::STATUS_ACTIVE);
        $needService = app(NeedService::class);
        $need = $needService->create('IDN-EVT-MEMBER8', [
            'owner_type' => Need::OWNER_GROUP, 'group_reference' => $group->public_reference,
            'title' => 'Former l’équipe à la comptabilité', 'context' => 'Notre équipe tient ses activités mais ne maîtrise pas encore le suivi des recettes et dépenses.',
            'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC,
        ], (new NeedConfiguration)->defaults());
        $needService->transition($need, 'IDN-EVT-LEADER8', Need::STATUS_OPEN);

        $this->signIn('IDN-EVT-MEMBER8');
        $this->get(route('needs.show', $need))
            ->assertOk()
            ->assertSee(route('zumra.groups.show', $group), false);
    }

    public function test_a_project_owned_by_a_zumra_links_back_to_the_real_group(): void
    {
        $group = $this->group('IDN-EVT-LEADER9');
        $this->membership($group, 'IDN-EVT-MEMBER9', ZumraGroupMembership::STATUS_ACTIVE);
        $this->activateProgram('IDN-EVT-MEMBER9');
        $project = app(ProjectService::class)->create('IDN-EVT-MEMBER9', [
            'owner_type' => 'GROUP', 'group_reference' => $group->public_reference, 'source_need_reference' => null,
            'name' => 'Atelier collectif de couture',
            'summary' => 'Créer un espace pratique où des membres apprennent ensemble et produisent des services utiles.',
            'problem' => 'Des membres motivés disposent de peu de cadres pratiques pour apprendre et transformer leurs acquis.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs et accompagnement.',
            'beneficiaries' => 'Membres débutants de la ZUMRA.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation\nGestion de projet",
            'required_resources' => "Matériel\nLocal",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'ZUMRA_COLLECTIVE', 'visibility' => 'PUBLIC',
        ], (new ProjectConfiguration)->defaults());
        app(ProjectService::class)->transition($project, 'IDN-EVT-LEADER9', 'ADOPTED');

        $this->signIn('IDN-EVT-MEMBER9');
        $this->get(route('projects.show', $project->fresh()))
            ->assertOk()
            ->assertSee(route('zumra.groups.show', $group), false);
    }

    // ===== Aucune relation métier inventée =====

    public function test_no_organization_to_project_need_or_mission_relation_was_invented(): void
    {
        self::assertFalse(Route::has('organizations.projects.index'));
        self::assertFalse(Route::has('organizations.needs.index'));
        self::assertFalse(Route::has('organizations.missions.index'));
    }

    public function test_no_global_events_catalog_route_exists(): void
    {
        self::assertFalse(Route::has('community-events.index'));
    }

    public function test_mobile_tabbar_still_lists_exactly_the_five_canonical_entries(): void
    {
        $this->signIn('IDN-EVT-TABBAR');
        $content = $this->get(route('member.space'))->assertOk()->getContent();

        foreach (['Fil', 'Personnes', 'Agir', 'ZUMRA', 'Moi'] as $needle) {
            self::assertStringContainsString($needle, $content);
        }
        self::assertStringNotContainsString('Événements', $content);
        self::assertStringNotContainsString('/evenements', $content);
    }

    // ===== UIUX-007 — gabarit de création d'Événement (GET manquant, POST déjà réel) =====

    public function test_the_zumra_leader_can_open_and_submit_the_event_creation_form(): void
    {
        $group = $this->group('IDN-EVT-CREATE-LEADER');

        $this->signIn('IDN-EVT-CREATE-LEADER');
        $this->get(route('community-events.zumra.create', $group))->assertOk()->assertSee($group->name);

        $this->post(route('community-events.zumra.store', $group), $this->eventPayload())->assertRedirect();
        self::assertDatabaseHas('dg_community_events', ['organizer_type' => CommunityEvent::ORGANIZER_ZUMRA_GROUP, 'organizer_reference' => $group->id]);
    }

    public function test_a_zumra_member_without_leadership_cannot_open_the_event_creation_form(): void
    {
        $group = $this->group('IDN-EVT-CREATE-LEADER2');
        $this->membership($group, 'IDN-EVT-CREATE-MEMBER2', ZumraGroupMembership::STATUS_ACTIVE);

        $this->signIn('IDN-EVT-CREATE-MEMBER2');
        $this->get(route('community-events.zumra.create', $group))->assertForbidden();
    }

    public function test_the_organization_manager_can_open_and_submit_the_event_creation_form(): void
    {
        $organization = $this->organization('IDN-EVT-CREATE-OWNER');

        $this->signIn('IDN-EVT-CREATE-OWNER');
        $this->get(route('community-events.organization.create', $organization))->assertOk()->assertSee($organization->name);

        $this->post(route('community-events.organization.store', $organization), $this->eventPayload())->assertRedirect();
        self::assertDatabaseHas('dg_community_events', ['organizer_type' => CommunityEvent::ORGANIZER_ORGANIZATION, 'organizer_reference' => $organization->id]);
    }

    public function test_a_non_manager_cannot_open_the_organization_event_creation_form(): void
    {
        $organization = $this->organization('IDN-EVT-CREATE-OWNER2');

        $this->signIn('IDN-EVT-CREATE-OUTSIDER2');
        $this->get(route('community-events.organization.create', $organization))->assertForbidden();
    }

    // ===== Aucun impact Ledger/Finance =====

    public function test_viewing_and_registering_for_an_event_has_no_finance_side_effect(): void
    {
        $group = $this->group('IDN-EVT-LEADER10');
        $this->membership($group, 'IDN-EVT-MEMBER10', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVT-LEADER10', $this->eventPayload());

        $this->signIn('IDN-EVT-MEMBER10');
        $this->get(route('community-events.show', $event))->assertOk();
        $this->post(route('community-events.register', $event))->assertRedirect();

        self::assertSame(0, LedgerEntry::query()->count());
    }

    // ===== Helpers =====

    private function eventPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Atelier de coordination mensuel',
            'description' => 'Une rencontre réelle pour coordonner les actions du mois à venir.',
            'location' => 'Abidjan',
            'visibility' => CommunityEvent::VISIBILITY_INTERNAL,
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Événement '.Str::random(6),
            'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, transmission, hiérarchie responsable. ', 3),
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

    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Organisation Événement '.Str::random(6),
            'description' => 'Une structure durable qui organise des activités réelles.',
            'type' => 'COOPERATIVE', 'visibility' => Organization::VISIBILITY_PRIVATE,
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
