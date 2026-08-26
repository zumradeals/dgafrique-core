<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Missions\MissionAssignmentService;
use App\Application\Missions\MissionBlockerService;
use App\Application\Missions\MissionService;
use App\Application\Missions\MissionSubmissionService;
use App\Application\Missions\MissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Mission;
use App\Models\MissionBlocker;
use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * UX-HARMONY-MISSIONS-001/002 — installation opt-in : php artisan db:seed --class=Database\\Seeders\\MissionsDirectoryDemoSeeder
 *
 * Peuple /missions avec des Mission réelles créées exclusivement via les services métier
 * existants (MissionWorkflow, MissionAssignmentService, MissionSubmissionService,
 * MissionBlockerService, MissionService) — aucune ligne SQL brute, aucun statut forcé sans passer
 * par la machine d'états réelle. Couvre : 2 Projets de domaines différents (Éducation, Santé) + 1
 * ZUMRA (domaine « Agriculture », texte libre) + 1 Besoin (sans domaine, pour éprouver le bucket
 * honnête « Sans domaine identifié ») ; 7 statuts réels (DRAFT, PROPOSED, OPEN, IN_PROGRESS,
 * BLOCKED, SUBMITTED, COMPLETED — les deux premiers hors de l'annuaire public par construction,
 * uniquement accessibles à leur créateur ou via ?scope=mine, exactement comme en production) ;
 * des échéances passées/proches/lointaines ; des Missions avec plusieurs contributeurs acceptés,
 * une offre de participation encore en attente (jamais acceptée, pour éprouver le panneau
 * Participation) et d'autres sans aucun contributeur ; une checklist partiellement complétée pour
 * tester la barre de progression et une Mission sans aucune checklist pour tester son état honnête
 * « — ». Idempotent : chaque Mission est retrouvée par son titre + son contexte, aucune
 * duplication au second lancement.
 */
final class MissionsDirectoryDemoSeeder extends Seeder
{
    private const LEADER = 'DEMO-MISSION-LEADER-01';

    private const ZUMRA_LEADER = 'DEMO-MISSION-LEADER-02';

    private const NEED_LEADER = 'DEMO-MISSION-LEADER-03';

    private const CONTRIBUTORS = ['DEMO-MISSION-CONTRIB-01', 'DEMO-MISSION-CONTRIB-02', 'DEMO-MISSION-CONTRIB-03', 'DEMO-MISSION-CONTRIB-04', 'DEMO-MISSION-CONTRIB-05'];

