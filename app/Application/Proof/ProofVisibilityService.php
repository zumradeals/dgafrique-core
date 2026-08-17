<?php

declare(strict_types=1);

namespace App\Application\Proof;

use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\ProofWitness;
use App\Models\ZumraGroupRole;

/**
 * Une Preuve est privée par défaut (fiche §10) : visible de l'auteur, des témoins et, pour
 * revue, de l'autorité contextuelle habilitée à la reconnaître. `DISCOVERABLE` est un choix
 * explicite de mise en avant personnelle — contrairement à une Transmission, une Preuve
 * n'est pas un objet partagé lié à l'accès du contexte cité : la personne choisit de
 * montrer ce qu'elle écrit à son propos, comme les champs narratifs existants du profil.
 */
final class ProofVisibilityService
{
    public function __construct(private readonly ProofContextService $contexts) {}

    public function canView(Proof $proof, string $actor): bool
    {
        if (hash_equals($proof->submitted_by_core_reference, $actor)) {
            return true;
        }
        if ($proof->owner_type === Proof::OWNER_PERSON && hash_equals($proof->owner_reference, $actor)) {
            return true;
        }
        if ($proof->owner_type === Proof::OWNER_GROUP && $this->isGroupLeader($proof->owner_reference, $actor)) {
            return true;
        }
        if ($this->isWitness($proof, $actor)) {
            return true;
        }
        if (in_array($proof->origin_type, Proof::ACKNOWLEDGEABLE_ORIGINS, true)) {
            $context = $this->contexts->resolve($proof->origin_type, $proof->origin_reference);
            if ($this->contexts->canAcknowledge($proof->origin_type, $context, $actor)) {
                return true;
            }
        }

        if ($proof->visibility !== Proof::VISIBILITY_DISCOVERABLE) {
            return false;
        }
        if ($proof->owner_type === Proof::OWNER_GROUP) {
            return true;
        }

        return PersonProfile::query()
            ->where('core_identity_reference', $proof->owner_reference)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->exists();
    }

    public function isWitness(Proof $proof, string $actor): bool
    {
        return ProofWitness::query()
            ->where('proof_id', $proof->id)
            ->where('core_identity_reference', $actor)
            ->exists();
    }

    private function isGroupLeader(string $groupId, string $actor): bool
    {
        return ZumraGroupRole::query()
            ->where('zumra_group_id', $groupId)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->exists();
    }
}
