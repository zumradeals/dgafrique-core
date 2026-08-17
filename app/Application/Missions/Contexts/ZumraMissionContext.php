<?php

declare(strict_types=1);

namespace App\Application\Missions\Contexts;

use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;

final class ZumraMissionContext implements MissionContextAdapter
{
    public function __construct(private readonly ZumraGroupService $groups) {}

    public function type(): string
    {
        return 'ZUMRA';
    }

    public function resolve(string $reference): object
    {
        return ZumraGroup::query()->where('public_reference', $reference)->firstOrFail();
    }

    public function canView(object $context, string $actor): bool
    {
        return $this->isActiveMember($context, $actor) || $this->groups->isLeader($context, $actor);
    }

    public function canPropose(object $context, string $actor): bool
    {
        // v0.5 §2.1 : membership de groupe ACTIVE AND groupe non SUSPENDED.
        return $this->isActiveMember($context, $actor) && $this->isOperational($context);
    }

    public function canOfficialize(object $context, string $actor): bool
    {
        return $this->groups->isLeader($context, $actor);
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
        return $context->state !== ZumraGroup::STATE_SUSPENDED;
    }

    public function maxVisibility(object $context, string $actor): string
    {
        // v0.5 §4.2 : le plafond Mission ZUMRA reste CONTEXT dans ce premier adaptateur —
        // le moteur ne fabrique pas une visibilité publique ZUMRA inexistante dans le métier.
        return Mission::VISIBILITY_CONTEXT;
    }

    public function label(object $context): string
    {
        return $context->name;
    }

    public function reference(object $context): string
    {
        return $context->public_reference;
    }

    private function isActiveMember(ZumraGroup $group, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->exists();
    }
}
