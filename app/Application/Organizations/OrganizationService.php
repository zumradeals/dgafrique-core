<?php

declare(strict_types=1);

namespace App\Application\Organizations;

use App\Infrastructure\GamadCore\GamadCoreClient;
use App\Models\Organization;
use App\Models\OrganizationEvent;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CAP-066/CAP-067 — Organisation. Une structure durable, distincte d'un Projet (une action
 * organisée autour d'une transformation) et d'une ZUMRA (une communauté d'action et de
 * transmission). Une ZUMRA ou un Projet peuvent conduire à une Organisation
 * (ZUMRA-DOCTRINE-INVARIANTE.md §2, ARCH-006), mais aucune mutation automatique n'existe : seule
 * la création volontaire par une personne réelle produit une Organisation.
 *
 * CAP-067 — toute nouvelle Organisation est raccordée à une identité (CAP-CORE-001) et une fiche
 * (CAP-CORE-002) canoniques réelles, via la délégation CORE-ORG-DELEGATION-001. GAMAD Core reste
 * souverain sur l'identité canonique ; DG Afrique reste souverain sur son métier local (§1). Le
 * raccordement Core est demandé avant toute écriture locale : GAMAD Core et DG Afrique restent
 * deux systèmes distincts, sans transaction distribuée — un échec Core interrompt la création
 * avant qu'aucune ligne locale ne soit écrite (voir le rapport de session CAP-067, §4).
 */
final class OrganizationService
{
    public function __construct(private readonly GamadCoreClient $core) {}

    public function create(string $actor, array $data): Organization
    {
        $type = (string) ($data['type'] ?? '');
        abort_unless(isset(Organization::TYPES[$type]), 422, 'Type d’organisation inconnu.');

        $otherTypeLabel = trim((string) ($data['other_type_label'] ?? ''));
        if ($type === 'OTHER') {
            abort_if($otherTypeLabel === '', 422, 'La forme envisagée doit être précisée.');
        } else {
            $otherTypeLabel = '';
        }

        $name = (string) $data['name'];
        $visibility = ($data['visibility'] ?? null) === Organization::VISIBILITY_PUBLIC
            ? Organization::VISIBILITY_PUBLIC
            : Organization::VISIBILITY_PRIVATE;

        // Le raccordement Core est demandé avant toute écriture locale : aucune fausse
        // Organisation locale n'est jamais finalisée si CAP-CORE-001/002 échoue. Les exceptions
        // Core (CoreUnavailableException/CoreProtocolException/CoreSessionRejectedException)
        // remontent volontairement à l'appelant, non capturées ici.
        $identity = $this->core->provisionOrganizationIdentity($name);
        $organizationCore = $this->core->createOrganization($identity['reference'], [
            'type_organisation_reference' => Organization::CORE_TYPE_MAP[$type],
            'proprietaire_reference' => $actor,
            'denomination_officielle' => $name,
            'description' => (string) $data['description'],
            'classification_reference' => $visibility === Organization::VISIBILITY_PUBLIC ? 'PUBLIC_ECOSYSTEME' : 'INTERNE',
        ]);

        return DB::transaction(function () use ($actor, $name, $data, $type, $otherTypeLabel, $visibility, $identity, $organizationCore): Organization {
            $organization = Organization::query()->create([
                'public_reference' => (string) Str::uuid(),
                'name' => $name,
                'description' => $data['description'],
                'type' => $type,
                'other_type_label' => $otherTypeLabel !== '' ? $otherTypeLabel : null,
                'status' => Organization::STATUS_ACTIVE,
                'visibility' => $visibility,
                'founder_core_reference' => $actor,
                'active_member_count' => 1,
                'core_identity_reference' => $identity['reference'],
                'core_organization_reference' => $organizationCore['reference'],
                'core_link_status' => Organization::CORE_LINK_LINKED,
            ]);

            OrganizationMembership::query()->create([
                'organization_id' => $organization->id,
                'core_identity_reference' => $actor,
                'role' => OrganizationMembership::ROLE_OWNER,
                'status' => OrganizationMembership::STATUS_ACTIVE,
                'entry_mode' => 'FOUNDER',
                'initiated_by_core_reference' => $actor,
                'joined_at' => now(),
            ]);

            $this->event($organization, 'ORGANIZATION_CREATED', $actor, [
                'type' => $type,
                'core_organization_reference' => $organizationCore['reference'],
            ]);

            return $organization;
        });
    }

    public function update(Organization $organization, string $actor, array $data): Organization
    {
        $this->assertManager($organization, $actor);
        abort_if($organization->status === Organization::STATUS_ARCHIVED, 409, 'Cette organisation est archivée.');

        $organization->update([
            'name' => $data['name'] ?? $organization->name,
            'description' => $data['description'] ?? $organization->description,
        ]);

        $this->event($organization, 'ORGANIZATION_UPDATED', $actor);

        return $organization->refresh();
    }

    public function canView(Organization $organization, string $actor): bool
    {
        if ($this->isMember($organization, $actor)) {
            return true;
        }

        return $organization->status !== Organization::STATUS_ARCHIVED
            && $organization->visibility === Organization::VISIBILITY_PUBLIC;
    }

