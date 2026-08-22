<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Organizations\OrganizationService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\Organization;
use App\Models\PersonProfile;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-001 Phase B : routeur de « première intention » sur Mon espace, capacité légère, et
 * correction de MemberSpaceController::priority() (un objet sans relation personnelle réelle ne
 * peut jamais devenir la priorité dominante du membre).
 */
final class MemberSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_brand_new_member_sees_the_first_intention_router_instead_of_zumra_as_a_first_level_action(): void
    {
        $this->signIn('IDN-SPACE-NEW');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Je peux apporter quelque chose', $content);
        self::assertStringContainsString('J’ai un besoin', $content);
        self::assertStringContainsString('Je veux découvrir', $content);
        self::assertStringContainsString('Je veux participer', $content);
        self::assertStringNotContainsString('Ouvrir ZUMRA', $content);
    }

    public function test_an_active_member_keeps_the_usual_quick_actions_and_never_repeats_the_intention_router(): void
    {
        $this->makeVisibleNeed('IDN-SPACE-ACTIVE');
        $this->signIn('IDN-SPACE-ACTIVE');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Ouvrir ZUMRA', $content);
        self::assertStringNotContainsString('Je veux participer', $content);
    }

    public function test_the_quick_capability_declaration_creates_a_real_capability_statement_without_a_parallel_model(): void
    {
        $this->signIn('IDN-SPACE-QUICK');

        $this->post('/espace/capacite-rapide', ['capability' => 'Je sais réparer des vélos.'])
            ->assertRedirect('/espace');

        $statement = CapabilityStatement::query()->sole();
        self::assertSame('IDN-SPACE-QUICK', $statement->core_identity_reference);
        self::assertSame(CapabilityStatement::KIND_POSSESSED, $statement->kind);
        self::assertSame('Je sais réparer des vélos.', $statement->label);
        self::assertSame(CapabilityStatement::STATUS_DECLARED, $statement->status);
        self::assertNull($statement->archived_at);

        $profile = PersonProfile::query()->sole();
        self::assertSame(['Je sais réparer des vélos.'], $profile->existing_skills);

        // Le profil complet en 7 étapes reste la voie d'approfondissement, inchangée.
        $this->get('/espace/profil')->assertOk()->assertSee('Je sais réparer des vélos.');
    }

    public function test_the_quick_capability_declaration_never_archives_a_capability_already_declared_via_the_full_profile(): void
    {
        $this->signIn('IDN-SPACE-KEEP');

        $this->put('/espace/profil', [
            'existing_skills_text' => 'Couture',
            'orientation_consent' => '0',
        ])->assertRedirect('/espace/profil');

        $this->post('/espace/capacite-rapide', ['capability' => 'Réparation de vélos'])
            ->assertRedirect('/espace');

        self::assertSame(2, CapabilityStatement::query()->whereNull('archived_at')->count());
        self::assertSame(
            ['Couture', 'Réparation de vélos'],
            PersonProfile::query()->sole()->existing_skills,
        );
    }

    public function test_priority_never_promotes_a_strangers_activity_without_a_personal_relevance_reason(): void
    {
        $this->makeVisibleNeed('IDN-SPACE-STRANGER');
        $this->signIn('IDN-SPACE-NORELATION');

        $content = $this->get('/espace')->assertOk()->getContent();

        // Aucune relation réelle avec ce besoin d'un inconnu : le titre peut légitimement
        // apparaître ailleurs sur la page (section « Pour vous maintenant », inchangée par ce
        // correctif), mais jamais comme la priorité dominante elle-même — précisément l'élément
        // marqué par son id unique `dg-space-priority-title`.
        self::assertStringNotContainsString(
            '<h2 id="dg-space-priority-title">Besoin réel visible dans le Fil</h2>',
            $content,
        );
    }

    public function test_priority_promotes_a_need_the_member_actually_authored(): void
    {
        $this->signIn('IDN-SPACE-OWNNEED');

        $this->post('/besoins', [
            'owner_type' => Need::OWNER_PERSON,
            'group_reference' => null,
            'title' => 'Mon propre besoin réel',
            'context' => 'Un contexte suffisamment détaillé pour ce besoin réellement porté par ce membre.',
            'category' => 'TRAINING',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_PUBLIC,
        ])->assertRedirect();

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('une seule chose compte', $content);
        self::assertStringContainsString('Mon propre besoin réel', $content);
    }

    public function test_a_pending_zumra_join_request_becomes_priority_for_the_authorized_leader(): void
    {
        $group = $this->zumraGroup('IDN-SPACE-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-SPACE-APPLICANT',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-SPACE-APPLICANT', 'requested_at' => now(),
        ]);

        $this->signIn('IDN-SPACE-LEADER');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('demande d’adhésion attend votre décision', $content);
        self::assertStringContainsString($group->name, $content);
    }

    public function test_a_pending_zumra_join_request_is_not_priority_for_an_unrelated_member(): void
    {
        $group = $this->zumraGroup('IDN-SPACE-LEADER2');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-SPACE-APPLICANT2',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-SPACE-APPLICANT2', 'requested_at' => now(),
        ]);

        $this->signIn('IDN-SPACE-UNRELATED2');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringNotContainsString('demande d’adhésion attend votre décision', $content);
        self::assertStringNotContainsString($group->name, $content);
    }

    public function test_a_pending_role_proposal_becomes_priority_for_the_proposed_member(): void
    {
        $group = $this->zumraGroup('IDN-SPACE-LEADER3');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-SPACE-LEADER3', 'FIRST_DEPUTY', 'IDN-SPACE-PROPOSED3');

        $this->signIn('IDN-SPACE-PROPOSED3');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Une responsabilité vous est proposée', $content);
        self::assertStringContainsString($group->name, $content);
    }

    public function test_a_pending_role_proposal_is_invisible_to_another_member(): void
    {
        $group = $this->zumraGroup('IDN-SPACE-LEADER4');
        app(ZumraGroupService::class)->proposeRole($group, 'IDN-SPACE-LEADER4', 'FIRST_DEPUTY', 'IDN-SPACE-PROPOSED4');

        $this->signIn('IDN-SPACE-OTHER4');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringNotContainsString('Une responsabilité vous est proposée', $content);
    }

    public function test_the_notifications_discoverability_signal_appears_when_other_actionable_items_exist(): void
    {
        $group = $this->zumraGroup('IDN-SPACE-LEADER5');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-SPACE-APPLICANT5A',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-SPACE-APPLICANT5A', 'requested_at' => now(),
        ]);
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-SPACE-APPLICANT5B',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-SPACE-APPLICANT5B', 'requested_at' => now()->subMinute(),
        ]);

        $this->signIn('IDN-SPACE-LEADER5');
        $content = $this->get('/espace')->assertOk()->getContent();

        // La priorité dominante montre une seule chose ; le reste (la seconde demande) doit
        // rester découvrable via un signal texte vers /notifications, jamais un second CTA ici.
        self::assertStringContainsString('D’autres éléments attendent votre attention.', $content);
        self::assertStringContainsString(route('notifications.index'), $content);
    }

    public function test_the_calm_state_is_preserved_when_nothing_needs_attention(): void
    {
        $this->signIn('IDN-SPACE-CALM');
        // Un profil déjà déclaré, sans aucune relation active, pour éviter la variante "déclarer
        // une capacité" et exercer l'état calme pur.
        $this->put('/espace/profil', ['existing_skills_text' => 'Couture', 'orientation_consent' => '0'])
            ->assertRedirect('/espace/profil');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Rien ne réclame une décision maintenant.', $content);
        self::assertStringNotContainsString('D’autres éléments attendent votre attention.', $content);
        self::assertStringNotContainsString('Des éléments attendent votre attention ailleurs.', $content);
    }

    public function test_pour_vous_sections_no_longer_show_unrelated_network_activity(): void
    {
        $this->makeVisibleNeed('IDN-SPACE-STRANGER-PV');
        $this->signIn('IDN-SPACE-VIEWER-PV');

        $content = $this->get('/espace')->assertOk()->getContent();

        // Ni la priorité, ni les sections « Pour vous maintenant »/« Cette semaine » ne doivent
        // présenter le besoin d'un inconnu comme personnellement destiné à ce membre.
        self::assertStringNotContainsString('Besoin réel visible dans le Fil', $content);
    }

    // ===== UIUX-006 — « Mes Organisations » sur Mon espace =====

    public function test_a_member_without_any_organization_sees_no_intrusive_empty_block(): void
    {
        $this->signIn('IDN-SPACE-NOORG');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringNotContainsString('Mes Organisations', $content);
    }

    public function test_a_member_representing_one_organization_gets_direct_access_from_my_space(): void
    {
        $organization = $this->organization('IDN-SPACE-ORG1');

        $this->signIn('IDN-SPACE-ORG1');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Mes Organisations', $content);
        self::assertStringContainsString($organization->name, $content);
        self::assertStringContainsString(route('organizations.show', $organization), $content);
    }

    public function test_a_member_representing_several_organizations_sees_them_all_distinctly_and_openable(): void
    {
        $first = $this->organization('IDN-SPACE-ORG2');
        $second = $this->organization('IDN-SPACE-OTHER-FOUNDER');
        app(OrganizationService::class)->invite($second, 'IDN-SPACE-OTHER-FOUNDER', 'IDN-SPACE-ORG2');
        app(OrganizationService::class)->acceptInvitation($second, 'IDN-SPACE-ORG2');
        $unrelated = $this->organization('IDN-SPACE-STRANGER-FOUNDER');

        $this->signIn('IDN-SPACE-ORG2');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString($first->name, $content);
        self::assertStringContainsString(route('organizations.show', $first), $content);
        self::assertStringContainsString($second->name, $content);
        self::assertStringContainsString(route('organizations.show', $second), $content);
        self::assertStringNotContainsString($unrelated->name, $content);
    }

    private function organization(string $founder, array $overrides = []): Organization
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

        return app(OrganizationService::class)->create($founder, array_replace([
            'name' => 'Structure Mon Espace '.Str::random(6),
            'description' => 'Une structure durable réutilisée pour vérifier « Mes Organisations » sur Mon espace.',
            'type' => 'COOPERATIVE',
            'visibility' => Organization::VISIBILITY_PRIVATE,
        ], $overrides));
    }

    private function zumraGroup(string $leader): ZumraGroup
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        if (! ZumraProgramMembership::query()->where('core_identity_reference', $leader)->exists()) {
            ZumraProgramMembership::query()->create([
                'core_identity_reference' => $leader,
                'status' => ZumraProgramMembership::STATUS_ACTIVE,
                'accepted_charter_id' => $charter->id,
                'accepted_charter_version' => $charter->version,
                'accepted_charter_hash' => $charter->content_hash,
                'charter_accepted_at' => now(),
                'submitted_at' => now(),
                'activated_at' => now(),
            ]);
        }

        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Espace '.Str::random(6),
            'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.',
            'assume_primary_lead' => true,
        ]);
    }

    private function makeVisibleNeed(string $owner): void
    {
        $need = Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $owner,
            'author_core_reference' => $owner,
            'title' => 'Besoin réel visible dans le Fil',
            'context' => 'Un contexte suffisamment précis pour rendre cette activité utile dans le réseau.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $owner,
            'published_at' => now(),
        ]);
        NeedEvent::query()->create([
            'need_id' => $need->id,
            'event' => 'NEED_PUBLISHED',
            'actor_core_reference' => $owner,
            'from_status' => null,
            'to_status' => $need->status,
            'context' => [],
            'occurred_at' => now(),
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
