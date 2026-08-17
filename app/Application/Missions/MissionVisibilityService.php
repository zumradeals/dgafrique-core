<?php

declare(strict_types=1);

namespace App\Application\Missions;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\PersonProfile;
use App\Models\ZumraProgramMembership;

/**
 * L'accès final à une Mission est toujours l'intersection :
 *   parent canView(actor) AND mission visibility allows(actor)
 *
 * La visibilité d'une Mission n'implique jamais la publicité des identités de ses
 * exécutants : canViewMission() et canExposeParticipantIdentity() sont deux questions
 * distinctes (v0.5 §4.4).
 */
final class MissionVisibilityService
{
    public function __construct(private readonly MissionContextRegistry $registry) {}

    public function canViewMission(Mission $mission, string $actor): bool
    {
        $adapter = $this->registry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        if (! $adapter->canView($context, $actor)) {
            return false;
        }

        return match ($mission->visibility) {
            Mission::VISIBILITY_PRIVATE => $this->canViewPrivate($mission, $actor, $adapter, $context),
            Mission::VISIBILITY_CONTEXT => true,
            Mission::VISIBILITY_PROGRAM => $this->hasActiveProgramMembership($actor),
            Mission::VISIBILITY_PUBLIC => true,
            default => false,
        };
    }

    public function canExposeParticipantIdentity(Mission $mission, string $viewer, MissionAssignment $assignment): bool
    {
        if (! $this->canViewMission($mission, $viewer)) {
            return false;
        }
        if (hash_equals($assignment->core_identity_reference, $viewer)) {
            return true;
        }

        return PersonProfile::query()
            ->where('core_identity_reference', $assignment->core_identity_reference)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->exists();
    }

    /**
     * Plafond de visibilité autorisé pour ce contexte : une Mission ne peut jamais être
     * plus visible que son contexte (PRIVATE < CONTEXT < PROGRAM < PUBLIC).
     */
    public function maxVisibility(string $contextType, string $contextReference, string $actor): string
    {
        $adapter = $this->registry->for($contextType);
        $context = $adapter->resolve($contextReference);

        return $adapter->maxVisibility($context, $actor);
    }

    public function assertVisibilityAllowed(string $requested, string $maxAllowed): void
    {
        abort_unless(
            Mission::VISIBILITY_ORDER[$requested] <= Mission::VISIBILITY_ORDER[$maxAllowed],
            422,
            'La visibilité demandée dépasse ce que le contexte autorise.'
        );
    }

    private function canViewPrivate(Mission $mission, string $actor, $adapter, object $context): bool
    {
        if (hash_equals($mission->created_by_core_reference, $actor)) {
            return true;
        }
        if ($adapter->canOfficialize($context, $actor)) {
            return true;
        }

        return MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('core_identity_reference', $actor)
            ->whereIn('status', MissionAssignment::CURRENT_STATUSES)
            ->exists();
    }

    private function hasActiveProgramMembership(string $actor): bool
    {
        return ZumraProgramMembership::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraProgramMembership::STATUS_ACTIVE)
            ->exists();
    }
}