    public function requestToJoin(Organization $organization, string $actor, ?string $motivation): void
    {
        abort_unless($this->canView($organization, $actor), 404);
        abort_if($organization->status === Organization::STATUS_ARCHIVED, 409, 'Cette organisation n’accepte plus de nouveaux membres.');

        DB::transaction(function () use ($organization, $actor, $motivation): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->where('core_identity_reference', $actor)
                ->lockForUpdate()
                ->first();
            abort_if($membership?->status === OrganizationMembership::STATUS_ACTIVE, 409, 'Vous êtes déjà membre de cette organisation.');
            $membership ??= new OrganizationMembership(['organization_id' => $organization->id, 'core_identity_reference' => $actor]);
            $membership->fill([
                'role' => OrganizationMembership::ROLE_MEMBER,
                'status' => OrganizationMembership::STATUS_REQUESTED,
                'entry_mode' => 'REQUEST',
                'initiated_by_core_reference' => $actor,
                'motivation' => $motivation,
                'requested_at' => now(),
                'invited_at' => null,
                'left_at' => null,
            ])->save();
            $this->event($organization, 'MEMBERSHIP_REQUESTED', $actor);
        });
    }

    public function invite(Organization $organization, string $actor, string $subject, string $role = OrganizationMembership::ROLE_MEMBER): void
    {
        $this->assertManager($organization, $actor);
        abort_if($actor === $subject, 422, 'Vous êtes déjà dans cette organisation.');
        abort_unless(isset(OrganizationMembership::ROLES[$role]), 422, 'Rôle inconnu.');

        DB::transaction(function () use ($organization, $actor, $subject, $role): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->where('core_identity_reference', $subject)
                ->lockForUpdate()
                ->first();
            abort_if($membership?->status === OrganizationMembership::STATUS_ACTIVE, 409, 'Cette personne est déjà membre.');
            $membership ??= new OrganizationMembership(['organization_id' => $organization->id, 'core_identity_reference' => $subject]);
            $membership->fill([
                'role' => $role,
                'status' => OrganizationMembership::STATUS_INVITED,
                'entry_mode' => 'INVITATION',
                'initiated_by_core_reference' => $actor,
                'invited_at' => now(),
                'requested_at' => null,
                'left_at' => null,
            ])->save();
            $this->event($organization, 'MEMBER_INVITED', $actor, ['role' => $role]);
        });
    }

    public function acceptInvitation(Organization $organization, string $actor): void
    {
        DB::transaction(function () use ($organization, $actor): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->where('core_identity_reference', $actor)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($membership->status === OrganizationMembership::STATUS_INVITED, 409, 'Aucune invitation active.');
            $membership->update(['status' => OrganizationMembership::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->refreshCount($organization);
            $this->event($organization, 'INVITATION_ACCEPTED', $actor);
        });
    }

    public function approveRequest(Organization $organization, string $actor, string $membershipId): void
    {
        $this->assertManager($organization, $actor);

        DB::transaction(function () use ($organization, $actor, $membershipId): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->whereKey($membershipId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($membership->status === OrganizationMembership::STATUS_REQUESTED, 409, 'Cette demande n’est plus en attente.');
            $membership->update(['status' => OrganizationMembership::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->refreshCount($organization);
            $this->event($organization, 'MEMBERSHIP_APPROVED', $actor);
        });
    }

    public function removeMember(Organization $organization, string $actor, string $membershipId, string $reason): void
    {
        $this->assertManager($organization, $actor);

        DB::transaction(function () use ($organization, $actor, $membershipId, $reason): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->whereKey($membershipId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($membership->status !== OrganizationMembership::STATUS_ACTIVE, 409, 'Cette personne n’est déjà plus membre.');
            abort_if($membership->role === OrganizationMembership::ROLE_OWNER, 409, 'Le fondateur ne peut pas être retiré.');
            $membership->update(['status' => OrganizationMembership::STATUS_REMOVED, 'decision_reason' => $reason, 'left_at' => now()]);
            $this->refreshCount($organization);
            $this->event($organization, 'MEMBER_REMOVED', $actor, ['reason' => $reason]);
        });
    }

    public function leave(Organization $organization, string $actor): void
    {
        DB::transaction(function () use ($organization, $actor): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->where('core_identity_reference', $actor)
                ->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($membership->role === OrganizationMembership::ROLE_OWNER, 409, 'Le fondateur doit d’abord transmettre la propriété avant de quitter cette organisation.');
            $membership->update(['status' => OrganizationMembership::STATUS_LEFT, 'left_at' => now()]);
            $this->refreshCount($organization);
            $this->event($organization, 'MEMBER_LEFT', $actor);
        });
    }

    public function isMember(Organization $organization, string $actor): bool
    {
        return OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('core_identity_reference', $actor)
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->exists();
    }

    public function isManager(Organization $organization, string $actor): bool
    {
        return OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('core_identity_reference', $actor)
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->whereIn('role', [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_ADMIN])
            ->exists();
    }

    private function assertManager(Organization $organization, string $actor): void
    {
        abort_unless($this->isManager($organization, $actor), 403);
    }

    private function refreshCount(Organization $organization): void
    {
        $count = $organization->memberships()->where('status', OrganizationMembership::STATUS_ACTIVE)->count();
        $organization->update(['active_member_count' => $count]);
    }

    private function event(Organization $organization, string $event, string $actor, array $context = []): void
    {
        OrganizationEvent::query()->create([
            'organization_id' => $organization->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
