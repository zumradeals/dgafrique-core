<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zumra\CollectiveCapabilityConfiguration;
use App\Application\Zumra\CollectiveCapabilityProfile;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CollectiveCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_individual_capability_is_aggregated_without_group_specific_consent(): void
    {
        $group = $this->group('IDN-FOUNDER');
        $this->capability('IDN-FOUNDER', 'Couture');

        self::assertTrue($this->profile($group)->isEmpty());
    }

    public function test_only_active_consented_and_discoverable_members_build_the_collective_profile(): void
    {
        $group = $this->group('IDN-FIRST');
        $this->member($group, 'IDN-SECOND', true);
        $this->member($group, 'IDN-LEFT', true, ZumraGroupMembership::STATUS_LEFT);
        $group->memberships()->where('core_identity_reference', 'IDN-FIRST')->update(['collective_capability_consent' => true, 'collective_capability_consented_at' => now()]);
        $this->capability('IDN-FIRST', 'Design graphique');
        $this->capability('IDN-SECOND', 'Design graphique');
        $this->capability('IDN-LEFT', 'Design graphique');
        $this->capability('IDN-SECOND', 'Comptabilité', false);

        $profile = app(CollectiveCapabilityProfile::class)->forGroup($group, ['minimum_members' => 2, 'max_capabilities' => 24]);

        self::assertCount(1, $profile);
        self::assertSame('Design graphique', $profile->first()->label);
        self::assertSame(2, (int) $profile->first()->member_count);
    }

    public function test_collective_profiles_are_strictly_isolated_between_groups(): void
    {
        $first = $this->group('IDN-FIRST');
        $second = $this->group('IDN-SECOND');
        $first->memberships()->update(['collective_capability_consent' => true, 'collective_capability_consented_at' => now()]);
        $second->memberships()->update(['collective_capability_consent' => true, 'collective_capability_consented_at' => now()]);
        $this->capability('IDN-FIRST', 'Menuiserie');
        $this->capability('IDN-SECOND', 'Agriculture');

        self::assertSame(['Menuiserie'], $this->profile($first)->pluck('label')->all());
        self::assertSame(['Agriculture'], $this->profile($second)->pluck('label')->all());
    }

    public function test_a_member_can_enable_and_withdraw_consent_immediately(): void
    {
        $group = $this->group('IDN-MEMBER');
        $this->capability('IDN-MEMBER', 'Développement web');
        $this->signIn('IDN-MEMBER');

        $this->put(route('zumra.groups.collective-capabilities.consent', $group), ['collective_capability_consent' => '1'])->assertRedirect();
        self::assertSame(['Développement web'], $this->profile($group)->pluck('label')->all());

        $this->put(route('zumra.groups.collective-capabilities.consent', $group), [])->assertRedirect();
        self::assertTrue($this->profile($group)->isEmpty());
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'COLLECTIVE_CAPABILITY_CONSENTED')->count());
        self::assertSame(1, ZumraGroupEvent::query()->where('event', 'COLLECTIVE_CAPABILITY_WITHDRAWN')->count());
    }

    public function test_group_page_never_exposes_contributing_identity_references(): void
    {
        $group = $this->group('IDN-PRIVATE-REFERENCE');
        $group->memberships()->update(['collective_capability_consent' => true, 'collective_capability_consented_at' => now()]);
        $this->capability('IDN-PRIVATE-REFERENCE', 'Formation numérique');
        $this->signIn('IDN-PRIVATE-REFERENCE');

        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Formation numérique')
            ->assertSee('1 membre volontaire et mobilisable')
            ->assertDontSee('IDN-PRIVATE-REFERENCE');
    }

    public function test_an_administrator_configures_aggregation_without_deployment(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');
        $this->put('/administration/capacites-collectives', [
            'section_title' => 'Forces mobilisables',
            'section_intro' => 'Une introduction administrable.',
            'empty_text' => 'Aucune force visible.',
            'consent_label' => 'Je consens à cette agrégation.',
            'minimum_members' => 3,
            'max_capabilities' => 30,
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(CollectiveCapabilityConfiguration::KEY);
        self::assertSame(3, $setting->value['minimum_members']);
        self::assertSame(30, $setting->value['max_capabilities']);
        self::assertSame('IDN-ADMIN', $setting->updated_by_core_reference);
    }

    private function group(string $founder): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($founder, [
            'name' => 'ZUMRA '.$founder,
            'domain' => 'Développement',
            'founding_objective' => 'Réunir des capacités réelles pour transmettre, apprendre et construire ensemble des solutions utiles.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect de la dignité, de la hiérarchie, du consentement, de la transmission et des décisions collectives responsables.',
            'assume_primary_lead' => true,
        ]);
    }

    private function member(ZumraGroup $group, string $identity, bool $consent, string $status = ZumraGroupMembership::STATUS_ACTIVE): void
    {
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => $identity, 'status' => $status, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $identity, 'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null, 'collective_capability_consent' => $consent, 'collective_capability_consented_at' => $consent ? now() : null]);
    }

    private function capability(string $identity, string $label, bool $discoverable = true): void
    {
        CapabilityStatement::query()->create(['core_identity_reference' => $identity, 'kind' => CapabilityStatement::KIND_POSSESSED, 'label' => $label, 'normalized_label' => mb_strtolower($label), 'matching_consent' => true, 'visibility' => $discoverable ? CapabilityStatement::VISIBILITY_DISCOVERABLE : CapabilityStatement::VISIBILITY_PRIVATE]);
    }

    private function profile(ZumraGroup $group)
    {
        return app(CollectiveCapabilityProfile::class)->forGroup($group, (new CollectiveCapabilityConfiguration())->defaults());
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
