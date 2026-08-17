<?php

declare(strict_types=1);

namespace App\Application\Missions\Contexts;

use App\Application\Projects\ProjectService;
use App\Models\Mission;
use App\Models\Project;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;

final class ProjectMissionContext implements MissionContextAdapter
{
    public function __construct(private readonly ProjectService $projects) {}

    public function type(): string
    {
        return 'PROJECT';
    }

    public function resolve(string $reference): object
    {
        return Project::query()->where('public_reference', $reference)->firstOrFail();
    }

    public function canView(object $context, string $actor): bool
    {
        return $this->projects->canView($context, $actor);
    }

    public function canPropose(object $context, string $actor): bool
    {
        // v0.5 §2.1 : la visibilité publique d'un Projet ne donne pas à un simple visiteur
        // non membre actif du Programme ZUMRA le droit de proposer une Mission.
        return $this->hasActiveProgramMembership($actor)
            && $this->canView($context, $actor)
            && $this->isOperational($context);
    }

    public function canOfficialize(object $context, string $actor): bool
    {
        return $this->projects->canDecide($context, $actor);
    }

    public function canManageAssignments(object $context, string $actor): bool
    {
        return $this->canOfficialize($context, $actor);
    }

    public function canValidate(object $context, string $actor): bool
    {
        return $this->canOfficialize($context, $actor);
    }

    public function canCancel(object $context, string $actor): bool
    {
        return $this->canOfficialize($context, $actor);
    }

    public function canReopen(object $context, string $actor): bool
    {
        return $this->canOfficialize($context, $actor);
    }

    public function isOperational(object $context): bool
    {
        return $context->status !== Project::STATUS_ARCHIVED;
    }

    public function maxVisibility(object $context, string $actor): string
    {
        return match ($context->visibility) {
            Project::VISIBILITY_PRIVATE => Mission::VISIBILITY_PRIVATE,
            Project::VISIBILITY_GROUP => Mission::VISIBILITY_CONTEXT,
            Project::VISIBILITY_PROGRAM => Mission::VISIBILITY_PROGRAM,
            Project::VISIBILITY_PUBLIC => Mission::VISIBILITY_PUBLIC,
            default => Mission::VISIBILITY_PRIVATE,
        };
    }

    public function label(object $context): string
    {
        return $context->name;
    }

    public function reference(object $context): string
    {
        return $context->public_reference;
    }

    public function authorityContextReferences(string $actor): array
    {
        $ledGroupIds = ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');

        return Project::query()
            ->where(function ($query) use ($actor, $ledGroupIds): void {
                $query->where(fn ($q) => $q->where('owner_type', Project::OWNER_PERSON)->where('owner_reference', $actor))
                    ->orWhere(fn ($q) => $q->where('owner_type', Project::OWNER_GROUP)->whereIn('owner_reference', $ledGroupIds));
            })
            ->pluck('public_reference')
            ->all();
    }

    private function hasActiveProgramMembership(string $actor): bool
    {
        return ZumraProgramMembership::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraProgramMembership::STATUS_ACTIVE)
            ->exists();
    }
}
