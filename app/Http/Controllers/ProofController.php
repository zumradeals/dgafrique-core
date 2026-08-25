<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Proof\ProofContextService;
use App\Application\Proof\ProofService;
use App\Application\Proof\ProofWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Mission;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\Proof;
use App\Models\ProofReference;
use App\Models\ProofWitness;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProofController
{
    public function index(Request $request, ProofService $proofs): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $sections = $proofs->mySections($identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        return view('proofs.index', compact('identity', 'sections', 'isAdministrator'));
    }

    public function memory(Request $request, ProofService $proofs, ?string $discoveryReference = null): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        if ($discoveryReference === null) {
            $ownerReference = $identity->reference;
            $ownerLabel = 'Vous';
        } else {
            $profile = PersonProfile::query()
                ->where('discovery_reference', $discoveryReference)
                ->where('orientation_consent', true)
                ->where('discovery_consent', true)
                ->whereNotNull('discovery_display_name')
                ->firstOrFail();
            $ownerReference = $profile->core_identity_reference;
            $ownerLabel = $profile->discovery_display_name;
        }

        $items = $proofs->memoryFor($ownerReference, $identity->reference);

        return view('proofs.memory', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'ownerLabel' => $ownerLabel,
            'isOwnMemory' => hash_equals($ownerReference, $identity->reference),
            'items' => $items,
        ]);
    }

    public function show(Request $request, Proof $proof, ProofService $proofs, ProofContextService $contexts): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $proof = $proofs->find($proof->public_reference, $identity->reference);
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        $proof->load(['references', 'witnesses']);

        // Une preuve peut rester visible (auteur/témoin/DISCOVERABLE) sans que le viewer ait
        // accès au contexte cité : le label et le lien du contexte ne sont jamais révélés
        // dans ce cas — seule l'existence d'un rattachement non public est signalée.
        $contextLabel = null;
        $contextUrl = null;
        $canAcknowledge = false;
        if (in_array($proof->origin_type, Proof::ACKNOWLEDGEABLE_ORIGINS, true)) {
            $context = $contexts->resolve($proof->origin_type, $proof->origin_reference);
            $viewerCanViewContext = $contexts->canView($proof->origin_type, $context, $identity->reference);

            if ($viewerCanViewContext) {
                $contextLabel = $contexts->label($proof->origin_type, $context);
                $contextUrl = $contexts->url($proof->origin_type, $context);
            } else {
                $contextLabel = 'Contexte non public';
            }

            $canAcknowledge = $viewerCanViewContext
                && $contexts->canAcknowledge($proof->origin_type, $context, $identity->reference)
                && in_array($proof->status, [Proof::STATUS_SUBMITTED, Proof::STATUS_WITNESSED], true);
        }

        $isAuthor = hash_equals($proof->submitted_by_core_reference, $identity->reference);
        $myWitness = $proof->witnesses->firstWhere('core_identity_reference', $identity->reference);
        $isConfirmedWitness = $myWitness?->status === ProofWitness::STATUS_CONFIRMED;

        // CAP-072 : la fiche ne montre un formulaire d'action que si l'acteur peut réellement
        // l'exécuter — ces indicateurs reflètent exactement l'autorité vérifiée côté service.
        $flags = [
            'isAuthor' => $isAuthor,
            'canInviteWitness' => $isAuthor,
            'canAcknowledge' => $canAcknowledge,
            'canDispute' => ($isConfirmedWitness || $canAcknowledge) && $proof->archived_at === null,
            'canArchive' => $isAuthor && $proof->archived_at === null,
            'canRestore' => $isAuthor && $proof->archived_at !== null,
        ];

        return view('proofs.show', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'proof' => $proof,
            'contextLabel' => $contextLabel,
            'contextUrl' => $contextUrl,
            'myWitness' => $myWitness,
        ] + $flags);
    }

    public function create(Request $request, ProofContextService $contexts): View
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();

        // UIUX-007 — Transmission → Preuve : le contexte n'est prérempli que si la Transmission
        // est réellement terminée et que l'acteur y a réellement participé — jamais une confiance
        // accordée au seul paramètre d'URL, jamais de preuve fabriquée automatiquement.
        $prefillTransmission = null;
        if ($request->query('origin_type') === Proof::ORIGIN_TRANSMISSION && $request->query('origin_reference')) {
            $transmission = Transmission::query()
                ->where('public_reference', $request->query('origin_reference'))
                ->whereIn('status', [Transmission::STATUS_COMPLETED_CONFIRMED, Transmission::STATUS_COMPLETED_BY_CONTEXT])
                ->first();

            $wasParticipant = $transmission !== null && $transmission->participants()
                ->where('core_identity_reference', $identity->reference)
                ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
                ->exists();

            $prefillTransmission = $wasParticipant ? $transmission : null;
        }

        // BETA-READY-004 (LOT 3) — même discipline pour Mission/Projet : le contexte n'est
        // prérempli que si l'acteur peut réellement le voir (ProofContextService::canView(),
        // strictement la même visibilité que le moteur de preuve applique déjà à la soumission)
        // ET que ce contexte est dans un état où produire une preuve est pertinent — jamais une
        // confiance accordée au seul paramètre d'URL.
        $prefillMission = null;
        if ($request->query('origin_type') === Proof::ORIGIN_MISSION && $request->query('origin_reference')) {
            $mission = Mission::query()->where('public_reference', $request->query('origin_reference'))->first();
            $isPertinent = $mission !== null && in_array($mission->status, [Mission::STATUS_IN_PROGRESS, Mission::STATUS_SUBMITTED, Mission::STATUS_COMPLETED], true);
            $prefillMission = $isPertinent && $contexts->canView(Proof::ORIGIN_MISSION, $mission, $identity->reference) ? $mission : null;
        }

        $prefillProject = null;
        if ($request->query('origin_type') === Proof::ORIGIN_PROJECT && $request->query('origin_reference')) {
            $project = Project::query()->where('public_reference', $request->query('origin_reference'))->first();
            $isPertinent = $project !== null && in_array($project->status, [Project::STATUS_IN_PROGRESS, Project::STATUS_COMPLETED], true);
            $prefillProject = $isPertinent && $contexts->canView(Proof::ORIGIN_PROJECT, $project, $identity->reference) ? $project : null;
        }

        return view('proofs.create', [
            'identity' => $identity,
            'isAdministrator' => $isAdministrator,
            'prefillTransmission' => $prefillTransmission,
            'prefillMission' => $prefillMission,
            'prefillProject' => $prefillProject,
        ]);
    }

    public function store(Request $request, ProofWorkflow $workflow): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'capability_label' => ['nullable', 'string', 'max:200'],
            'owner_type' => ['required', Rule::in([Proof::OWNER_PERSON, Proof::OWNER_GROUP])],
            'group_reference' => ['nullable', 'string', 'required_if:owner_type,'.Proof::OWNER_GROUP],
            'origin_type' => ['required', Rule::in(Proof::ORIGIN_TYPES)],
            'origin_reference' => ['nullable', 'string', 'max:191'],
            'visibility' => ['required', Rule::in([Proof::VISIBILITY_PRIVATE, Proof::VISIBILITY_DISCOVERABLE])],
            'occurred_at' => ['nullable', 'date'],
            'reference_type' => ['nullable', Rule::in(ProofReference::USABLE_TYPES)],
            'reference_value' => ['nullable', 'string', 'max:2000'],
            'reference_label' => ['nullable', 'string', 'max:200'],
        ]);

        $references = [];
        if (! empty($data['reference_type']) && ! empty($data['reference_value'])) {
            $references[] = ['type' => $data['reference_type'], 'value' => $data['reference_value'], 'label' => $data['reference_label'] ?? null];
        }

        $proof = $workflow->submit($identity->reference, [
            'title' => $data['title'],
            'description' => $data['description'],
            'capability_label' => $data['capability_label'] ?? null,
            'owner_type' => $data['owner_type'],
            'group_reference' => $data['group_reference'] ?? null,
            'origin_type' => $data['origin_type'],
            'origin_reference' => $data['origin_reference'] ?? null,
            'visibility' => $data['visibility'],
            'occurred_at' => $data['occurred_at'] ?? null,
            'references' => $references,
        ]);

        return redirect()->route('proofs.show', $proof)->with('status', 'La preuve est enregistrée.');
    }
}
