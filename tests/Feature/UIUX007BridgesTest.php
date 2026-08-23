<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\Proof;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-007 Phase B — vérifie chaque pont UX ajouté entre des capacités déjà réelles, et surtout
 * ses absences : aucune autorité nouvelle n'apparaît pour un acteur non habilité, aucune relation
 * (ZUMRA, participation, Preuve, Mission) n'est jamais fabriquée automatiquement.
 */
final class UIUX007BridgesTest extends TestCase
{
    use RefreshDatabase;

    // ===== §1 Routeur première intention — Créer ma ZUMRA =====

    public function test_a_brand_new_member_is_offered_membership_first_never_a_direct_creation_link(): void
    {
        $this->signIn('IDN-U7-NEW');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Adhérer au Programme ZUMRA pour créer ma ZUMRA', $content);
        self::assertStringNotContainsString(route('zumra.groups.create'), $content);
    }

    public function test_an_active_program_member_without_a_group_sees_a_real_creation_link(): void
    {
        $this->activateProgram('IDN-U7-ACTIVE');
        $this->signIn('IDN-U7-ACTIVE');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Créer ma ZUMRA', $content);
        self::assertStringContainsString(route('zumra.groups.create'), $content);
        self::assertStringNotContainsString('Adhérer au Programme ZUMRA pour créer ma ZUMRA', $content);
    }

    // ===== §2/§3 Fiche ZUMRA — collaborateurs, Besoin/Projet, Événement =====

    public function test_the_leader_sees_the_collaborator_need_project_and_event_bridges(): void
    {
        $group = $this->group('IDN-U7-LEADER');

        $this->signIn('IDN-U7-LEADER');
        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();

        self::assertStringContainsString('Trouver des collaborateurs', $content);
        self::assertStringContainsString(route('needs.create', ['group' => $group->public_reference]), $content);
        self::assertStringContainsString(route('projects.create', ['group' => $group->public_reference]), $content);
        self::assertStringContainsString(route('community-events.zumra.create', $group), $content);
    }

    public function test_an_active_non_leader_member_sees_need_project_bridges_but_not_leader_only_ones(): void
    {
        $group = $this->group('IDN-U7-LEADER2');
        $this->membership($group, 'IDN-U7-MEMBER2', ZumraGroupMembership::STATUS_ACTIVE);

        $this->signIn('IDN-U7-MEMBER2');
        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();

        self::assertStringContainsString(route('needs.create', ['group' => $group->public_reference]), $content);
        self::assertStringContainsString(route('projects.create', ['group' => $group->public_reference]), $content);
        self::assertStringNotContainsString('Trouver des collaborateurs', $content);
        self::assertStringNotContainsString(route('community-events.zumra.create', $group), $content);
    }

    public function test_visiting_the_need_creation_form_from_a_zumra_prefills_only_when_actively_a_member(): void
    {
        $group = $this->group('IDN-U7-PREFILL-LEADER');
        $outsiderGroup = $this->group('IDN-U7-OUTSIDER-LEADER');

        $this->signIn('IDN-U7-PREFILL-LEADER');
        $content = $this->get(route('needs.create', ['group' => $group->public_reference]))->assertOk()->getContent();
        self::assertMatchesRegularExpression('/value="'.preg_quote($group->public_reference, '/').'"[^>]*selected/', $content);

        // L'acteur n'est pas membre de $outsiderGroup : sa référence ne doit jamais se préremplir.
        $content = $this->get(route('needs.create', ['group' => $outsiderGroup->public_reference]))->assertOk()->getContent();
        self::assertDoesNotMatchRegularExpression('/value="'.preg_quote($outsiderGroup->public_reference, '/').'"[^>]*selected/', $content);
    }

    // ===== §5 Personne → Transmission =====

