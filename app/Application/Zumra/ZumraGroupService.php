<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ZumraGroupService
{
    public function create(string $actor, array $data): ZumraGroup
    {
        return DB::transaction(function () use ($actor, $data): ZumraGroup {
            $group = ZumraGroup::query()->create([
                'public_reference' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
                'domain' => $data['domain'],
                'founding_objective' => $data['founding_objective'],
                'participation_mode' => $data['participation_mode'],
                'internal_charter' => $data['internal_charter'],
                'state' => ZumraGroup::STATE_CONSTITUTING,
                'maturity' => ZumraGroup::MATURITY_EMERGING,
                'proposer_core_reference' => $actor,
                'active_member_count' => 1,
            ]);

            ZumraGroupMembership::query()->create([
                'zumra_group_id' => $group->id,
                'core_identity_reference' => $actor,
                'status' => ZumraGroupMembership::STATUS_ACTIVE,
                'entry_mode' => 'FOUNDER',
                'initiated_by_core_reference' => $actor,
                'joined_at' => now(),
            ]);

            foreach (ZumraGroupRole::LABELS as $role => $_label) {
                $takesLead = $role === 'PRIMARY_LEAD' && ($data['assume_primary_lead'] ?? false);
                ZumraGroupRole::query()->create([
                    'zumra_group_id' => $group->id,
                    'role' => $role,
                    'core_identity_reference' => $takesLead ? $actor : null,
                    'status' => $takesLead ? ZumraGroupRole::STATUS_ACCEPTED : ZumraGroupRole::STATUS_VACANT,
                    'proposed_by_core_reference' => $takesLead ? $actor : null,
                    'proposed_at' => $takesLead ? now() : null,
                    'accepted_at' => $takesLead ? now() : null,
                ]);
            }

            $this->event($group, 'GROUP_PROPOSED', $actor, ['assumed_primary_lead' => (bool) ($data['assume_primary_lead'] ?? false)]);

            return $group;
        });
    }

    public function requestToJoin(ZumraGroup $group, string $actor, ?string $motivation): void
    {
        abort_if(in_array($group->state, [ZumraGroup::STATE_SUSPENDED], true), 409, 'Cette ZUMRA ne reçoit pas de demande actuellement.');

        DB::transaction(function () use ($group, $actor, $motivation): void {
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $actor)->lockForUpdate()->first();
            abort_if($membership?->status === ZumraGroupMembership::STATUS_ACTIVE, 409, 'Vous êtes déjà membre de cette ZUMRA.');
            $membership ??= new ZumraGroupMembership(['zumra_group_id' => $group->id, 'core_identity_reference' => $actor]);
            $membership->fill(['status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $actor, 'motivation' => $motivation, 'requested_at' => now(), 'invited_at' => null, 'left_at' => null])->save();
            $this->event($group, 'MEMBERSHIP_REQUESTED', $actor);
        });
    }

    public function invite(ZumraGroup $group, string $actor, string $subject): void
    {
        $this->assertLeader($group, $actor);
        abort_if($actor === $subject, 422, 'Vous êtes déjà dans cette ZUMRA.');

        DB::transaction(function () use ($group, $actor, $subject): void {
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $subject)->lockForUpdate()->first();
            abort_if($membership?->status === ZumraGroupMembership::STATUS_ACTIVE, 409, 'Cette personne est déjà membre.');
            $membership ??= new ZumraGroupMembership(['zumra_group_id' => $group->id, 'core_identity_reference' => $subject]);
            $membership->fill(['status' => ZumraGroupMembership::STATUS_INVITED, 'entry_mode' => 'INVITATION', 'initiated_by_core_reference' => $actor, 'invited_at' => now(), 'requested_at' => null, 'left_at' => null])->save();
            $this->event($group, 'MEMBER_INVITED', $actor);
        });
    }

    public function acceptInvitation(ZumraGroup $group, string $actor, int $establishedThreshold): void
    {
        DB::transaction(function () use ($group, $actor, $establishedThreshold): void {
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $actor)->lockForUpdate()->firstOrFail();
            abort_unless($membership->status === ZumraGroupMembership::STATUS_INVITED, 409, 'Aucune invitation active.');
            $membership->update(['status' => ZumraGroupMembership::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'INVITATION_ACCEPTED', $actor);
        });
    }

    public function approveRequest(ZumraGroup $group, string $actor, string $membershipId, int $establishedThreshold): void
    {
        $this->assertLeader($group, $actor);
        DB::transaction(function () use ($group, $actor, $membershipId, $establishedThreshold): void {
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->whereKey($membershipId)->lockForUpdate()->firstOrFail();
            abort_unless($membership->status === ZumraGroupMembership::STATUS_REQUESTED, 409, 'Cette demande n’est plus en attente.');
            $membership->update(['status' => ZumraGroupMembership::STATUS_ACTIVE, 'joined_at' => now()]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'MEMBERSHIP_APPROVED', $actor);
        });
    }

    public function leave(ZumraGroup $group, string $actor, int $establishedThreshold): void
    {
        DB::transaction(function () use ($group, $actor, $establishedThreshold): void {
            abort_if($group->roles()->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->exists(), 409, 'Transmettez d’abord votre responsabilité avant de quitter cette ZUMRA.');
            $membership = ZumraGroupMembership::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $actor)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->lockForUpdate()->firstOrFail();
            $membership->update(['status' => ZumraGroupMembership::STATUS_LEFT, 'left_at' => now()]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'MEMBER_LEFT', $actor);
        });
    }

    public function isLeader(ZumraGroup $group, string $actor): bool
    {
        return $group->roles()->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->exists();
    }

    private function assertLeader(ZumraGroup $group, string $actor): void
    {
        abort_unless($this->isLeader($group, $actor), 403);
    }

    private function refreshCount(ZumraGroup $group, int $establishedThreshold): void
    {
        $count = $group->memberships()->where('status', ZumraGroupMembership::STATUS_ACTIVE)->count();
        $group->update(['active_member_count' => $count, 'maturity' => $count >= $establishedThreshold ? ZumraGroup::MATURITY_ESTABLISHED : ZumraGroup::MATURITY_EMERGING]);
    }

    private function event(ZumraGroup $group, string $event, string $actor, array $context = []): void
    {
        ZumraGroupEvent::query()->create(['zumra_group_id' => $group->id, 'event' => $event, 'actor_core_reference' => $actor, 'context' => $context, 'occurred_at' => now()]);
    }
}
