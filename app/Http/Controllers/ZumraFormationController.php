<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionVisibilityService;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\PortalAdministrator;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ZumraFormationController
{
    public function __invoke(
        Request $request,
        ZumraGroup $group,
        ZumraGroupService $groups,
        TransmissionVisibilityService $visibility,
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isLeader = $groups->isLeader($group, $identity->reference);

        abort_if($group->state === ZumraGroup::STATE_SUSPENDED && ! $isLeader, 404);

        $membership = $group->memberships()
            ->where('core_identity_reference', $identity->reference)
            ->first();
        $isActiveMember = $membership?->status === ZumraGroupMembership::STATUS_ACTIVE;

        // FORMATION-001 — les objectifs d'apprentissage sont privés par contrat. Cette surface
        // n'affiche donc que les déclarations de la personne connectée, jamais celles d'autrui.
        $learningGoals = CapabilityStatement::query()
            ->where('holder_type', CapabilityStatement::HOLDER_PERSON)
            ->where('core_identity_reference', $identity->reference)
            ->where('kind', CapabilityStatement::KIND_LEARNING)
            ->whereNull('archived_at')
            ->orderBy('label')
            ->get();

        $transmissionOffers = CapabilityStatement::query()
            ->where('holder_type', CapabilityStatement::HOLDER_PERSON)
            ->where('core_identity_reference', $identity->reference)
            ->where('kind', CapabilityStatement::KIND_TRANSMISSION)
            ->whereNull('archived_at')
            ->orderBy('label')
            ->get();

        // Une Transmission ZUMRA n'est jamais rendue publique par cette page : on repasse par le
        // même service de visibilité que la fiche Transmission. Quitter la ZUMRA retire donc
        // automatiquement l'accès aux transmissions de contexte.
        $groupTransmissions = ($isActiveMember || $isLeader)
            ? Transmission::query()
                ->where('context_type', Transmission::CONTEXT_ZUMRA)
                ->where('context_reference', $group->public_reference)
                ->latest('proposed_at')
                ->limit(60)
                ->get()
                ->filter(fn (Transmission $transmission): bool => $visibility->canView($transmission, $identity->reference))
                ->take(12)
                ->values()
            : collect();

        $groupTransmissions->load('participants');
        $activeTransmissions = $groupTransmissions
            ->reject(fn (Transmission $transmission): bool => in_array($transmission->status, Transmission::TERMINAL_STATUSES, true));
        $completedTransmissions = $groupTransmissions
            ->filter(fn (Transmission $transmission): bool => in_array($transmission->status, [Transmission::STATUS_COMPLETED_CONFIRMED, Transmission::STATUS_COMPLETED_BY_CONTEXT], true));

        $myTransmissionParticipation = $groupTransmissions->mapWithKeys(function (Transmission $transmission) use ($identity): array {
            $participant = $transmission->participants->firstWhere('core_identity_reference', $identity->reference);

            return [$transmission->id => $participant?->role];
        });

        return view('zumra.groups.formation', [
            'identity' => $identity,
            'group' => $group,
            'membership' => $membership,
            'isLeader' => $isLeader,
            'isActiveMember' => $isActiveMember,
            'isAdministrator' => PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            'learningGoals' => $learningGoals,
            'transmissionOffers' => $transmissionOffers,
            'groupTransmissions' => $groupTransmissions,
            'activeTransmissions' => $activeTransmissions,
            'completedTransmissions' => $completedTransmissions,
            'myTransmissionParticipation' => $myTransmissionParticipation,
            'statusLabels' => Transmission::STATUS_LABELS,
            'roleLabels' => TransmissionParticipant::ROLE_LABELS,
        ]);
    }
}
