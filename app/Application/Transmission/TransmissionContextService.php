<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Missions\MissionVisibilityService;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\Transmission;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Support\Str;

/**
 * Contrat de contexte Transmission (fiche §9) : contrairement à MissionContextRegistry, ce
 * n'est jamais un registre fail-closed d'autorité obligatoire — une Transmission reste
 * valide sans aucun contexte. Un rattachement PROJECT/ZUMRA/MISSION ajoute seulement une
 * visibilité contextuelle et une officialisation possible ; NEED reste une référence de
 * visibilité sans étape d'officialisation (Besoin n'a pas d'autorité contextuelle dédiée
 * dans ce référentiel — décision explicite de la fiche, pas un oubli).
 */
final class TransmissionContextService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly ZumraGroupService $zumraGroups,
        private readonly MissionContextRegistry $missionRegistry,
        private readonly MissionVisibilityService $missionVisibility,
    ) {}

    public function resolve(string $type, string $reference): object
    {
        return match ($type) {
            Transmission::CONTEXT_NEED => Need::query()->where('public_reference', $reference)->firstOrFail(),
            Transmission::CONTEXT_PROJECT => Project::query()->where('public_reference', $reference)->firstOrFail(),
            Transmission::CONTEXT_ZUMRA => ZumraGroup::query()->where('public_reference', $reference)->firstOrFail(),
            Transmission::CONTEXT_MISSION => Mission::query()->where('public_reference', $reference)->firstOrFail(),
            default => abort(422, 'Ce contexte de Transmission n’est pas pris en charge.'),
        };
    }

    public function canView(string $type, object $context, string $actor): bool
    {
        return match ($type) {
            Transmission::CONTEXT_NEED => $this->needs->canView($context, $actor),
            Transmission::CONTEXT_PROJECT => $this->projects->canView($context, $actor),
            Transmission::CONTEXT_ZUMRA => $this->isActiveZumraMember($context, $actor) || $this->zumraGroups->isLeader($context, $actor),
            Transmission::CONTEXT_MISSION => $this->missionVisibility->canViewMission($context, $actor),
            default => false,
        };
    }

    /** Officialisation contextuelle (fiche §5.A) : jamais disponible pour NEED. */
    public function canOfficialize(string $type, object $context, string $actor): bool
    {
        return match ($type) {
            Transmission::CONTEXT_PROJECT => $this->projects->canDecide($context, $actor),
            Transmission::CONTEXT_ZUMRA => $this->zumraGroups->isLeader($context, $actor),
            Transmission::CONTEXT_MISSION => $this->missionOfficializer($context, $actor),
            default => false,
        };
    }

    public function label(string $type, object $context): string
    {
        return match ($type) {
            Transmission::CONTEXT_NEED => 'Besoin · '.$context->title,
            Transmission::CONTEXT_PROJECT => 'Projet · '.$context->name,
            Transmission::CONTEXT_ZUMRA => 'ZUMRA · '.$context->name,
            Transmission::CONTEXT_MISSION => 'Mission · '.$context->title,
            default => (string) Str::title(mb_strtolower($type)),
        };
    }

    public function url(string $type, object $context): ?string
    {
        return match ($type) {
            Transmission::CONTEXT_NEED => route('needs.show', $context),
            Transmission::CONTEXT_PROJECT => route('projects.show', $context),
            Transmission::CONTEXT_ZUMRA => route('zumra.groups.show', $context),
            Transmission::CONTEXT_MISSION => route('missions.show', $context),
            default => null,
        };
    }

    private function missionOfficializer(Mission $mission, string $actor): bool
    {
        if (! $this->missionRegistry->has($mission->context_type)) {
            return false;
        }
        $adapter = $this->missionRegistry->for($mission->context_type);
        $context = $adapter->resolve($mission->context_reference);

        return $adapter->canOfficialize($context, $actor);
    }

    private function isActiveZumraMember(ZumraGroup $group, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->exists();
    }
}