    public function run(): void
    {
        $this->command?->warn('UX-HARMONY-MISSIONS-001 : installation opt-in de missions de démonstration DEMO-MISSION-*');

        foreach ([self::LEADER, self::ZUMRA_LEADER, self::NEED_LEADER, ...self::CONTRIBUTORS] as $actor) {
            $this->activateProgram($actor);
        }

        $projectEducation = $this->project(self::LEADER, 'Centre de lecture communautaire de Kayes', 'EDUCATION', 'Kayes, Mali');
        $projectHealth = $this->project(self::LEADER, 'Réseau de santé mobile de Kolda', 'HEALTH', 'Kolda, Sénégal');
        $zumraAgri = $this->zumra(self::ZUMRA_LEADER, 'ZUMRA Maraîchage Solidaire Ségou', 'Agriculture');
        $need = $this->need(self::NEED_LEADER, 'Appui à la distribution de kits scolaires', 'Abomey, Bénin');

        // Une Mission ZUMRA n'est visible et propose-able qu'aux membres actifs du groupe
        // (ZumraMissionContext::canView) : les contributeurs de démonstration affectés à cette
        // ZUMRA doivent donc en être réellement membres, pas seulement titulaires du Programme.
        foreach ([self::CONTRIBUTORS[1], self::CONTRIBUTORS[3]] as $member) {
            $this->joinZumra($zumraAgri, $member);
        }

        $workflow = app(MissionWorkflow::class);
        $assignments = app(MissionAssignmentService::class);
        $submissions = app(MissionSubmissionService::class);
        $blockers = app(MissionBlockerService::class);
        $missions = app(MissionService::class);

        // ===== Contexte Projet Éducation — OPEN, une offre de participation en attente (jamais
        // acceptée), pour éprouver réellement le panneau Participation (accepter/décliner) =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectEducation->public_reference, [
            'title' => 'Recenser les écoles partenaires de Kayes',
            'description' => 'Identifier et contacter les écoles primaires susceptibles d’accueillir le centre de lecture itinérant.',
            'expected_result' => 'Une liste vérifiée d’écoles partenaires avec un point de contact par établissement.',
            'location' => 'Kayes, Mali',
            'due_at' => now()->addDays(45),
        ], target: 'OPEN', checklist: [], contributors: [], pendingOffers: [self::CONTRIBUTORS[2]]);

        // ===== Contexte Projet Éducation — DRAFT, jamais proposée =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectEducation->public_reference, [
            'title' => 'Cartographier les besoins de formation continue',
            'description' => 'Identifier les besoins de formation continue des bénévoles du centre de lecture avant de structurer une offre.',
            'expected_result' => 'Une cartographie des besoins de formation validée par l’équipe.',
            'location' => 'Kayes, Mali',
            'due_at' => now()->addDays(60),
        ], target: 'DRAFT', checklist: [], contributors: []);

        // ===== Contexte Projet Éducation — PROPOSED, en attente de décision =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectEducation->public_reference, [
            'title' => 'Organiser une collecte de fournitures scolaires',
            'description' => 'Organiser une collecte de fournitures scolaires auprès des familles du quartier pour compléter le fonds du centre.',
            'expected_result' => 'Une collecte organisée avec un lieu, une date et un objectif quantifié.',
            'location' => 'Kayes, Mali',
            'due_at' => now()->addDays(30),
        ], target: 'PROPOSED', checklist: [], contributors: []);

        // ===== Contexte Projet Éducation — IN_PROGRESS, checklist partielle, échéance proche =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectEducation->public_reference, [
            'title' => 'Constituer le premier fonds de livres',
            'description' => 'Collecter et trier les ouvrages destinés au centre de lecture, en lien avec les écoles partenaires déjà recensées.',
            'expected_result' => 'Un fonds initial de 300 ouvrages triés par âge et par thème, prêt à être installé.',
            'location' => 'Kayes, Mali',
            'due_at' => now()->addDays(5),
        ], target: 'IN_PROGRESS', checklist: [
            ['Collecter les dons de livres', true], ['Trier par tranche d’âge', true],
            ['Cataloguer les ouvrages', false], ['Installer les étagères', false],
        ], contributors: [self::CONTRIBUTORS[0], self::CONTRIBUTORS[1]]);

        // ===== Contexte Projet Santé — BLOCKED, échéance dépassée =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectHealth->public_reference, [
            'title' => 'Équiper la première tournée sanitaire mobile',
            'description' => 'Réunir le matériel médical de base nécessaire à la première tournée dans les villages isolés autour de Kolda.',
            'expected_result' => 'Une trousse médicale mobile complète, validée par l’équipe sanitaire.',
            'location' => 'Kolda, Sénégal',
            'due_at' => now()->subDays(3),
        ], target: 'BLOCKED', checklist: [
            ['Réunir le matériel de base', true], ['Valider avec l’équipe sanitaire', false],
        ], contributors: [self::CONTRIBUTORS[2]]);

        // ===== Contexte Projet Santé — SUBMITTED, résultat en attente de validation =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectHealth->public_reference, [
            'title' => 'Livrer le rapport de la première tournée sanitaire',
            'description' => 'Consolider le rapport de la première tournée sanitaire mobile pour transmission à l’équipe de coordination.',
            'expected_result' => 'Un rapport consolidé avec le nombre de personnes vues et les besoins identifiés.',
            'location' => 'Kolda, Sénégal',
            'due_at' => now()->subDays(1),
        ], target: 'SUBMITTED', checklist: [
            ['Collecter les données de terrain', true], ['Rédiger le rapport', true],
        ], contributors: [self::CONTRIBUTORS[3]]);

        // ===== Contexte Projet Santé — COMPLETED, plusieurs contributeurs =====
        $this->mission($workflow, $missions, self::LEADER, 'PROJECT', $projectHealth->public_reference, [
            'title' => 'Former les relais communautaires de santé',
            'description' => 'Organiser une session de formation pour les relais communautaires qui accompagneront les tournées sanitaires.',
            'expected_result' => 'Douze relais communautaires formés aux gestes de premier accueil.',
            'location' => 'Kolda, Sénégal',
            'due_at' => now()->subDays(20),
        ], target: 'COMPLETED', checklist: [
            ['Préparer le support de formation', true], ['Organiser la session', true], ['Évaluer les participants', true],
        ], contributors: [self::CONTRIBUTORS[0], self::CONTRIBUTORS[2], self::CONTRIBUTORS[3], self::CONTRIBUTORS[4]]);

        // ===== Contexte ZUMRA Agriculture — OPEN, échéance moyenne =====
        $this->mission($workflow, $missions, self::ZUMRA_LEADER, 'ZUMRA', $zumraAgri->public_reference, [
            'title' => 'Préparer la parcelle collective de maraîchage',
            'description' => 'Débroussailler et préparer la parcelle collective avant la prochaine saison de plantation.',
            'expected_result' => 'Une parcelle prête à recevoir les semis, avec les rangs délimités.',
            'location' => 'Ségou, Mali',
            'due_at' => now()->addDays(12),
        ], target: 'OPEN', checklist: [], contributors: []);

        // ===== Contexte ZUMRA Agriculture — IN_PROGRESS, sans checklist (progression honnêtement « — ») =====
        $this->mission($workflow, $missions, self::ZUMRA_LEADER, 'ZUMRA', $zumraAgri->public_reference, [
            'title' => 'Organiser la vente groupée au marché de Ségou',
            'description' => 'Coordonner le transport et la vente groupée de la récolte au marché local pour limiter les pertes.',
            'expected_result' => 'Une vente groupée organisée avec un point de vente réservé au marché.',
            'location' => 'Ségou, Mali',
            'due_at' => now()->addDays(2),
        ], target: 'IN_PROGRESS', checklist: [], contributors: [self::CONTRIBUTORS[1], self::CONTRIBUTORS[3]]);

        // ===== Contexte Besoin — OPEN, sans domaine identifié =====
        $this->mission($workflow, $missions, self::NEED_LEADER, 'NEED', $need->public_reference, [
            'title' => 'Organiser le transport des kits scolaires',
            'description' => 'Trouver un véhicule et un chauffeur pour acheminer les kits scolaires collectés vers les écoles bénéficiaires.',
            'expected_result' => 'Un transport confirmé avec une date de livraison arrêtée.',
            'location' => 'Abomey, Bénin',
            'due_at' => now()->addDays(9),
        ], target: 'OPEN', checklist: [], contributors: []);

        // ===== Contexte Besoin — IN_PROGRESS, un seul contributeur, checklist quasi complète =====
        $this->mission($workflow, $missions, self::NEED_LEADER, 'NEED', $need->public_reference, [
            'title' => 'Trier et emballer les kits scolaires collectés',
            'description' => 'Vérifier le contenu de chaque kit collecté et les emballer pour le transport vers les écoles.',
            'expected_result' => 'L’ensemble des kits collectés triés, complets et prêts à être transportés.',
            'location' => 'Abomey, Bénin',
            'due_at' => now()->addDays(1),
        ], target: 'IN_PROGRESS', checklist: [
            ['Vérifier le contenu de chaque kit', true], ['Compléter les kits incomplets', true], ['Emballer pour le transport', false],
        ], contributors: [self::CONTRIBUTORS[4]]);

        $this->command?->info(sprintf(
            '%d missions de démonstration : 4 contextes (2 Projets de domaines différents, 1 ZUMRA, 1 Besoin), statuts DRAFT/PROPOSED/OPEN/IN_PROGRESS/BLOCKED/SUBMITTED/COMPLETED.',
            Mission::query()->whereIn('created_by_core_reference', [self::LEADER, self::ZUMRA_LEADER, self::NEED_LEADER])->count()
        ));
    }

    /**
     * Fait naître une Mission exclusivement via les services métier réels (MissionWorkflow,
     * MissionAssignmentService, MissionSubmissionService, MissionBlockerService), jamais par
     * une écriture directe de statut. Idempotent : retrouvée par (context, title).
     *
     * UX-HARMONY-MISSIONS-002 — $pendingOffers laisse volontairement des offres à l'état OFFERED
     * (jamais acceptées) pour éprouver réellement le panneau Participation de la Fiche Mission
     * (accepter/décliner), au lieu de ne tester que des affectations déjà ACCEPTED.
     */
    private function mission(MissionWorkflow $workflow, MissionService $missionsService, string $officializer, string $contextType, string $contextReference, array $data, string $target, array $checklist, array $contributors, array $pendingOffers = []): Mission
    {
        $existing = Mission::query()
            ->where('context_type', $contextType)->where('context_reference', $contextReference)
            ->where('title', $data['title'])->first();
        if ($existing !== null) {
            return $existing;
        }

        $mission = $workflow->create($officializer, $contextType, $contextReference, $data);

        $assignmentService = app(MissionAssignmentService::class);

        if ($target === 'DRAFT') {
            return $mission->fresh();
        }

        $mission = $workflow->propose($mission, $officializer);

        if ($target === 'PROPOSED') {
            return $mission->fresh();
        }

        $mission = $workflow->officialize($mission, $officializer, ['expected_result' => $data['expected_result']]);

        foreach ($pendingOffers as $offerer) {
            $assignmentService->offer($mission, $offerer, 'EXECUTOR');
        }

        $acceptedAssignments = [];
        foreach ($contributors as $i => $contributor) {
            $role = $i === 0 && $target !== 'OPEN' ? 'COORDINATOR' : 'EXECUTOR';
            $assignment = $assignmentService->offer($mission, $contributor, $role);
            $assignment = $assignmentService->acceptOffer($mission, $officializer, $assignment);
            $acceptedAssignments[] = $assignment;
        }

        foreach ($checklist as [$label, $completed]) {
            $item = $missionsService->addChecklistItem($mission, $officializer, $label, true);
            if ($completed) {
                $missionsService->setChecklistItemCompletion($mission, $officializer, $item, true);
            }
        }

        if ($target === 'OPEN') {
            return $mission->fresh();
        }

        $mission = $workflow->start($mission, $officializer);

        if ($target === 'IN_PROGRESS') {
            return $mission->fresh();
        }

        if ($target === 'BLOCKED') {
            $blockerService = app(MissionBlockerService::class);

            return $blockerService->block($mission, $officializer, MissionBlocker::TYPE_MISSING_RESOURCE, 'Ressource manquante dans le scénario de démonstration.')->fresh();
        }

        // SUBMITTED / COMPLETED : le coordinateur/exécutant accepté soumet un résultat réel.
        $submissionService = app(MissionSubmissionService::class);
        $submitter = $acceptedAssignments[0]?->core_identity_reference ?? $officializer;
        $submissionService->submit($mission, $submitter, 'Résultat produit dans le scénario de démonstration, conforme au résultat attendu.');

        if ($target === 'SUBMITTED') {
            return $mission->fresh();
        }

        // COMPLETED : l'officialisateur valide le résultat soumis.
        $mission = $submissionService->accept($mission->fresh(), $officializer, 'Résultat conforme, validé dans le scénario de démonstration.');

        return $mission->fresh();
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
            'founding_objective' => 'Structurer une activité agricole collective, réelle et mesurable, au bénéfice du groupe.',
            'participation_mode' => 'PHYSICAL', 'internal_charter' => 'Respect, responsabilité partagée et transmission des savoir-faire agricoles.',
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

    private function need(string $actor, string $title, string $location): Need
    {
        $existing = Need::query()->where('author_core_reference', $actor)->where('title', $title)->first();
        if ($existing !== null) {
            return $existing;
        }

        return Need::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Need::OWNER_PERSON, 'owner_reference' => $actor,
            'author_core_reference' => $actor, 'title' => $title,
            'context' => 'Contexte suffisamment détaillé pour respecter la validation métier existante ici.',
            'category' => 'LOGISTICS', 'collaboration_mode' => 'ANY', 'location' => $location,
            'visibility' => Need::VISIBILITY_PUBLIC, 'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $actor,
        ]);
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
