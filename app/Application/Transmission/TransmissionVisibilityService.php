<?php

declare(strict_types=1);

namespace App\Application\Transmission;

use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraProgramMembership;

/**
 * Une Transmission est privée par défaut (fiche §16) : visible du/des participant(s) et de
 * l'initiateur, puis élargie par choix explicite (CONTEXT/PROGRAM), jamais PUBLIC en v1.
 */
final class TransmissionVisibilityService
{
    public function __construct(private readonly TransmissionContextService $contexts) {}

    /**
     * Fiche §16 : context.canView(actor) ET visibilité propre, jamais l'un sans l'autre. Le
     * contexte est revalidé EN PREMIER quand il existe : un participant ou l'initiateur ne
     * contourne jamais une perte d'accès au contexte porteur (ex. quitter la ZUMRA rattachée)
     * via le raccourci participant/initiateur.
     */
    public function canView(Transmission $transmission, string $actor): bool
    {
        if ($transmission->context_type === null) {
            if ($this->isParticipant($transmission, $actor) || hash_equals($transmission->proposed_by_core_reference, $actor)) {
                return true;
            }

            // Sans contexte, seule la visibilité PROGRAM a un sens (audience = Programme
            // ZUMRA) ; CONTEXT n'a pas de contexte à rejoindre, donc jamais vraie ici.
            return $transmission->visibility === Transmission::VISIBILITY_PROGRAM && $this->hasActiveProgramMembership($actor);
        }

        $context = $this->contexts->resolve($transmission->context_type, $transmission->context_reference);
        if (! $this->contexts->canView($transmission->context_type, $context, $actor)) {
            return false;
        }

        if ($this->isParticipant($transmission, $actor) || hash_equals($transmission->proposed_by_core_reference, $actor)) {
            return true;
        }

        return match ($transmission->visibility) {
            Transmission::VISIBILITY_PRIVATE => $this->contexts->canOfficialize($transmission->context_type, $context, $actor),
            Transmission::VISIBILITY_CONTEXT => true,
            Transmission::VISIBILITY_PROGRAM => $this->hasActiveProgramMembership($actor),
            default => false,
        };
    }

    public function isParticipant(Transmission $transmission, string $actor): bool
    {
        return TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('core_identity_reference', $actor)
            ->exists();
    }

    public function isAcceptedParticipant(Transmission $transmission, string $actor, ?string $role = null): bool
    {
        return TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('core_identity_reference', $actor)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->when($role !== null, fn ($query) => $query->where('role', $role))
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
