<?php

declare(strict_types=1);

namespace App\Application\Proof;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Models\PersonProfile;
use App\Models\Proof;
use App\Models\ProofEvent;
use App\Models\ProofReference;
use App\Models\ProofWitness;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Machine d'états Preuve (fiche §8/§13). Aucun état ne s'appelle « VÉRIFIÉE » ni
 * « CERTIFIÉE » — WITNESSED/ACKNOWLEDGED décrivent une corroboration humaine, jamais un
 * verdict de vérité. Convention de verrouillage : toujours la Preuve d'abord, puis l'objet
 * enfant (témoin), dans cet ordre.
 */
final class ProofWorkflow
{
    public function __construct(private readonly ProofContextService $contexts) {}

    public function submit(string $actor, array $data): Proof
    {
        abort_unless(in_array($data['owner_type'] ?? null, [Proof::OWNER_PERSON, Proof::OWNER_GROUP], true), 422, 'Type de porteur invalide.');
        abort_unless(in_array($data['origin_type'] ?? null, Proof::ORIGIN_TYPES, true), 422, 'Origine de la preuve invalide.');

        $title = trim((string) ($data['title'] ?? ''));
        abort_if($title === '', 422, 'Le titre est requis.');
        $description = trim((string) ($data['description'] ?? ''));
        abort_if($description === '', 422, 'La description est requise.');

        $ownerReference = $this->resolveOwnerReference($actor, $data['owner_type'], $data['group_reference'] ?? null);

        $originType = $data['origin_type'];
        $originReference = $data['origin_reference'] ?? null;
        if ($originType !== Proof::ORIGIN_NONE && $originType !== Proof::ORIGIN_INTERACTION) {
            abort_if($originReference === null || $originReference === '', 422, 'Une référence d’origine est requise pour ce type.');
            // Une personne ne peut citer que ce qu'elle est déjà autorisée à voir — jamais
            // fabriquer un accès. Fail-closed : un contexte inaccessible répond 404, jamais
            // 403, pour ne pas révéler son existence à quelqu'un qui n'y a pas accès.
            $context = $this->contexts->resolve($originType, $originReference);
            abort_unless($this->contexts->canView($originType, $context, $actor), 404);
        } else {
            $originReference = null;
        }

        $capabilityLabel = isset($data['capability_label']) ? trim((string) $data['capability_label']) : null;
        $capabilityLabel = $capabilityLabel === '' ? null : $capabilityLabel;

        $visibility = $data['visibility'] ?? Proof::VISIBILITY_PRIVATE;
        abort_unless(in_array($visibility, [Proof::VISIBILITY_PRIVATE, Proof::VISIBILITY_DISCOVERABLE], true), 422, 'Visibilité invalide.');

        $occurredAt = $data['occurred_at'] ?? now();

        return DB::transaction(function () use ($actor, $data, $ownerReference, $originType, $originReference, $title, $description, $capabilityLabel, $visibility, $occurredAt): Proof {
            $proof = Proof::query()->create([
                'public_reference' => (string) Str::uuid(),
                'owner_type' => $data['owner_type'],
                'owner_reference' => $ownerReference,
                'title' => $title,
                'description' => $description,
                'capability_label' => $capabilityLabel,
                'normalized_label' => $capabilityLabel !== null ? CapabilityStatementSynchronizer::normalize($capabilityLabel) : null,
                'origin_type' => $originType,
                'origin_reference' => $originReference,
                'occurred_at' => $occurredAt,
                'submitted_by_core_reference' => $actor,
                'submitted_at' => now(),
                'visibility' => $visibility,
                'status' => Proof::STATUS_SUBMITTED,
            ]);

            foreach ($data['references'] ?? [] as $reference) {
                $type = $reference['type'] ?? null;
                $value = trim((string) ($reference['value'] ?? ''));
                if ($value === '' || ! in_array($type, ProofReference::USABLE_TYPES, true)) {
                    continue;
                }
                ProofReference::query()->create([
                    'proof_id' => $proof->id,
                    'type' => $type,
                    'value' => $value,
                    'label' => isset($reference['label']) ? trim((string) $reference['label']) : null,
                ]);
            }

            $this->record($proof, 'PROOF_SUBMITTED', $actor, null, Proof::STATUS_SUBMITTED);

            return $proof;
        });
    }

