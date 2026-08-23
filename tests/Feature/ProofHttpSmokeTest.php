<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Activity\ActivityFeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Proof\ProofWorkflow;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProofHttpSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_proofs_index_create_and_show_render(): void
    {
        $this->signIn('IDN-OWNER');

        $this->get('/preuves')->assertOk()->assertSee('Mon Carnet de preuves');
        $this->get('/preuves/creer')->assertOk()->assertSee('Enregistrer une preuve');

        $this->post('/preuves', [
            'title' => 'Récolte communautaire organisée',
            'description' => 'Une récolte a été organisée avec succès pour douze familles du quartier.',
            'owner_type' => 'PERSON',
            'origin_type' => 'NONE',
            'visibility' => 'PRIVATE',
        ])->assertRedirect();

        $proof = Proof::query()->where('title', 'Récolte communautaire organisée')->firstOrFail();
        self::assertSame('IDN-OWNER', $proof->submitted_by_core_reference);
        self::assertSame(Proof::STATUS_SUBMITTED, $proof->status);

        $this->get('/preuves/'.$proof->public_reference)->assertOk()->assertSee('Récolte communautaire organisée');
        $this->get('/preuves/memoire')->assertOk()->assertSee('Ma mémoire d’expérience');
    }

    public function test_landing_and_proof_business_tables_never_seed_demo_data(): void
    {
        $content = $this->get('/')->assertOk()->getContent();
        self::assertStringContainsString('· Exemple', $content);
        self::assertSame(0, Proof::query()->count(), 'Les fixtures d’exemple ne doivent jamais être présentes dans les tables métier.');
    }

    public function test_no_separate_proof_feed_exists_and_context_linked_event_joins_shared_fil(): void
    {
        self::assertArrayNotHasKey('PROOFS', ActivityFeedService::FILTERS, 'Il n’existe qu’un seul Fil DG Afrique — pas de filtre Preuve autonome (fiche §15).');

        $this->activateProgram('IDN-OWNER');
        $project = app(ProjectService::class)->create('IDN-OWNER', $this->projectPayload(['zumra_group_reference' => $this->zumraFor('IDN-OWNER')]), (new ProjectConfiguration)->defaults());

        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-OWNER', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Preuve rattachée au projet',
            'description' => 'Une activité réelle du projet a été réalisée et documentée.',
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $project->public_reference,
        ]);
        $workflow->acknowledgeByContext($proof, 'IDN-OWNER');

        $this->signIn('IDN-OWNER');
        $this->get('/activite')->assertOk()->assertSee('Preuve rattachée au projet');
    }

    public function test_mon_espace_surfaces_a_pending_witness_invitation_as_next_action(): void
    {
        $workflow = app(ProofWorkflow::class);
        $witnessRef = $this->discoverableProfile('IDN-WITNESS', 'Témoin Espace');

        $proof = $workflow->submit('IDN-AUTHOR', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Réparation de vélo réalisée',
            'description' => 'Une crevaison a été réparée pour un voisin.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);
        $workflow->inviteWitness($proof, 'IDN-AUTHOR', $witnessRef);

        $this->signIn('IDN-WITNESS');
        $this->get('/espace')->assertOk()->assertSee('Réparation de vélo réalisée');
    }

    public function test_comments_and_share_are_wired_for_proof_context(): void
    {
        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-AUTHOR2', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Réunion de coordination tenue',
            'description' => 'Une réunion de coordination a rassemblé les membres actifs.',
            'origin_type' => Proof::ORIGIN_NONE,
        ]);

        $this->signIn('IDN-AUTHOR2');

        $this->get('/commentaires/preuves/'.$proof->public_reference)->assertOk();
        $this->post('/commentaires/preuves/'.$proof->public_reference, [
            'purpose' => 'COORDINATION', 'body' => 'Merci à tous les participants.',
        ])->assertRedirect();
        self::assertDatabaseHas('dg_context_comments', ['context_type' => 'PROOF', 'context_reference' => $proof->public_reference]);

        $this->get('/partages/preuves/'.$proof->public_reference)->assertOk();
    }

    public function test_discoverable_proof_never_reveals_a_private_context_label_or_link_to_an_unauthorized_viewer(): void
    {
        $this->activateProgram('IDN-OWNER-CTX');
        $this->activateProgram('IDN-VIEWER-CTX');
        $this->discoverableProfile('IDN-OWNER-CTX', 'Porteur Contexte');
        $this->discoverableProfile('IDN-VIEWER-CTX', 'Viewer Contexte');

        $project = app(ProjectService::class)->create('IDN-OWNER-CTX', $this->projectPayload([
            'name' => 'Atelier Confidentiel Ultra Secret',
            'visibility' => 'PRIVATE',
            'zumra_group_reference' => $this->zumraFor('IDN-OWNER-CTX'),
        ]), (new ProjectConfiguration)->defaults());

        $workflow = app(ProofWorkflow::class);
        $proof = $workflow->submit('IDN-OWNER-CTX', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Activité réalisée dans un cadre privé',
            'description' => 'Une activité réelle a été menée dans le cadre de ce projet.',
            'origin_type' => Proof::ORIGIN_PROJECT,
            'origin_reference' => $project->public_reference,
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ]);

        // Le viewer n'a pas accès au Projet privé : il voit la preuve (DISCOVERABLE) mais
        // jamais le nom ni le lien du contexte cité.
        $this->signIn('IDN-VIEWER-CTX');
        $response = $this->get('/preuves/'.$proof->public_reference);
        $response->assertOk()
            ->assertSee('Activité réalisée dans un cadre privé')
            ->assertSee('Contexte non public')
            ->assertDontSee('Atelier Confidentiel Ultra Secret')
            ->assertDontSee('/projets/'.$project->public_reference);

        // Une fois que le viewer a réellement accès au contexte (ici : visibilité PROGRAM +
        // adhésion active), le label et le lien réels s'affichent normalement.
        $project->update(['visibility' => 'PROGRAM']);
        $response = $this->get('/preuves/'.$proof->public_reference);
        $response->assertOk()
            ->assertSee('Atelier Confidentiel Ultra Secret')
            ->assertSee('/projets/'.$project->public_reference);
    }

    public function test_matching_surfaces_proof_as_explainable_reason_never_as_a_score(): void
    {
        $this->discoverableProfile('IDN-VIEWER', 'Vue Matching');
        CapabilityStatement::query()->create([
            'core_identity_reference' => 'IDN-VIEWER', 'kind' => CapabilityStatement::KIND_LEARNING,
            'label' => 'Apiculture', 'normalized_label' => 'apiculture', 'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_PRIVATE, 'matching_consent' => true, 'source' => 'PROFILE',
        ]);

        $this->discoverableProfile('IDN-CANDIDATE', 'Candidat Preuve');
        $workflow = app(ProofWorkflow::class);
        $workflow->submit('IDN-CANDIDATE', [
            'owner_type' => Proof::OWNER_PERSON,
            'title' => 'Ruche installée avec succès',
            'description' => 'Une ruche a été installée et suivie pendant plusieurs semaines.',
            'capability_label' => 'Apiculture',
            'origin_type' => Proof::ORIGIN_NONE,
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ]);

        $engine = app(PersonRecommendationEngine::class);
        $result = $engine->forIdentity('IDN-VIEWER', (new RecommendationConfiguration)->defaults());

        $match = collect($result['recommendations'])->first(fn (array $r): bool => $r['profile']->core_identity_reference === 'IDN-CANDIDATE');
        self::assertNotNull($match, 'La preuve DISCOVERABLE doit produire une recommandation explicable.');
        self::assertTrue(collect($match['reasons'])->contains(fn (string $reason): bool => str_contains($reason, 'preuve')));
        // Aucun champ de score n'est jamais exposé dans une recommandation.
        foreach ($result['recommendations'] as $recommendation) {
            self::assertArrayNotHasKey('score', $recommendation);
            self::assertArrayNotHasKey('_priority', $recommendation);
        }
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
