<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Zumra\ZumraGroupService;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;

/**
 * Autorité projet extraite de ProjectService (comportement inchangé) pour être partagée avec
 * NeedService (CAP-042) sans créer de dépendance circulaire — ProjectService dépend déjà de
 * NeedService pour résoudre source_need_reference.
 */
final class ProjectAuthority
{
    public function __construct(private readonly ZumraGroupService $groups) {}

    public function canView(Project $project, string $actor): bool
    {
        if ($project->initiator_core_reference === $actor || ($project->owner_type === Project::OWNER_PERSON && $project->owner_reference === $actor)) {
            return true;
        }
        if ($project->owner_type === Project::OWNER_GROUP && $this->isActiveGroupMember($project->owner_reference, $actor)) {
            return true;
        }
        if ($project->status === Project::STATUS_ARCHIVED || $project->visibility === Project::VISIBILITY_PRIVATE) {
            return false;
        }
        if ($project->visibility === Project::VISIBILITY_PUBLIC) {
            return true;
        }
        // PROJET-ZUMRA-INVARIANT-001 — la visibilité GROUP désigne la ZUMRA d'ancrage
        // (zumra_group_id), jamais seulement les Projects owner_type=GROUP : un Projet gouverné
        // par sa Personne initiatrice mais ancré dans une ZUMRA peut aussi choisir cette
        // visibilité. Absente sur un Projet historique (zumra_group_id nul) : jamais accordée.
        if ($project->visibility === Project::VISIBILITY_GROUP) {
            return $project->zumra_group_id !== null && $this->isActiveGroupMember($project->zumra_group_id, $actor);
        }
        if ($project->visibility === Project::VISIBILITY_PROGRAM) {
            return ZumraProgramMembership::query()->where('core_identity_reference', $actor)->where('status', ZumraProgramMembership::STATUS_ACTIVE)->exists();
        }

        return false;
    }

    public function canDecide(Project $project, string $actor): bool
    {
        if ($project->owner_type === Project::OWNER_PERSON) {
            return hash_equals($project->owner_reference, $actor);
        }

        $group = ZumraGroup::query()->find($project->owner_reference);

        return $group && $this->groups->isLeader($group, $actor);
    }

    public function isActiveGroupMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()->where('zumra_group_id', $groupId)->where('core_identity_reference', $actor)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->exists();
    }
}
