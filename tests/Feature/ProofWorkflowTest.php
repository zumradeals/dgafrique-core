<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionWorkflow;
use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Proof\ProofService;
use App\Application\Proof\ProofVisibilityService;
use App\Application\Proof\ProofWorkflow;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\ProofReference;
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

final class ProofWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_happy_path_with_witness_and_archive_restore(): void
    {
        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS', 'Témoin Test');

        $proof = $workflow->submit('IDN-AUTHOR', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Réparation d’un puits communautaire',
            'description' => 'Le puits du quartier a été réparé avec les outils disponibles.',
            'origin_type' => Proof::ORIGIN_INTERACTION,
            'visibility' => Proof::VISIBILITY_PRIVATE,
        ]);
        self::assertSame(Proof::STATUS_SUBMITTED, $proof->status);
        self::assertDatabaseHas('dg_proof_events', ['proof_id' => $proof->id, 'event' => 'PROOF_SUBMITTED']);

        $witness = $workflow->inviteWitness($proof, 'IDN-AUTHOR', $witnessRef);
        self::assertSame('INVITED', $witness->status);
        self::assertSame(Proof::STATUS_SUBMITTED, $proof->fresh()->status, 'Une invitation seule ne fait jamais avancer la preuve.');

        $this->assertAborts(403, fn () => $workflow->confirmWitness($proof, 'IDN-STRANGER', $witness));

        $workflow->confirmWitness($proof, 'IDN-WITNESS', $witness);
        $proof = $proof->fresh();
        self::assertSame(Proof::STATUS_WITNESSED, $proof->status);
        self::assertDatabaseHas('dg_proof_events', ['proof_id' => $proof->id, 'event' => 'PROOF_WITNESSED']);

        // Archivage réversible : jamais une suppression.
        $proof = $workflow->archive($proof, 'IDN-AUTHOR');
        self::assertNotNull($proof->archived_at);
        $this->assertAborts(403, fn () => $workflow->archive($proof, 'IDN-STRANGER'));
        $proof = $workflow->restore($proof, 'IDN-AUTHOR');
        self::assertNull($proof->archived_at);
        self::assertSame(Proof::STATUS_WITNESSED, $proof->status, 'Le statut opérationnel survit à archivage/restauration.');
    }

    public function test_witness_can_decline_without_certifying_anything(): void
    {
        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS2', 'Témoin Refuse');

        $proof = $workflow->submit('IDN-AUTHOR2', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Formation dispensée à un groupe',
            'description' => 'Une session de formation a été donnée à cinq personnes.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);
        $witness = $workflow->inviteWitness($proof, 'IDN-AUTHOR2', $witnessRef);
        $workflow->declineWitness($proof, 'IDN-WITNESS2', $witness);

        self::assertSame('DECLINED', $witness->fresh()->status);
        self::assertSame(Proof::STATUS_SUBMITTED, $proof->fresh()->status, 'Une preuve sans témoin confirmé reste pleinement valide (fiche §5.5).');
    }

    public function test_context_acknowledgement_never_accepts_on_behalf_of_a_witness(): void
    {
        $this->activateProgram('IDN-DECIDER');
        $this->activateProgram('IDN-AUTHOR3');
        $project = app(ProjectService::class)->create('IDN-DECIDER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-AUTHOR3', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Rapport d’avancement du projet',
            'description' => 'Les premières activités du projet ont été réalisées comme prévu.',
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $project->public_reference,
        ]);

        $this->assertAborts(403, fn () => $workflow->acknowledgeByContext($proof, 'IDN-STRANGER'));

        $proof = $workflow->acknowledgeByContext($proof, 'IDN-DECIDER');
        self::assertSame(Proof::STATUS_ACKNOWLEDGED, $proof->status);
        self::assertSame('IDN-DECIDER', $proof->context_acknowledged_by_core_reference);

        // Reconnaître ne garantit jamais la véracité — la preuve n'a reçu aucun témoin.
        self::assertDatabaseMissing('dg_proof_witnesses', ['proof_id' => $proof->id]);
    }

    public function test_submission_requires_actual_access_to_the_cited_context_fail_closed(): void
    {
        $this->activateProgram('IDN-DECIDER-PRIV');
        $this->activateProgram('IDN-OUTSIDER-PRIV');
        $privateProject = app(ProjectService::class)->create('IDN-DECIDER-PRIV', $this->projectPayload(['visibility' => 'PRIVATE']), (new ProjectConfiguration)->defaults());

        $workflow = app(ProofWorkflow::class);

        // Un tiers sans accès au Projet privé ne peut pas soumettre une preuve qui le cite —
        // fail-closed en 404, pour ne jamais révéler l'existence du contexte à qui n'y a pas accès.
        $this->assertAborts(404, fn () => $workflow->submit('IDN-OUTSIDER-PRIV', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Preuve non autorisée',
            'description' => 'Une tentative de citer un contexte privé sans y avoir accès.',
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $privateProject->public_reference,
        ]));

        // Le propriétaire du Projet, lui, peut citer son propre contexte privé.
        $proof = $workflow->submit('IDN-DECIDER-PRIV', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Preuve autorisée',
            'description' => 'Le propriétaire du Projet cite son propre contexte privé.',
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $privateProject->public_reference,
        ]);
        self::assertSame(Proof::ORIGIN_PROJECT, $proof->origin_type);

        // Même garde-fou pour un Besoin privé.
        $privateNeed = app(NeedService::class)->create('IDN-DECIDER-PRIV', $this->needPayload(['visibility' => Need::VISIBILITY_PRIVATE]), (new NeedConfiguration)->defaults());
        $this->assertAborts(404, fn () => $workflow->submit('IDN-OUTSIDER-PRIV', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Preuve non autorisée (besoin)',
            'description' => 'Une tentative de citer un besoin privé sans y avoir accès.',
            'origin_type' => Proof::ORIGIN_NEED,
            'origin_reference' => $privateNeed->public_reference,
        ]));

        // Même garde-fou pour une ZUMRA dont l'acteur n'est pas membre.
        $group = $this->group('IDN-DECIDER-PRIV');
        $this->assertAborts(404, fn () => $workflow->submit('IDN-OUTSIDER-PRIV', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Preuve non autorisée (zumra)',
            'description' => 'Une tentative de citer une ZUMRA sans en être membre.',
            'origin_type' => Proof::ORIGIN_ZUMRA,
            'origin_reference' => $group->public_reference,
        ]));
    }

    public function test_acknowledge_across_all_contextual_origins(): void
    {
        $this->activateProgram('IDN-LEADER');
        $this->activateProgram('IDN-AUTHOR4');
        $this->activateProgram('IDN-LEARN4');
        $group = $this->group('IDN-LEADER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-AUTHOR4',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-AUTHOR4', 'joined_at' => now(),
        ]);
        $workflow = app(ProofWorkflow::class);

        // ZUMRA : le responsable reconnaît.
        $zumraProof = $workflow->submit('IDN-AUTHOR4', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Animation de réunion ZUMRA', 'description' => 'Une réunion mensuelle a été animée avec succès.',
            'origin_type' => Proof::ORIGIN_ZUMRA, 'origin_reference' => $group->public_reference,
        ]);
        $this->assertAborts(403, fn () => $workflow->acknowledgeByContext($zumraProof, 'IDN-AUTHOR4'));
        $zumraProof = $workflow->acknowledgeByContext($zumraProof, 'IDN-LEADER');
        self::assertSame(Proof::STATUS_ACKNOWLEDGED, $zumraProof->status);

        // NEED : le décideur du Besoin reconnaît.
        $need = app(NeedService::class)->create('IDN-AUTHOR4', $this->needPayload(), (new NeedConfiguration)->defaults());
        $needProof = $workflow->submit('IDN-AUTHOR4', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Résolution du besoin', 'description' => 'Le besoin exprimé a été satisfait grâce à une action concrète.',
            'origin_type' => Proof::ORIGIN_NEED, 'origin_reference' => $need->public_reference,
        ]);
        $needProof = $workflow->acknowledgeByContext($needProof, 'IDN-AUTHOR4');
        self::assertSame(Proof::STATUS_ACKNOWLEDGED, $needProof->status);

        // MISSION : l'officialisateur de la Mission (autorité de son propre contexte) reconnaît.
        $project = app(ProjectService::class)->create('IDN-AUTHOR4', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $missionWorkflow = app(MissionWorkflow::class);
        $mission = $missionWorkflow->create('IDN-AUTHOR4', 'PROJECT', $project->public_reference, [
            'title' => 'Réaliser une session pratique', 'description' => 'Organiser une session pratique de terrain avec les bénéficiaires.',
        ]);
        $mission = $missionWorkflow->propose($mission, 'IDN-AUTHOR4');
        $mission = $missionWorkflow->officialize($mission, 'IDN-AUTHOR4', ['expected_result' => 'Une session tenue.']);
        $missionProof = $workflow->submit('IDN-AUTHOR4', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Session de terrain réalisée', 'description' => 'La session pratique prévue par la Mission a eu lieu.',
            'origin_type' => Proof::ORIGIN_MISSION, 'origin_reference' => $mission->public_reference,
        ]);
        $this->assertAborts(403, fn () => $workflow->acknowledgeByContext($missionProof, 'IDN-STRANGER'));
        $missionProof = $workflow->acknowledgeByContext($missionProof, 'IDN-AUTHOR4');
        self::assertSame(Proof::STATUS_ACKNOWLEDGED, $missionProof->status);

        // TRANSMISSION : un TRANSMITTER accepté reconnaît, jamais un LEARNER.
        $transmissionWorkflow = app(TransmissionWorkflow::class);
        $transmissionParticipation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN4', 'Apprenant Preuve');
        $transmission = $transmissionWorkflow->create('IDN-AUTHOR4', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Poterie', 'learning_objective' => 'Tourner un bol simple.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $p = $transmissionParticipation->invite($transmission, 'IDN-AUTHOR4', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $transmissionParticipation->acceptInvitation($transmission, 'IDN-LEARN4', $p);

        $transmissionProof = $workflow->submit('IDN-AUTHOR4', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Bol tourné avec succès', 'description' => 'Un premier bol a été tourné pendant la Transmission.',
            'origin_type' => Proof::ORIGIN_TRANSMISSION, 'origin_reference' => $transmission->public_reference,
        ]);
        $this->assertAborts(403, fn () => $workflow->acknowledgeByContext($transmissionProof, 'IDN-LEARN4'), 'Un LEARNER n’est jamais autorité contextuelle.');
        $transmissionProof = $workflow->acknowledgeByContext($transmissionProof, 'IDN-AUTHOR4');
        self::assertSame(Proof::STATUS_ACKNOWLEDGED, $transmissionProof->status);
    }

    public function test_none_and_interaction_origins_cannot_be_acknowledged(): void
    {
        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-AUTHOR5', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Preuve autonome', 'description' => 'Une chose réelle sans contexte particulier.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);

        $this->assertAborts(422, fn () => $workflow->acknowledgeByContext($proof, 'IDN-AUTHOR5'));
    }

    public function test_dispute_by_witness_or_context_authority_never_by_author(): void
    {
        $this->activateProgram('IDN-DECIDER6');
        $this->activateProgram('IDN-AUTHOR6');
        $project = app(ProjectService::class)->create('IDN-DECIDER6', $this->projectPayload(), (new ProjectConfiguration)->defaults());
        $witnessRef = $this->discoverableProfile('IDN-WITNESS6', 'Témoin Contestation');

        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-AUTHOR6', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Rapport contesté', 'description' => 'Un rapport dont le contenu est mis en doute.',
            'origin_type' => Proof::ORIGIN_PROJECT, 'origin_reference' => $project->public_reference,
        ]);

        $this->assertAborts(403, fn () => $workflow->dispute($proof, 'IDN-AUTHOR6'), 'L’auteur ne peut jamais contester sa propre preuve.');

        $witness = $workflow->inviteWitness($proof, 'IDN-AUTHOR6', $witnessRef);
        $this->assertAborts(403, fn () => $workflow->dispute($proof, 'IDN-WITNESS6'), 'Un témoin non confirmé ne peut pas contester.');

        $workflow->confirmWitness($proof, 'IDN-WITNESS6', $witness);
        $proof = $workflow->dispute($proof->fresh(), 'IDN-WITNESS6', 'Les faits décrits ne correspondent pas à ce qui a été observé.');
        self::assertSame(Proof::STATUS_DISPUTED, $proof->status);
        self::assertSame('IDN-WITNESS6', $proof->disputed_by_core_reference);

        // L'autorité contextuelle peut aussi contester une autre preuve.
        $secondProof = $workflow->submit('IDN-AUTHOR6', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Second rapport', 'description' => 'Un second rapport soumis pour ce projet.',
            'origin_type' => Proof::ORIGIN_PROJECT, 'origin_reference' => $project->public_reference,
        ]);
        $secondProof = $workflow->dispute($secondProof, 'IDN-DECIDER6');
        self::assertSame(Proof::STATUS_DISPUTED, $secondProof->status);
    }

    public function test_group_owned_proof_requires_group_leader_authority(): void
    {
        $this->activateProgram('IDN-LEADER7');
        $group = $this->group('IDN-LEADER7');

        $workflow = app(ProofWorkflow::class);
        $this->assertAborts(403, fn () => $workflow->submit('IDN-OUTSIDER7', [
            'owner_type' => Proof::OWNER_GROUP, 'group_reference' => $group->public_reference,
            'title' => 'Action collective', 'description' => 'Une action réalisée au nom de la ZUMRA.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]));

        $proof = $workflow->submit('IDN-LEADER7', [
            'owner_type' => Proof::OWNER_GROUP, 'group_reference' => $group->public_reference,
            'title' => 'Action collective', 'description' => 'Une action réalisée au nom de la ZUMRA.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);
        self::assertSame($group->id, $proof->owner_reference);
    }

    public function test_only_external_url_and_free_text_references_are_usable_gamadrive_stays_reserved(): void
    {
        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-AUTHOR8', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Preuve avec références', 'description' => 'Une preuve accompagnée de références diverses.',
            'origin_type' => Proof::ORIGIN_NONE,
            'references' => [
                ['type' => ProofReference::TYPE_EXTERNAL_URL, 'value' => 'https://example.org/preuve'],
                ['type' => ProofReference::TYPE_FREE_TEXT, 'value' => 'Note libre décrivant le contexte.'],
                ['type' => ProofReference::TYPE_GAMADRIVE_FEDERATED, 'value' => 'gamadrive://doc/123'],
            ],
        ]);

        self::assertSame(2, $proof->references()->count(), 'GAMADRIVE_FEDERATED reste réservé et désactivé : aucune fédération documentaire réelle n’existe.');
        self::assertDatabaseMissing('dg_proof_references', ['proof_id' => $proof->id, 'type' => ProofReference::TYPE_GAMADRIVE_FEDERATED]);
    }

    public function test_no_capability_statement_is_ever_created_or_modified(): void
    {
        CapabilityStatement::query()->create([
            'core_identity_reference' => 'IDN-AUTHOR9', 'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => 'Menuiserie', 'normalized_label' => 'menuiserie', 'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_PRIVATE, 'matching_consent' => false, 'source' => 'PROFILE',
        ]);
        $before = CapabilityStatement::query()->where('core_identity_reference', 'IDN-AUTHOR9')->first();

        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS9', 'Témoin Statement');
        $proof = $workflow->submit('IDN-AUTHOR9', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Meuble fabriqué', 'description' => 'Un meuble simple a été fabriqué.',
            'capability_label' => 'Menuiserie', 'origin_type' => Proof::ORIGIN_NONE,
        ]);
        $witness = $workflow->inviteWitness($proof, 'IDN-AUTHOR9', $witnessRef);
        $workflow->confirmWitness($proof, 'IDN-WITNESS9', $witness);

        self::assertSame(1, CapabilityStatement::query()->count(), 'Aucune CapabilityStatement supplémentaire n’est créée par une preuve.');
        $after = $before->fresh();
        self::assertSame($before->status, $after->status, 'CARNET DE PREUVES ne modifie jamais DECLARED -> VERIFIED/ATTESTED, même manuellement, en v1.');
        self::assertSame($before->updated_at->toIso8601String(), $after->updated_at->toIso8601String());
    }

    public function test_visibility_private_by_default_witness_can_view_discoverable_requires_consent(): void
    {
        $visibility = app(ProofVisibilityService::class);
        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS10', 'Témoin Visibilité');

        $privateProof = $workflow->submit('IDN-AUTHOR10', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Preuve privée', 'description' => 'Reste privée par défaut.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);
        self::assertSame(Proof::VISIBILITY_PRIVATE, $privateProof->visibility);
        self::assertTrue($visibility->canView($privateProof, 'IDN-AUTHOR10'));
        self::assertFalse($visibility->canView($privateProof, 'IDN-STRANGER10'));

        $witness = $workflow->inviteWitness($privateProof, 'IDN-AUTHOR10', $witnessRef);
        self::assertTrue($visibility->canView($privateProof, 'IDN-WITNESS10'), 'Un témoin invité doit pouvoir consulter la preuve, même avant confirmation.');

        $this->discoverableProfile('IDN-AUTHOR11', 'Auteur Découvrable');
        $discoverableProof = $workflow->submit('IDN-AUTHOR11', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Preuve visible', 'description' => 'Rendue visible dans la mémoire d’expérience.',
            'origin_type' => Proof::ORIGIN_NONE, 'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ]);
        self::assertTrue($visibility->canView($discoverableProof, 'IDN-STRANGER10'));
    }

    public function test_memory_view_is_chronological_and_respects_visibility(): void
    {
        $this->discoverableProfile('IDN-MEMORY', 'Mémoire Test');
        $workflow = app(ProofWorkflow::class);
        $proofs = app(ProofService::class);

        $old = $workflow->submit('IDN-MEMORY', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Ancienne preuve', 'description' => 'La plus ancienne des deux.',
            'origin_type' => Proof::ORIGIN_NONE, 'visibility' => Proof::VISIBILITY_DISCOVERABLE, 'occurred_at' => now()->subDays(10),
        ]);
        $recent = $workflow->submit('IDN-MEMORY', [
            'owner_type' => Proof::OWNER_PERSON, 'title' => 'Preuve récente', 'description' => 'La plus récente des deux.',
            'origin_type' => Proof::ORIGIN_NONE, 'visibility' => Proof::VISIBILITY_PRIVATE, 'occurred_at' => now(),
        ]);

        $ownView = $proofs->memoryFor('IDN-MEMORY', 'IDN-MEMORY');
        self::assertCount(2, $ownView, 'Le propriétaire voit toutes ses preuves, privées ou non.');
        self::assertSame($recent->id, $ownView->first()->id, 'Ordre chronologique décroissant.');

        $strangerView = $proofs->memoryFor('IDN-MEMORY', 'IDN-STRANGER-MEMORY');
        self::assertCount(1, $strangerView, 'Un tiers ne voit que les preuves DISCOVERABLE.');
        self::assertSame($old->id, $strangerView->first()->id);
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
