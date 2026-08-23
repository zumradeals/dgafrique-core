<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ProjectBrainConversation;
use App\Models\ProjectBrainDraft;
use App\Models\ProjectBrainMessage;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cerveau du Projet — refonte visuelle V1 (PVB-I05, docs/design/DESIGN-INVARIANTS.md §20).
 * La refonte est purement visuelle : ces tests protègent le contrat métier existant
 * (conversation réelle, brouillon nécessitant confirmation explicite, aucune mutation Core
 * silencieuse) qui n'était couvert par aucun test avant ce chantier.
 */
final class ProjectBrainTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_outsider_cannot_view_a_private_projects_brain(): void
    {
        $this->member('IDN-BRAIN-OWNER');
        $project = $this->project('IDN-BRAIN-OWNER', ['visibility' => 'PRIVATE']);
        $this->signIn('IDN-BRAIN-OUTSIDER');

        $this->get(route('projects.brain.show', $project))->assertNotFound();
    }

    public function test_the_brain_shares_the_real_global_navigation(): void
    {
        $this->member('IDN-BRAIN-NAV');
        $project = $this->project('IDN-BRAIN-NAV');
        $this->signIn('IDN-BRAIN-NAV');

        $this->get(route('projects.brain.show', $project))
            ->assertOk()
            ->assertSeeInOrder(['Fil', 'Mon espace', 'Personnes', 'Besoins', 'Projets', 'ZUMRA'])
            ->assertSee('dg-tabbar', false)
            ->assertSee(route('projects.show', $project), false);
    }

    public function test_real_conversation_messages_and_a_pending_draft_render_with_their_real_confirm_and_cancel_routes(): void
    {
        $this->member('IDN-BRAIN-CHAT');
        $project = $this->project('IDN-BRAIN-CHAT');
        $conversation = ProjectBrainConversation::query()->create(['project_id' => $project->id, 'actor_core_reference' => 'IDN-BRAIN-CHAT', 'title' => 'Cerveau — '.$project->name]);
        ProjectBrainMessage::query()->create(['conversation_id' => $conversation->id, 'role' => ProjectBrainMessage::ROLE_USER, 'content' => 'Un message réel envoyé par le porteur.']);
        $draft = ProjectBrainDraft::query()->create([
            'conversation_id' => $conversation->id, 'project_id' => $project->id, 'actor_core_reference' => 'IDN-BRAIN-CHAT',
            'kind' => ProjectBrainDraft::KIND_NEED_CREATE, 'status' => ProjectBrainDraft::STATUS_PENDING,
            'payload' => ['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference, 'title' => 'Local pour formation', 'context' => 'Un contexte suffisamment détaillé pour ce brouillon de besoin.', 'category' => 'MATERIAL', 'collaboration_mode' => 'LOCAL', 'visibility' => Need::VISIBILITY_PRIVATE],
        ]);
        ProjectBrainMessage::query()->create(['conversation_id' => $conversation->id, 'role' => ProjectBrainMessage::ROLE_ASSISTANT, 'content' => 'Voici une proposition de besoin.', 'meta' => ['draft_reference' => $draft->id]]);
        $this->signIn('IDN-BRAIN-CHAT');

        $content = $this->get(route('projects.brain.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('Un message réel envoyé par le porteur.', $content);
        self::assertStringContainsString('Voici une proposition de besoin.', $content);
        self::assertStringContainsString('Local pour formation', $content);
        self::assertStringContainsString(route('projects.brain.needs.confirm', [$project, $draft]), $content);
        self::assertStringContainsString(route('projects.brain.drafts.cancel', [$project, $draft]), $content);

        // Aucune mutation Core silencieuse : un simple GET ne confirme jamais le brouillon.
        self::assertSame(ProjectBrainDraft::STATUS_PENDING, $draft->fresh()->status);
        self::assertSame(0, Need::query()->count());
    }

    public function test_the_illustrative_pending_action_example_is_visually_disabled_not_a_real_mutation(): void
    {
        $this->member('IDN-BRAIN-EMPTY');
        $project = $this->project('IDN-BRAIN-EMPTY');
        $this->signIn('IDN-BRAIN-EMPTY');

        $content = $this->get(route('projects.brain.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('Créer une équipe projet (minimum 3 personnes)', $content);
        self::assertMatchesRegularExpression(
            '/<span[^>]*class="dg-btn dg-btn--project"[^>]*aria-disabled="true"[^>]*>Valider<\/span>/',
            $content
        );
    }

    public function test_real_needs_and_team_members_render_honestly_when_present_and_empty_when_absent(): void
    {
        $this->member('IDN-BRAIN-DATA');
        $this->member('IDN-BRAIN-TEAM');
        $project = $this->project('IDN-BRAIN-DATA');
        PersonProfile::query()->create(['core_identity_reference' => 'IDN-BRAIN-TEAM', 'discovery_display_name' => 'Aïcha K.', 'discovery_consent' => true, 'orientation_consent' => true]);
        ProjectTeamMember::query()->create([
            'project_id' => $project->id, 'core_identity_reference' => 'IDN-BRAIN-TEAM',
            'status' => ProjectTeamMember::STATUS_ACTIVE, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST,
            'initiated_by_core_reference' => 'IDN-BRAIN-TEAM', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        $this->signIn('IDN-BRAIN-DATA');

        $content = $this->get(route('projects.brain.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('Aucun besoin exprimé pour ce projet pour le moment.', $content);
        self::assertStringContainsString('Équipe (1)', $content);
        self::assertStringNotContainsString('Aucune personne n’a encore rejoint l’équipe.', $content);
    }

    public function test_the_left_sidebar_lists_real_accessible_projects_not_a_fabricated_list(): void
    {
        $this->member('IDN-BRAIN-MULTI');
        $projectOne = $this->project('IDN-BRAIN-MULTI', ['name' => 'Premier projet réel']);
        $projectTwo = $this->project('IDN-BRAIN-MULTI', ['name' => 'Second projet réel']);
        $this->signIn('IDN-BRAIN-MULTI');

        $content = $this->get(route('projects.brain.show', $projectOne))->assertOk()->getContent();

        self::assertStringContainsString('Premier projet réel', $content);
        self::assertStringContainsString('Second projet réel', $content);
        self::assertStringContainsString(route('projects.brain.show', $projectTwo), $content);
    }

    public function test_mobile_drawers_and_desktop_columns_are_both_present_in_the_markup(): void
    {
        $this->member('IDN-BRAIN-RESPONSIVE');
        $project = $this->project('IDN-BRAIN-RESPONSIVE');
        $this->signIn('IDN-BRAIN-RESPONSIVE');

        $content = $this->get(route('projects.brain.show', $project))->assertOk()->getContent();

        self::assertStringContainsString('dg-brain-sidebar--desktop', $content);
        self::assertStringContainsString('dg-brain-aside--desktop', $content);
        self::assertStringContainsString('dg-brain-drawer--sidebar', $content);
        self::assertStringContainsString('dg-brain-drawer--aside', $content);
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

    private function project(string $owner, array $overrides = []): Project
    {
        return app(ProjectService::class)->create($owner, array_replace([
            'owner_type' => 'PERSON',
            'group_reference' => null,
            'zumra_group_reference' => $this->zumraFor($owner),
            'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire',
            'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes",
            'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet",
            'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => 'PUBLIC',
        ], $overrides), (new ProjectConfiguration)->defaults());
    }

    private function member(string $reference): void
    {
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
