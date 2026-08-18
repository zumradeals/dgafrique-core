<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Projects\ProjectTeamService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Couvre CAP-041 (équipe projet) : une adhésion réelle et distincte du masquage de suggestion
 * (ProjectMatchDecision), autorité strictement réutilisée depuis ProjectService::canView/canDecide.
 */
final class ProjectTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_to_join_stays_pending_until_the_project_owner_approves_it(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-APPLICANT');
        $project = $this->project('IDN-OWNER');
        $team = app(ProjectTeamService::class);

        $team->requestToJoin($project, 'IDN-APPLICANT', 'Je souhaite contribuer.');
        $request = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', 'IDN-APPLICANT')->sole();
        self::assertSame(ProjectTeamMember::STATUS_REQUESTED, $request->status);

        $team->approveRequest($project, 'IDN-OWNER', $request->id);
        self::assertSame(ProjectTeamMember::STATUS_ACTIVE, $request->refresh()->status);
        self::assertTrue(ProjectEvent::query()->where('project_id', $project->id)->where('event', 'TEAM_MEMBER_JOINED')->exists());
    }

    public function test_only_the_project_owner_can_approve_a_request_or_invite(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-APPLICANT');
        $this->member('IDN-OUTSIDER');
        $project = $this->project('IDN-OWNER');
        $team = app(ProjectTeamService::class);
        $team->requestToJoin($project, 'IDN-APPLICANT', null);
        $request = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', 'IDN-APPLICANT')->sole();

        $this->expectException(HttpException::class);
        $team->approveRequest($project, 'IDN-OUTSIDER', $request->id);
    }

    public function test_an_invitation_requires_acceptance_and_is_never_pre_activated(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-INVITED');
        $profile = PersonProfile::query()->create(['core_identity_reference' => 'IDN-INVITED', 'discovery_reference' => (string) Str::uuid(), 'discovery_display_name' => 'Personne invitée', 'discovery_consent' => true, 'discovery_consented_at' => now()]);
        $project = $this->project('IDN-OWNER');
        $team = app(ProjectTeamService::class);

        $team->invite($project, 'IDN-OWNER', $profile->core_identity_reference);
        $invitation = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', 'IDN-INVITED')->sole();
        self::assertSame(ProjectTeamMember::STATUS_INVITED, $invitation->status);

        $team->acceptInvitation($project, 'IDN-INVITED');
        self::assertSame(ProjectTeamMember::STATUS_ACTIVE, $invitation->refresh()->status);
    }

    public function test_inviting_a_person_without_discovery_consent_is_refused(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-PRIVATE');
        $profile = PersonProfile::query()->create(['core_identity_reference' => 'IDN-PRIVATE', 'discovery_reference' => (string) Str::uuid(), 'discovery_display_name' => 'Personne privée', 'discovery_consent' => false]);
        $project = $this->project('IDN-OWNER');

        session()->flush();
        $this->signIn('IDN-OWNER');
        $this->post(route('projects.team.invite', $project), ['person_reference' => $profile->discovery_reference])->assertNotFound();
        self::assertSame(0, ProjectTeamMember::query()->count());
    }

    public function test_a_duplicate_request_is_refused(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-APPLICANT');
        $project = $this->project('IDN-OWNER');
        $team = app(ProjectTeamService::class);
        $team->requestToJoin($project, 'IDN-APPLICANT', null);

        $this->expectException(HttpException::class);
        $team->requestToJoin($project, 'IDN-APPLICANT', null);
    }

    public function test_an_active_member_can_leave_and_the_owner_can_remove_with_a_reason(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-MEMBER-A');
        $this->member('IDN-MEMBER-B');
        $project = $this->project('IDN-OWNER');
        $team = app(ProjectTeamService::class);
        $team->requestToJoin($project, 'IDN-MEMBER-A', null);
        $requestA = ProjectTeamMember::query()->where('core_identity_reference', 'IDN-MEMBER-A')->sole();
        $team->approveRequest($project, 'IDN-OWNER', $requestA->id);
        $team->requestToJoin($project, 'IDN-MEMBER-B', null);
        $requestB = ProjectTeamMember::query()->where('core_identity_reference', 'IDN-MEMBER-B')->sole();
        $team->approveRequest($project, 'IDN-OWNER', $requestB->id);

        $team->leave($project, 'IDN-MEMBER-A');
        self::assertSame(ProjectTeamMember::STATUS_LEFT, $requestA->refresh()->status);

        $team->remove($project, 'IDN-OWNER', $requestB->id, 'Indisponibilité prolongée signalée par la personne elle-même.');
        self::assertSame(ProjectTeamMember::STATUS_REMOVED, $requestB->refresh()->status);
        self::assertSame('Indisponibilité prolongée signalée par la personne elle-même.', $requestB->decision_reason);
    }

    public function test_a_private_project_refuses_a_request_to_join_from_a_non_viewer(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-OUTSIDER');
        $project = $this->project('IDN-OWNER', ['visibility' => Project::VISIBILITY_PRIVATE]);

        $this->expectException(HttpException::class);
        app(ProjectTeamService::class)->requestToJoin($project, 'IDN-OUTSIDER', null);
    }

    public function test_the_team_section_shows_an_honest_empty_state_and_active_members(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-MEMBER');
        $profile = PersonProfile::query()->create(['core_identity_reference' => 'IDN-MEMBER', 'discovery_reference' => (string) Str::uuid(), 'discovery_display_name' => 'Aïcha K.', 'discovery_consent' => true, 'discovery_consented_at' => now()]);
        $project = $this->project('IDN-OWNER');

        session()->flush();
        $this->signIn('IDN-OWNER');
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Aucune personne n’a encore rejoint l’équipe.');

        app(ProjectTeamService::class)->invite($project, 'IDN-OWNER', $profile->core_identity_reference);
        app(ProjectTeamService::class)->acceptInvitation($project, 'IDN-MEMBER');

        $this->get(route('projects.show', $project))->assertOk()->assertSee('Aïcha K.')->assertDontSee('Aucune personne n’a encore rejoint l’équipe.');
    }

    private function project(string $owner, array $overrides = []): Project
    {
        return app(ProjectService::class)->create($owner, array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire', 'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes", 'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet", 'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote", 'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides), (new ProjectConfiguration)->defaults());
    }

    private function member(string $id): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->create(['core_identity_reference' => $id, 'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    /**
     * Les closures lisent l'identité réellement demandée dans chaque requête : un test qui se
     * connecte successivement sous plusieurs identités verrait sinon la toute première réponse
     * enregistrée gagner pour toujours (Http::fake empile les callbacks, il ne les remplace pas).
     */
    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => function ($request) {
                $entite = $request['identifiant'] ?? $request['entite'];

                return Http::response(['jeton' => 'bearer-'.$entite, 'entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201);
            },
            'core.test/api/v1/identites/*' => function ($request) {
                $entite = Str::afterLast($request->url(), '/');

                return Http::response(['reference' => $entite, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            },
            'core.test/api/v1/sessions/current' => function ($request) {
                $entite = Str::after((string) $request->header('Authorization')[0], 'bearer-');

                return Http::response(['entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']);
            },
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
