<?php

declare(strict_types=1);

namespace App\Application\Organizations;

use App\Models\Organization;
use App\Models\OrganizationEvent;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CAP-066 — Organisation. Une structure durable, distincte d'un Projet (une action organisée
 * autour d'une transformation) et d'une ZUMRA (une communauté d'action et de transmission).
 * Une ZUMRA ou un Projet peuvent conduire à une Organisation (ZUMRA-DOCTRINE-INVARIANTE.md §2,
 * ARCH-006), mais aucune mutation automatique n'existe : seule la création volontaire par une
 * personne réelle produit une Organisation.
 */
final class OrganizationService
{
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

        return DB::transaction(function () use ($actor, $data, $type, $otherTypeLabel): Organization {
            $organization = Organization::query()->create([
                'public_reference' => (string) Str::uuid(),
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $type,
                'other_type_label' => $otherTypeLabel !== '' ? $otherTypeLabel : null,
                'status' => Organization::STATUS_ACTIVE,
                'visibility' => ($data['visibility'] ?? null) === Organization::VISIBILITY_PUBLIC
                    ? Organization::VISIBILITY_PUBLIC
                    : Organization::VISIBILITY_PRIVATE,
                'founder_core_reference' => $actor,
                'active_member_count' => 1,
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

            $this->event($organization, 'ORGANIZATION_CREATED', $actor, ['type' => $type]);

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
