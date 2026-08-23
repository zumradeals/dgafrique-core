<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Projects\ProjectSignalsEngine;
use App\Application\Zumra\ZumraGroupService;
use App\Models\ContextComment;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre CAP-044 : chaque signal doit être une phrase factuelle attachée à un objet métier réel,
 * jamais un score ni un pourcentage, et ne doit jamais écrire sur Project.maturity.
 */
final class ProjectSignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_milestones_signal_reflects_the_real_completed_count(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $project->milestones()->first()->update(['status' => 'COMPLETED']);

        $signals = app(ProjectSignalsEngine::class)->forProject($project);

        self::assertContains('1 jalon(s) réalisé(s) sur 3.', $signals);
    }

    public function test_team_signal_appears_only_when_a_member_is_really_active(): void
    {
        $this->member('IDN-OWNER');
        $this->member('IDN-MEMBER');
        $project = $this->project('IDN-OWNER');

        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertFalse($this->containsSubstring($signals, 'membre(s) actif'));

        ProjectTeamMember::query()->create(['project_id' => $project->id, 'core_identity_reference' => 'IDN-MEMBER', 'status' => ProjectTeamMember::STATUS_ACTIVE, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST, 'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now()]);
        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertContains('1 membre(s) actif(s) dans l’équipe.', $signals);
    }

    public function test_needs_signal_reflects_an_honest_resolved_over_total_ratio(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        $need = app(NeedService::class)->create('IDN-OWNER', $this->needPayload($project), (new NeedConfiguration)->defaults());

        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertContains('0 besoin(s) résolu(s) sur 1 exprimés.', $signals);

        app(NeedService::class)->transition($need, 'IDN-OWNER', Need::STATUS_IN_PROGRESS);
        app(NeedService::class)->transition($need->refresh(), 'IDN-OWNER', Need::STATUS_RESOLVED, 'Le besoin a été couvert par un membre de l’équipe.');
        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertContains('1 besoin(s) résolu(s) sur 1 exprimés.', $signals);
    }

    public function test_contribution_signal_appears_only_when_a_real_comment_exists(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertFalse($this->containsSubstring($signals, 'contribution'));

        ContextComment::query()->create(['public_reference' => (string) Str::uuid(), 'context_type' => ContextComment::CONTEXT_PROJECT, 'context_reference' => $project->id, 'author_core_reference' => 'IDN-OWNER', 'purpose' => 'PRECISION', 'body' => 'Une précision utile sur ce projet.', 'posted_at' => now()]);
        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertContains('1 contribution(s) déposée(s).', $signals);
    }

    public function test_last_activity_signal_reflects_the_real_project_creation_event(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');

        $signals = app(ProjectSignalsEngine::class)->forProject($project);
        self::assertContains('Dernière activité observée le '.now()->format('d/m/Y').'.', $signals);
    }

    public function test_signals_never_write_to_project_maturity(): void
    {
        $this->member('IDN-OWNER');
        $project = $this->project('IDN-OWNER');
        self::assertSame('IDEA', $project->maturity);

        app(ProjectSignalsEngine::class)->forProject($project);

        self::assertSame('IDEA', $project->refresh()->maturity);
    }

    private function containsSubstring(array $signals, string $needle): bool
    {
        foreach ($signals as $signal) {
            if (str_contains($signal, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function needPayload(Project $project): array
    {
        return ['owner_type' => Need::OWNER_PROJECT, 'project_reference' => $project->public_reference, 'group_reference' => null, 'title' => 'Un besoin réel exprimé par le projet', 'context' => 'Le projet manque d’un accompagnement précis pour avancer sur ce point concret.', 'category' => 'SKILL', 'collaboration_mode' => 'HYBRID', 'visibility' => Need::VISIBILITY_PUBLIC];
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

    private function project(string $owner): Project
    {
        return app(ProjectService::class)->create($owner, [
            'owner_type' => 'PERSON', 'group_reference' => null, 'zumra_group_reference' => $this->zumraFor($owner), 'source_need_reference' => null,
            'name' => 'Atelier numérique communautaire', 'summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services numériques utiles.',
            'problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre, expérimenter et transformer leurs acquis en activités utiles.',
            'proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs, exercices réels et accompagnement vers des premiers services.',
            'beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire trois services pilotes", 'required_capabilities' => "Formation numérique\nGestion de projet",
            'required_resources' => "Ordinateurs\nConnexion internet", 'risks' => "Disponibilité irrégulière\nAccès au matériel",
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote", 'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], (new ProjectConfiguration)->defaults());
    }

    private function member(string $id): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->create(['core_identity_reference' => $id, 'status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }
}
