<?php

declare(strict_types=1);

namespace App\Application\Proof;

use App\Models\Mission;
use App\Models\Need;
use App\Models\Project;
use App\Models\Proof;
use App\Models\ProofWitness;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Collection;

/**
 * Couche de lecture, sections « Mon Carnet de preuves » et Mémoire d'expérience (fiche
 * §16/§3). CAP-035 n'est pas un second modèle : `memoryFor()` est la seule vue chronologique
 * agrégée des preuves d'une personne.
 */
final class ProofService
{
    private const POOL_LIMIT = 200;
    private const MEMORY_LIMIT = 100;

    public function __construct(private readonly ProofVisibilityService $visibility) {}

    public function find(string $publicReference, string $actor): Proof
    {
        $proof = Proof::query()->where('public_reference', $publicReference)->firstOrFail();
        abort_unless($this->visibility->canView($proof, $actor), 404);

        return $proof;
    }

    /**
     * Vue Mémoire d'expérience (CAP-035) : preuves non archivées d'une personne, triées par
     * date d'occurrence. `$viewer === $ownerReference` voit tout ; sinon uniquement ce qui
     * est DISCOVERABLE et consenti.
     */
    public function memoryFor(string $ownerReference, string $viewer): Collection
    {
        return Proof::query()
            ->where('owner_type', Proof::OWNER_PERSON)
            ->where('owner_reference', $ownerReference)
            ->whereNull('archived_at')
            ->latest('occurred_at')
            ->limit(self::MEMORY_LIMIT)
            ->get()
            ->filter(fn (Proof $proof): bool => $this->visibility->canView($proof, $viewer))
            ->values();
    }

    /**
     * Sections « Mon Carnet de preuves » (fiche §16) — jamais un mur de statistiques.
     */
    public function mySections(string $actor): array
    {
        $myWitnessInvitations = ProofWitness::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ProofWitness::STATUS_INVITED)
            ->with('proof')
            ->latest('created_at')
            ->limit(self::POOL_LIMIT)
            ->get()
            ->filter(fn (ProofWitness $w): bool => $w->proof !== null);

        $myProofs = Proof::query()
            ->where('submitted_by_core_reference', $actor)
            ->whereNull('archived_at')
            ->latest('submitted_at')
            ->limit(self::POOL_LIMIT)
            ->get();

        $authorityPool = $this->authorityCandidatePool($actor);
        $toAcknowledge = $authorityPool->filter(fn (Proof $p): bool => in_array($p->status, [Proof::STATUS_SUBMITTED, Proof::STATUS_WITNESSED], true));

        return [
            'temoignages_demandes' => $myWitnessInvitations->values(),
            'mes_preuves' => $myProofs,
            'a_reconnaitre' => $toAcknowledge->values(),
        ];
    }

    /**
     * Une seule prochaine action Preuve, explicable, pour Mon espace. Retourne null si rien
     * ne le concerne.
     */
    public function nextAction(string $actor): ?array
    {
        $sections = $this->mySections($actor);

        if ($sections['temoignages_demandes']->isNotEmpty()) {
            $proof = $sections['temoignages_demandes']->first()->proof;

            return [
                'heading' => 'On vous demande de témoigner pour « '.$proof->title.' ».',
                'body' => 'Confirmer un témoignage reste entièrement votre choix — cela ne certifie rien.',
                'primary' => ['label' => 'Répondre à la demande', 'href' => route('proofs.show', $proof)],
            ];
        }

        if ($sections['a_reconnaitre']->isNotEmpty()) {
            $proof = $sections['a_reconnaitre']->first();

            return [
                'heading' => 'La preuve « '.$proof->title.' » peut être reconnue par le contexte.',
                'body' => 'Reconnaître ne garantit jamais la véracité d’une preuve, mais confirme son inscription dans ce contexte.',
                'primary' => ['label' => 'Examiner la preuve', 'href' => route('proofs.show', $proof)],
            ];
        }

        return null;
    }

    /**
     * Bassin de preuves « à reconnaître » construit depuis l'empreinte propre de l'acteur
     * (ses Projets/Besoins décidables, ses ZUMRA dirigées, ses Missions officialisables, ses
     * Transmissions où il est transmetteur accepté) — jamais un balayage global.
     */
    private function authorityCandidatePool(string $actor): Collection
    {
        $projectRefs = Project::query()
            ->where(fn ($q) => $q->where('owner_type', 'PERSON')->where('owner_reference', $actor))
            ->orWhere(fn ($q) => $q->where('owner_type', 'GROUP')->whereIn('owner_reference', $this->ledGroupIds($actor)))
            ->pluck('public_reference');

        $needRefs = Need::query()
            ->where(fn ($q) => $q->where('owner_type', 'PERSON')->where('owner_reference', $actor))
            ->orWhere(fn ($q) => $q->where('owner_type', 'GROUP')->whereIn('owner_reference', $this->ledGroupIds($actor)))
            ->pluck('public_reference');

        $groupRefs = ZumraGroup::query()
            ->whereIn('id', $this->ledGroupIds($actor))
            ->pluck('public_reference');

        $missionRefs = Mission::query()
            ->where('created_by_core_reference', $actor)
            ->pluck('public_reference');

        $transmissionRefs = TransmissionParticipant::query()
            ->where('dg_transmission_participants.core_identity_reference', $actor)
            ->where('dg_transmission_participants.status', 'ACCEPTED')
            ->where('dg_transmission_participants.role', 'TRANSMITTER')
            ->join('dg_transmissions', 'dg_transmissions.id', '=', 'dg_transmission_participants.transmission_id')
            ->pluck('dg_transmissions.public_reference');

        return Proof::query()
            ->whereNull('archived_at')
            ->where(function ($query) use ($projectRefs, $needRefs, $groupRefs, $missionRefs, $transmissionRefs): void {
                $query->where(fn ($q) => $q->where('origin_type', Proof::ORIGIN_PROJECT)->whereIn('origin_reference', $projectRefs))
                    ->orWhere(fn ($q) => $q->where('origin_type', Proof::ORIGIN_NEED)->whereIn('origin_reference', $needRefs))
                    ->orWhere(fn ($q) => $q->where('origin_type', Proof::ORIGIN_ZUMRA)->whereIn('origin_reference', $groupRefs))
                    ->orWhere(fn ($q) => $q->where('origin_type', Proof::ORIGIN_MISSION)->whereIn('origin_reference', $missionRefs))
                    ->orWhere(fn ($q) => $q->where('origin_type', Proof::ORIGIN_TRANSMISSION)->whereIn('origin_reference', $transmissionRefs));
            })
            ->get();
    }

    private function ledGroupIds(string $actor): Collection
    {
        return ZumraGroupRole::query()
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->pluck('zumra_group_id');
    }
}
