<?php

declare(strict_types=1);

namespace App\Application\Partnerships;

use App\Application\Needs\NeedService;
use App\Application\Organizations\OrganizationService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\Organization;
use App\Models\Partnership;
use App\Models\PartnershipEvent;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CAP-065 — connecte des acteurs déjà réels (Personne, Organisation) à un contexte déjà réel
 * (Projet, ZUMRA, Besoin) par une relation de partenariat explicite, sans dupliquer le domaine
 * Capacité ni fabriquer d'identité organisationnelle Core. Une seule capacité par partenariat
 * en V1 ; en retirer une termine le partenariat (voir withdraw()).
 */
final class PartnershipService
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly ZumraGroupService $zumraGroups,
        private readonly OrganizationService $organizations,
    ) {}

    public function propose(string $actor, array $data): Partnership
    {
        $providerType = (string) ($data['provider_type'] ?? '');
        abort_unless(in_array($providerType, [Partnership::PROVIDER_PERSON, Partnership::PROVIDER_ORGANIZATION], true), 422, 'Type de fournisseur inconnu.');

        $providerReference = $this->assertProvider($providerType, $actor, $data);

        $contextType = (string) ($data['context_type'] ?? '');
        [$context, $contextReference] = $this->resolveContext($contextType, (string) ($data['context_reference'] ?? ''));
        abort_unless($this->canViewContext($contextType, $context, $actor), 404);

        $statement = $this->assertCapability($providerType, $providerReference, $data);
        $capabilityStatementId = $statement?->id;

        // Une Personne fournit toujours une capacité réelle du domaine Capacité : le libellé est
        // dérivé de la CapabilityStatement liée, jamais déclaré librement par le client, pour
        // qu'aucune capacité textuelle contradictoire ne puisse être affichée (CAP-065, correctif
        // post-revue). Une Organisation, tant que CAP-067 la prive d'identité Core, reste sur une
        // déclaration libre.
        $label = $providerType === Partnership::PROVIDER_PERSON
            ? (string) $statement?->label
            : trim((string) ($data['capability_label'] ?? ''));
        abort_if($label === '', 422, 'La capacité proposée doit être décrite.');

        return DB::transaction(function () use ($providerType, $providerReference, $contextType, $contextReference, $capabilityStatementId, $label, $data, $actor): Partnership {
            $partnership = Partnership::query()->create([
                'public_reference' => (string) Str::uuid(),
                'provider_type' => $providerType,
                'provider_reference' => $providerReference,
                'context_type' => $contextType,
                'context_reference' => $contextReference,
                'capability_statement_id' => $capabilityStatementId,
                'capability_label' => $label,
                'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
                'status' => Partnership::STATUS_PROPOSED,
                'visibility' => ($data['visibility'] ?? null) === Partnership::VISIBILITY_PUBLIC
                    ? Partnership::VISIBILITY_PUBLIC
                    : Partnership::VISIBILITY_PRIVATE,
                'initiated_by_core_reference' => $actor,
            ]);

            $this->event($partnership, 'PARTNERSHIP_PROPOSED', $actor);

            return $partnership;
        });
    }

    public function activate(Partnership $partnership, string $actor): Partnership
    {
        abort_unless($partnership->status === Partnership::STATUS_PROPOSED, 409, 'Ce partenariat n’est plus en attente de décision.');
        $this->assertContextAuthority($partnership, $actor);

        $partnership->update([
            'status' => Partnership::STATUS_ACTIVE,
            'decided_by_core_reference' => $actor,
            'decided_at' => now(),
        ]);
        $this->event($partnership, 'PARTNERSHIP_ACTIVATED', $actor);

        return $partnership->refresh();
    }

    public function pause(Partnership $partnership, string $actor): Partnership
    {
        abort_unless($partnership->status === Partnership::STATUS_ACTIVE, 409, 'Seul un partenariat actif peut être mis en pause.');
        $this->assertProviderActor($partnership, $actor);

        $partnership->update(['status' => Partnership::STATUS_PAUSED, 'paused_at' => now()]);
        $this->event($partnership, 'PARTNERSHIP_PAUSED', $actor);

        return $partnership->refresh();
    }

    public function resume(Partnership $partnership, string $actor): Partnership
    {
        abort_unless($partnership->status === Partnership::STATUS_PAUSED, 409, 'Seul un partenariat en pause peut reprendre.');
        $this->assertProviderActor($partnership, $actor);

        $partnership->update(['status' => Partnership::STATUS_ACTIVE, 'paused_at' => null]);
        $this->event($partnership, 'PARTNERSHIP_RESUMED', $actor);

        return $partnership->refresh();
    }

    /** Le fournisseur retire sa capacité : geste côté fournisseur. */
    public function withdraw(Partnership $partnership, string $actor): Partnership
    {
        abort_unless(in_array($partnership->status, [Partnership::STATUS_PROPOSED, Partnership::STATUS_ACTIVE, Partnership::STATUS_PAUSED], true), 409, 'Ce partenariat est déjà terminé.');
        $this->assertProviderActor($partnership, $actor);

        $partnership->update(['status' => Partnership::STATUS_ENDED, 'ended_by_core_reference' => $actor, 'ended_at' => now()]);
        $this->event($partnership, 'CAPABILITY_WITHDRAWN', $actor);

        return $partnership->refresh();
    }

    /** Le contexte met fin au partenariat : geste côté autorité de contexte. */
    public function end(Partnership $partnership, string $actor, ?string $reason = null): Partnership
    {
        abort_unless(in_array($partnership->status, [Partnership::STATUS_PROPOSED, Partnership::STATUS_ACTIVE, Partnership::STATUS_PAUSED], true), 409, 'Ce partenariat est déjà terminé.');
        $this->assertContextAuthority($partnership, $actor);

        $partnership->update(['status' => Partnership::STATUS_ENDED, 'ended_by_core_reference' => $actor, 'ended_at' => now()]);
        $this->event($partnership, 'PARTNERSHIP_ENDED', $actor, $reason !== null ? ['reason' => $reason] : []);

        return $partnership->refresh();
    }

    public function canView(Partnership $partnership, string $actor): bool
    {
        if ($this->isProviderActor($partnership, $actor)) {
            return true;
        }

        // L'autorité de décision du contexte (jamais un simple lecteur du contexte) doit pouvoir
        // gouverner les partenariats qui lui sont proposés, même privés — sinon elle ne pourrait
        // jamais les activer/terminer. Un simple lecteur du contexte ne voit qu'un partenariat
        // explicitement PUBLIC et ACTIVE, jamais un partenariat PRIVATE par la seule visibilité
        // de son contexte partagé (CAP-065 §18 : aucune relation privée ne doit fuiter).
        [$context] = $this->resolveContext($partnership->context_type, $partnership->context_reference, silently: true);
        if ($context !== null && $this->canDecideContext($partnership->context_type, $context, $actor)) {
            return true;
        }

        return $partnership->status === Partnership::STATUS_ACTIVE && $partnership->visibility === Partnership::VISIBILITY_PUBLIC;
    }

    /** UIUX-005 — décision du fournisseur réel (pause/reprise/retrait) : jamais recalculée en dehors du service. */
    public function isProviderActor(Partnership $partnership, string $actor): bool
    {
        if ($partnership->provider_type === Partnership::PROVIDER_PERSON) {
            return hash_equals($partnership->provider_reference, $actor);
        }

        $organization = Organization::query()->find($partnership->provider_reference);

        return $organization !== null && $this->organizations->isManager($organization, $actor);
    }

    private function assertProviderActor(Partnership $partnership, string $actor): void
    {
        abort_unless($this->isProviderActor($partnership, $actor), 403);
    }

    private function assertContextAuthority(Partnership $partnership, string $actor): void
    {
        [$context] = $this->resolveContext($partnership->context_type, $partnership->context_reference);
        abort_unless($this->canDecideContext($partnership->context_type, $context, $actor), 403);
    }

    /**
     * UIUX-005 — décision de l'autorité de contexte (activer/mettre fin) exposée pour décider
     * quoi afficher côté fiche : réutilise exactement `canDecideContext()`, jamais recalculée.
     */
    public function canManageAsContextAuthority(Partnership $partnership, string $actor): bool
    {
        [$context] = $this->resolveContext($partnership->context_type, $partnership->context_reference, silently: true);

        return $this->canDecideContext($partnership->context_type, $context, $actor);
    }

    /**
     * UIUX-005 — résolution de l'objet de contexte réel (Besoin/Projet/ZUMRA) pour la présentation
     * humaine d'un partenariat (libellé, lien de retour) — jamais une nouvelle relation, seulement
     * la lecture de celle déjà portée par `context_type`/`context_reference`.
     */
    public function resolvedContext(Partnership $partnership): Need|Project|ZumraGroup|null
    {
        [$context] = $this->resolveContext($partnership->context_type, $partnership->context_reference, silently: true);

        return $context;
    }

    private function assertProvider(string $providerType, string $actor, array $data): string
    {
        if ($providerType === Partnership::PROVIDER_PERSON) {
            return $actor;
        }

        $organizationReference = (string) ($data['organization_reference'] ?? '');
        $organization = Organization::query()->where('public_reference', $organizationReference)->first();
        abort_if($organization === null, 404);
        abort_unless($this->organizations->isManager($organization, $actor), 403, 'Seul un membre habilité de l’organisation peut la proposer comme partenaire.');

        return $organization->id;
    }

    private function assertCapability(string $providerType, string $providerReference, array $data): ?CapabilityStatement
    {
        if ($providerType === Partnership::PROVIDER_ORGANIZATION) {
            // CAP-067 (identité organisationnelle Core) n'existe pas encore : une Organisation ne
            // peut pas posséder de CapabilityStatement réel. La capacité reste une déclaration
            // libre (capability_label), jamais une fausse CapabilityStatement fabriquée.
            return null;
        }

        // CAP-065 est « Partenaire comme fournisseur de CAPACITÉ » : une Personne doit toujours
        // lier une CapabilityStatement réelle du domaine Capacité, jamais une simple déclaration
        // textuelle libre (correctif post-revue).
        $statementReference = $data['capability_statement_id'] ?? null;
        abort_if(! is_string($statementReference) || $statementReference === '', 422, 'Une capacité réelle du domaine Capacité doit être liée (capability_statement_id).');

        $statement = CapabilityStatement::query()->find($statementReference);
        abort_if($statement === null, 422, 'Capacité introuvable.');
        abort_unless(hash_equals($statement->core_identity_reference, $providerReference), 403, 'Cette capacité ne vous appartient pas.');
        abort_unless($statement->matching_consent, 422, 'Cette capacité n’a pas été consentie au rapprochement.');
        abort_if($statement->archived_at !== null, 422, 'Cette capacité est archivée.');

        $profile = PersonProfile::query()->find($providerReference);
        abort_if($profile !== null && $profile->availability_status === PersonProfile::AVAILABILITY_PAUSED, 409, 'Vous vous déclarez en pause : aucune nouvelle proposition de capacité.');

        return $statement;
    }

    /** @return array{0: Need|Project|ZumraGroup|null, 1: string} */
    private function resolveContext(string $contextType, string $contextReference, bool $silently = false): array
    {
        $context = match ($contextType) {
            Partnership::CONTEXT_NEED => Need::query()->where('public_reference', $contextReference)->first(),
            Partnership::CONTEXT_PROJECT => Project::query()->where('public_reference', $contextReference)->first(),
            Partnership::CONTEXT_ZUMRA => ZumraGroup::query()->where('public_reference', $contextReference)->first(),
            default => null,
        };

        if (! $silently) {
            abort_if($contextType === '' || ! in_array($contextType, [Partnership::CONTEXT_NEED, Partnership::CONTEXT_PROJECT, Partnership::CONTEXT_ZUMRA], true), 422, 'Contexte de partenariat inconnu.');
            abort_if($context === null, 404);
        }

        return [$context, $contextReference];
    }

    private function canViewContext(string $contextType, Need|Project|ZumraGroup|null $context, string $actor): bool
    {
        if ($context === null) {
            return false;
        }

        return match ($contextType) {
            Partnership::CONTEXT_NEED => $this->needs->canView($context, $actor),
            Partnership::CONTEXT_PROJECT => $this->projects->canView($context, $actor),
            Partnership::CONTEXT_ZUMRA => ZumraGroupMembership::query()
                ->where('zumra_group_id', $context->id)
                ->where('core_identity_reference', $actor)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->exists() || $this->zumraGroups->isLeader($context, $actor),
            default => false,
        };
    }

    private function canDecideContext(string $contextType, Need|Project|ZumraGroup|null $context, string $actor): bool
    {
        if ($context === null) {
            return false;
        }

        return match ($contextType) {
            Partnership::CONTEXT_NEED => $this->needs->canDecide($context, $actor),
            Partnership::CONTEXT_PROJECT => $this->projects->canDecide($context, $actor),
            Partnership::CONTEXT_ZUMRA => $this->zumraGroups->isLeader($context, $actor),
            default => false,
        };
    }

    private function event(Partnership $partnership, string $event, string $actor, array $context = []): void
    {
        PartnershipEvent::query()->create([
            'partnership_id' => $partnership->id,
            'event' => $event,
            'actor_core_reference' => $actor,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
