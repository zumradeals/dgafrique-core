<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Community\CommunityEventService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CommunityEvent;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraEventSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_gets_the_human_event_space_with_real_events(): void
    {
        $group = $this->group('IDN-EVENT-LEADER');
        $this->membership($group, 'IDN-EVENT-MEMBER');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVENT-LEADER', $this->payload());

        $this->signIn('IDN-EVENT-MEMBER');
        $this->get(route('community-events.zumra.index', $group))
            ->assertOk()
            ->assertSee('Se retrouver pour apprendre et agir.')
            ->assertSee($event->title)
            ->assertSee(route('community-events.show', $event), false)
            ->assertDontSee('Créer un événement');
    }

    public function test_leader_sees_creation_action_and_can_open_the_real_form(): void
    {
        $group = $this->group('IDN-EVENT-LEADER2');
        $this->signIn('IDN-EVENT-LEADER2');

        $this->get(route('community-events.zumra.index', $group))
            ->assertOk()
            ->assertSee('Créer un événement')
            ->assertSee(route('community-events.zumra.create', $group), false);

        $this->get(route('community-events.zumra.create', $group))
            ->assertOk()
            ->assertSee('Organiser une rencontre utile.');
    }

    public function test_outsider_cannot_open_internal_zumra_event_space(): void
    {
        $group = $this->group('IDN-EVENT-LEADER3');
        $this->signIn('IDN-EVENT-OUTSIDER');

        $this->get(route('community-events.zumra.index', $group))->assertNotFound();
    }

    public function test_json_clients_keep_the_existing_cap068_contract(): void
    {
        $group = $this->group('IDN-EVENT-LEADER4');
        $event = app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-EVENT-LEADER4', $this->payload());
        $this->signIn('IDN-EVENT-LEADER4');

        $this->getJson(route('community-events.zumra.index', $group))
            ->assertOk()
            ->assertJsonPath('events.0.public_reference', $event->public_reference)
            ->assertJsonPath('events.0.title', $event->title);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Événement '.Str::random(6),
            'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre, se coordonner et agir ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, transmission et responsabilité. ', 3),
            'assume_primary_lead' => true,
        ]);
    }

    private function membership(ZumraGroup $group, string $reference): void
    {
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $reference,
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => $reference,
            'joined_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'title' => 'Atelier collectif de préparation',
            'description' => 'Une rencontre réelle pour préparer ensemble la prochaine action de la ZUMRA.',
            'location' => 'Abidjan',
            'visibility' => CommunityEvent::VISIBILITY_INTERNAL,
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
        ];
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre GAMAD',
                'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
