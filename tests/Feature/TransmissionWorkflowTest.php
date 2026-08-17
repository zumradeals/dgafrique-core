<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Transmission\TransmissionContextService;
use App\Application\Transmission\TransmissionMatchingEngine;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class TransmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_individual_happy_path_person_to_person(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN', 'Apprenant Test');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Maraîchage biologique',
            'learning_objective' => 'Pouvoir cultiver un potager biologique de base.',
            'origin_type' => Transmission::ORIGIN_INTERACTION,
        ]);
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->status);
        self::assertDatabaseHas('dg_transmission_events', ['transmission_id' => $transmission->id, 'event' => 'TRANSMISSION_PROPOSED']);

        // Silence ne vaut pas acceptation : la Transmission reste PROPOSED tant que personne
        // d'autre n'a explicitement accepté.
        $participant = $participation->invite($transmission, 'IDN-TEACH', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        self::assertSame(TransmissionParticipant::STATUS_INVITED, $participant->status);
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->fresh()->status);

        $participation->acceptInvitation($transmission, 'IDN-LEARN', $participant);
        $transmission = $transmission->fresh();
        self::assertSame(Transmission::STATUS_ACCEPTED, $transmission->status, 'Quorum atteint (1 TRANSMITTER + 1 LEARNER acceptés) : promotion automatique.');
        self::assertDatabaseHas('dg_transmission_events', ['transmission_id' => $transmission->id, 'event' => 'TRANSMISSION_ACCEPTED']);

        $transmission = $workflow->start($transmission, 'IDN-LEARN');
        self::assertSame(Transmission::STATUS_IN_PROGRESS, $transmission->status);

        // Déclarer sa part terminée ne valide jamais seul la Transmission.
        $workflow->declareDone($transmission, 'IDN-TEACH');
        $this->assertAborts(409, fn () => $workflow->confirmCompletion($transmission, 'IDN-TEACH', 'Résumé prématuré.'));

        $workflow->declareDone($transmission, 'IDN-LEARN', 'Potager mis en place.');
        $transmission = $workflow->confirmCompletion($transmission, 'IDN-LEARN', 'Le potager biologique a été planté et entretenu ensemble.');
        self::assertSame(Transmission::STATUS_COMPLETED_CONFIRMED, $transmission->status);
        self::assertNotNull($transmission->completion_summary);
        self::assertDatabaseHas('dg_transmission_events', ['transmission_id' => $transmission->id, 'event' => 'TRANSMISSION_COMPLETED', 'to_state' => 'COMPLETED_CONFIRMED']);
    }

    public function test_self_offer_requires_explicit_authority_acceptance(): void
    {
        $this->activateProgram('IDN-CRAFT');
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);

        // Visibilité PROGRAM : un membre du Programme peut découvrir la proposition et se
        // proposer, sans que cela lui accorde une acceptation.
        $transmission = $workflow->create('IDN-STUDENT', TransmissionParticipant::ROLE_LEARNER, [
            'capability_label' => 'Menuiserie',
            'learning_objective' => 'Fabriquer un petit meuble simple.',
            'origin_type' => Transmission::ORIGIN_NONE,
            'visibility' => Transmission::VISIBILITY_PROGRAM,
        ]);

        $offer = $participation->offer($transmission, 'IDN-CRAFT', TransmissionParticipant::ROLE_TRANSMITTER);
        self::assertSame(TransmissionParticipant::STATUS_OFFERED, $offer->status);
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->fresh()->status, 'Une offre seule ne fait jamais avancer la Transmission.');

        // Un tiers sans autorité ne peut ni accepter ni refuser l'offre.
        $this->assertAborts(403, fn () => $participation->acceptOffer($transmission, 'IDN-STRANGER', $offer));

        $participation->acceptOffer($transmission, 'IDN-STUDENT', $offer);
        self::assertSame(Transmission::STATUS_ACCEPTED, $transmission->fresh()->status);
    }

    public function test_refusal_and_withdrawal_never_promote_the_transmission(): void
    {
        $this->activateProgram('IDN-OFFERER');
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $ref = $this->discoverableProfile('IDN-DECLINE', 'Refuse Test');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Couture', 'learning_objective' => 'Coudre un vêtement simple.', 'origin_type' => Transmission::ORIGIN_NONE,
            'visibility' => Transmission::VISIBILITY_PROGRAM,
        ]);

        $invited = $participation->invite($transmission, 'IDN-TEACH', $ref, TransmissionParticipant::ROLE_LEARNER);
        $participation->declineInvitation($transmission, 'IDN-DECLINE', $invited);
        self::assertSame(TransmissionParticipant::STATUS_DECLINED, $invited->fresh()->status);
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->fresh()->status);

        $offered = $participation->offer($transmission->fresh(), 'IDN-OFFERER', TransmissionParticipant::ROLE_LEARNER);
        $participation->withdraw($transmission, 'IDN-OFFERER', $offered);
        self::assertSame(TransmissionParticipant::STATUS_WITHDRAWN, $offered->fresh()->status);
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->fresh()->status);
    }

    public function test_collective_transmission_multiple_transmitters_and_learners(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerA = $this->discoverableProfile('IDN-L1', 'Apprenant Un');
        $learnerB = $this->discoverableProfile('IDN-L2', 'Apprenant Deux');
        $coTeacher = $this->discoverableProfile('IDN-T2', 'Transmetteur Deux');

        $transmission = $workflow->create('IDN-T1', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Réparation de vélo', 'learning_objective' => 'Réparer une crevaison et régler des freins.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);

        foreach ([[$learnerA, TransmissionParticipant::ROLE_LEARNER, 'IDN-L1'], [$learnerB, TransmissionParticipant::ROLE_LEARNER, 'IDN-L2'], [$coTeacher, TransmissionParticipant::ROLE_TRANSMITTER, 'IDN-T2']] as [$discoveryRef, $role, $identity]) {
            $p = $participation->invite($transmission, 'IDN-T1', $discoveryRef, $role);
            $participation->acceptInvitation($transmission, $identity, $p);
        }

        $transmission = $transmission->fresh();
        self::assertSame(Transmission::STATUS_ACCEPTED, $transmission->status);
        self::assertSame(4, $transmission->participants()->where('status', TransmissionParticipant::STATUS_ACCEPTED)->count());

        $transmission = $workflow->start($transmission, 'IDN-T1');
        foreach (['IDN-T1', 'IDN-T2', 'IDN-L1', 'IDN-L2'] as $identity) {
            $workflow->declareDone($transmission, $identity);
        }
        $transmission = $workflow->confirmCompletion($transmission, 'IDN-L1', 'Tous les participants ont réparé un vélo.');
        self::assertSame(Transmission::STATUS_COMPLETED_CONFIRMED, $transmission->status);
    }

    public function test_remove_participant_rules(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerA = $this->discoverableProfile('IDN-LA', 'Apprenant A');
        $learnerB = $this->discoverableProfile('IDN-LB', 'Apprenant B');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Poterie', 'learning_objective' => 'Tourner un bol simple.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $pa = $participation->invite($transmission, 'IDN-TEACH', $learnerA, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LA', $pa);
        $pb = $participation->invite($transmission, 'IDN-TEACH', $learnerB, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LB', $pb);

        // Un LEARNER ne peut jamais retirer un autre LEARNER.
        $this->assertAborts(403, fn () => $participation->remove($transmission, 'IDN-LA', $pb, 'Je ne suis pas d’accord.'));

        // Un TRANSMITTER accepté peut retirer un autre participant, avec raison obligatoire.
        $this->assertAborts(422, fn () => $participation->remove($transmission, 'IDN-TEACH', $pb, ''));
        $participation->remove($transmission, 'IDN-TEACH', $pb, 'Indisponibilité prolongée.');
        self::assertSame(TransmissionParticipant::STATUS_REMOVED, $pb->fresh()->status);

        // On ne se retire jamais soi-même via remove() — uniquement leave().
        $ownParticipant = $transmission->participants()->where('core_identity_reference', 'IDN-TEACH')->firstOrFail();
        $this->assertAborts(422, fn () => $participation->remove($transmission, 'IDN-TEACH', $ownParticipant, 'Je pars.'));
        $participation->leave($transmission, 'IDN-TEACH', $ownParticipant);
        self::assertSame(TransmissionParticipant::STATUS_WITHDRAWN, $ownParticipant->fresh()->status);
    }

    public function test_cancel_only_before_in_progress_end_after(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-L', 'Apprenant');

        $t1 = $workflow->create('IDN-T', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Cuisine locale', 'learning_objective' => 'Cuisiner un plat traditionnel.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $t1 = $workflow->cancel($t1, 'IDN-T', 'Finalement indisponible.');
        self::assertSame(Transmission::STATUS_CANCELLED, $t1->status);

        $t2 = $workflow->create('IDN-T', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Cuisine locale', 'learning_objective' => 'Cuisiner un plat traditionnel.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $p = $participation->invite($t2, 'IDN-T', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($t2, 'IDN-L', $p);
        $t2 = $workflow->start($t2->fresh(), 'IDN-T');

        $this->assertAborts(409, fn () => $workflow->cancel($t2, 'IDN-T', 'Trop tard.'));

        $t2 = $workflow->end($t2, 'IDN-T', 'Emploi du temps incompatible.');
        self::assertSame(Transmission::STATUS_ENDED, $t2->status);
    }

    public function test_officialize_context_never_accepts_on_behalf_of_participants(): void
    {
        $this->activateProgram('IDN-DECIDER');
        $this->activateProgram('IDN-TEACH');
        $project = app(ProjectService::class)->create('IDN-DECIDER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(TransmissionWorkflow::class);
        $contexts = app(TransmissionContextService::class);

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Comptabilité associative',
            'learning_objective' => 'Tenir un livre de comptes simple pour le projet.',
            'origin_type' => Transmission::ORIGIN_PROJECT,
            'context_type' => Transmission::CONTEXT_PROJECT,
            'context_reference' => $project->public_reference,
        ]);
        self::assertSame(Transmission::CONTEXT_PROJECT, $transmission->context_type);

        // Un acteur sans autorité de décision sur le Projet ne peut pas officialiser.
        $this->assertAborts(403, fn () => $workflow->officializeContext($transmission, 'IDN-STRANGER'));

        $transmission = $workflow->officializeContext($transmission, 'IDN-DECIDER', Transmission::VISIBILITY_CONTEXT);
        self::assertNotNull($transmission->context_officialized_at);
        self::assertSame('IDN-DECIDER', $transmission->context_officialized_by_core_reference);
        // Officialiser ne crée aucune participation et ne fait pas avancer le statut.
        self::assertSame(Transmission::STATUS_PROPOSED, $transmission->status);
        self::assertTrue($contexts->canOfficialize(Transmission::CONTEXT_PROJECT, $project, 'IDN-DECIDER'));
        self::assertDatabaseMissing('dg_transmission_participants', ['transmission_id' => $transmission->id, 'core_identity_reference' => 'IDN-DECIDER']);
    }

    public function test_validate_by_context_requires_context_authority_and_in_progress(): void
    {
        $this->activateProgram('IDN-DECIDER');
        $this->activateProgram('IDN-TEACH');
        $this->activateProgram('IDN-LEARN2');
        $project = app(ProjectService::class)->create('IDN-DECIDER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN2', 'Apprenant Contexte');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Suivi budgétaire', 'learning_objective' => 'Suivre un budget simple.',
            'origin_type' => Transmission::ORIGIN_PROJECT, 'context_type' => Transmission::CONTEXT_PROJECT, 'context_reference' => $project->public_reference,
        ]);
        $p = $participation->invite($transmission, 'IDN-TEACH', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LEARN2', $p);
        $transmission = $transmission->fresh();

        $this->assertAborts(409, fn () => $workflow->validateByContext($transmission, 'IDN-DECIDER'), 'Pas encore IN_PROGRESS.');

        $transmission = $workflow->start($transmission, 'IDN-TEACH');
        $this->assertAborts(403, fn () => $workflow->validateByContext($transmission, 'IDN-STRANGER'));

        // Sans trace réelle (aucune contribution, aucune evidence_context), la validation
        // contextuelle est refusée — même par une autorité légitime.
        $this->assertAborts(409, fn () => $workflow->validateByContext($transmission, 'IDN-DECIDER'), 'Aucune trace : validation contextuelle refusée.');

        app(\App\Application\Transmission\TransmissionService::class)->addContribution($transmission, 'IDN-TEACH', 'Séance de suivi budgétaire réalisée avec le budget du projet.');

        $transmission = $workflow->validateByContext($transmission, 'IDN-DECIDER', 'Réalisation confirmée par le porteur du projet.');
        self::assertSame(Transmission::STATUS_COMPLETED_BY_CONTEXT, $transmission->status);
        self::assertSame('IDN-DECIDER', $transmission->context_validated_by_core_reference);
    }

    public function test_zumra_context_officialize_uses_group_leader_authority(): void
    {
        $this->activateProgram('IDN-LEADER');
        $this->activateProgram('IDN-TEACH');
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-TEACH',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-TEACH', 'joined_at' => now(),
        ]);

        $workflow = app(TransmissionWorkflow::class);
        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Facilitation de réunion', 'learning_objective' => 'Animer une réunion de ZUMRA.',
            'origin_type' => Transmission::ORIGIN_ZUMRA, 'context_type' => Transmission::CONTEXT_ZUMRA, 'context_reference' => $group->public_reference,
        ]);

        $this->assertAborts(403, fn () => $workflow->officializeContext($transmission, 'IDN-TEACH'), 'Le transmetteur n’est pas responsable de la ZUMRA.');
        $transmission = $workflow->officializeContext($transmission, 'IDN-LEADER');
        self::assertSame('IDN-LEADER', $transmission->context_officialized_by_core_reference);
    }

    public function test_zumra_participant_loses_operational_access_after_leaving_the_group(): void
    {
        $this->activateProgram('IDN-LEADER');
        $group = $this->group('IDN-LEADER');
        $teachMembership = ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-TEACH',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-TEACH', 'joined_at' => now(),
        ]);
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-LEARN',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-LEARN', 'joined_at' => now(),
        ]);
        $learnerRef = $this->discoverableProfile('IDN-LEARN', 'Apprenant ZUMRA');

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $visibility = app(TransmissionVisibilityService::class);

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Médiation de conflit', 'learning_objective' => 'Médiatiser un désaccord simple.',
            'origin_type' => Transmission::ORIGIN_ZUMRA, 'context_type' => Transmission::CONTEXT_ZUMRA, 'context_reference' => $group->public_reference,
        ]);
        $p = $participation->invite($transmission, 'IDN-TEACH', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LEARN', $p);
        $transmission = $transmission->fresh();
        self::assertSame(Transmission::STATUS_ACCEPTED, $transmission->status);
        self::assertTrue($visibility->canView($transmission, 'IDN-TEACH'));

        // IDN-TEACH quitte la ZUMRA rattachée : reste ACCEPTED en base, mais perd l'accès
        // opérationnel — le raccourci participant/initiateur ne doit jamais contourner ça.
        $teachMembership->update(['status' => ZumraGroupMembership::STATUS_LEFT]);

        self::assertFalse($visibility->canView($transmission, 'IDN-TEACH'), 'La perte d’accès au contexte porteur retire aussi la visibilité, malgré le statut ACCEPTED en base.');
        $this->assertAborts(403, fn () => $workflow->start($transmission, 'IDN-TEACH'));

        // Mais quitter proprement reste toujours possible : la sortie n'est jamais bloquée.
        $ownParticipant = $transmission->participants()->where('core_identity_reference', 'IDN-TEACH')->firstOrFail();
        $participation->leave($transmission, 'IDN-TEACH', $ownParticipant);
        self::assertSame(TransmissionParticipant::STATUS_WITHDRAWN, $ownParticipant->fresh()->status);
    }

    public function test_zumra_outsider_cannot_be_invited_to_a_zumra_context_transmission(): void
    {
        $this->activateProgram('IDN-LEADER');
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-TEACH',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-TEACH', 'joined_at' => now(),
        ]);
        $outsiderRef = $this->discoverableProfile('IDN-OUTSIDER', 'Étranger à la ZUMRA');

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Gestion de conflit', 'learning_objective' => 'Gérer un désaccord au sein du groupe.',
            'origin_type' => Transmission::ORIGIN_ZUMRA, 'context_type' => Transmission::CONTEXT_ZUMRA, 'context_reference' => $group->public_reference,
        ]);

        // Une invitation ne fabrique jamais un accès ZUMRA : un outsider non membre reste refusé.
        $this->assertAborts(422, fn () => $participation->invite($transmission, 'IDN-TEACH', $outsiderRef, TransmissionParticipant::ROLE_LEARNER));
    }

    public function test_standalone_transmission_without_context_keeps_working_normally(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LSTAND', 'Apprenant Autonome');

        $transmission = $workflow->create('IDN-TSTAND', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Chant traditionnel', 'learning_objective' => 'Chanter un morceau traditionnel simple.',
            'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        self::assertNull($transmission->context_type);

        $p = $participation->invite($transmission, 'IDN-TSTAND', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LSTAND', $p);
        $transmission = $workflow->start($transmission->fresh(), 'IDN-TSTAND');
        $workflow->declareDone($transmission, 'IDN-TSTAND');
        $workflow->declareDone($transmission, 'IDN-LSTAND');
        $transmission = $workflow->confirmCompletion($transmission, 'IDN-LSTAND', 'Le morceau a été appris et chanté ensemble.');
        self::assertSame(Transmission::STATUS_COMPLETED_CONFIRMED, $transmission->status);
    }

    public function test_mission_context_link_and_need_context_visibility_only(): void
    {
        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $missionWorkflow = app(MissionWorkflow::class);
        $mission = $missionWorkflow->create('IDN-OWNER', 'PROJECT', $project->public_reference, [
            'title' => 'Former un pair', 'description' => 'Organiser une session de formation entre pairs sur le terrain.',
        ]);
        $mission = $missionWorkflow->propose($mission, 'IDN-OWNER');
        $mission = $missionWorkflow->officialize($mission, 'IDN-OWNER', ['expected_result' => 'Une session tenue.']);

        $workflow = app(TransmissionWorkflow::class);
        $transmission = $workflow->create('IDN-OWNER', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Encadrement de session', 'learning_objective' => 'Encadrer une session pratique.',
            'origin_type' => Transmission::ORIGIN_MISSION, 'context_type' => Transmission::CONTEXT_MISSION, 'context_reference' => $mission->public_reference,
        ]);
        self::assertSame(Transmission::CONTEXT_MISSION, $transmission->context_type);
        // L'officialisateur de la Mission (l'autorité de son propre contexte Projet) reste l'autorité contextuelle réutilisée.
        $transmission = $workflow->officializeContext($transmission, 'IDN-OWNER');
        self::assertNotNull($transmission->context_officialized_at);

        // Contexte NEED : visibilité seulement, jamais d'officialisation (fiche §9).
        $need = app(NeedService::class)->create('IDN-OWNER', $this->needPayload(), (new NeedConfiguration)->defaults());
        $needTransmission = $workflow->create('IDN-OWNER', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Comptabilité de base', 'learning_objective' => 'Tenir les comptes du besoin exprimé.',
            'origin_type' => Transmission::ORIGIN_NEED, 'context_type' => Transmission::CONTEXT_NEED, 'context_reference' => $need->public_reference,
        ]);
        $this->assertAborts(422, fn () => $workflow->officializeContext($needTransmission, 'IDN-OWNER'));
    }

    public function test_no_capability_statement_is_ever_created_or_modified_silently(): void
    {
        self::assertSame(0, CapabilityStatement::query()->count());

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-L', 'Apprenant');

        $transmission = $workflow->create('IDN-T', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Élevage de volailles', 'learning_objective' => 'Élever des poules pondeuses.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $p = $participation->invite($transmission, 'IDN-T', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-L', $p);
        $transmission = $workflow->start($transmission->fresh(), 'IDN-T');
        $workflow->declareDone($transmission, 'IDN-T');
        $workflow->declareDone($transmission, 'IDN-L');
        $workflow->confirmCompletion($transmission, 'IDN-L', 'Poulailler mis en place et entretenu.');

        self::assertSame(0, CapabilityStatement::query()->count(), 'Une Transmission ne crée ni ne modifie jamais silencieusement une CapabilityStatement.');
    }

    public function test_matching_engine_explains_and_can_hide_without_creating_anything(): void
    {
        $candidateRef = $this->discoverableProfile('IDN-CANDIDATE', 'Candidat Transmetteur');
        CapabilityStatement::query()->create([
            'core_identity_reference' => 'IDN-CANDIDATE',
            'kind' => CapabilityStatement::KIND_TRANSMISSION,
            'label' => 'Apiculture',
            'normalized_label' => 'apiculture',
            'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
            'matching_consent' => true,
            'source' => 'PROFILE',
        ]);

        $workflow = app(TransmissionWorkflow::class);
        $transmission = $workflow->create('IDN-STUDENT', TransmissionParticipant::ROLE_LEARNER, [
            'capability_label' => 'Apiculture', 'learning_objective' => 'Apprendre à gérer une ruche.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);

        $matching = app(TransmissionMatchingEngine::class);
        $results = $matching->recommend($transmission, 'IDN-STUDENT', TransmissionParticipant::ROLE_TRANSMITTER);
        self::assertCount(1, $results);
        self::assertStringContainsString('Apiculture', $results[0]['reasons'][0]);
        self::assertSame(0, CapabilityStatement::query()->where('kind', '!=', CapabilityStatement::KIND_TRANSMISSION)->count());

        $matching->hide($transmission, 'IDN-STUDENT', $candidateRef);
        $results = $matching->recommend($transmission, 'IDN-STUDENT', TransmissionParticipant::ROLE_TRANSMITTER);
        self::assertCount(0, $results);
    }

    public function test_visibility_private_by_default_and_never_public(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $visibility = app(TransmissionVisibilityService::class);

        $transmission = $workflow->create('IDN-T', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Réparation de vélo', 'learning_objective' => 'Réparer un vélo.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        self::assertSame(Transmission::VISIBILITY_PRIVATE, $transmission->visibility);
        self::assertTrue($visibility->canView($transmission, 'IDN-T'));
        self::assertFalse($visibility->canView($transmission, 'IDN-STRANGER'), 'Privée par défaut : un tiers non participant ne voit rien.');

        $this->assertAborts(422, fn () => $workflow->create('IDN-T', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Test', 'learning_objective' => 'Test.', 'origin_type' => Transmission::ORIGIN_NONE, 'visibility' => 'PUBLIC',
        ]), 'Aucune visibilité PUBLIC n’existe en v1 (fiche §16).');
    }

    private function assertAborts(int $status, callable $fn, string $message = ''): void
    {
        try {
            $fn();
            self::fail("Expected an HttpException with status {$status} but none was thrown. {$message}");
        } catch (HttpException $e) {
            self::assertSame($status, $e->getStatusCode(), $message);
        }
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

    private function needPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Need::OWNER_PERSON, 'group_reference' => null,
            'title' => 'Former notre équipe à la comptabilité',
            'context' => 'Notre équipe tient ses activités mais ne maîtrise pas encore le suivi simple des recettes et dépenses.',
            'category' => 'TRAINING', 'capability_label' => 'Comptabilité', 'collaboration_mode' => 'HYBRID',
            'location' => 'Abidjan', 'visibility' => Need::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    private function group(string $leader): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($leader, [
            'name' => 'ZUMRA '.uniqid(), 'domain' => 'Formation',
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