    public function test_proposing_a_transmission_from_a_discovered_profile_invites_but_never_auto_accepts(): void
    {
        $learnerRef = $this->discoverableProfile('IDN-U7-LEARN', 'Apprenant Découvert');

        $this->signIn('IDN-U7-TEACH');
        $content = $this->get(route('transmissions.create', ['invite' => $learnerRef]))->assertOk()->getContent();
        self::assertStringContainsString('Apprenant Découvert', $content);

        $this->post(route('transmissions.store'), [
            'capability_label' => 'Réparation de vélos',
            'learning_objective' => 'Savoir réparer une crevaison et régler des freins.',
            'initiator_role' => 'TRANSMITTER',
            'origin_type' => 'NONE',
            'visibility' => 'PRIVATE',
            'invite_discovery_reference' => $learnerRef,
        ])->assertRedirect();

        $transmission = Transmission::query()->where('capability_label', 'Réparation de vélos')->firstOrFail();
        self::assertDatabaseHas('dg_transmission_participants', [
            'transmission_id' => $transmission->id, 'core_identity_reference' => 'IDN-U7-LEARN',
            'role' => TransmissionParticipant::ROLE_LEARNER, 'status' => TransmissionParticipant::STATUS_INVITED,
        ]);
        self::assertDatabaseMissing('dg_transmission_participants', [
            'transmission_id' => $transmission->id, 'core_identity_reference' => 'IDN-U7-LEARN', 'status' => TransmissionParticipant::STATUS_ACCEPTED,
        ]);
    }

