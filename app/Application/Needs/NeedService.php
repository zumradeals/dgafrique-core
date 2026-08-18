<?php

declare(strict_types=1);

namespace App\Application\Needs;

use App\Application\Projects\ProjectAuthority;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NeedService
{
    public function __construct(private readonly ZumraGroupService $groups, private readonly ProjectAuthority $projects) {}

    public function create(string $actor, array $data, array $configuration): Need
    {
        $ownerType = $data['owner_type'];
        $ownerReference = $actor;
        $status = Need::STATUS_OPEN;

        if ($ownerType === Need::OWNER_GROUP) {
            $group = ZumraGroup::query()->where('public_reference', $data['group_reference'])->firstOrFail();
            $this->assertActiveGroupMember($group, $actor);
            $ownerReference = $group->id;
            $status = $this->groups->isLeader($group, $actor) ? Need::STATUS_OPEN : Need::STATUS_PROPOSED;
        }

        if ($ownerType === Need::OWNER_PROJECT) {
            $project = Project::query()->where('public_reference', $data['project_reference'])->firstOrFail();
            $this->assertEligibleProjectContributor($project, $actor);
            $ownerReference = $project->id;
            $status = $this->projects->canDecide($project, $actor) ? Need::STATUS_OPEN : Need::STATUS_PROPOSED;
        }

        $limit = $ownerType === Need::OWNER_PERSON ? (int) $configuration['max_open_personal_needs'] : (int) $configuration['max_open_group_needs'];
        $openCount = Need::query()->where('owner_type', $ownerType)->where('owner_reference', $ownerReference)->whereIn('status', [Need::STATUS_PROPOSED, Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS])->count();
        abort_if($openCount >= $limit, 409, 'Le nombre maximal de besoins actifs est atteint.');

        return DB::transaction(function () use ($actor, $data, $ownerType, $ownerReference, $status): Need {
            $need = Need::query()->create([
                'public_reference' => (string) Str::uuid(),
                'owner_type' => $ownerType,
                'owner_reference' => $ownerReference,
                'author_core_reference' => $actor,
                'title' => $data['title'],
                'context' => $data['context'],
                'category' => $data['category'],
                'capability_label' => $data['capability_label'] ?? null,
                'collaboration_mode' => $data['collaboration_mode'],
                'location' => $data['location'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'visibility' => in_array($ownerType, [Need::OWNER_PERSON, Need::OWNER_PROJECT], true) && $data['visibility'] === Need::VISIBILITY_GROUP ? Need::VISIBILITY_PRIVATE : $data['visibility'],
                'status' => $status,
                'decided_by_core_reference' => $status === Need::STATUS_OPEN ? $actor : null,
                'published_at' => $status === Need::STATUS_OPEN ? now() : null,
            ]);
            $this->event($need, $status === Need::STATUS_OPEN ? 'NEED_PUBLISHED' : 'NEED_PROPOSED', $actor, null, $status);

            return $need;
        });
    }

    public function transition(Need $need, string $actor, string $target, ?string $resolutionNote = null): void
    {
        $this->assertDecisionRight($need, $actor);
        $allowed = match ($target) {
            Need::STATUS_OPEN => [Need::STATUS_PROPOSED],
            Need::STATUS_IN_PROGRESS => [Need::STATUS_OPEN],
            Need::STATUS_RESOLVED => [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS],
            Need::STATUS_ARCHIVED => [Need::STATUS_PROPOSED, Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS, Need::STATUS_RESOLVED],
            default => [],
        };
        abort_unless(in_array($need->status, $allowed, true), 409, 'Cette transition de besoin n’est pas permise.');
        abort_if($target === Need::STATUS_RESOLVED && mb_strlen(trim((string) $resolutionNote)) < 10, 422, 'Une note de résolution est requise.');

        DB::transaction(function () use ($need, $actor, $target, $resolutionNote): void {
            $from = $need->status;
            $need->update([
                'status' => $target,
                'decided_by_core_reference' => $actor,
                'published_at' => $target === Need::STATUS_OPEN ? now() : $need->published_at,
                'resolution_note' => $target === Need::STATUS_RESOLVED ? trim((string) $resolutionNote) : $need->resolution_note,
                'resolved_at' => $target === Need::STATUS_RESOLVED ? now() : $need->resolved_at,
                'archived_at' => $target === Need::STATUS_ARCHIVED ? now() : null,
            ]);
            $event = match ($target) {
                Need::STATUS_OPEN => 'NEED_PUBLISHED',
                Need::STATUS_IN_PROGRESS => 'NEED_STARTED',
                Need::STATUS_RESOLVED => 'NEED_RESOLVED',
                default => 'NEED_ARCHIVED',
            };
            $this->event($need, $event, $actor, $from, $target);
        });
    }

    public function canView(Need $need, string $actor): bool
    {
        if ($need->author_core_reference === $actor || ($need->owner_type === Need::OWNER_PERSON && $need->owner_reference === $actor)) {
            return true;
        }
        if ($need->owner_type === Need::OWNER_GROUP && $this->isActiveGroupMember($need->owner_reference, $actor)) {
            return true;
        }
        if ($need->owner_type === Need::OWNER_PROJECT) {
            $project = Project::query()->find($need->owner_reference);
            if ($project !== null && $this->isEligibleProjectContributor($project, $actor)) {
                return true;
            }
        }
        if ($need->status === Need::STATUS_PROPOSED || $need->status === Need::STATUS_ARCHIVED || $need->visibility === Need::VISIBILITY_PRIVATE) {
            return false;
        }
        if ($need->visibility === Need::VISIBILITY_PUBLIC) {
            return true;
        }
        if ($need->visibility === Need::VISIBILITY_PROGRAM) {
            return ZumraProgramMembership::query()->where('core_identity_reference', $actor)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists();
        }

        return false;
    }

    public function canDecide(Need $need, string $actor): bool
    {
        if ($need->owner_type === Need::OWNER_PERSON) {
            return hash_equals($need->owner_reference, $actor);
        }

        if ($need->owner_type === Need::OWNER_PROJECT) {
            $project = Project::query()->find($need->owner_reference);
            return $project !== null && $this->projects->canDecide($project, $actor);
        }

        $group = ZumraGroup::query()->find($need->owner_reference);
        return $group !== null && $this->groups->isLeader($group, $actor);
    }

    private function assertDecisionRight(Need $need, string $actor): void
    {
        abort_unless($this->canDecide($need, $actor), 403);
    }

    private function assertActiveGroupMember(ZumraGroup $group, string $actor): void
    {
        abort_unless($this->isActiveGroupMember($group->id, $actor), 403);
    }

    private function isActiveGroupMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()->where('zumra_group_id', $groupId)->where('core_identity_reference', $actor)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->exists();
    }

    private function assertEligibleProjectContributor(Project $project, string $actor): void
    {
        abort_unless($this->isEligibleProjectContributor($project, $actor), 403);
    }

    private function isEligibleProjectContributor(Project $project, string $actor): bool
    {
        if ($project->initiator_core_reference === $actor || ($project->owner_type === Project::OWNER_PERSON && $project->owner_reference === $actor)) {
            return true;
        }

        return ProjectTeamMember::query()->where('project_id', $project->id)->where('core_identity_reference', $actor)->where('status', ProjectTeamMember::STATUS_ACTIVE)->exists();
    }

    private function event(Need $need, string $event, string $actor, ?string $from, string $to): void
    {
        NeedEvent::query()->create(['need_id' => $need->id, 'event' => $event, 'actor_core_reference' => $actor, 'from_status' => $from, 'to_status' => $to, 'context' => [], 'occurred_at' => now()]);
    }
}
