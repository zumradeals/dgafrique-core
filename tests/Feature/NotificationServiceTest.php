<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Notifications\NotificationService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Proof\ProofWorkflow;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\NotificationRead;
use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre CAP-054, fiche §10 : notification = « voici ce qui vous concerne », jamais une
 * seconde détection ni une seconde autorité que celle déjà tranchée par le module source.
 */
final class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mission_invitation_appears_as_actionable(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->zumraFor('IDN-OWNER')]), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $inviteeRef = $this->discoverableProfile('IDN-INVITEE', 'Invité Mission');

        $mission = $workflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Préparer le lancement pilote',
            'description' => 'Organiser la première session pilote avec les premiers bénéficiaires.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER');
        $mission = $workflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une session pilote réalisée.']);
        $assignments->invite($mission, 'IDN-OWNER', $inviteeRef, MissionAssignment::ROLE_EXECUTOR);

        $sections = app(NotificationService::class)->sections('IDN-INVITEE');
        self::assertTrue($sections['a_traiter']->contains(fn (array $i): bool => $i['source_type'] === 'MISSION' && str_contains($i['title'], 'Préparer le lancement pilote')));

        // Aucun tiers ne voit la notification d'un autre.
        $strangerSections = app(NotificationService::class)->sections('IDN-STRANGER');
        self::assertTrue($strangerSections['a_traiter']->isEmpty());
    }

    public function test_transmission_invitation_appears_as_actionable(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN', 'Apprenant Notification');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Maraîchage biologique',
            'learning_objective' => 'Pouvoir cultiver un potager biologique de base.',
            'origin_type' => Transmission::ORIGIN_INTERACTION,
        ]);
        $participation->invite($transmission, 'IDN-TEACH', $learnerRef, TransmissionParticipant::ROLE_LEARNER);

        $sections = app(NotificationService::class)->sections('IDN-LEARN');
        self::assertTrue($sections['a_traiter']->contains(fn (array $i): bool => $i['source_type'] === 'TRANSMISSION' && str_contains($i['title'], 'Maraîchage biologique')));
    }

    public function test_proof_witness_invitation_appears_as_actionable(): void
    {
        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS', 'Témoin Notification');

        $proof = $workflow->submit('IDN-AUTHOR', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Réparation de vélo réalisée',
            'description' => 'Une crevaison a été réparée pour un voisin.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);
        $workflow->inviteWitness($proof, 'IDN-AUTHOR', $witnessRef);

        $sections = app(NotificationService::class)->sections('IDN-WITNESS');
        self::assertTrue($sections['a_traiter']->contains(fn (array $i): bool => $i['source_type'] === 'PROOF' && str_contains($i['title'], 'Réparation de vélo réalisée')));
    }

    public function test_resolved_actionable_item_disappears_from_a_traiter(): void
    {
        $this->activateProgram('IDN-OWNER2');
        $project = app(ProjectService::class)->create('IDN-OWNER2', $this->projectPayload(['zumra_group_reference' => $this->zumraFor('IDN-OWNER2')]), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $inviteeRef = $this->discoverableProfile('IDN-INVITEE2', 'Invité Résolu');

        $mission = $workflow->create('IDN-OWNER2', 'PROJECT', $project->public_reference, [
            'title' => 'Réserver le matériel',
            'description' => 'Trouver et réserver le matériel nécessaire pour la session.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-OWNER2');
        $mission = $workflow->officialize($mission, 'IDN-OWNER2', ['expected_result' => 'Matériel réservé.']);
        $assignment = $assignments->invite($mission, 'IDN-OWNER2', $inviteeRef, MissionAssignment::ROLE_EXECUTOR);

        $sections = app(NotificationService::class)->sections('IDN-INVITEE2');
        self::assertTrue($sections['a_traiter']->isNotEmpty());

        $assignments->acceptInvitation($mission, 'IDN-INVITEE2', $assignment);

        $sections = app(NotificationService::class)->sections('IDN-INVITEE2');
        self::assertTrue($sections['a_traiter']->isEmpty(), 'Une invitation acceptée ne doit plus apparaître dans « À traiter » — aucun masquage manuel requis.');
    }

    public function test_fyi_significant_event_appears_and_marking_it_read_never_changes_the_underlying_mission(): void
    {
        $this->activateProgram('IDN-EXEC3');
        $group = $this->group('IDN-LEADER3');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-EXEC3',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-EXEC3', 'joined_at' => now(),
        ]);

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $blockers = app(MissionBlockerService::class);

        $mission = $workflow->create('IDN-LEADER3', 'ZUMRA', $group->public_reference, [
            'title' => 'Organiser la session mensuelle',
            'description' => 'Préparer et animer la session mensuelle de la ZUMRA.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-LEADER3');
        $mission = $workflow->officialize($mission, 'IDN-LEADER3', ['expected_result' => 'Une session tenue.']);
        $a = $assignments->offer($mission, 'IDN-EXEC3', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-LEADER3', $a);
        $mission = $workflow->start($mission, 'IDN-EXEC3');

        // MISSION_STARTED n'est pas dans le sous-ensemble FYI curé : aucune notification encore.
        $sections = app(NotificationService::class)->sections('IDN-EXEC3');
        self::assertTrue($sections['recentes']->isEmpty(), 'Un événement non éligible (MISSION_STARTED) ne doit jamais générer de notification.');

        $blockers->block($mission, 'IDN-LEADER3', 'MISSING_RESOURCE', 'Aucun budget disponible pour la location.');

        $sections = app(NotificationService::class)->sections('IDN-EXEC3');
        $item = $sections['recentes']->first(fn (array $i): bool => $i['source_type'] === 'MISSION');
        self::assertNotNull($item, 'MISSION_BLOCKED est un événement FYI significatif pour un exécutant accepté.');
        self::assertFalse($item['read']);

        $missionBefore = $mission->fresh();
        app(NotificationService::class)->markFyiSeen('IDN-EXEC3', $sections['recentes']);

        // Marquer comme lu ne modifie jamais l'objet métier source.
        $missionAfter = $mission->fresh();
        self::assertSame($missionBefore->status, $missionAfter->status);
        self::assertSame($missionBefore->updated_at->timestamp, $missionAfter->updated_at->timestamp);
        self::assertSame(1, NotificationRead::query()->where('core_identity_reference', 'IDN-EXEC3')->count());

        $sections = app(NotificationService::class)->sections('IDN-EXEC3');
        $item = $sections['recentes']->first(fn (array $i): bool => $i['source_type'] === 'MISSION');
        self::assertTrue($item['read']);
    }

    public function test_fyi_item_disappears_when_recipient_loses_access_to_the_context(): void
    {
        $this->activateProgram('IDN-EXEC4');
        $group = $this->group('IDN-LEADER4');
        $membership = ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-EXEC4',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-EXEC4', 'joined_at' => now(),
        ]);

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $blockers = app(MissionBlockerService::class);

        $mission = $workflow->create('IDN-LEADER4', 'ZUMRA', $group->public_reference, [
            'title' => 'Préparer le budget annuel',
            'description' => 'Réunir les éléments du budget annuel de la ZUMRA.',
        ]);
        $mission = $workflow->propose($mission, 'IDN-LEADER4');
        $mission = $workflow->officialize($mission, 'IDN-LEADER4', ['expected_result' => 'Un budget consolidé.']);
        $a = $assignments->offer($mission, 'IDN-EXEC4', MissionAssignment::ROLE_EXECUTOR);
        $assignments->acceptOffer($mission, 'IDN-LEADER4', $a);
        $workflow->start($mission, 'IDN-EXEC4');
        $blockers->block($mission, 'IDN-LEADER4', 'MISSING_RESOURCE', 'Chiffres manquants.');

        $sections = app(NotificationService::class)->sections('IDN-EXEC4');
        self::assertNotNull($sections['recentes']->first(fn (array $i): bool => $i['source_type'] === 'MISSION'), 'La notification doit exister tant que l’accès au contexte est réel.');

        // La personne quitte la ZUMRA : elle perd l'accès au contexte porteur de la Mission.
        $membership->update(['status' => ZumraGroupMembership::STATUS_LEFT]);

        $sections = app(NotificationService::class)->sections('IDN-EXEC4');
        self::assertNull($sections['recentes']->first(fn (array $i): bool => $i['source_type'] === 'MISSION'), 'Une fois l’accès au contexte perdu, la notification disparaît immédiatement — aucune fuite du titre/lien.');
    }

    public function test_zumra_role_proposal_appears_as_actionable_only_for_the_proposed_person(): void
    {
        $group = $this->group('IDN-ZLEADER5');
        $service = app(ZumraGroupService::class);
        $service->proposeRole($group, 'IDN-ZLEADER5', 'FIRST_DEPUTY', 'IDN-ZPROPOSED5');

        $sections = app(NotificationService::class)->sections('IDN-ZPROPOSED5');
        self::assertTrue($sections['a_traiter']->contains(
            fn (array $i): bool => $i['source_type'] === 'ZUMRA' && str_contains($i['title'], 'Proposition de responsabilité'),
        ));

        $strangerSections = app(NotificationService::class)->sections('IDN-ZSTRANGER5');
        self::assertTrue($strangerSections['a_traiter']->isEmpty());
    }

    public function test_zumra_pending_join_request_appears_as_actionable_only_for_the_leader(): void
    {
        $group = $this->group('IDN-ZLEADER6');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-ZAPPLICANT6',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-ZAPPLICANT6', 'requested_at' => now(),
        ]);

        $sections = app(NotificationService::class)->sections('IDN-ZLEADER6');
        self::assertTrue($sections['a_traiter']->contains(
            fn (array $i): bool => $i['source_type'] === 'ZUMRA' && str_contains($i['title'], 'demande d’adhésion'),
        ));

        $memberSections = app(NotificationService::class)->sections('IDN-ZAPPLICANT6');
        self::assertTrue($memberSections['a_traiter']->isEmpty(), 'La personne candidate ne décide pas elle-même de sa propre demande.');
    }

    public function test_never_seeds_fictional_notification_data(): void
    {
        $this->get('/')->assertOk();
        self::assertSame(0, NotificationRead::query()->count(), 'Les fixtures d’exemple ne doivent jamais peupler dg_notification_reads.');
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
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
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
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA Notifications '.uniqid(), 'domain' => 'Formation',
            'founding_objective' => 'Réunir des personnes pour apprendre et transmettre des capacités utiles au développement.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Respect, dignité, transmission, hiérarchie responsable et décisions conformes à la charte commune.',
            'assume_primary_lead' => true,
        ]);
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

    private function activateProgram(string $reference): void
    {
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->exists()) {
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
}
