<?php

declare(strict_types=1);

namespace App\Application\Zumra;

use App\Models\PortalAdministrator;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ZUMRA-COMP-001 — le cycle de vie opérationnel (state) est distinct de la maturité
 * (maturity, déjà réelle via le seuil de 50 membres) et de l'adhésion au Programme ZUMRA
 * (ZumraProgramMembership). READY est une constatation structurelle automatisable ; VALIDATED
 * reste toujours une décision explicite de l'autorité DG Afrique/GAMAD (PortalAdministrator),
 * qui porte aussi ACTIVE/WARNED/SUSPENDED/REHABILITATING/la réactivation — aucun contrôle
 * anti-fraude/conformité n'est fabriqué : le critère doctrinal correspondant n'entre jamais
 * dans l'évaluation automatique de readiness.
 */
final class ZumraGroupService
{
    public function create(string $actor, array $data, int $maxSimultaneousFounderRoles = 3): ZumraGroup
    {
        $assumesPrimaryLead = (bool) ($data['assume_primary_lead'] ?? false);
        if ($assumesPrimaryLead) {
            $this->assertUnderFounderRoleLimit($actor, $maxSimultaneousFounderRoles);
        }

        return DB::transaction(function () use ($actor, $data, $assumesPrimaryLead): ZumraGroup {
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
                $takesLead = $role === 'PRIMARY_LEAD' && $assumesPrimaryLead;
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

            $this->event($group, 'GROUP_PROPOSED', $actor, ['assumed_primary_lead' => $assumesPrimaryLead]);

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

    /** Un responsable propose une personne pour un siège fondateur vacant (art. 8). */
    public function proposeRole(ZumraGroup $group, string $actor, string $role, string $subject): void
    {
        $this->assertLeader($group, $actor);
        abort_unless(array_key_exists($role, ZumraGroupRole::LABELS), 422, 'Responsabilité inconnue.');

        DB::transaction(function () use ($group, $role, $actor, $subject): void {
            $roleRow = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', $role)->lockForUpdate()->firstOrFail();
            abort_unless($roleRow->status === ZumraGroupRole::STATUS_VACANT, 409, 'Cette responsabilité n’est plus vacante.');
            abort_if(
                ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('core_identity_reference', $subject)->where('status', '!=', ZumraGroupRole::STATUS_VACANT)->exists(),
                409,
                'Une personne ne peut exercer qu’une seule responsabilité fondatrice dans une même ZUMRA (art. 8).'
            );

            $roleRow->update([
                'core_identity_reference' => $subject,
                'status' => ZumraGroupRole::STATUS_PROPOSED,
                'proposed_by_core_reference' => $actor,
                'proposed_at' => now(),
                'accepted_at' => null,
            ]);
            $this->event($group, 'ROLE_PROPOSED', $actor, ['role' => $role, 'subject' => $subject]);
        });
    }

    /**
     * La personne proposée accepte explicitement (art. 7 : « la personne proposée comme
     * responsable doit accepter explicitement son rôle »). Vérifie la limite globale de
     * responsabilités fondatrices simultanées (art. 8) puis déclenche, si l'automatisation est
     * activée, la constatation READY.
     */
    public function acceptRole(ZumraGroup $group, string $actor, string $role, int $maxSimultaneousFounderRoles, bool $autoValidationEnabled): void
    {
        DB::transaction(function () use ($group, $role, $actor, $maxSimultaneousFounderRoles, $autoValidationEnabled): void {
            $roleRow = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', $role)->lockForUpdate()->firstOrFail();
            abort_unless(
                $roleRow->status === ZumraGroupRole::STATUS_PROPOSED && $roleRow->core_identity_reference !== null && hash_equals($roleRow->core_identity_reference, $actor),
                409,
                'Aucune proposition de responsabilité active pour vous sur ce siège.'
            );

            $this->assertUnderFounderRoleLimit($actor, $maxSimultaneousFounderRoles, lockForUpdate: true);

            $roleRow->update(['status' => ZumraGroupRole::STATUS_ACCEPTED, 'accepted_at' => now()]);
            $this->event($group, 'ROLE_ACCEPTED', $actor, ['role' => $role]);

            if ($autoValidationEnabled) {
                $this->maybeTransitionToReady($group, $actor);
            }
        });
    }

    /**
     * Évaluation PUREMENT STRUCTURELLE — répond à « le dossier est-il structurellement complet
     * et prêt à être soumis à validation ? », jamais à « les 7 critères doctrinaux de l'art. 10
     * sont-ils tous validés ? ». Sur les 7 critères doctrinaux, seuls 6 sont automatiquement
     * vérifiables ici (identité Core, adhésion Programme active, domaine, objectif, charte, cinq
     * responsabilités distinctes acceptées). Le 7e critère — « contrôles de nom, de doublon, de
     * risque et d'usurpation » — est un CONTRÔLE DE CONFORMITÉ qui exige un jugement humain :
     * l'unicité technique du `slug` en base n'en est qu'une garantie partielle et ne doit jamais
     * être présentée comme preuve que ce critère est satisfait. Il n'entre donc jamais dans cette
     * évaluation ; il reste à la charge de l'autorité DG Afrique/GAMAD au moment de VALIDATED.
     *
     * @return array{structurally_ready: bool, criteria: array<string, bool>, missing: list<string>}
     */
    public function evaluateStructuralReadiness(ZumraGroup $group): array
    {
        $criteria = [
            'core_identity_recognized' => trim((string) $group->proposer_core_reference) !== '',
            'program_membership_active' => ZumraProgramMembership::query()
                ->where('core_identity_reference', $group->proposer_core_reference)
                ->where('status', ZumraProgramMembership::STATUS_ACTIVE)
                ->exists(),
            'domain_set' => trim((string) $group->domain) !== '',
            'objective_set' => trim((string) $group->founding_objective) !== '',
            'charter_accepted' => trim((string) $group->internal_charter) !== '',
            'five_distinct_roles_accepted' => $group->roles()->where('status', ZumraGroupRole::STATUS_ACCEPTED)->count() === 5,
        ];

        return [
            'structurally_ready' => ! in_array(false, $criteria, true),
            'criteria' => $criteria,
            'missing' => array_keys(array_filter($criteria, static fn (bool $met): bool => ! $met)),
        ];
    }

    /**
     * Constatation MANUELLE de READY par l'autorité DG Afrique/GAMAD — le chemin qui reste
     * possible lorsque `auto_validation_enabled=false` : le cycle ne doit jamais devenir
     * impossible faute d'automatisation. Vérifie exactement les mêmes préconditions
     * structurelles que le chemin automatique (`acceptRole()`), jamais moins strictement.
     */
    public function markReady(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_CONSTITUTING, 409, 'Seule une ZUMRA en constitution peut être constatée prête.');
            abort_unless($this->evaluateStructuralReadiness($fresh)['structurally_ready'], 409, 'Les critères structurels ne sont pas tous réunis : impossible de constater READY.');

            $fresh->update(['state' => ZumraGroup::STATE_READY, 'ready_at' => now()]);
            $this->event($fresh, 'GROUP_READY', $actor);

            return $fresh;
        });
    }

