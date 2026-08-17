<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionContextService;
use App\Application\Transmission\TransmissionService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TransmissionController
{
    private const CONTEXT_OPTION_LIMIT = 20;

    public function index(Request $request, TransmissionService $transmissions): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $sections = $transmissions->myTransmissionsSections($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('transmissions.index', compact('identity', 'sections', 'isAdministrator'));
    }

    public function show(Request $request, Transmission $transmission, TransmissionService $transmissions, TransmissionContextService $contexts, TransmissionWorkflow $workflow): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $transmission = $transmissions->find($transmission->public_reference, $identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $transmission->load(['participants', 'milestones', 'contributions']);
        $myParticipant = $transmission->participants->firstWhere('core_identity_reference', $identity->reference);

        $contextLabel = null;
        $contextUrl = null;
        $canOfficializeContext = false;
        if ($transmission->context_type !== null) {
            $context = $contexts->resolve($transmission->context_type, $transmission->context_reference);
            $contextLabel = $contexts->label($transmission->context_type, $context);
            $contextUrl = $contexts->url($transmission->context_type, $context);
            $canOfficializeContext = in_array($transmission->context_type, Transmission::OFFICIALIZABLE_CONTEXTS, true)
                && $contexts->canOfficialize($transmission->context_type, $context, $identity->reference);
        }

        $isAccepted = $myParticipant?->status === TransmissionParticipant::STATUS_ACCEPTED;
        $isAcceptedTransmitter = $isAccepted && $myParticipant->role === TransmissionParticipant::ROLE_TRANSMITTER;

        // CAP-072 : la fiche ne montre un formulaire d'action que si l'acteur peut réellement
        // l'exécuter — ces indicateurs reflètent exactement l'autorité vérifiée côté service.
        $flags = [
            'canStart' => $isAccepted && $transmission->status === Transmission::STATUS_ACCEPTED,
            'canDeclareDone' => $isAccepted && $transmission->status === Transmission::STATUS_IN_PROGRESS && $myParticipant->declared_done_at === null,
            'canConfirmCompletion' => $isAccepted && $transmission->status === Transmission::STATUS_IN_PROGRESS,
            'canValidateByContext' => $canOfficializeContext && $transmission->status === Transmission::STATUS_IN_PROGRESS,
            'canEnd' => $isAccepted && in_array($transmission->status, [Transmission::STATUS_ACCEPTED, Transmission::STATUS_IN_PROGRESS], true),
            'canCancel' => (hash_equals($transmission->proposed_by_core_reference, $identity->reference) || $isAcceptedTransmitter)
                && in_array($transmission->status, [Transmission::STATUS_PROPOSED, Transmission::STATUS_ACCEPTED], true),
            'canManageParticipation' => hash_equals($transmission->proposed_by_core_reference, $identity->reference) || $isAcceptedTransmitter,
            'canOfficializeContext' => $canOfficializeContext,
            'canAddMilestoneOrContribution' => $isAccepted,
        ];

        return view('transmissions.show', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'transmission' => $transmission,
            'myParticipant' => $myParticipant,
            'contextLabel' => $contextLabel,
            'contextUrl' => $contextUrl,
            'invitationCandidates' => $flags['canManageParticipation'] ? $transmissions->invitationCandidates($transmission, $identity->reference) : [],
        ] + $flags);
    }

    public function create(Request $request): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('transmissions.create', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'contextOptions' => $this->contextOptions($identity->reference),
        ]);
    }

    public function store(Request $request, TransmissionWorkflow $workflow): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'capability_label' => ['required', 'string', 'min:2', 'max:200'],
            'learning_objective' => ['required', 'string', 'min:10', 'max:2000'],
            'initiator_role' => ['required', Rule::in(TransmissionParticipant::ROLES)],
            'origin_type' => ['required', Rule::in(Transmission::ORIGIN_TYPES)],
            'context_choice' => ['nullable', 'string', 'max:220'],
            'visibility' => ['required', Rule::in([Transmission::VISIBILITY_PRIVATE, Transmission::VISIBILITY_CONTEXT, Transmission::VISIBILITY_PROGRAM])],
            'availability_note' => ['nullable', 'string', 'max:300'],
        ]);

        [$contextType, $contextReference] = $this->splitContextChoice($data['context_choice'] ?? null);

        $transmission = $workflow->create($identity->reference, $data['initiator_role'], [
            'capability_label' => $data['capability_label'],
            'learning_objective' => $data['learning_objective'],
            'origin_type' => $data['origin_type'],
            'origin_reference' => $contextReference,
            'context_type' => $contextType,
            'context_reference' => $contextReference,
            'visibility' => $data['visibility'],
            'availability_note' => $data['availability_note'] ?? null,
        ]);

        return redirect()->route('transmissions.show', $transmission)
            ->with('status', 'La Transmission est proposée. Elle attend l’acceptation explicite des autres participants.');
    }

    /** @return array{0: string|null, 1: string|null} */
    private function splitContextChoice(?string $choice): array
    {
        if ($choice === null || $choice === '') {
            return [null, null];
        }

        [$type, $reference] = array_pad(explode('|', $choice, 2), 2, null);
        abort_unless(in_array($type, Transmission::CONTEXT_TYPES, true) && $reference !== null && $reference !== '', 422, 'Le contexte sélectionné est invalide.');

        return [$type, $reference];
    }

    /** @return list<array{type: string, reference: string, label: string}> */
    private function contextOptions(string $actor): array
    {
        $options = [];

        foreach (ZumraGroup::query()
            ->whereIn('id', ZumraGroupMembership::query()->where('core_identity_reference', $actor)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->pluck('zumra_group_id'))
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->limit(self::CONTEXT_OPTION_LIMIT)->get(['public_reference', 'name']) as $group) {
            $options[] = ['type' => Transmission::CONTEXT_ZUMRA, 'reference' => $group->public_reference, 'label' => 'ZUMRA · '.$group->name];
        }

        foreach (Project::query()
            ->where('owner_type', Project::OWNER_PERSON)->where('owner_reference', $actor)
            ->where('status', '!=', Project::STATUS_ARCHIVED)
            ->limit(self::CONTEXT_OPTION_LIMIT)->get(['public_reference', 'name']) as $project) {
            $options[] = ['type' => Transmission::CONTEXT_PROJECT, 'reference' => $project->public_reference, 'label' => 'Projet · '.$project->name];
        }

        foreach (Mission::query()
            ->whereIn('id', MissionAssignment::query()->where('core_identity_reference', $actor)->where('status', MissionAssignment::STATUS_ACCEPTED)->pluck('mission_id'))
            ->whereNotIn('status', Mission::TERMINAL_STATUSES)
            ->limit(self::CONTEXT_OPTION_LIMIT)->get(['public_reference', 'title']) as $mission) {
            $options[] = ['type' => Transmission::CONTEXT_MISSION, 'reference' => $mission->public_reference, 'label' => 'Mission · '.$mission->title];
        }

        foreach (Need::query()
            ->where('owner_type', Need::OWNER_PERSON)->where('owner_reference', $actor)
            ->where('status', '!=', Need::STATUS_ARCHIVED)
            ->limit(self::CONTEXT_OPTION_LIMIT)->get(['public_reference', 'title']) as $need) {
            $options[] = ['type' => Transmission::CONTEXT_NEED, 'reference' => $need->public_reference, 'label' => 'Besoin · '.$need->title];
        }

        return $options;
    }
}