    /**
     * L'auteur (ou l'autorité du groupe porteur) invite un témoin — jamais une
     * core_identity_reference technique saisie côté client : $subjectDiscoveryReference est
     * résolue vers une identité canonique consentante, même mécanisme que Missions/Transmission.
     */
    public function inviteWitness(Proof $proof, string $actor, string $subjectDiscoveryReference): ProofWitness
    {
        $this->assertAuthor($proof, $actor);

        $subject = $this->resolveInvitableSubject($subjectDiscoveryReference);
        abort_if($subject === $actor, 422, 'Vous ne pouvez pas vous inviter vous-même comme témoin.');

        return DB::transaction(function () use ($proof, $actor, $subject): ProofWitness {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();

            $witness = ProofWitness::query()
                ->where('proof_id', $locked->id)
                ->where('core_identity_reference', $subject)
                ->first();
            abort_if($witness !== null, 409, 'Cette personne est déjà invitée comme témoin.');

            $witness = ProofWitness::query()->create([
                'proof_id' => $locked->id,
                'core_identity_reference' => $subject,
                'status' => ProofWitness::STATUS_INVITED,
                'invited_by_core_reference' => $actor,
            ]);

            $this->record($locked, 'PROOF_WITNESS_INVITED', $actor, null, null);

            return $witness;
        });
    }

    public function confirmWitness(Proof $proof, string $actor, ProofWitness $witness): ProofWitness
    {
        $this->assertBelongs($proof, $witness);
        abort_unless(hash_equals($witness->core_identity_reference, $actor), 403);

        return DB::transaction(function () use ($proof, $actor, $witness): ProofWitness {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            $lockedWitness = ProofWitness::query()->whereKey($witness->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedWitness->status === ProofWitness::STATUS_INVITED, 409, 'Cette invitation n’est plus en attente.');

            $lockedWitness->update(['status' => ProofWitness::STATUS_CONFIRMED, 'responded_at' => now()]);
            $this->record($locked, 'PROOF_WITNESS_CONFIRMED', $actor, null, null);

            if ($locked->status === Proof::STATUS_SUBMITTED) {
                $locked->update(['status' => Proof::STATUS_WITNESSED]);
                $this->record($locked, 'PROOF_WITNESSED', $actor, Proof::STATUS_SUBMITTED, Proof::STATUS_WITNESSED);
            }

            return $lockedWitness;
        });
    }

    public function declineWitness(Proof $proof, string $actor, ProofWitness $witness): ProofWitness
    {
        $this->assertBelongs($proof, $witness);
        abort_unless(hash_equals($witness->core_identity_reference, $actor), 403);

        return DB::transaction(function () use ($proof, $actor, $witness): ProofWitness {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            $lockedWitness = ProofWitness::query()->whereKey($witness->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedWitness->status === ProofWitness::STATUS_INVITED, 409, 'Cette invitation n’est plus en attente.');

            $lockedWitness->update(['status' => ProofWitness::STATUS_DECLINED, 'responded_at' => now()]);
            $this->record($locked, 'PROOF_WITNESS_DECLINED', $actor, null, null);

            return $lockedWitness;
        });
    }

