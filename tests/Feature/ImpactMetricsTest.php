<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Community\CommunityEventService;
use App\Application\Ecosystem\ImpactMetricsService;
use App\Application\Organizations\OrganizationService;
use App\Models\CommunityEvent;
use App\Models\Need;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAP-080 — « Ce que devrait mesurer DG Afrique ». Projection de lecture pure dérivée des domaines
 * existants : capacité collective à transformer des capacités en actions/résultats réels, jamais la
 * valeur des personnes. Aucun score, classement, réputation, gamification ou KPI d'engagement.
 */
final class ImpactMetricsTest extends TestCase
{
    use RefreshDatabase;

    // ===== Calculs =====

    public function test_portal_metrics_reflect_real_domain_facts(): void
    {
        $this->need('IDN-OWNER', Need::STATUS_RESOLVED);
        $this->need('IDN-OWNER', Need::STATUS_OPEN);
        $this->project('IDN-OWNER', Project::STATUS_COMPLETED);
        $group = $this->group('IDN-LEADER');
        app(CommunityEventService::class)->createForZumraGroup($group, 'IDN-LEADER', $this->eventPayload());

        $metrics = app(ImpactMetricsService::class)->portal();

        self::assertSame(2, $metrics['needs_expressed_count']);
        self::assertSame(1, $metrics['needs_resolved_count']);
        self::assertSame(1, $metrics['projects_completed_count']);
        self::assertSame(1, $metrics['community_events_organized_count']);
        self::assertSame(1, $metrics['zumra_groups_active_count']);
    }

    public function test_zumra_metrics_are_scoped_to_the_group_only(): void
    {
        $groupA = $this->group('IDN-LEADER-A');
        $groupB = $this->group('IDN-LEADER-B');
        $this->needForGroup($groupA, 'IDN-LEADER-A', Need::STATUS_RESOLVED);
        $this->needForGroup($groupB, 'IDN-LEADER-B', Need::STATUS_RESOLVED);
        $this->needForGroup($groupB, 'IDN-LEADER-B', Need::STATUS_RESOLVED);

        $metricsA = app(ImpactMetricsService::class)->forZumraGroup($groupA, 'IDN-LEADER-A');
        $metricsB = app(ImpactMetricsService::class)->forZumraGroup($groupB, 'IDN-LEADER-B');

        self::assertSame(1, $metricsA['needs_resolved_count']);
        self::assertSame(2, $metricsB['needs_resolved_count']);
    }

    public function test_organization_metrics_are_scoped_to_the_organization_only(): void
    {
        $orgA = $this->organization('IDN-OWNER-A');
        $orgB = $this->organization('IDN-OWNER-B');
        app(CommunityEventService::class)->createForOrganization($orgA, 'IDN-OWNER-A', $this->eventPayload());
        app(CommunityEventService::class)->createForOrganization($orgB, 'IDN-OWNER-B', $this->eventPayload());
        app(CommunityEventService::class)->createForOrganization($orgB, 'IDN-OWNER-B', $this->eventPayload());

        $metricsA = app(ImpactMetricsService::class)->forOrganization($orgA, 'IDN-OWNER-A');
        $metricsB = app(ImpactMetricsService::class)->forOrganization($orgB, 'IDN-OWNER-B');

        self::assertSame(1, $metricsA['community_events_organized_count']);
        self::assertSame(2, $metricsB['community_events_organized_count']);
    }

    // ===== Autorisation / confidentialité =====

    public function test_an_outsider_cannot_view_zumra_metrics(): void
    {
        $group = $this->group('IDN-LEADER');

        $this->assertAborts(404, fn () => app(ImpactMetricsService::class)->forZumraGroup($group, 'IDN-OUTSIDER'));
    }

    public function test_an_active_zumra_member_can_view_metrics(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        $metrics = app(ImpactMetricsService::class)->forZumraGroup($group, 'IDN-MEMBER');

        self::assertArrayHasKey('active_member_count', $metrics);
    }

    public function test_an_outsider_cannot_view_a_private_organizations_metrics(): void
    {
        $organization = $this->organization('IDN-OWNER');

        $this->assertAborts(404, fn () => app(ImpactMetricsService::class)->forOrganization($organization, 'IDN-OUTSIDER'));
    }

    public function test_an_organization_manager_can_view_metrics(): void
    {
        $organization = $this->organization('IDN-OWNER');

        $metrics = app(ImpactMetricsService::class)->forOrganization($organization, 'IDN-OWNER');

        self::assertArrayHasKey('active_member_count', $metrics);
    }

    // ===== Absence de score/classement/valeur individuelle =====

    public function test_no_score_ranking_or_person_level_field_exists_anywhere(): void
    {
        $group = $this->group('IDN-LEADER');
        $organization = $this->organization('IDN-OWNER');
        $forbidden = ['score', 'rank', 'ranking', 'level', 'reputation', 'engagement', 'popularity', 'likes', 'views', 'trust'];

        $payloads = [
            app(ImpactMetricsService::class)->portal(),
            app(ImpactMetricsService::class)->forZumraGroup($group, 'IDN-LEADER'),
            app(ImpactMetricsService::class)->forOrganization($organization, 'IDN-OWNER'),
        ];

        foreach ($payloads as $metrics) {
            foreach ($metrics as $key => $value) {
                self::assertIsInt($value, "La métrique {$key} doit être un entier collectif, jamais une valeur composite.");
                foreach ($forbidden as $word) {
                    self::assertStringNotContainsStringIgnoringCase($word, $key);
                }
            }
        }
    }

