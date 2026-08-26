<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * UX-HARMONY-BESOINS-001 — installation opt-in : php artisan db:seed --class=Database\\Seeders\\NeedsDirectoryDemoSeeder
 *
 * Peuple /besoins avec des Need réels (pas des fixtures JSON) pour tester la maquette dans des
 * conditions réalistes : 6 catégories, plusieurs pays/villes, 3 types de porteur (Personne, ZUMRA,
 * Projet), 3 statuts réellement supportés dans l'annuaire (OPEN, IN_PROGRESS, RESOLVED — PROPOSED
 * n'est jamais visible hors de son auteur et n'apporterait rien à cette démonstration), anciennetés
 * variées (certaines dépassent le seuil « urgent » de 30 jours). Idempotent : chaque ligne est
 * retrouvée par un public_reference déterministe, aucune duplication au second lancement.
 */
final class NeedsDirectoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('UX-HARMONY-BESOINS-001 : installation opt-in de besoins de démonstration DEMO-NEED-*');

        $group1 = $this->group('DEMO-NEED-LEADER-01', 'ZUMRA Ateliers Numériques Bouaké');
        $group2 = $this->group('DEMO-NEED-LEADER-02', 'ZUMRA Couture Solidaire Yamoussoukro');
        $project1 = $this->project('DEMO-NEED-LEADER-01', 'Centre d’initiation informatique de Bouaké', 'Bouaké, Côte d’Ivoire');
        $project2 = $this->project('DEMO-NEED-LEADER-02', 'Réseau d’irrigation goutte-à-goutte de Ségou', 'Ségou, Mali');

        $rows = [
            ['SKILL', 'Formatrice en couture pour l’atelier de Yamoussoukro', 'Un atelier solidaire cherche une personne pour transmettre des techniques de couture avancées à un groupe de dix apprenties.', 'Yamoussoukro, Côte d’Ivoire', 'PERSON', 'OPEN', 2],
            ['SKILL', 'Développeur mobile pour une application agricole', 'Une coopérative maraîchère a besoin d’une personne pour finaliser une application de suivi des récoltes déjà à moitié construite.', 'Thiès, Sénégal', 'PERSON', 'OPEN', 9],
            ['SKILL', 'Comptable bénévole pour une association de Kaolack', 'L’association tient sa comptabilité à la main depuis trois ans et cherche un appui ponctuel pour la structurer.', 'Kaolack, Sénégal', 'PERSON', 'OPEN', 38],
            ['SKILL', 'Coach en prise de parole pour de jeunes leaders', 'Un groupe de douze jeunes engagés dans la vie associative locale souhaite progresser en prise de parole en public.', 'Cotonou, Bénin', 'PERSON', 'IN_PROGRESS', 14],
            ['PARTNER', 'Partenaire logistique pour une distribution de kits scolaires', 'Cinq cents kits scolaires sont prêts mais aucun partenaire de transport n’a encore été identifié pour les acheminer.', 'Ouagadougou, Burkina Faso', 'PERSON', 'OPEN', 5],
            ['PARTNER', 'Organisation partenaire pour un programme de vaccination', 'Une clinique communautaire cherche une organisation partenaire pour élargir une campagne de vaccination déjà lancée.', 'Bamako, Mali', 'PERSON', 'OPEN', 44],
            ['PARTNER', 'Partenaire local pour une implantation à Sikasso', 'Un porteur de projet cherche un partenaire déjà implanté à Sikasso pour faciliter les premières démarches locales.', 'Sikasso, Mali', 'PERSON', 'RESOLVED', 60],
            ['TRAINING', 'Formation en maintenance de kits solaires', 'Les kits solaires installés dans trois kiosques communautaires n’ont plus de technicien formé pour leur entretien courant.', 'Tambacounda, Sénégal', 'PERSON', 'OPEN', 3],
            ['TRAINING', 'Formation d’initiation informatique pour jeunes de Bouaké', 'Un centre associatif souhaite proposer une première session d’initiation informatique à des jeunes non scolarisés.', 'Bouaké, Côte d’Ivoire', 'PROJECT', 'OPEN', 7, 1],
            ['TRAINING', 'Formation en gestion financière pour artisanes', 'Un groupement d’artisanes textiles cherche une formation courte en gestion de trésorerie adaptée à leur activité.', 'Korhogo, Côte d’Ivoire', 'PERSON', 'IN_PROGRESS', 21],
            ['TRAINING', 'Formation aux premiers secours pour bénévoles ruraux', 'Une équipe de bénévoles intervenant en zone rurale n’a reçu aucune formation aux gestes de premier secours.', 'Kayes, Mali', 'PERSON', 'OPEN', 47],
            ['RESOURCE', 'Local pour un centre d’initiation informatique à Bouaké', 'Le centre dispose déjà de dix ordinateurs mais cherche un local sécurisé et accessible pour accueillir les sessions.', 'Bouaké, Côte d’Ivoire', 'PROJECT', 'OPEN', 4, 1],
            ['RESOURCE', 'Mobilier scolaire pour l’école primaire de Djidja', 'L’école primaire manque de tables-bancs en nombre suffisant pour accueillir tous les élèves inscrits cette année.', 'Djidja, Bénin', 'PERSON', 'RESOLVED', 55],
            ['RESOURCE', 'Kits solaires pour des kiosques communautaires', 'Trois kiosques communautaires fonctionnent encore sans électricité stable et cherchent des kits solaires autonomes.', 'Tambacounda, Sénégal', 'PERSON', 'OPEN', 1],
            ['RESOURCE', 'Semences améliorées pour une coopérative maraîchère', 'La coopérative a perdu une partie de sa récolte et cherche des semences résistantes pour la prochaine saison.', 'Ségou, Mali', 'PROJECT', 'IN_PROGRESS', 18, 2],
            ['RESOURCE', 'Bibliothèque mobile pour un quartier périphérique', 'Un collectif de quartier souhaite constituer un fonds de livres pour une bibliothèque mobile destinée aux enfants.', 'Kigali, Rwanda', 'PERSON', 'OPEN', 12],
            ['TECHNICAL', 'Forage d’un puits pour le village de Farako', 'Les familles du village parcourent plusieurs kilomètres chaque jour pour accéder à un point d’eau potable.', 'Ségou, Mali', 'PROJECT', 'OPEN', 6, 2],
            ['TECHNICAL', 'Appui technique pour un réseau d’irrigation goutte-à-goutte', 'Le matériel d’irrigation est arrivé sur site mais son installation nécessite un appui technique spécialisé.', 'Ségou, Mali', 'PROJECT', 'OPEN', 10, 2],
            ['TECHNICAL', 'Maintenance de panneaux solaires à Koudougou', 'Les panneaux solaires installés l’an dernier montrent des signes de baisse de rendement et nécessitent une inspection.', 'Koudougou, Burkina Faso', 'PERSON', 'OPEN', 33],
            ['TECHNICAL', 'Appui à la réparation d’une pompe à eau', 'La pompe à eau du dispensaire est en panne depuis deux semaines et aucun technicien local ne peut la réparer.', 'Kolda, Sénégal', 'PERSON', 'RESOLVED', 40],
            ['LOGISTICS', 'Transport de matériel médical vers Ouahigouya', 'Du matériel médical est disponible à Ouagadougou mais son acheminement vers Ouahigouya reste à organiser.', 'Ouahigouya, Burkina Faso', 'PERSON', 'OPEN', 8],
            ['LOGISTICS', 'Appui logistique pour une collecte de vivres à Thiès', 'Une collecte de vivres est organisée mais aucun véhicule n’est encore disponible pour centraliser les dons.', 'Thiès, Sénégal', 'GROUP', 'OPEN', 2, 1],
            ['LOGISTICS', 'Véhicule pour des tournées sanitaires rurales', 'Une équipe sanitaire mobile a besoin d’un véhicule fiable pour couvrir les villages les plus isolés de sa zone.', 'Accra, Ghana', 'PERSON', 'IN_PROGRESS', 26],
            ['LOGISTICS', 'Entreposage temporaire pour du matériel scolaire', 'Le matériel scolaire collecté doit être stocké en lieu sûr en attendant sa distribution dans les écoles concernées.', 'Yamoussoukro, Côte d’Ivoire', 'GROUP', 'RESOLVED', 70, 2],
            ['SKILL', 'Photographe bénévole pour documenter un projet solaire', 'Le projet solaire de Tambacounda cherche une personne pour documenter son avancement en images.', 'Tambacounda, Sénégal', 'PERSON', 'OPEN', 0],
            ['PARTNER', 'Partenaire pédagogique pour un programme d’alphabétisation', 'Un programme d’alphabétisation pour adultes cherche un partenaire pédagogique pour enrichir ses supports.', 'Man, Côte d’Ivoire', 'PERSON', 'OPEN', 52],
        ];

        $groups = ['DEMO-NEED-LEADER-01' => $group1, 'DEMO-NEED-LEADER-02' => $group2];
        $projects = [1 => $project1, 2 => $project2];
        $groupOwners = ['DEMO-NEED-LEADER-01', 'DEMO-NEED-LEADER-02'];

        foreach ($rows as $i => [$category, $title, $context, $location, $ownerType, $status, $ageDays]) {
            $projectIndex = $ownerType === 'PROJECT' || $ownerType === 'GROUP' ? ($rows[$i][7] ?? 1) : null;
            $ref = sprintf('93000000-0000-4000-8000-%012d', $i + 1);
            $createdAt = Carbon::now()->subDays($ageDays)->subHours($i);

            $ownerReference = match ($ownerType) {
                'GROUP' => $groups[$groupOwners[$projectIndex - 1]]->id,
                'PROJECT' => $projects[$projectIndex]->id,
                default => 'DEMO-NEED-PERSON-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            };
            $author = match ($ownerType) {
                'GROUP' => $groupOwners[$projectIndex - 1],
                'PROJECT' => $projectIndex === 1 ? 'DEMO-NEED-LEADER-01' : 'DEMO-NEED-LEADER-02',
                default => $ownerReference,
            };

            $isResolved = $status === 'RESOLVED';
            $need = Need::query()->firstOrNew(['public_reference' => $ref]);
            $need->fill([
                'owner_type' => $ownerType, 'owner_reference' => $ownerReference, 'author_core_reference' => $author,
                'title' => $title, 'context' => $context, 'category' => $category,
                'collaboration_mode' => ['LOCAL', 'DISTANCE', 'ANY'][$i % 3], 'location' => $location,
                'visibility' => Need::VISIBILITY_PUBLIC, 'status' => $status,
                'decided_by_core_reference' => $author,
                'resolution_note' => $isResolved ? 'Besoin réellement satisfait dans le scénario de démonstration.' : null,
                'published_at' => $createdAt, 'resolved_at' => $isResolved ? $createdAt->copy()->addDays(5) : null,
            ]);
            $need->created_at = $createdAt;
            $need->updated_at = $isResolved ? $createdAt->copy()->addDays(5) : $createdAt;
            $need->save();
        }

        $this->command?->info(sprintf('%d besoins : 6 catégories, %d localisations, 3 types de porteur, statuts OPEN/IN_PROGRESS/RESOLVED.', count($rows), collect($rows)->pluck(3)->unique()->count()));
    }

    private function group(string $leader, string $name): ZumraGroup
    {
        $group = ZumraGroup::query()->firstOrNew(['proposer_core_reference' => $leader, 'name' => $name]);
        $group->fill([
            'public_reference' => $group->public_reference ?? (string) Str::uuid(),
            'slug' => $group->slug ?? Str::slug($name).'-demo-need',
            'domain' => 'Numérique', 'founding_objective' => 'Former une équipe qui transmet des outils utiles et agit concrètement pour sa communauté.',
            'participation_mode' => 'HYBRID', 'internal_charter' => 'Respect, responsabilité et transmission.',
            'state' => ZumraGroup::STATE_ACTIVE, 'maturity' => ZumraGroup::MATURITY_EMERGING, 'active_member_count' => 4,
        ]);
        $group->save();

        return $group;
    }

    private function project(string $actor, string $name, string $location): Project
    {
        $project = Project::query()->firstOrNew(['initiator_core_reference' => $actor, 'name' => $name]);
        $project->fill([
            'public_reference' => $project->public_reference ?? (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $actor,
            'summary' => 'Un projet réel destiné à construire des capacités utiles pour sa communauté.',
            'problem' => 'Des besoins concrets et documentés freinent l’activité de la communauté visée.',
            'proposed_solution' => 'Structurer une réponse progressive et mesurable à ces besoins.',
            'beneficiaries' => 'Habitants et bénévoles de la zone concernée.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Répondre aux besoins identifiés'], 'required_capabilities' => ['Coordination de projet'],
            'required_resources' => ['Temps bénévole'], 'risks' => [], 'property_regime' => 'PERSONAL_SUPPORTED', 'location' => $location,
            'visibility' => Project::VISIBILITY_PUBLIC, 'status' => Project::STATUS_ADOPTED, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $actor, 'adopted_at' => now()->subDays(90),
        ]);
        $project->save();

        return $project;
    }
}