    /** Reconnaissance contextuelle (fiche §9) : ne garantit jamais la véracité de la preuve. */
    public function acknowledgeByContext(Proof $proof, string $actor): Proof
    {
        abort_unless(in_array($proof->origin_type, Proof::ACKNOWLEDGEABLE_ORIGINS, true), 422, 'Cette preuve n’a pas de contexte pouvant la reconnaître.');
        $context = $this->contexts->resolve($proof->origin_type, $proof->origin_reference);
        abort_unless($this->contexts->canAcknowledge($proof->origin_type, $context, $actor), 403);

        return DB::transaction(function () use ($proof, $actor): Proof {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [Proof::STATUS_SUBMITTED, Proof::STATUS_WITNESSED], true), 409, 'Cette preuve n’est plus dans un état permettant la reconnaissance.');

            $from = $locked->status;
            $locked->update([
                'status' => Proof::STATUS_ACKNOWLEDGED,
                'context_acknowledged_by_core_reference' => $actor,
                'context_acknowledged_at' => now(),
            ]);
            $this->record($locked, 'PROOF_ACKNOWLEDGED', $actor, $from, Proof::STATUS_ACKNOWLEDGED);

            return $locked->fresh();
        });
    }

    /** Un témoin confirmé ou l'autorité contextuelle peut contester — jamais l'auteur de sa propre preuve. */
    public function dispute(Proof $proof, string $actor, ?string $note = null): Proof
    {
        $canDispute = $this->isConfirmedWitness($proof, $actor);
        if (! $canDispute && in_array($proof->origin_type, Proof::ACKNOWLEDGEABLE_ORIGINS, true)) {
            $context = $this->contexts->resolve($proof->origin_type, $proof->origin_reference);
            $canDispute = $this->contexts->canAcknowledge($proof->origin_type, $context, $actor);
        }
        abort_unless($canDispute, 403);

        return DB::transaction(function () use ($proof, $actor, $note): Proof {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->archived_at === null, 409, 'Cette preuve est archivée.');

            $from = $locked->status;
            $locked->update([
                'status' => Proof::STATUS_DISPUTED,
                'disputed_by_core_reference' => $actor,
                'disputed_at' => now(),
                'dispute_note' => $note,
            ]);
            $this->record($locked, 'PROOF_DISPUTED', $actor, $from, Proof::STATUS_DISPUTED, $note !== null ? ['note' => $note] : []);

            return $locked->fresh();
        });
    }

    /** Archivage réversible (doctrine : une preuve n'est jamais détruite depuis l'UI). */
    public function archive(Proof $proof, string $actor): Proof
    {
        $this->assertAuthor($proof, $actor);

        return DB::transaction(function () use ($proof, $actor): Proof {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->archived_at !== null, 409, 'Cette preuve est déjà archivée.');
            $locked->update(['archived_at' => now()]);
            $this->record($locked, 'PROOF_ARCHIVED', $actor, null, null);

            return $locked->fresh();
        });
    }

    public function restore(Proof $proof, string $actor): Proof
    {
        $this->assertAuthor($proof, $actor);

        return DB::transaction(function () use ($proof, $actor): Proof {
            $locked = Proof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->archived_at === null, 409, 'Cette preuve n’est pas archivée.');
            $locked->update(['archived_at' => null]);
            $this->record($locked, 'PROOF_RESTORED', $actor, null, null);

            return $locked->fresh();
        });
    }

    public function record(Proof $proof, string $event, string $actor, ?string $from, ?string $to, array $context = []): void
    {
        ProofEvent::query()->create([
            'proof_id' => $proof->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'from_state' => $from,
            'to_state' => $to,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    public function isConfirmedWitness(Proof $proof, string $actor): bool
    {
        return ProofWitness::query()
            ->where('proof_id', $proof->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ProofWitness::STATUS_CONFIRMED)
            ->exists();
    }

    private function resolveOwnerReference(string $actor, string $ownerType, ?string $groupReference): string
    {
        if ($ownerType === Proof::OWNER_PERSON) {
            return $actor;
        }

        abort_if($groupReference === null || $groupReference === '', 422, 'Une ZUMRA porteuse est requise.');
        $group = ZumraGroup::query()->where('public_reference', $groupReference)->firstOrFail();
        abort_unless(
            ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->exists(),
            403,
            'Seul un responsable de cette ZUMRA peut soumettre une preuve en son nom.'
        );

        return $group->id;
    }

    private function resolveInvitableSubject(string $discoveryReference): string
    {
        $profile = PersonProfile::query()
            ->where('discovery_reference', $discoveryReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first();

        abort_unless($profile !== null, 422, 'Cette personne n’est pas disponible pour un témoignage.');

        return $profile->core_identity_reference;
    }

    private function assertAuthor(Proof $proof, string $actor): void
    {
        if (hash_equals($proof->submitted_by_core_reference, $actor)) {
            return;
        }
        abort(403);
    }

    private function assertBelongs(Proof $proof, ProofWitness $witness): void
    {
        abort_unless($witness->proof_id === $proof->id, 404);
    }
}