    public function test_metrics_never_expose_a_person_identity_or_list(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->membership($group, 'IDN-MEMBER', ZumraGroupMembership::STATUS_ACTIVE);

        $metrics = app(ImpactMetricsService::class)->forZumraGroup($group, 'IDN-LEADER');

        self::assertStringNotContainsString('IDN-', json_encode($metrics));
    }

    // ===== Aucune mutation des domaines sources =====

    public function test_reading_metrics_never_mutates_source_domains(): void
    {
        $group = $this->group('IDN-LEADER');
        $need = $this->needForGroup($group, 'IDN-LEADER', Need::STATUS_OPEN);
        $updatedAtBefore = $need->updated_at;
        $groupUpdatedAtBefore = $group->updated_at;

        app(ImpactMetricsService::class)->portal();
        app(ImpactMetricsService::class)->forZumraGroup($group, 'IDN-LEADER');

        self::assertEquals($updatedAtBefore, $need->fresh()->updated_at);
        self::assertEquals($groupUpdatedAtBefore, $group->fresh()->updated_at);
    }

    // ===== HTTP =====

    public function test_http_portal_endpoint_returns_metrics_json(): void
    {
        $this->signIn('IDN-VIEWER');

        $this->get(route('impact-metrics.portal'))
            ->assertOk()
            ->assertJsonStructure(['metrics' => ['needs_expressed_count', 'projects_initiated_count']]);
    }

    // ===== Helpers =====

    private function assertAborts(int $status, callable $fn): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode());
        }
    }

    private function eventPayload(): array
    {
        return [
            'title' => 'Atelier de coordination', 'description' => 'Une rencontre réelle pour coordonner les actions.',
            'visibility' => CommunityEvent::VISIBILITY_INTERNAL, 'scheduled_at' => now()->addWeek()->toDateTimeString(),
        ];
    }

    private function need(string $owner, string $status): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Need::OWNER_PERSON, 'owner_reference' => $owner,
            'author_core_reference' => $owner, 'title' => 'Besoin réel '.Str::random(5),
            'context' => 'Un contexte réel et suffisamment précis pour organiser une coordination utile.',
            'category' => 'SKILL', 'capability_label' => 'Coordination', 'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_PUBLIC, 'status' => $status,
            'decided_by_core_reference' => $owner, 'published_at' => now(),
        ]);
    }

    private function needForGroup(ZumraGroup $group, string $author, string $status): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Need::OWNER_GROUP, 'owner_reference' => $group->id,
            'author_core_reference' => $author, 'title' => 'Besoin du groupe '.Str::random(5),
            'context' => 'Un contexte réel et suffisamment précis pour organiser une coordination utile.',
            'category' => 'SKILL', 'capability_label' => 'Coordination', 'collaboration_mode' => 'LOCAL',
            'visibility' => 'PUBLIC', 'status' => $status,
            'decided_by_core_reference' => $author, 'published_at' => now(),
        ]);
    }

    private function project(string $owner, string $status): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $owner,
            'initiator_core_reference' => $owner, 'name' => 'Projet réel '.Str::random(5),
            'summary' => 'Un projet concret et suffisamment décrit.', 'problem' => 'Un problème réel.',
            'proposed_solution' => 'Une solution progressive.', 'beneficiaries' => 'Communauté locale.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'objectives' => ['Agir'],
            'required_capabilities' => ['Coordination'], 'required_resources' => ['Temps'], 'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PUBLIC,
            'status' => $status, 'maturity' => 'ACTIVITY', 'decided_by_core_reference' => $owner, 'started_at' => now(),
        ]);
    }

    private function group(string $leader): ZumraGroup
    {
        $group = ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(), 'name' => 'ZUMRA Mesure '.Str::random(6),
            'slug' => 'zumra-mesure-'.Str::lower(Str::random(8)), 'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles.',
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE, 'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => $leader, 'active_member_count' => 1,
        ]);
        $this->membership($group, $leader, ZumraGroupMembership::STATUS_ACTIVE);
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id, 'role' => 'PRIMARY_LEAD', 'core_identity_reference' => $leader,
            'status' => ZumraGroupRole::STATUS_ACCEPTED, 'proposed_by_core_reference' => $leader,
            'proposed_at' => now(), 'accepted_at' => now(),
        ]);

        return $group;
    }

    private function membership(ZumraGroup $group, string $identity, string $status): void
    {
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => $identity, 'status' => $status,
            'entry_mode' => 'FOUNDER', 'initiated_by_core_reference' => $group->proposer_core_reference,
            'joined_at' => $status === ZumraGroupMembership::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function organization(string $founder): Organization
    {
        $this->fakeCoreOrganizationProvisioning();

        return app(OrganizationService::class)->create($founder, [
            'name' => 'Organisation Mesure '.Str::random(6),
            'description' => 'Une structure durable qui organise des activités réelles.',
            'type' => 'COOPERATIVE', 'visibility' => Organization::VISIBILITY_PRIVATE,
        ]);
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
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-10-30T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-10-30T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
