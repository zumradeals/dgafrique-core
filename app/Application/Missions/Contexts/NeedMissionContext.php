<?php

declare(strict_types=1);

namespace App\Application\Missions\Contexts;

use App\Application\Needs\NeedService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\ZumraProgramMembership;

final class NeedMissionContext implements MissionContextAdapter
{
    public function __construct(private readonly NeedService $needs) {}

    public function type(): string
    {
        return 'NEED';
    }

    public function resolve(string $reference): object
    {
        return Need::query()->where('public_reference', $reference)->firstOrFail();
    }

    public function canView(object $context, string $actor): bool
    {
        return $this->needs->canView($context, $actor);
    }

    public function canPropose(object $context, string $actor): bool
    {
        return $this->hasActiveProgramMembership($actor)
            && $this->canView($context, $actor)
            && $this->isOperational($context);
    }

    public function canOfficialize(object $context, string $actor): bool
    {
        return $this->needs->canDecide($context, $actor);
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
        return $context->status !== Need::STATUS_ARCHIVED;
    }

    public function maxVisibility(object $context, string $actor): string
    {
        return match ($context->visibility) {
            Need::VISIBILITY_PRIVATE => Mission::VISIBILITY_PRIVATE,
            Need::VISIBILITY_GROUP => Mission::VISIBILITY_CONTEXT,
            Need::VISIBILITY_PROGRAM => Mission::VISIBILITY_PROGRAM,
            Need::VISIBILITY_PUBLIC => Mission::VISIBILITY_PUBLIC,
            default => Mission::VISIBILITY_PRIVATE,
        };
    }

    public function label(object $context): string
    {
        return $context->title;
    }

    public function reference(object $context): string
    {
        return $context->public_reference;
    }

    private function hasActiveProgramMembership(string $actor): bool
    {
        return ZumraProgramMembership::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraProgramMembership::STATUS_ACTIVE)
            ->exists();
    }
}
