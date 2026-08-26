<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Proof\ProofWorkflow;
use App\Application\Transmission\TransmissionParticipationService;
use App\Application\Transmission\TransmissionService;
use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\Proof;
use App\Models\ProofWitness;
use App\Models\Transmission;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * UX-HARMONY-TRANSMISSIONS-PROOFS-001 — installation opt-in :
 * php artisan db:seed --class=Database\\Seeders\\TransmissionsProofsDemoSeeder
 *
 * Peuple les surfaces Transmission (Mes Transmissions, Fiche, création, matching) et Preuve
 * (Carnet, Mémoire, Fiche, enregistrement) avec des données réelles créées exclusivement via
 * les services métier existants (TransmissionWorkflow, TransmissionParticipationService,
 * TransmissionService, ProofWorkflow) — aucune ligne SQL brute, aucun statut forcé sans passer
 * par la machine d'états réelle. Couvre : 8 Transmissions sur les 7 statuts réels (PROPOSED,
 * ACCEPTED, IN_PROGRESS ×2, COMPLETED_CONFIRMED, COMPLETED_BY_CONTEXT, ENDED, CANCELLED), 3
 * contextes (aucun/Projet officialisable/ZUMRA officialisable) ; 6 Preuves sur 4 statuts réels
 * (SUBMITTED ×2, WITNESSED, ACKNOWLEDGED, DISPUTED) plus une variante archivée, 3 origines
 * (aucune, Transmission réellement terminée — démontrant le pont réel Transmission → Preuve —,
 * Projet), un témoin en attente et un témoin confirmé puis contestataire. Idempotent : chaque
 * Transmission/Preuve est retrouvée par son titre/libellé, aucune duplication au second
 * lancement.
 */
final class TransmissionsProofsDemoSeeder extends Seeder
{
    private const LEADER = 'DEMO-TP-LEADER-01';

    private const PEER1 = 'DEMO-TP-PEER-01';

    private const PEER2 = 'DEMO-TP-PEER-02';

    private const PEER3 = 'DEMO-TP-PEER-03';

    private const WITNESS = 'DEMO-TP-WITNESS-01';