    /**
     * READY → VALIDATED : décision explicite de l'autorité DG Afrique/GAMAD (PortalAdministrator
     * — aucune autorité applicative ZUMRA-interne, comme isLeader(), ne peut décider de ceci).
     * Impossible si les critères structurels ne sont plus réunis (art. 10, dernier alinéa) —
     * et impossible directement depuis CONSTITUTING : READY reste toujours une étape distincte.
     */
    public function validate(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_READY, 409, 'Seule une ZUMRA prête peut être validée.');
            abort_unless($this->evaluateStructuralReadiness($fresh)['structurally_ready'], 409, 'Les critères structurels ne sont plus réunis : la validation est impossible.');

            $fresh->update(['state' => ZumraGroup::STATE_VALIDATED, 'validated_at' => now()]);
            $this->event($fresh, 'GROUP_VALIDATED', $actor);

            return $fresh;
        });
    }

    public function activate(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_VALIDATED, 409, 'Seule une ZUMRA validée peut être activée.');

            $fresh->update(['state' => ZumraGroup::STATE_ACTIVE, 'activated_at' => now()]);
            $this->event($fresh, 'GROUP_ACTIVATED', $actor);

            return $fresh;
        });
    }

    public function warn(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_ACTIVE, 409, 'Seule une ZUMRA active peut être avertie.');

            $fresh->update(['state' => ZumraGroup::STATE_WARNED, 'warned_at' => now()]);
            $this->event($fresh, 'GROUP_WARNED', $actor);

            return $fresh;
        });
    }

    public function suspend(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_WARNED, 409, 'Seule une ZUMRA avertie peut être suspendue.');

            $fresh->update(['state' => ZumraGroup::STATE_SUSPENDED, 'suspended_at' => now()]);
            $this->event($fresh, 'GROUP_SUSPENDED', $actor);

            return $fresh;
        });
    }

    public function enterRehabilitation(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_SUSPENDED, 409, 'Seule une ZUMRA suspendue peut entrer en réhabilitation.');

            $fresh->update(['state' => ZumraGroup::STATE_REHABILITATING, 'rehabilitating_at' => now()]);
            $this->event($fresh, 'GROUP_REHABILITATING', $actor);

            return $fresh;
        });
    }

    /** REHABILITATING → ACTIVE : une transition de réactivation, jamais un état permanent distinct. */
    public function reactivate(ZumraGroup $group, string $actor): ZumraGroup
    {
        $this->assertAdministrator($actor);

        return DB::transaction(function () use ($group, $actor): ZumraGroup {
            $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            abort_unless($fresh->state === ZumraGroup::STATE_REHABILITATING, 409, 'Seule une ZUMRA en réhabilitation peut être réactivée.');

            $fresh->update(['state' => ZumraGroup::STATE_ACTIVE, 'activated_at' => now()]);
            $this->event($fresh, 'GROUP_REACTIVATED', $actor);

            return $fresh;
        });
    }

    public function isLeader(ZumraGroup $group, string $actor): bool
    {
        return $group->roles()->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->exists();
    }

    public function isPrimaryLead(ZumraGroup $group, string $subject): bool
    {
        return $group->roles()->where('role', 'PRIMARY_LEAD')->where('core_identity_reference', $subject)->where('status', ZumraGroupRole::STATUS_ACCEPTED)->exists();
    }

    /**
     * MODERATION-COMP-001 (art. 11.4/12/19) — lacune déjà préparée par le schéma (STATUS_EXCLUDED
     * était un constant mort) : décision disciplinaire ACTIVE|SUSPENDED → EXCLUDED, jamais un
     * départ volontaire (LEFT reste distinct). Un responsable ordinaire ne peut jamais décider de
     * l'exclusion du PRIMARY_LEAD : seule l'autorité DG Afrique/GAMAD (niveau 3) le peut — un
     * responsable ne peut pas juger son propre premier responsable.
     */
    public function exclude(ZumraGroup $group, string $actor, string $subject, string $reason, int $establishedThreshold): void
    {
        $this->assertDisciplinaryAuthority($group, $actor, $subject);
        abort_if(trim($reason) === '', 422, 'Un motif est obligatoire pour une exclusion (art. 11.4).');

        DB::transaction(function () use ($group, $actor, $subject, $reason, $establishedThreshold): void {
            $membership = ZumraGroupMembership::query()
                ->where('zumra_group_id', $group->id)->where('core_identity_reference', $subject)
                ->whereIn('status', [ZumraGroupMembership::STATUS_ACTIVE, ZumraGroupMembership::STATUS_SUSPENDED])
                ->lockForUpdate()->firstOrFail();
            $membership->update(['status' => ZumraGroupMembership::STATUS_EXCLUDED, 'decision_reason' => $reason, 'left_at' => now()]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'MEMBER_EXCLUDED', $actor, ['subject' => $subject, 'reason' => $reason]);
        });
    }

    /**
     * Suspension INDIVIDUELLE (art. 19) — ne réutilise jamais ZumraGroup::STATE_SUSPENDED, qui
     * suspend la ZUMRA entière. Cible exclusivement l'adhésion de la personne dans cette ZUMRA ;
     * réversible via reinstate(), contrairement à l'exclusion.
     */
    public function suspendMember(ZumraGroup $group, string $actor, string $subject, string $reason, int $establishedThreshold): void
    {
        $this->assertDisciplinaryAuthority($group, $actor, $subject);
        abort_if(trim($reason) === '', 422, 'Un motif est obligatoire pour une suspension individuelle.');

        DB::transaction(function () use ($group, $actor, $subject, $reason, $establishedThreshold): void {
            $membership = ZumraGroupMembership::query()
                ->where('zumra_group_id', $group->id)->where('core_identity_reference', $subject)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->lockForUpdate()->firstOrFail();
            $membership->update(['status' => ZumraGroupMembership::STATUS_SUSPENDED, 'decision_reason' => $reason]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'MEMBER_SUSPENDED', $actor, ['subject' => $subject, 'reason' => $reason]);
        });
    }

    /**
     * Mécanisme interne réservé à ModerationDecisionService (recours confirmé/levé par l'autorité
     * supérieure, ou expiration calculée à la lecture — art. 19/23.2) : l'autorité a déjà été
     * validée au niveau de la décision disciplinaire elle-même, jamais revalidée ici.
     */
    public function reinstate(ZumraGroup $group, string $actor, string $subject, int $establishedThreshold): void
    {
        DB::transaction(function () use ($group, $actor, $subject, $establishedThreshold): void {
            $membership = ZumraGroupMembership::query()
                ->where('zumra_group_id', $group->id)->where('core_identity_reference', $subject)
                ->where('status', ZumraGroupMembership::STATUS_SUSPENDED)
                ->lockForUpdate()->firstOrFail();
            $membership->update(['status' => ZumraGroupMembership::STATUS_ACTIVE]);
            $this->refreshCount($group, $establishedThreshold);
            $this->event($group, 'MEMBER_REINSTATED', $actor, ['subject' => $subject]);
        });
    }

    /**
     * Révocation de rôle (art. 8/19) — réutilise ZumraGroupRole, aucun second système de rôles.
     * PRIMARY_LEAD reste toujours niveau 3 (aucun pair ZUMRA ne peut juger le premier responsable).
     */
    public function revokeRole(ZumraGroup $group, string $actor, string $role): void
    {
        abort_unless(array_key_exists($role, ZumraGroupRole::LABELS), 422, 'Responsabilité inconnue.');
        if ($role === 'PRIMARY_LEAD') {
            $this->assertAdministrator($actor);
        } else {
            $this->assertLeader($group, $actor);
        }

        DB::transaction(function () use ($group, $actor, $role): void {
            $roleRow = ZumraGroupRole::query()->where('zumra_group_id', $group->id)->where('role', $role)->lockForUpdate()->firstOrFail();
            abort_unless($roleRow->status === ZumraGroupRole::STATUS_ACCEPTED, 409, 'Cette responsabilité n’est pas occupée.');
            $subject = $roleRow->core_identity_reference;
            $roleRow->update(['core_identity_reference' => null, 'status' => ZumraGroupRole::STATUS_VACANT, 'proposed_by_core_reference' => null, 'proposed_at' => null, 'accepted_at' => null]);
            $this->event($group, 'ROLE_REVOKED', $actor, ['role' => $role, 'subject' => $subject]);
        });
    }

    private function assertLeader(ZumraGroup $group, string $actor): void
    {
        abort_unless($this->isLeader($group, $actor), 403);
    }

    private function assertAdministrator(string $actor): void
    {
        abort_unless(PortalAdministrator::query()->whereKey($actor)->exists(), 403, 'Seule l’autorité DG Afrique/GAMAD peut décider de cette transition.');
    }

    /**
     * PRIMARY_LEAD ne peut jamais être jugé par ses pairs (art. 8) : escalade obligatoire vers
     * l'autorité DG Afrique/GAMAD dès que la cible d'une décision disciplinaire ZUMRA est le
     * premier responsable.
     */
    private function assertDisciplinaryAuthority(ZumraGroup $group, string $actor, string $subject): void
    {
        if ($this->isPrimaryLead($group, $subject)) {
            $this->assertAdministrator($actor);

            return;
        }

        $this->assertLeader($group, $actor);
    }

    private function assertUnderFounderRoleLimit(string $actor, int $maxSimultaneousFounderRoles, bool $lockForUpdate = false): void
    {
        $query = ZumraGroupRole::query()->where('core_identity_reference', $actor)->where('status', ZumraGroupRole::STATUS_ACCEPTED);
        $count = $lockForUpdate ? $query->lockForUpdate()->get()->count() : $query->count();
        abort_if($count >= $maxSimultaneousFounderRoles, 409, 'Le nombre maximal de responsabilités fondatrices simultanées est déjà atteint (art. 8).');
    }

    /**
     * CONSTITUTING → READY : constatation automatisable et idempotente, jamais une décision de
     * conformité. Déclenchée après l'acceptation d'un rôle (art. 10 : « notamment acceptation du
     * cinquième rôle »), uniquement si `auto_validation_enabled` autorise cette automatisation.
     */
    private function maybeTransitionToReady(ZumraGroup $group, string $triggeringActor): void
    {
        $fresh = ZumraGroup::query()->whereKey($group->id)->lockForUpdate()->first();
        if ($fresh === null || $fresh->state !== ZumraGroup::STATE_CONSTITUTING) {
            return;
        }
        if (! $this->evaluateStructuralReadiness($fresh)['structurally_ready']) {
            return;
        }

        $fresh->update(['state' => ZumraGroup::STATE_READY, 'ready_at' => now()]);
        $this->event($fresh, 'GROUP_READY', $triggeringActor);
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
