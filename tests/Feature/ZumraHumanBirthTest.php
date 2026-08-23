<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contributions\ContributionService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\PortalAdministrator;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupActivity;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ZUMRA-HUMAN-BIRTH-001 Phase B — la naissance d'une ZUMRA reste bien plus légère que sa
 * structuration : activité principale obligatoire, charte différable, cinq responsabilités
 * jamais une condition d'existence. Activités dérivées (secondaires/sous-activités) avec
 * filiation explicite vers l'activité principale — jamais une validation IA, jamais une
 * taxonomie globale. Aucun matching géographique/activité construit ici.
 */
final class ZumraHumanBirthTest extends TestCase
{
    use RefreshDatabase;

    public function test_birth_page_is_a_four_step_human_journey_without_charter_or_technical_language(): void
    {
        $this->programMember('IDN-BIRTH-UI');
        $this->signIn('IDN-BIRTH-UI');

        $content = $this->get('/zumra/groupes/proposer')->assertOk()->getContent();

        self::assertStringContainsString('data-step="1"', $content);
        self::assertStringContainsString('data-step="4"', $content);
        self::assertStringContainsString('Aperçu de votre ZUMRA', $content);
        self::assertStringContainsString('Faire naître ma ZUMRA', $content);
        self::assertStringContainsString('activity_relation[]', $content);
        self::assertStringNotContainsString('internal_charter', $content);
        self::assertStringNotContainsString('Votre objectif fondateur', $content);
    }

