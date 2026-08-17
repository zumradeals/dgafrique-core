<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TransmissionHttpSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_transmissions_index_create_and_show_render(): void
    {
        $this->signIn('IDN-OWNER');

        $this->get('/transmissions')->assertOk()->assertSee('Mes Transmissions');
        $this->get('/transmissions/creer')->assertOk()->assertSee('Proposer une Transmission');

        $this->post('/transmissions', [
            'capability_label' => 'Élevage de lapins',
            'learning_objective' => 'Savoir installer et entretenir un petit élevage de lapins.',
            'initiator_role' => 'TRANSMITTER',
            'origin_type' => 'NONE',
            'visibility' => 'PRIVATE',
        ])->assertRedirect();

        $transmission = Transmission::query()->where('capability_label', 'Élevage de lapins')->firstOrFail();
        self::assertSame('IDN-OWNER', $transmission->proposed_by_core_reference);
        self::assertDatabaseHas('dg_transmission_participants', [
            'transmission_id' => $transmission->id, 'core_identity_reference' => 'IDN-OWNER', 'role' => 'TRANSMITTER', 'status' => 'ACCEPTED',
        ]);

        $this->get('/transmissions/'.$transmission->public_reference)->assertOk()->assertSee('Élevage de lapins');
        $this->get('/transmissions/'.$transmission->public_reference.'/correspondances')->assertOk();
    }

    public function test_landing_and_transmission_business_tables_never_seed_demo_data(): void
    {
        $content = $this->get('/')->assertOk()->getContent();
        self::assertStringContainsString('· Exemple', $content);
        self::assertSame(0, Transmission::query()->count(), 'Les fixtures d’exemple ne doivent jamais être présentes dans les tables métier.');
    }

    public function test_no_separate_transmission_feed_exists_and_context_linked_event_joins_shared_fil(): void
    {
        self::assertArrayNotHasKey('TRANSMISSIONS', ActivityFeedService::FILTERS, 'Il n’existe qu’un seul Fil DG Afrique — pas de filtre Transmission autonome (fiche §19).');

        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN', 'Apprenant Fil');

        $transmission = $workflow->create('IDN-OWNER', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Suivi de trésorerie',
            'learning_objective' => 'Suivre la trésorerie du projet au quotidien.',
            'origin_type' => Transmission::ORIGIN_PROJECT,
            'context_type' => Transmission::CONTEXT_PROJECT,
            'context_reference' => $project->public_reference,
        ]);
        $p = $participation->invite($transmission, 'IDN-OWNER', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LEARN', $p);

        $this->signIn('IDN-OWNER');
        $this->get('/activite')->assertOk()->assertSee('Suivi de trésorerie');
    }

    public function test_mon_espace_surfaces_a_pending_transmission_invitation_as_next_action(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-INVITED', 'Invité Test');

        $transmission = $workflow->create('IDN-TEACH', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Tissage traditionnel',
            'learning_objective' => 'Tisser un motif simple.',
            'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $participation->invite($transmission, 'IDN-TEACH', $learnerRef, TransmissionParticipant::ROLE_LEARNER);

        $this->signIn('IDN-INVITED');
        $this->get('/espace')->assertOk()->assertSee('Tissage traditionnel');
    }

    public function test_comments_share_and_messaging_are_wired_for_transmission_context(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-LEARN3', 'Apprenant Coord');

        $transmission = $workflow->create('IDN-TEACH3', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Sérigraphie', 'learning_objective' => 'Imprimer un motif simple sur tissu.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $p = $participation->invite($transmission, 'IDN-TEACH3', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-LEARN3', $p);

        $this->signIn('IDN-TEACH3');

        $this->get('/commentaires/transmissions/'.$transmission->public_reference)->assertOk();
        $this->post('/commentaires/transmissions/'.$transmission->public_reference, [
            'purpose' => 'COORDINATION', 'body' => 'On se retrouve mardi prochain.',
        ])->assertRedirect();
        self::assertDatabaseHas('dg_context_comments', ['context_type' => 'TRANSMISSION', 'context_reference' => $transmission->public_reference]);

        $this->get('/partages/transmissions/'.$transmission->public_reference)->assertOk();

        $this->post('/messages/transmissions/'.$transmission->public_reference)->assertRedirect();
        self::assertDatabaseHas('dg_message_conversations', ['context_type' => 'TRANSMISSION', 'context_reference' => $transmission->public_reference]);
    }

    public function test_no_automatic_certification_ever_touches_capability_statements(): void
    {
        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $learnerRef = $this->discoverableProfile('IDN-L4', 'Apprenant Certif');

        CapabilityStatement::query()->create([
            'core_identity_reference' => 'IDN-L4', 'kind' => CapabilityStatement::KIND_LEARNING,
            'label' => 'Vannerie', 'normalized_label' => 'vannerie', 'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_PRIVATE, 'matching_consent' => false, 'source' => 'PROFILE',
        ]);
        $before = CapabilityStatement::query()->where('core_identity_reference', 'IDN-L4')->first();

        $transmission = $workflow->create('IDN-T4', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Vannerie', 'learning_objective' => 'Tresser un panier simple.', 'origin_type' => Transmission::ORIGIN_NONE,
        ]);
        $p = $participation->invite($transmission, 'IDN-T4', $learnerRef, TransmissionParticipant::ROLE_LEARNER);
        $participation->acceptInvitation($transmission, 'IDN-L4', $p);
        $transmission = $workflow->start($transmission->fresh(), 'IDN-T4');
        $workflow->declareDone($transmission, 'IDN-T4');
        $workflow->declareDone($transmission, 'IDN-L4');
        $workflow->confirmCompletion($transmission, 'IDN-T4', 'Panier tressé avec succès.');

        $after = $before->fresh();
        self::assertSame($before->status, $after->status, 'Aucune certification automatique : le statut de la déclaration ne bouge jamais tout seul.');
        self::assertSame($before->updated_at->toIso8601String(), $after->updated_at->toIso8601String(), 'La déclaration préexistante n’est jamais mutée silencieusement par une Transmission.');
        self::assertSame(1, CapabilityStatement::query()->count(), 'Aucune CapabilityStatement supplémentaire n’est créée par la Transmission.');
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
