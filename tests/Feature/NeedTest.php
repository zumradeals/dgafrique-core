<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class NeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_person_publishes_an_autonomous_need_and_event(): void
    {
        $this->signIn('IDN-AUTHOR');
        $this->post('/besoins', $this->payload())->assertRedirect();

        $need = Need::query()->sole();
        self::assertSame(Need::OWNER_PERSON, $need->owner_type);
        self::assertSame(Need::STATUS_OPEN, $need->status);
        self::assertSame('IDN-AUTHOR', $need->owner_reference);
        self::assertSame('NEED_PUBLISHED', NeedEvent::query()->sole()->event);
    }

    public function test_a_group_member_proposes_and_a_leader_publishes_the_need(): void
    {
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now()]);
        $service = app(NeedService::class);
        $need = $service->create('IDN-MEMBER', $this->payload(['owner_type' => Need::OWNER_GROUP, 'group_reference' => $group->public_reference, 'visibility' => Need::VISIBILITY_PUBLIC]), (new NeedConfiguration())->defaults());

        self::assertSame(Need::STATUS_PROPOSED, $need->status);
        self::assertFalse($service->canView($need, 'IDN-OUTSIDER'));
        $service->transition($need, 'IDN-LEADER', Need::STATUS_OPEN);
        self::assertSame(Need::STATUS_OPEN, $need->refresh()->status);
        self::assertTrue($service->canView($need, 'IDN-OUTSIDER'));
    }

    public function test_an_outsider_cannot_publish_for_a_group(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(NeedService::class)->create('IDN-OUTSIDER', $this->payload(['owner_type' => Need::OWNER_GROUP, 'group_reference' => $group->public_reference]), (new NeedConfiguration())->defaults());
    }

    public function test_private_need_is_isolated_and_public_need_is_visible(): void
    {
        $service = app(NeedService::class);
        $private = $service->create('IDN-ONE', $this->payload(['visibility' => Need::VISIBILITY_PRIVATE]), (new NeedConfiguration())->defaults());
        $public = $service->create('IDN-ONE', $this->payload(['title' => 'Un second besoin concret', 'visibility' => Need::VISIBILITY_PUBLIC]), (new NeedConfiguration())->defaults());

        self::assertFalse($service->canView($private, 'IDN-TWO'));
        self::assertTrue($service->canView($public, 'IDN-TWO'));
    }

    public function test_resolution_requires_a_real_note_and_is_journaled(): void
    {
        $service = app(NeedService::class);
        $need = $service->create('IDN-OWNER', $this->payload(), (new NeedConfiguration())->defaults());
        $service->transition($need, 'IDN-OWNER', Need::STATUS_IN_PROGRESS);
        $service->transition($need->refresh(), 'IDN-OWNER', Need::STATUS_RESOLVED, 'La compétence recherchée a rejoint le groupe et la transmission a commencé.');

        self::assertSame(Need::STATUS_RESOLVED, $need->refresh()->status);
        self::assertNotNull($need->resolved_at);
        self::assertSame(1, NeedEvent::query()->where('event', 'NEED_RESOLVED')->count());
    }

    public function test_administrator_configures_categories_and_limits_without_deployment(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');
        $this->put('/administration/besoins', ['directory_title' => 'Besoins utiles', 'directory_intro' => 'Une introduction administrable et honnête.', 'creation_title' => 'Décrire le manque', 'category_codes' => ['SKILL', 'MATERIAL'], 'category_labels' => ['Compétence', 'Matériel'], 'max_open_personal_needs' => 9, 'max_open_group_needs' => 40, 'directory_page_size' => 24])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(NeedConfiguration::KEY);
        self::assertSame('Matériel', $setting->value['categories']['MATERIAL']);
        self::assertSame(9, $setting->value['max_open_personal_needs']);
        self::assertSame('IDN-ADMIN', $setting->updated_by_core_reference);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['owner_type' => Need::OWNER_PERSON, 'group_reference' => null, 'title' => 'Former notre équipe à la comptabilité', 'context' => 'Notre équipe tient ses activités mais ne maîtrise pas encore le suivi simple des recettes et dépenses mensuelles.', 'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID', 'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC], $overrides);
    }

    private function group(string $leader)
    {
        return app(ZumraGroupService::class)->create($leader, ['name' => 'ZUMRA CAP 013', 'domain' => 'Formation', 'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.', 'participation_mode' => 'HYBRID', 'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.', 'assume_primary_lead' => true]);
    }

    private function signIn(string $reference): void
    {
        Http::fake(['core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201), 'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']), 'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'])]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