    public function test_a_non_discoverable_or_non_consenting_reference_is_never_trusted_for_prefill(): void
    {
        // Profil existant mais sans consentement de découverte : ne doit jamais se pré-remplir.
        $hidden = PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-U7-HIDDEN', 'orientation_consent' => true, 'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(), 'discovery_display_name' => 'Profil Caché', 'discovery_consent' => false,
        ]);

        $this->signIn('IDN-U7-TEACH2');
        $content = $this->get(route('transmissions.create', ['invite' => $hidden->discovery_reference]))->assertOk()->getContent();
        self::assertStringNotContainsString('Profil Caché', $content);
    }

    // ===== §6 Transmission → Preuve =====

    public function test_a_completed_transmission_offers_a_proof_bridge_to_its_accepted_participants(): void
    {
        [$transmission] = $this->completedTransmission('IDN-U7-T-TEACH', 'IDN-U7-T-LEARN');

        $this->signIn('IDN-U7-T-TEACH');
        $content = $this->get(route('transmissions.show', $transmission))->assertOk()->getContent();

        $expectedUrl = route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $transmission->public_reference]);
        self::assertStringContainsString('Enregistrer une preuve', $content);
        self::assertStringContainsString(htmlspecialchars($expectedUrl), $content);
    }

    public function test_a_non_participant_never_sees_the_proof_bridge_even_on_a_program_visible_completed_transmission(): void
    {
        [$transmission] = $this->completedTransmission('IDN-U7-T-TEACH2', 'IDN-U7-T-LEARN2', Transmission::VISIBILITY_PROGRAM);
        $this->activateProgram('IDN-U7-OUTSIDER3');

        $this->signIn('IDN-U7-OUTSIDER3');
        $content = $this->get(route('transmissions.show', $transmission))->assertOk()->getContent();

        self::assertStringNotContainsString('Enregistrer une preuve', $content);
    }

    public function test_proof_prefill_is_honored_for_a_genuine_participant_of_a_completed_transmission(): void
    {
        [$transmission] = $this->completedTransmission('IDN-U7-T-TEACH3', 'IDN-U7-T-LEARN3');

        $this->signIn('IDN-U7-T-TEACH3');
        $content = $this->get(route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $transmission->public_reference]))->assertOk()->getContent();
        self::assertStringContainsString('terminée', $content);
        self::assertMatchesRegularExpression('/name="origin_reference"[^>]*value="'.preg_quote($transmission->public_reference, '/').'"/', $content);
    }

    public function test_proof_prefill_is_never_honored_for_a_non_participant_of_the_transmission(): void
    {
        [$transmission] = $this->completedTransmission('IDN-U7-T-TEACH4B', 'IDN-U7-T-LEARN4B');
        $this->activateProgram('IDN-U7-OUTSIDER4');

        $this->signIn('IDN-U7-OUTSIDER4');
        $content = $this->get(route('proofs.create', ['origin_type' => 'TRANSMISSION', 'origin_reference' => $transmission->public_reference]))->assertOk()->getContent();
        self::assertDoesNotMatchRegularExpression('/name="origin_reference"[^>]*value="'.preg_quote($transmission->public_reference, '/').'"/', $content);
    }

    public function test_submitting_a_proof_never_happens_automatically_only_an_explicit_form_submission_creates_one(): void
    {
        [$transmission] = $this->completedTransmission('IDN-U7-T-TEACH4', 'IDN-U7-T-LEARN4');

        $this->signIn('IDN-U7-T-TEACH4');
        $this->get(route('transmissions.show', $transmission))->assertOk();

        self::assertSame(0, Proof::query()->count(), 'Visiter la fiche ne doit jamais créer de Preuve automatiquement.');
    }

    // ===== §7 Besoin → « Je peux aider » =====

    public function test_an_eligible_active_member_sees_the_help_bridge_on_an_open_need(): void
    {
        $need = app(NeedService::class)->create('IDN-U7-NEEDOWNER', $this->needPayload(), (new NeedConfiguration)->defaults());
        $this->activateProgram('IDN-U7-HELPER');

        $this->signIn('IDN-U7-HELPER');
        $content = $this->get(route('needs.show', $need))->assertOk()->getContent();

        self::assertStringContainsString('Je peux apporter cette capacité', $content);
        self::assertStringContainsString(route('needs.missions.create', $need), $content);
    }

    public function test_a_member_without_active_program_membership_never_sees_the_help_bridge(): void
    {
        $need = app(NeedService::class)->create('IDN-U7-NEEDOWNER2', $this->needPayload(), (new NeedConfiguration)->defaults());

        // Signé mais sans adhésion active au Programme ZUMRA.
        $this->signIn('IDN-U7-NOPROGRAM');
        $content = $this->get(route('needs.show', $need))->assertOk()->getContent();

        self::assertStringNotContainsString('Je peux apporter cette capacité', $content);
    }

    public function test_an_archived_need_is_never_reachable_by_a_helper_and_never_offers_the_bridge_to_its_own_owner(): void
    {
        $this->activateProgram('IDN-U7-NEEDOWNER3');
        $service = app(NeedService::class);
        $need = $service->create('IDN-U7-NEEDOWNER3', $this->needPayload(), (new NeedConfiguration)->defaults());
        $service->transition($need, 'IDN-U7-NEEDOWNER3', Need::STATUS_ARCHIVED, null);

        // Un aidant tiers ne peut même plus consulter un Besoin archivé (NeedService::canView).
        $this->activateProgram('IDN-U7-HELPER3');
        $this->signIn('IDN-U7-HELPER3');
        $this->get(route('needs.show', $need))->assertNotFound();
    }

    public function test_an_archived_need_never_offers_the_help_bridge_even_to_its_own_author(): void
    {
        $this->activateProgram('IDN-U7-NEEDOWNER4');
        $service = app(NeedService::class);
        $need = $service->create('IDN-U7-NEEDOWNER4', $this->needPayload(), (new NeedConfiguration)->defaults());
        $service->transition($need, 'IDN-U7-NEEDOWNER4', Need::STATUS_ARCHIVED, null);

        // L'auteur voit toujours sa propre fiche (canView), mais la Mission ne se propose plus
        // sur un Besoin non opérationnel (NeedMissionContext::isOperational()).
        $this->signIn('IDN-U7-NEEDOWNER4');
        $content = $this->get(route('needs.show', $need))->assertOk()->getContent();

        self::assertStringNotContainsString('Je peux apporter cette capacité', $content);
    }

    // ===== §8 Projet → Mission =====

    public function test_an_eligible_active_member_sees_the_mission_bridge_on_a_project(): void
    {
        $this->activateProgram('IDN-U7-PROJOWNER');
        $project = app(ProjectService::class)->create('IDN-U7-PROJOWNER', $this->projectPayload(['zumra_group_reference' => $this->zumraFor('IDN-U7-PROJOWNER')]), (new ProjectConfiguration)->defaults());
        $this->activateProgram('IDN-U7-PROJHELPER');

        $this->signIn('IDN-U7-PROJHELPER');
        $content = $this->get(route('projects.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('Proposer une Mission', $content);
        self::assertStringContainsString(route('projects.missions.create', $project), $content);
    }

    public function test_a_member_without_active_program_membership_never_sees_the_project_mission_bridge(): void
    {
        $this->activateProgram('IDN-U7-PROJOWNER2');
        $project = app(ProjectService::class)->create('IDN-U7-PROJOWNER2', $this->projectPayload(['name' => 'Second projet '.uniqid(), 'zumra_group_reference' => $this->zumraFor('IDN-U7-PROJOWNER2')]), (new ProjectConfiguration)->defaults());

        $this->signIn('IDN-U7-PROJNOPROGRAM');
        $content = $this->get(route('projects.show', $project))->assertOk()->getContent();

        self::assertStringNotContainsString('Proposer une Mission', $content);
    }

    // ===== §9 Mon espace — aucune fabrication de structure, aucun jargon =====

    public function test_the_zumra_creation_bridge_never_leaks_cap_identifiers_or_engine_jargon(): void
    {
        $this->activateProgram('IDN-U7-JARGON');
        $this->signIn('IDN-U7-JARGON');
        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertDoesNotMatchRegularExpression('/CAP-\d/', $content);
    }

    public function test_visiting_mon_espace_never_creates_a_zumra_or_a_membership_by_itself(): void
    {
        $this->signIn('IDN-U7-NOOP');
        $this->get('/espace')->assertOk();
        $this->activateProgram('IDN-U7-NOOP2');
        $this->signIn('IDN-U7-NOOP2');
        $this->get('/espace')->assertOk();

        self::assertSame(0, ZumraGroup::query()->count(), 'Consulter Mon espace ne doit jamais fabriquer de ZUMRA.');
    }

    // ===== Helpers =====

    private function needPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Need::OWNER_PERSON, 'group_reference' => null,
            'title' => 'Trouver un appui en comptabilité de base',
            'context' => 'Notre activité grandit et nous devons suivre plus rigoureusement les recettes et dépenses chaque mois.',
            'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    private function zumraFor(string $actor): string
    {
        return app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.uniqid(), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ])->public_reference;
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Project::OWNER_PERSON, 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier '.uniqid(),
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre et transformer leurs acquis.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs et accompagnement.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l'équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    /** @return array{0: Transmission, 1: string} */
    private function completedTransmission(string $teacher, string $learner, string $visibility = Transmission::VISIBILITY_PRIVATE): array
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile($learner, 'Apprenant '.$learner);

        $transmission = $workflow->create($teacher, TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Compétence '.uniqid(),
            'learning_objective' => 'Atteindre un savoir-faire concret et transmissible.',
            'origin_type' => Transmission::ORIGIN_NONE,
            'visibility' => $visibility,
        ]);
        $p = $participation->invite($transmission, $teacher, $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, $learner, $p);
        $transmission = $workflow->start($transmission->fresh(), $teacher);
        $workflow->declareDone($transmission, $teacher);
        $workflow->declareDone($transmission, $learner);
        $transmission = $workflow->confirmCompletion($transmission, $teacher, 'Compétence transmise avec succès.');

        return [$transmission->fresh(), $learnerRef];
    }

    private function discoverableProfile(string $reference, string $name): string
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
        ]);

        return $profile->discovery_reference;
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA UIUX-007 '.Str::random(6),
            'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, dignité, transmission, hiérarchie responsable. ', 3),
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

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
