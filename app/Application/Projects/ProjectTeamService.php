<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectTeamMember;
use Illuminate\Support\Facades\DB;

/**
 * CAP-041 — équipe projet : adhésion réelle, distincte du masquage de suggestion géré par
 * ProjectMatchDecision. Réutilise strictement ProjectService::canView/canDecide, aucune nouvelle
 * autorité fabriquée ici.
 */
final class ProjectTeamService
{
    public function __construct(private readonly ProjectService $projects) {}

    public function requestToJoin(Project $project, string $actor, ?string $motivation): void
    {
        abort_unless($this->projects->canView($project, $actor), 404);
        abort_if($this->isOwnerOrInitiator($project, $actor), 409, 'Vous portez déjà ce projet.');

        DB::transaction(function () use ($project, $actor, $motivation): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $actor)->lockForUpdate()->first();
            abort_if($member !== null && in_array($member->status, [ProjectTeamMember::STATUS_ACTIVE, ProjectTeamMember::STATUS_REQUESTED, ProjectTeamMember::STATUS_INVITED], true), 409, 'Une demande ou une adhésion existe déjà pour ce projet.');
            $member ??= new ProjectTeamMember(['project_id' => $project->id, 'core_identity_reference' => $actor]);
            $member->fill(['status' => ProjectTeamMember::STATUS_REQUESTED, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_REQUEST, 'initiated_by_core_reference' => $actor, 'motivation' => $motivation, 'requested_at' => now(), 'invited_at' => null, 'left_at' => null])->save();
            $this->event($project, 'TEAM_MEMBER_REQUESTED', $actor);
        });
    }

    public function invite(Project $project, string $actor, string $subject): void
    {
        $this->assertDecision($project, $actor);
        abort_if($actor === $subject || $this->isOwnerOrInitiator($project, $subject), 422, 'Cette personne porte déjà ce projet.');

        DB::transaction(function () use ($project, $actor, $subject): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $subject)->lockForUpdate()->first();
            abort_if($member?->status === ProjectTeamMember::STATUS_ACTIVE, 409, 'Cette personne fait déjà partie de l’équipe.');
            $member ??= new ProjectTeamMember(['project_id' => $project->id, 'core_identity_reference' => $subject]);
            $member->fill(['status' => ProjectTeamMember::STATUS_INVITED, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_INVITATION, 'initiated_by_core_reference' => $actor, 'invited_at' => now(), 'requested_at' => null, 'left_at' => null])->save();
            $this->event($project, 'TEAM_MEMBER_INVITED', $actor);
        });
    }

    public function acceptInvitation(Project $project, string $actor): void
    {
        DB::transaction(function () use ($project, $actor): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $actor)->lockForUpdate()->firstOrFail();
            abort_unless($member->status === ProjectTeamMember::STATUS_INVITED, 409, 'Aucune invitation active.');
            $member->update(['status' => ProjectTeamMember::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->event($project, 'TEAM_MEMBER_JOINED', $actor);
        });
    }

    public function approveRequest(Project $project, string $actor, string $teamMemberId): void
    {
        $this->assertDecision($project, $actor);
        DB::transaction(function () use ($project, $actor, $teamMemberId): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->whereKey($teamMemberId)->lockForUpdate()->firstOrFail();
            abort_unless($member->status === ProjectTeamMember::STATUS_REQUESTED, 409, 'Cette demande n’est plus en attente.');
            $member->update(['status' => ProjectTeamMember::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->event($project, 'TEAM_MEMBER_JOINED', $actor);
        });
    }

    public function leave(Project $project, string $actor): void
    {
        DB::transaction(function () use ($project, $actor): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $actor)->where('status', ProjectTeamMember::STATUS_ACTIVE)->lockForUpdate()->firstOrFail();
            $member->update(['status' => ProjectTeamMember::STATUS_LEFT, 'left_at' => now()]);
            $this->event($project, 'TEAM_MEMBER_LEFT', $actor);
        });
    }

    public function remove(Project $project, string $actor, string $teamMemberId, string $reason): void
    {
        $this->assertDecision($project, $actor);
        DB::transaction(function () use ($project, $actor, $teamMemberId, $reason): void {
            $member = ProjectTeamMember::query()->where('project_id', $project->id)->whereKey($teamMemberId)->lockForUpdate()->firstOrFail();
            abort_if(in_array($member->status, [ProjectTeamMember::STATUS_LEFT, ProjectTeamMember::STATUS_REMOVED], true), 409, 'Cette personne n’est déjà plus dans l’équipe.');
            $member->update(['status' => ProjectTeamMember::STATUS_REMOVED, 'decision_reason' => $reason, 'left_at' => now()]);
            $this->event($project, 'TEAM_MEMBER_REMOVED', $actor, ['reason' => $reason]);
        });
    }

    private function isOwnerOrInitiator(Project $project, string $actor): bool
    {
        return $project->initiator_core_reference === $actor || ($project->owner_type === Project::OWNER_PERSON && $project->owner_reference === $actor);
    }

    private function assertDecision(Project $project, string $actor): void
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
    }

    private function event(Project $project, string $event, string $actor, array $context = []): void
    {
        ProjectEvent::query()->create(['project_id' => $project->id, 'event' => $event, 'actor_core_reference' => $actor, 'context' => $context, 'occurred_at' => now()]);
    }
}