    public function run(): void
    {
        $this->command?->warn('UX-HARMONY-TRANSMISSIONS-PROOFS-001 : installation opt-in de Transmissions/Preuves de démonstration DEMO-TP-*');

        foreach ([self::LEADER, self::PEER1, self::PEER2, self::PEER3, self::WITNESS] as $actor) {
            $this->activateProgram($actor);
        }

        $project = $this->project(self::LEADER, 'Atelier de couture solidaire de Thiès', 'CRAFT', 'Thiès, Sénégal');
        $zumra = $this->zumra(self::LEADER, 'ZUMRA Tissage et Teinture de Bamako', 'Artisanat');
        $this->joinZumra($zumra, self::PEER2);

        $this->discoverableProfile(self::LEADER, 'Meneur·se de l’atelier de couture');
        $peer1Discovery = $this->discoverableProfile(self::PEER1, 'Membre DG Afrique — Apprenti·e couture');
        $peer2Discovery = $this->discoverableProfile(self::PEER2, 'Membre DG Afrique — Apprenti·e tissage');
        $peer3Discovery = $this->discoverableProfile(self::PEER3, 'Membre DG Afrique — Apprenti·e broderie');

        $workflow = app(TransmissionWorkflow::class);
        $participation = app(TransmissionParticipationService::class);
        $transmissions = app(TransmissionService::class);

        // ===== PROPOSED, privée, un seul participant (initiateur) =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Point de croix wax traditionnel',
            'learning_objective' => 'Réaliser un point de croix régulier sur tissu wax, prêt pour une bordure de boubou.',
        ], initiatorRole: 'TRANSMITTER', contextType: null, contextReference: null, target: 'PROPOSED');

        // ===== ACCEPTED, contexte Projet (jamais officialisé) =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Découpe et assemblage de tissus wax',
            'learning_objective' => 'Découper un patron simple et assembler deux pièces de wax avec une couture droite.',
        ], initiatorRole: 'TRANSMITTER', contextType: Transmission::CONTEXT_PROJECT, contextReference: $project->public_reference, target: 'ACCEPTED', peer: self::PEER1, peerRole: 'LEARNER', peerDiscoveryReference: $peer1Discovery);

        // ===== IN_PROGRESS, privée, étapes + trace, quorum de clôture partiel =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Teinture indigo traditionnelle',
            'learning_objective' => 'Préparer un bain d’indigo et teindre un tissu de coton avec une couleur régulière.',
        ], initiatorRole: 'TRANSMITTER', contextType: null, contextReference: null, target: 'IN_PROGRESS', peer: self::PEER1, peerRole: 'LEARNER', milestones: [
            ['Préparer le bain d’indigo', true], ['Immerger le tissu par passes successives', true], ['Rincer et sécher à l’ombre', false],
        ], contribution: 'Premier bain préparé, couleur encore inégale sur les bords.', declarePeerDone: true, peerDiscoveryReference: $peer1Discovery);

        // ===== IN_PROGRESS, contexte ZUMRA (officialisé), sans étapes (progression honnêtement absente) =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Tissage sur métier traditionnel',
            'learning_objective' => 'Monter une chaîne simple et tisser une bande régulière de vingt centimètres.',
        ], initiatorRole: 'TRANSMITTER', contextType: Transmission::CONTEXT_ZUMRA, contextReference: $zumra->public_reference, target: 'IN_PROGRESS', peer: self::PEER2, peerRole: 'LEARNER', officialize: true, peerDiscoveryReference: $peer2Discovery);

        // ===== COMPLETED_CONFIRMED, contexte Projet (officialisé), résumé de clôture =====
        $t5 = $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Finition et repassage des pièces cousues',
            'learning_objective' => 'Repasser et plier une pièce cousue selon les standards de l’atelier.',
        ], initiatorRole: 'TRANSMITTER', contextType: Transmission::CONTEXT_PROJECT, contextReference: $project->public_reference, target: 'COMPLETED_CONFIRMED', peer: self::PEER1, peerRole: 'LEARNER', officialize: true, peerDiscoveryReference: $peer1Discovery);

        // ===== COMPLETED_BY_CONTEXT, contexte ZUMRA (officialisé), validée par l’autorité du groupe =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Réparation de métiers à tisser',
            'learning_objective' => 'Diagnostiquer et réparer une casse de fil de chaîne sur un métier traditionnel.',
        ], initiatorRole: 'TRANSMITTER', contextType: Transmission::CONTEXT_ZUMRA, contextReference: $zumra->public_reference, target: 'COMPLETED_BY_CONTEXT', peer: self::PEER2, peerRole: 'LEARNER', officialize: true, contribution: 'Réparation effectuée et vérifiée sur le métier n°2.', peerDiscoveryReference: $peer2Discovery);

        // ===== ENDED, privée, arrêtée sans résultat validé =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Broderie main sur boubou',
            'learning_objective' => 'Réaliser une broderie décorative simple sur un col de boubou.',
        ], initiatorRole: 'TRANSMITTER', contextType: null, contextReference: null, target: 'ENDED', peer: self::PEER3, peerRole: 'LEARNER', peerDiscoveryReference: $peer3Discovery);

        // ===== CANCELLED, privée, jamais acceptée =====
        $this->transmission($workflow, $participation, $transmissions, self::LEADER, [
            'capability_label' => 'Fabrication de teinture naturelle à base de feuilles',
            'learning_objective' => 'Préparer une teinture naturelle à partir de feuilles locales séchées.',
        ], initiatorRole: 'TRANSMITTER', contextType: null, contextReference: null, target: 'CANCELLED');

        $proofWorkflow = app(ProofWorkflow::class);
        $witnessDiscoveryReference = $this->discoverableProfile(self::WITNESS, 'Témoin de démonstration');

        // ===== SUBMITTED, origine NONE, aucun témoin =====
        $this->proof($proofWorkflow, self::PEER1, [
            'title' => 'Réparation autonome d’une machine à coudre',
            'description' => 'J’ai diagnostiqué et réparé seul·e une casse de courroie sur la machine à coudre de l’atelier.',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_NONE, 'origin_reference' => null,
            'visibility' => Proof::VISIBILITY_PRIVATE,
        ], target: 'SUBMITTED');

        // ===== SUBMITTED, origine NONE, témoin invité mais pas encore confirmé =====
        $this->proof($proofWorkflow, self::LEADER, [
            'title' => 'Formation improvisée à la découpe de wax',
            'description' => 'J’ai improvisé une courte formation à la découpe de wax pour deux bénévoles de l’atelier.',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_NONE, 'origin_reference' => null,
            'visibility' => Proof::VISIBILITY_PRIVATE,
        ], target: 'SUBMITTED', pendingWitness: $witnessDiscoveryReference);

        // ===== WITNESSED, origine TRANSMISSION (pont réel Transmission → Preuve, T5 réellement terminée) =====
        $this->proof($proofWorkflow, self::PEER1, [
            'title' => 'Finition d’une première pièce cousue',
            'description' => 'Repassage et pliage réalisés en autonomie à l’issue de la Transmission « Finition et repassage des pièces cousues ».',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_TRANSMISSION, 'origin_reference' => $t5->public_reference,
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ], target: 'WITNESSED', confirmedWitness: $witnessDiscoveryReference);

        // ===== ACKNOWLEDGED, origine PROJECT, reconnue par l’autorité du Projet =====
        $this->proof($proofWorkflow, self::PEER1, [
            'title' => 'Découpe de dix patrons en une matinée',
            'description' => 'Dix patrons découpés et assemblés pour l’atelier, contrôlés par l’équipe du Projet.',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_PROJECT, 'origin_reference' => $project->public_reference,
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ], target: 'ACKNOWLEDGED', acknowledger: self::LEADER);

        // ===== DISPUTED, origine NONE, contestée par le témoin confirmé =====
        $this->proof($proofWorkflow, self::LEADER, [
            'title' => 'Teinture indigo réussie en une seule passe',
            'description' => 'Teinture obtenue en une seule passe, résultat jugé exceptionnellement rapide.',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_NONE, 'origin_reference' => null,
            'visibility' => Proof::VISIBILITY_DISCOVERABLE,
        ], target: 'DISPUTED', confirmedWitness: $witnessDiscoveryReference, disputeNote: 'Le tissu présenté n’était pas celui teint pendant la session observée.');

        // ===== SUBMITTED puis archivée (réversible) =====
        $this->proof($proofWorkflow, self::PEER1, [
            'title' => 'Essai de teinture non concluant',
            'description' => 'Essai de teinture naturelle non concluant, conservé pour mémoire mais retiré des mises en avant.',
            'owner_type' => Proof::OWNER_PERSON, 'origin_type' => Proof::ORIGIN_NONE, 'origin_reference' => null,
            'visibility' => Proof::VISIBILITY_PRIVATE,
        ], target: 'SUBMITTED', archive: true);

        $this->command?->info(sprintf(
            '%d Transmissions (7 statuts réels) et %d Preuves (4 statuts réels + archivage) de démonstration.',
            Transmission::query()->where('proposed_by_core_reference', self::LEADER)->count(),
            Proof::query()->whereIn('submitted_by_core_reference', [self::LEADER, self::PEER1])->count()
        ));
    }

    /**
     * Fait naître une Transmission exclusivement via les services métier réels
     * (TransmissionWorkflow, TransmissionParticipationService, TransmissionService), jamais par
     * une écriture directe de statut. Idempotent : retrouvée par capability_label.
     */
    private function transmission(
        TransmissionWorkflow $workflow,
        TransmissionParticipationService $participation,
        TransmissionService $transmissions,
        string $initiator,
        array $data,
        string $initiatorRole,
        ?string $contextType,
        ?string $contextReference,
        string $target,
        ?string $peer = null,
        ?string $peerRole = null,
        array $milestones = [],
        ?string $contribution = null,
        bool $declarePeerDone = false,
        bool $officialize = false,
        ?string $peerDiscoveryReference = null,
    ): Transmission {
        $existing = Transmission::query()->where('capability_label', $data['capability_label'])->first();
        if ($existing !== null) {
            return $existing;
        }

        $transmission = $workflow->create($initiator, $initiatorRole, [
            'capability_label' => $data['capability_label'],
            'learning_objective' => $data['learning_objective'],
            'origin_type' => match ($contextType) {
                Transmission::CONTEXT_PROJECT => Transmission::ORIGIN_PROJECT,
                Transmission::CONTEXT_ZUMRA => Transmission::ORIGIN_ZUMRA,
                default => Transmission::ORIGIN_INTERACTION,
            },
            'origin_reference' => $contextReference,
            'context_type' => $contextType,
            'context_reference' => $contextReference,
            'visibility' => $contextType !== null ? Transmission::VISIBILITY_CONTEXT : Transmission::VISIBILITY_PRIVATE,
        ]);

        if ($target === 'PROPOSED') {
            return $transmission->fresh();
        }

        if ($target === 'CANCELLED') {
            return $workflow->cancel($transmission, $initiator, 'Annulée dans le scénario de démonstration.')->fresh();
        }

        // Une Transmission privée n'est visible que de son initiateur et de ses participants
        // (TransmissionVisibilityService::canView) : le second participant ne peut donc jamais
        // s'auto-proposer (offer()) avant d'avoir été invité — l'invitation reste le seul chemin
        // uniforme, qu'il y ait un contexte visible ou non.
        $peerRole ??= $initiatorRole === 'TRANSMITTER' ? 'LEARNER' : 'TRANSMITTER';
        $invitation = $participation->invite($transmission, $initiator, $peerDiscoveryReference, $peerRole);
        $participation->acceptInvitation($transmission, $peer, $invitation);
        $transmission = $transmission->fresh();

        if ($officialize) {
            $workflow->officializeContext($transmission, $initiator);
        }

        if ($target === 'ACCEPTED') {
            return $transmission->fresh();
        }

        $transmission = $workflow->start($transmission, $initiator);

        foreach ($milestones as [$label, $completed]) {
            $milestone = $transmissions->addMilestone($transmission, $initiator, $label, true);
            if ($completed) {
                $transmissions->setMilestoneCompletion($transmission, $initiator, $milestone, true);
            }
        }

        if ($contribution !== null) {
            $transmissions->addContribution($transmission, $initiator, $contribution);
        }

        if ($declarePeerDone) {
            $workflow->declareDone($transmission, $peer, 'Ma part est terminée dans le scénario de démonstration.');
        }

        if ($target === 'IN_PROGRESS') {
            return $transmission->fresh();
        }

        if ($target === 'ENDED') {
            return $workflow->end($transmission, $initiator, 'Arrêtée dans le scénario de démonstration.')->fresh();
        }

        if ($target === 'COMPLETED_BY_CONTEXT') {
            return $workflow->validateByContext($transmission, $initiator, 'Réalisation validée dans le scénario de démonstration.')->fresh();
        }

        // COMPLETED_CONFIRMED : les deux rôles déclarent leur part terminée, puis confirmation conjointe.
        $workflow->declareDone($transmission, $initiator, 'Ma part est terminée dans le scénario de démonstration.');
        $workflow->declareDone($transmission, $peer, 'Ma part est terminée dans le scénario de démonstration.');

        return $workflow->confirmCompletion($transmission, $initiator, 'Transmission conforme à l’objectif d’apprentissage, dans le scénario de démonstration.')->fresh();
    }

    /**
     * Fait naître une Preuve exclusivement via ProofWorkflow, jamais par une écriture directe
     * de statut. Idempotent : retrouvée par titre.
     */
    private function proof(
        ProofWorkflow $workflow,
        string $author,
        array $data,
        string $target,
        ?string $pendingWitness = null,
        ?string $confirmedWitness = null,
        ?string $acknowledger = null,
        ?string $disputeNote = null,
        bool $archive = false,
    ): Proof {
        $existing = Proof::query()->where('title', $data['title'])->first();
        if ($existing !== null) {
            return $existing;
        }

        $proof = $workflow->submit($author, [
            'title' => $data['title'],
            'description' => $data['description'],
            'owner_type' => $data['owner_type'],
            'group_reference' => null,
            'origin_type' => $data['origin_type'],
            'origin_reference' => $data['origin_reference'],
            'visibility' => $data['visibility'],
            'occurred_at' => now()->subDays(random_int(1, 30)),
        ]);

        if ($pendingWitness !== null) {
            $workflow->inviteWitness($proof, $author, $pendingWitness);
        }

        if ($confirmedWitness !== null) {
            $witness = $workflow->inviteWitness($proof, $author, $confirmedWitness);
            $witnessActor = ProofWitness::query()->whereKey($witness->id)->firstOrFail()->core_identity_reference;
            $workflow->confirmWitness($proof->fresh(), $witnessActor, $witness);
            $proof = $proof->fresh();

            if ($target === 'DISPUTED') {
                return $workflow->dispute($proof, $witnessActor, $disputeNote)->fresh();
            }
        }

        if ($target === 'ACKNOWLEDGED' && $acknowledger !== null) {
            $proof = $workflow->acknowledgeByContext($proof->fresh(), $acknowledger)->fresh();
        }

        if ($archive) {
            $proof = $workflow->archive($proof->fresh(), $author)->fresh();
        }

        return $proof->fresh();
    }

    private function project(string $actor, string $name, string $domain, string $location): Project
    {
        $project = Project::query()->firstOrNew(['initiator_core_reference' => $actor, 'name' => $name]);
        $project->fill([
            'public_reference' => $project->public_reference ?? (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $actor,
            'summary' => 'Un projet réel destiné à construire des capacités utiles pour sa communauté.',
            'problem' => 'Des besoins concrets et documentés freinent l’activité de la communauté visée.',
            'proposed_solution' => 'Structurer une réponse progressive et mesurable à ces besoins.',
            'beneficiaries' => 'Habitants et bénévoles de la zone concernée.', 'domain' => $domain, 'participation_mode' => 'HYBRID',
            'objectives' => ['Répondre aux besoins identifiés'], 'required_capabilities' => ['Coordination de projet'],
            'required_resources' => ['Temps bénévole'], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED', 'location' => $location,
            'visibility' => Project::VISIBILITY_PUBLIC, 'status' => Project::STATUS_ADOPTED, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $actor, 'adopted_at' => now()->subDays(90),
        ]);
        $project->save();

        return $project;
    }

    private function zumra(string $actor, string $name, string $domain): ZumraGroup
    {
        $existing = ZumraGroup::query()->where('proposer_core_reference', $actor)->where('name', $name)->first();
        if ($existing !== null) {
            return $existing;
        }

        return app(ZumraGroupService::class)->create($actor, [
            'name' => $name, 'domain' => $domain,
            'founding_objective' => 'Structurer une activité artisanale collective, réelle et mesurable, au bénéfice du groupe.',
            'participation_mode' => 'PHYSICAL', 'internal_charter' => 'Respect, responsabilité partagée et transmission des savoir-faire artisanaux.',
            'assume_primary_lead' => true,
        ]);
    }

    private function joinZumra(ZumraGroup $group, string $actor): void
    {
        ZumraGroupMembership::query()->firstOrCreate(
            ['zumra_group_id' => $group->id, 'core_identity_reference' => $actor],
            ['status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $actor, 'joined_at' => now()],
        );
    }

    private function discoverableProfile(string $reference, string $name): string
    {
        $existing = PersonProfile::query()->where('core_identity_reference', $reference)->first();
        if ($existing !== null) {
            return $existing->discovery_reference;
        }

        $profile = PersonProfile::query()->create([
            'core_identity_reference' => $reference,
            'orientation_consent' => true,
            'orientation_consented_at' => now(),
            'discovery_reference' => (string) Str::uuid(),
            'discovery_display_name' => $name,
            'discovery_consent' => true,
            'discovery_consented_at' => now(),
        ]);

        return $profile->discovery_reference;
    }

    private function activateProgram(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $reference],
            ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()],
        );
    }
}