    public function test_a_zumra_is_born_with_only_activity_objective_mode_and_name(): void
    {
        $this->programMember('IDN-BIRTH-MIN');
        $this->signIn('IDN-BIRTH-MIN');

        $this->post('/zumra/groupes', [
            'name' => 'Atelier minimal '.Str::random(5),
            'domain' => 'Couture',
            'founding_objective' => 'Apprendre et transmettre la couture aux jeunes du quartier, ensemble.',
            'participation_mode' => 'HYBRID',
        ])->assertRedirect();

        $group = ZumraGroup::query()->sole();
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->state);
        self::assertNull($group->internal_charter);
        self::assertNull($group->welcome_capacity);
        self::assertNull($group->location);
        self::assertSame(1, $group->active_member_count);
    }

    public function test_the_group_stays_real_and_visible_without_assuming_primary_lead(): void
    {
        $this->programMember('IDN-BIRTH-NOLEAD');
        $this->signIn('IDN-BIRTH-NOLEAD');

        $this->post('/zumra/groupes', collect($this->minimalPayload())->except('assume_primary_lead')->all())->assertRedirect();

        $group = ZumraGroup::query()->sole();
        self::assertSame(0, $group->roles()->where('status', ZumraGroupRole::STATUS_ACCEPTED)->count());
        self::assertSame(5, $group->roles()->where('status', ZumraGroupRole::STATUS_VACANT)->count());
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->state);
    }

    public function test_derived_activities_can_be_declared_at_birth_with_an_explicit_relation(): void
    {
        $this->programMember('IDN-BIRTH-ACT');
        $this->signIn('IDN-BIRTH-ACT');

        $this->post('/zumra/groupes', $this->minimalPayload() + [
            'activity_label' => ['Couture pour enfants', ''],
            'activity_relation' => ['Application de la couture générale à des tailles et motifs adaptés aux enfants.', 'Ligne vide ignorée'],
        ])->assertRedirect();

        $group = ZumraGroup::query()->sole();
        self::assertSame(1, $group->activities()->count());
        $activity = $group->activities()->sole();
        self::assertSame('Couture pour enfants', $activity->label);
        self::assertStringContainsString('Application de la couture générale', $activity->relation_to_principal);
    }

    public function test_a_leader_can_add_a_derived_activity_after_birth_but_must_state_the_relation(): void
    {
        $group = $this->group('IDN-BIRTH-LEAD');
        $service = app(ZumraGroupService::class);

        $activity = $service->addActivity($group, 'IDN-BIRTH-LEAD', 'Couture haute qualité', 'Une spécialisation exigeante de notre couture générale, pour des pièces uniques.');
        self::assertInstanceOf(ZumraGroupActivity::class, $activity);
        self::assertSame(1, $group->activities()->count());

        try {
            $service->addActivity($group, 'IDN-BIRTH-LEAD', 'Agriculture urbaine', '');
            self::fail('Une activité dérivée sans filiation explicite ne doit jamais être acceptée.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->getStatusCode());
        }
        self::assertSame(1, $group->activities()->count());
    }

    public function test_a_non_leader_cannot_add_a_derived_activity(): void
    {
        $group = $this->group('IDN-BIRTH-LEAD2');
        $this->programMember('IDN-BIRTH-MEMBER');
        $service = app(ZumraGroupService::class);

        try {
            $service->addActivity($group, 'IDN-BIRTH-MEMBER', 'Couture rapide', 'Dérive de notre activité principale.');
            self::fail('Un membre sans responsabilité ne doit jamais pouvoir déclarer une activité dérivée.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_a_charter_less_group_never_reaches_ready(): void
    {
        $group = $this->group('IDN-BIRTH-NOCHART');
        $service = app(ZumraGroupService::class);
        self::assertNull($group->internal_charter);

        $criteria = $service->evaluateStructuralReadiness($group);
        self::assertFalse($criteria['criteria']['charter_accepted']);
        self::assertFalse($criteria['structurally_ready']);
        self::assertContains('charter_accepted', $criteria['missing']);
    }

    public function test_a_leader_can_complete_the_charter_after_birth_and_it_then_counts_toward_readiness(): void
    {
        $group = $this->group('IDN-BIRTH-SETCHART');
        $service = app(ZumraGroupService::class);

        $service->setCharter($group, 'IDN-BIRTH-SETCHART', str_repeat('Respect, transmission et responsabilité partagée. ', 4));

        $fresh = $group->refresh();
        self::assertNotNull($fresh->internal_charter);
        self::assertTrue($service->evaluateStructuralReadiness($fresh)['criteria']['charter_accepted']);
    }

    public function test_a_non_leader_cannot_complete_the_charter(): void
    {
        $group = $this->group('IDN-BIRTH-SETCHART2');
        $this->programMember('IDN-BIRTH-MEMBER2');
        $service = app(ZumraGroupService::class);

        try {
            $service->setCharter($group, 'IDN-BIRTH-MEMBER2', str_repeat('Respect et transmission. ', 5));
            self::fail('Un membre sans responsabilité ne doit jamais pouvoir écrire la charte.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_the_charter_can_no_longer_be_set_once_the_group_left_constituting(): void
    {
        $group = $this->group('IDN-BIRTH-VALIDATED');
        $service = app(ZumraGroupService::class);
        $service->setCharter($group, 'IDN-BIRTH-VALIDATED', str_repeat('Respect, transmission et responsabilité partagée. ', 4));

        foreach (['FIRST_DEPUTY' => 'IDN-DEP1', 'SECOND_DEPUTY' => 'IDN-DEP2', 'FINANCE_LEAD' => 'IDN-DEP3', 'SOCIAL_RELATIONS_LEAD' => 'IDN-DEP4'] as $role => $subject) {
            $this->acceptRoleAsNewMember($group, $service, 'IDN-BIRTH-VALIDATED', $role, $subject);
        }

        $service->markReady($group->refresh(), $this->administrator());
        self::assertSame(ZumraGroup::STATE_READY, $group->refresh()->state);

        try {
            $service->setCharter($group, 'IDN-BIRTH-VALIDATED', 'Nouvelle charte tentée après READY.');
            self::fail('La charte ne doit plus être modifiable une fois sortie de CONSTITUTING.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->getStatusCode());
        }
    }

    public function test_welcome_capacity_and_location_are_accepted_and_optional(): void
    {
        $this->programMember('IDN-BIRTH-WELCOME');
        $this->signIn('IDN-BIRTH-WELCOME');

        $this->post('/zumra/groupes', $this->minimalPayload() + [
            'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY,
            'location' => 'Bouaké',
            'participation_mode' => 'PHYSICAL',
        ])->assertRedirect();

        $group = ZumraGroup::query()->sole();
        self::assertSame(ZumraGroup::WELCOME_PROGRESSIVELY, $group->welcome_capacity);
        self::assertSame('Bouaké', $group->location);
    }

    public function test_an_invalid_welcome_capacity_value_is_rejected(): void
    {
        $this->programMember('IDN-BIRTH-BADWELCOME');
        $this->signIn('IDN-BIRTH-BADWELCOME');

        $this->post('/zumra/groupes', $this->minimalPayload() + ['welcome_capacity' => 'INVENTED'])
            ->assertSessionHasErrors('welcome_capacity');
        self::assertSame(0, ZumraGroup::query()->count());
    }

    public function test_collective_contribution_stays_gated_on_validated_regardless_of_the_lighter_birth(): void
    {
        $group = $this->group('IDN-BIRTH-CONTRIB');
        self::assertNull($group->internal_charter);
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->state);

        try {
            app(ContributionService::class)->proposeCollective($group, 'IDN-BIRTH-CONTRIB');
            self::fail('La contribution collective ne doit jamais être proposable avant VALIDATED, charte différée ou non.');
        } catch (HttpException $exception) {
            self::assertContains($exception->getStatusCode(), [403, 409]);
        }
    }

    public function test_a_historical_zumra_created_with_a_charter_is_unaffected(): void
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => 'ZUMRA historique',
            'slug' => 'zumra-historique-'.Str::lower(Str::random(6)),
            'domain' => 'Menuiserie',
            'founding_objective' => str_repeat('Objectif fondateur historique. ', 3),
            'participation_mode' => 'PHYSICAL',
            'internal_charter' => str_repeat('Charte historique déjà rédigée avant ce chantier. ', 3),
            'state' => ZumraGroup::STATE_CONSTITUTING,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-HIST',
            'active_member_count' => 1,
        ]);

        self::assertNotNull($group->internal_charter);
        self::assertNull($group->welcome_capacity);
        self::assertNull($group->location);
        self::assertTrue(app(ZumraGroupService::class)->evaluateStructuralReadiness($group)['criteria']['charter_accepted']);
    }

    private function minimalPayload(): array
    {
        return [
            'name' => 'Atelier '.Str::random(6),
            'domain' => 'Couture',
            'founding_objective' => 'Apprendre et transmettre la couture aux jeunes du quartier, ensemble.',
            'participation_mode' => 'HYBRID',
            'assume_primary_lead' => '1',
        ];
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);
        $this->signIn($leader);
        $this->post('/zumra/groupes', $this->minimalPayload())->assertRedirect();

        return ZumraGroup::query()->latest()->firstOrFail();
    }

    private function acceptRoleAsNewMember(ZumraGroup $group, ZumraGroupService $service, string $leader, string $role, string $subject): void
    {
        $this->programMember($subject);
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => $subject, 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'INVITATION', 'initiated_by_core_reference' => $leader, 'joined_at' => now()]);
        $service->proposeRole($group, $leader, $role, $subject);
        $service->acceptRole($group, $subject, $role, 5, false);
    }

    private function administrator(): string
    {
        $reference = 'IDN-ADMIN-'.Str::random(6);
        PortalAdministrator::query()->create(['core_identity_reference' => $reference]);

        return $reference;
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
