<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Community\CommunityEventService;
use App\Application\Organizations\OrganizationService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CommunityEvent;
use App\Models\Organization;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-004 — parcours organisateur complet de l'Événement (CAP-068) : découvrir → consulter →
 * participer → organiser → constater l'état final. Aucune capacité métier nouvelle : ces tests
 * vérifient que la fiche n'expose jamais plus que ce que CommunityEventService::update()/cancel()/
 * markCompleted()/participants() et ZumraGroupService::isLeader()/OrganizationService::isManager()
 * autorisent déjà — jamais une autorité recalculée dans Blade.
 */
final class CommunityEventOrganizerJourneyTest extends TestCase
{
    use RefreshDatabase;

    // ===== Le bon organisateur voit et peut gérer =====

    public function test_the_zumra_leader_sees_the_management_card_on_the_fiche(): void
    {
        $group = $this->group('IDN-ORG-LEADER1');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER1', $this->eventPayload());

        $this->signIn('IDN-ORG-LEADER1');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee('Vous organisez cet événement')
            ->assertSee('Marquer comme tenu')
            ->assertSee('Modifier les informations')
            ->assertSee("Annuler l'événement", false);
    }

    public function test_the_organization_manager_sees_the_management_card_on_the_fiche(): void
    {
        $organization = $this->organization('IDN-ORG-FOUNDER1');
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-ORG-FOUNDER1', $this->eventPayload());

        $this->signIn('IDN-ORG-FOUNDER1');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee('Vous organisez cet événement');
    }

    // ===== Un acteur sans autorité ne voit ni ne peut rien gérer =====

