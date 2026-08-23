<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionWorkflow;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\CapabilityStatement;
use App\Models\Mission;
use App\Models\PersonProfile;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UIUX-002 — décision #5 : surface humaine dédiée pour CAP-064. Réutilise OpportunityEngine
 * strictement tel quel (aucun recalcul) ; la vue n'expose jamais `priority`, un identifiant CAP,
 * ou du jargon moteur/matching.
 */
final class OpportunityHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_authorized_opportunities_with_explainable_reasons_and_a_link_to_the_mission(): void
    {
        $this->profile('IDN-OPP-ACTOR');
        $this->capability('IDN-OPP-ACTOR', 'Comptabilité');
        $mission = $this->openMission('IDN-OPP-OWNER', 'Tenir la comptabilité mensuelle');
        $this->requirement($mission, 'Comptabilité');

        $this->signIn('IDN-OPP-ACTOR');
        $content = $this->get('/opportunites')->assertOk()->getContent();

        self::assertStringContainsString('Tenir la comptabilité mensuelle', $content);
        self::assertStringContainsString('Comptabilité', $content);
        self::assertStringContainsString(route('missions.show', ['mission' => $mission->public_reference]), $content);
    }

    public function test_never_exposes_internal_priority_score_cap_identifier_or_engine_jargon(): void
    {
        $this->profile('IDN-OPP-ACTOR2');
        $this->capability('IDN-OPP-ACTOR2', 'Menuiserie');
        $mission = $this->openMission('IDN-OPP-OWNER2', 'Fabriquer du mobilier scolaire');
        $this->requirement($mission, 'Menuiserie');

        $this->signIn('IDN-OPP-ACTOR2');
        $content = $this->get('/opportunites')->assertOk()->getContent();

        // Le mot « classement » peut légitimement apparaître pour le REJETER explicitement (même
        // patron déjà établi ailleurs dans l'app, ex. Fil : « pas de score, pas de classement »).
        // Ce qui doit rester strictement absent : l'entier interne `priority`, un identifiant
        // CAP, et le jargon technique moteur/matching.
        self::assertDoesNotMatchRegularExpression('/CAP-\d/', $content);
        self::assertStringNotContainsStringIgnoringCase('priority', $content);
        self::assertStringNotContainsStringIgnoringCase('score', $content);
        self::assertStringNotContainsStringIgnoringCase('matching', $content);
        self::assertStringNotContainsStringIgnoringCase('moteur', $content);
    }

    public function test_shows_an_honest_empty_state_without_fabricating_an_opportunity(): void
    {
        $this->signIn('IDN-OPP-EMPTY');
        $content = $this->get('/opportunites')->assertOk()->getContent();

        self::assertStringContainsString('Aucune opportunité pour le moment', $content);
    }

    public function test_respects_existing_consent_and_availability_gates(): void
    {
        $this->profile('IDN-OPP-PAUSED', ['availability_status' => PersonProfile::AVAILABILITY_PAUSED]);
        $this->capability('IDN-OPP-PAUSED', 'Soudure');
        $mission = $this->openMission('IDN-OPP-OWNER3', 'Réparer la charpente métallique');
        $this->requirement($mission, 'Soudure');

        $this->signIn('IDN-OPP-PAUSED');
        $content = $this->get('/opportunites')->assertOk()->getContent();

        self::assertStringNotContainsString('Réparer la charpente métallique', $content);
    }

    private function profile(string $reference, array $overrides = []): PersonProfile
    {
        return PersonProfile::query()->create(array_replace([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'availability_status' => PersonProfile::AVAILABILITY_OPEN,
            'participation_mode' => 'HYBRID',
            'city' => 'Abidjan',
        ], $overrides));
    }

    private function capability(string $reference, string $label): CapabilityStatement
    {
        return CapabilityStatement::query()->create([
            'core_identity_reference' => $reference,
            'kind' => CapabilityStatement::KIND_POSSESSED,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => true,
            'visibility' => CapabilityStatement::VISIBILITY_DISCOVERABLE,
        ]);
    }

    private function requirement(Mission $mission, string $label): void
    {
        $mission->capabilityRequirements()->create([
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'requirement_level' => 'REQUIRED',
            'quantity' => 1,
        ]);
    }

    private function openMission(string $owner, string $title): Mission
    {
        $this->activateProgram($owner);
        $project = app(ProjectService::class)->create($owner, $this->projectPayload(), (new ProjectConfiguration)->defaults());

        $workflow = app(MissionWorkflow::class);
        $mission = $workflow->create($owner, 'PROJECT', $project->public_reference, [
            'title' => $title,
            'description' => 'Une description suffisamment précise pour cette mission de test.',
            'visibility' => Mission::VISIBILITY_PUBLIC,
        ]);
        $mission = $workflow->propose($mission, $owner);

        return $workflow->officialize($mission, $owner, ['expected_result' => 'Résultat attendu de test.']);
    }

    private function projectPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier '.uniqid(),
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
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

    private function activateProgram(string $reference): void
    {
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists()) {
            return;
        }

        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
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
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
