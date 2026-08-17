<?php

declare(strict_types=1);

namespace App\Application\Proof;

use App\Application\Missions\MissionContextRegistry;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\Proof;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;

/**
 * Contrat de contexte Preuve (fiche §9) : une preuve reste toujours valide de manière
 * autonome (origin_type = NONE/INTERACTION). Un rattachement MISSION/TRANSMISSION/NEED/
 * PROJECT/ZUMRA ajoute seulement la possibilité, pour l'autorité déjà existante de ce
 * contexte, de reconnaître la preuve — jamais de garantir sa véracité, jamais une nouvelle
 * autorité créée.
 */
final class ProofContextService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly ZumraGroupService $zumraGroups,
        private readonly MissionContextRegistry $missionRegistry,
    ) {}

    public function resolve(string $type, string $reference): object
    {
        return match ($type) {
            Proof::ORIGIN_NEED => Need::query()->where('public_reference', $reference)->firstOrFail(),
            Proof::ORIGIN_PROJECT => Project::query()->where('public_reference', $reference)->firstOrFail(),
            Proof::ORIGIN_ZUMRA => ZumraGroup::query()->where('public_reference', $reference)->firstOrFail(),
            Proof::ORIGIN_MISSION => Mission::query()->where('public_reference', $reference)->firstOrFail(),
            Proof::ORIGIN_TRANSMISSION => Transmission::query()->where('public_reference', $reference)->firstOrFail(),
            default => abort(422, 'Cette origine de preuve n’est pas prise en charge.'),
        };
    }

    /** Reconnaissance contextuelle (fiche §9) : jamais disponible pour NONE/INTERACTION. */
    public function canAcknowledge(string $type, object $context, string $actor): bool
    {
        return match ($type) {
            Proof::ORIGIN_NEED => $this->needs->canDecide($context, $actor),
            Proof::ORIGIN_PROJECT => $this->projects->canDecide($context, $actor),
            Proof::ORIGIN_ZUMRA => $this->zumraGroups->isLeader($context, $actor),
            Proof::ORIGIN_MISSION => $this->missionOfficializer($context, $actor),
            Proof::ORIGIN_TRANSMISSION => $this->transmissionTransmitter($context, $actor),
            default => false,
        };
    }

    public function label(string $type, object $context): string
    {
        return match ($type) {
            Proof::ORIGIN_NEED => 'Besoin · '.$context->title,
            Proof::ORIGIN_PROJECT => 'Projet · '.$context->name,
            Proof::ORIGIN_ZUMRA => 'ZUMRA · '.$context->name,
            Proof::ORIGIN_MISSION => 'Mission · '.$context->title,
            Proof::ORIGIN_TRANSMISSION => 'Transmission · '.$context->capability_label,
            default => 'Interaction',
        };
    }

    public function url(string $type, object $context): ?string
    {
        return match ($type) {
            Proof::ORIGIN_NEED => route('needs.show', $context),
            Proof::ORIGIN_PROJECT => route('projects.show', $context),
            Proof::ORIGIN_ZUMRA => route('zumra.groups.show', $context),
            Proof::ORIGIN_MISSION => route('missions.show', $context),
            Proof::ORIGIN_TRANSMISSION => route('transmissions.show', $context),
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

    private function transmissionTransmitter(Transmission $transmission, string $actor): bool
    {
        return TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->where('role', TransmissionParticipant::ROLE_TRANSMITTER)
            ->exists();
    }
}