    public function test_an_ordinary_member_does_not_see_the_management_card(): void
    {
        $group = $this->group('IDN-ORG-LEADER2');
        $this->membership($group, 'IDN-ORG-MEMBER2', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER2', $this->eventPayload());

        $this->signIn('IDN-ORG-MEMBER2');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertDontSee('Vous organisez cet événement')
            ->assertDontSee('Marquer comme tenu');
    }

    public function test_an_ordinary_member_cannot_call_management_endpoints_directly(): void
    {
        $group = $this->group('IDN-ORG-LEADER3');
        $this->membership($group, 'IDN-ORG-MEMBER3', ZumraGroupMembership::STATUS_ACTIVE);
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER3', $this->eventPayload());

        $this->signIn('IDN-ORG-MEMBER3');
        $this->patch(route('community-events.update', $event), $this->editPayload())->assertForbidden();
        $this->post(route('community-events.cancel', $event))->assertForbidden();
        $this->post(route('community-events.complete', $event))->assertForbidden();
        self::assertSame(CommunityEvent::STATUS_SCHEDULED, $event->fresh()->status);
    }

    public function test_a_leader_of_another_zumra_cannot_manage_this_event(): void
    {
        $group = $this->group('IDN-ORG-LEADER4');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER4', $this->eventPayload(['visibility' => CommunityEvent::VISIBILITY_PUBLIC]));
        $this->group('IDN-ORG-OUTSIDER-LEADER');

        $this->signIn('IDN-ORG-OUTSIDER-LEADER');
        $this->get(route('community-events.show', $event))->assertOk()->assertDontSee('Vous organisez cet événement');
        $this->post(route('community-events.cancel', $event))->assertForbidden();
        self::assertSame(CommunityEvent::STATUS_SCHEDULED, $event->fresh()->status);
    }

    public function test_a_manager_of_another_organization_cannot_manage_this_event(): void
    {
        $organization = $this->organization('IDN-ORG-FOUNDER2');
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-ORG-FOUNDER2', $this->eventPayload());
        $this->organization('IDN-ORG-OUTSIDER-FOUNDER');

        $this->signIn('IDN-ORG-OUTSIDER-FOUNDER');
        $this->post(route('community-events.complete', $event))->assertForbidden();
        self::assertSame(CommunityEvent::STATUS_SCHEDULED, $event->fresh()->status);
    }

    // ===== Modification =====

    public function test_the_organizer_can_update_the_event_nominally_and_stays_in_its_context(): void
    {
        $group = $this->group('IDN-ORG-LEADER5');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER5', $this->eventPayload());

        $this->signIn('IDN-ORG-LEADER5');
        $this->from(route('community-events.show', $event))
            ->patch(route('community-events.update', $event), $this->editPayload(['title' => 'Titre corrigé après relecture']))
            ->assertRedirect(route('community-events.show', $event));

        self::assertSame('Titre corrigé après relecture', $event->fresh()->title);
        $this->get(route('community-events.show', $event))->assertOk()->assertSee('Titre corrigé après relecture');
    }

    public function test_updating_with_invalid_data_shows_errors_and_leaves_the_event_unchanged(): void
    {
        $group = $this->group('IDN-ORG-LEADER6');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER6', $this->eventPayload());
        $originalTitle = $event->title;

        $this->signIn('IDN-ORG-LEADER6');
        $this->from(route('community-events.show', $event))
            ->patch(route('community-events.update', $event), $this->editPayload(['title' => 'Xx']))
            ->assertRedirect(route('community-events.show', $event))
            ->assertSessionHasErrors('title');

        self::assertSame($originalTitle, $event->fresh()->title);
        $this->get(route('community-events.show', $event))->assertOk()->assertSee('Modifier les informations');
    }

    // ===== Annulation =====

    public function test_the_organizer_can_cancel_the_event_and_stays_in_its_context(): void
    {
        $group = $this->group('IDN-ORG-LEADER7');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-ORG-LEADER7', $this->eventPayload());

        $this->signIn('IDN-ORG-LEADER7');
        $this->from(route('community-events.show', $event))
            ->post(route('community-events.cancel', $event), ['note' => 'Empêchement de dernière minute.'])
            ->assertRedirect(route('community-events.show', $event));

        self::assertSame(CommunityEvent::STATUS_CANCELLED, $event->fresh()->status);
    }

    public function test_cancelling_an_already_terminal_event_is_refused(): void
    {
        $group = $this->group('IDN-ORG-LEADER8');
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER8', $this->eventPayload());
        $service->cancel($event, 'IDN-ORG-LEADER8', null);

        $this->signIn('IDN-ORG-LEADER8');
        $this->post(route('community-events.cancel', $event->fresh()))->assertStatus(409);
    }

    // ===== Complétion =====

    public function test_the_organizer_can_mark_the_event_completed_and_stays_in_its_context(): void
    {
        $organization = $this->organization('IDN-ORG-FOUNDER3');
        $event = app(CommunityEventService::class)->createForOrganization($organization, 'IDN-ORG-FOUNDER3', $this->eventPayload());

        $this->signIn('IDN-ORG-FOUNDER3');
        $this->from(route('community-events.show', $event))
            ->post(route('community-events.complete', $event))
            ->assertRedirect(route('community-events.show', $event));

        self::assertSame(CommunityEvent::STATUS_COMPLETED, $event->fresh()->status);
    }

    public function test_completing_an_already_cancelled_event_is_refused(): void
    {
        $group = $this->group('IDN-ORG-LEADER9');
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER9', $this->eventPayload());
        $service->cancel($event, 'IDN-ORG-LEADER9', null);

        $this->signIn('IDN-ORG-LEADER9');
        $this->post(route('community-events.complete', $event->fresh()))->assertStatus(409);
    }

    // ===== Comportement de la fiche après un état terminal =====

    public function test_the_fiche_shows_no_management_actions_once_completed(): void
    {
        $group = $this->group('IDN-ORG-LEADER10');
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER10', $this->eventPayload());
        $service->markCompleted($event, 'IDN-ORG-LEADER10', null);

        $this->signIn('IDN-ORG-LEADER10');
        $this->get(route('community-events.show', $event->fresh()))
            ->assertOk()
            ->assertSee('Tenu')
            ->assertDontSee('Vous organisez cet événement')
            ->assertDontSee("M'inscrire", false);
    }

    public function test_the_fiche_shows_no_management_actions_once_cancelled(): void
    {
        $group = $this->group('IDN-ORG-LEADER11');
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER11', $this->eventPayload());
        $service->cancel($event, 'IDN-ORG-LEADER11', null);

        $this->signIn('IDN-ORG-LEADER11');
        $this->get(route('community-events.show', $event->fresh()))
            ->assertOk()
            ->assertSee('Annulé')
            ->assertDontSee('Vous organisez cet événement');
    }

    public function test_registering_is_refused_once_completed_and_the_fiche_never_offers_it(): void
    {
        $group = $this->group('IDN-ORG-LEADER12');
        $this->membership($group, 'IDN-ORG-MEMBER12', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER12', $this->eventPayload());
        $service->markCompleted($event, 'IDN-ORG-LEADER12', null);

        $this->signIn('IDN-ORG-MEMBER12');
        $this->get(route('community-events.show', $event->fresh()))
            ->assertOk()
            ->assertSee('terminé')
            ->assertDontSee("M'inscrire", false);
        $this->post(route('community-events.register', $event->fresh()))->assertStatus(409);
    }

    // ===== Participants : agrégat autorisé, identités jamais exposées =====

    public function test_the_organizer_sees_an_aggregate_count_but_never_participant_identities(): void
    {
        $group = $this->group('IDN-ORG-LEADER13');
        $this->membership($group, 'IDN-ORG-MEMBER13A', ZumraGroupMembership::STATUS_ACTIVE);
        $this->membership($group, 'IDN-ORG-MEMBER13B', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER13', $this->eventPayload());
        $service->register($event, 'IDN-ORG-MEMBER13A');
        $service->register($event, 'IDN-ORG-MEMBER13B');

        $this->signIn('IDN-ORG-LEADER13');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertSee('2 inscrits')
            ->assertDontSee('IDN-ORG-MEMBER13A')
            ->assertDontSee('IDN-ORG-MEMBER13B');
    }

    public function test_an_ordinary_member_never_sees_the_participants_count(): void
    {
        $group = $this->group('IDN-ORG-LEADER14');
        $this->membership($group, 'IDN-ORG-MEMBER14', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER14', $this->eventPayload());
        $service->register($event, 'IDN-ORG-MEMBER14');

        $this->signIn('IDN-ORG-MEMBER14');
        $this->get(route('community-events.show', $event))
            ->assertOk()
            ->assertDontSee('Inscriptions')
            ->assertDontSee('inscrits');
    }

    // ===== Cartes ZUMRA/Organisation : l'état réel de l'Événement doit s'y refléter =====

    public function test_the_zumra_card_reflects_a_cancelled_events_real_state(): void
    {
        $group = $this->group('IDN-ORG-LEADER15');
        $this->membership($group, 'IDN-ORG-MEMBER15', ZumraGroupMembership::STATUS_ACTIVE);
        $service = app(CommunityEventService::class);
        $event = $service->createForZumraGroup($group, 'IDN-ORG-LEADER15', $this->eventPayload());
        $service->cancel($event, 'IDN-ORG-LEADER15', null);

        $this->signIn('IDN-ORG-MEMBER15');
        $this->get(route('zumra.groups.show', $group))->assertOk()->assertSee('Annulé');
    }

    public function test_the_organization_card_reflects_a_completed_events_real_state(): void
    {
        $organization = $this->organization('IDN-ORG-FOUNDER4');
        $service = app(CommunityEventService::class);
        $event = $service->createForOrganization($organization, 'IDN-ORG-FOUNDER4', $this->eventPayload());
        $service->markCompleted($event, 'IDN-ORG-FOUNDER4', null);

        $this->signIn('IDN-ORG-FOUNDER4');
        $this->get(route('organizations.show', $organization))->assertOk()->assertSee('Tenu');
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

    private function editPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Atelier de coordination mensuel',
            'description' => 'Une rencontre réelle, mise à jour, pour coordonner les actions du mois à venir.',
            'location' => 'Abidjan',
            'visibility' => CommunityEvent::VISIBILITY_INTERNAL,
            'scheduled_at' => now()->addWeeks(2)->toDateTimeString(),
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Organisateur '.Str::random(6),
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

    private function organization(string $founder, array $overrides = []): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Organisation Organisateur '.Str::random(6),
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
