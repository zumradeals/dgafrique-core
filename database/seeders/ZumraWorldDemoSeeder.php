<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Zumra\ZumraGroupService;
use App\Models\CommunityEvent;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use App\Models\ZumraProximityShowcase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * UIUX-010 — plante le décor du carrefour ZUMRA pour qu'un environnement de démonstration
 * ressemble réellement à un réseau vivant, sans jamais inventer de logique métier : chaque
 * ZUMRA naît par `ZumraGroupService::create()` (les mêmes invariants qu'une naissance humaine
 * réelle), chaque membre supplémentaire est une vraie ligne `ZumraGroupMembership` active.
 *
 * Volontairement PAS branché sur `DatabaseSeeder::run()` (qui reste vide — GAMAD Core est
 * l'autorité d'identité, cf. commentaire du fichier) : ce seeder est un outil de démonstration
 * opt-in, à exécuter explicitement (`php artisan db:seed --class="Database\\Seeders\\ZumraWorldDemoSeeder"`)
 * sur un environnement de démonstration/staging, jamais implicitement en production. Toutes les
 * identités seedées portent le préfixe DEMO- pour rester reconnaissables comme démonstration.
 *
 * Se connecter avec l'identité DEMO-IDN-VIEWER (via le pont de session habituel de l'environnement
 * de démonstration) pour observer l'expérience personnelle complète : une ZUMRA rejointe, une
 * invitation reçue et une demande d'adhésion propre en attente, conformément aux blocs personnels
 * du mandat UIUX-010.
 */
final class ZumraWorldDemoSeeder extends Seeder
{
    private const DOMAINS = [
        'Numériques' => ['Studio Kodi', 'Digital Faso Lab', 'CodeSud Collectif', 'Nova Devs Abidjan', 'Réseau IT Solidaire'],
        'Éducation' => ["Edu'Action", 'Lire et Grandir', 'Classe Ouverte', 'Mentors du Savoir'],
        'Santé' => ['Santé Pour Tous', 'Soins de Quartier', 'Prévention Active'],
        'Agriculture' => ["Agri'Demain", 'Green Village', 'Semences Locales', 'Terre Fertile CI'],
        'Artisanat' => ['Atelier Bois & Design', 'Mains d’Or', 'Tissage Nouveau', 'Cuir & Savoir-faire'],
        'Social' => ['Solidarité Active', 'Voisins Utiles', 'Main Tendue'],
        'Culture' => ['Scène Ouverte', 'Mémoire Vivante'],
        'Autres' => ['Makers Lab Abidjan', 'Collectif Utile'],
    ];

    private const CITIES = ['Abidjan', 'Yamoussoukro', 'Dakar', 'Bamako', 'Bouaké', 'Korhogo', 'Ouagadougou', 'San-Pédro'];

    public function run(): void
    {
        $this->command?->warn('ZumraWorldDemoSeeder : ceci ajoute un réseau ZUMRA de démonstration (identités DEMO-*). '
            .'À réserver aux environnements de démonstration/staging.');

        $charter = $this->publishedCharter();
        $service = app(ZumraGroupService::class);

        $flagship = $this->flagshipGroups($service, $charter);
        $this->populateDomains($service, $charter, $flagship);
        $this->flagshipSpaceDecor($flagship);
        $this->proximityShowcase();
        $this->demoViewerPersonalState($flagship);

        $this->command?->info('ZumraWorldDemoSeeder : réseau de démonstration planté.');
    }

    /** @return array<string, ZumraGroup> les quatre ZUMRA nommées explicitement par le mandat, indexées par nom. */
    private function flagshipGroups(ZumraGroupService $service, ZumraCharter $charter): array
    {
        $specs = [
            [
                'founder' => 'DEMO-IDN-F001', 'name' => 'RAHMAN Technology', 'domain' => 'Numériques',
                'objective' => 'Nous développons et transmettons des solutions numériques utiles aux particuliers et aux professionnels.',
                'mode' => 'HYBRID', 'location' => 'Abidjan', 'members' => 15,
                'welcome' => ZumraGroup::WELCOME_ALREADY_CAPABLE,
                'activities' => [
                    ['label' => 'Formation au numérique', 'relation_to_principal' => 'Transmission des compétences directement mobilisées par l’activité numérique principale.'],
                    ['label' => 'Design et communication digitale', 'relation_to_principal' => 'Conception des interfaces et supports nécessaires aux services numériques portés par la ZUMRA.'],
                    ['label' => 'Conseil et accompagnement digital', 'relation_to_principal' => 'Accompagnement des usages qui prolonge directement les services numériques et digitaux de la ZUMRA.'],
                ],
            ],
            [
                'founder' => 'DEMO-IDN-F002', 'name' => 'Atelier Bois & Design', 'domain' => 'Artisanat',
                'objective' => 'Apprendre et transmettre la menuiserie moderne en créant des meubles utiles et durables.',
                'mode' => 'PHYSICAL', 'location' => 'Yamoussoukro', 'members' => 12,
                'welcome' => ZumraGroup::WELCOME_ALREADY_CAPABLE,
                'activities' => [['label' => 'Design', 'relation_to_principal' => 'Spécialisation de la menuiserie vers la conception de mobilier et d’objets utiles.']],
            ],
            [
                'founder' => 'DEMO-IDN-F003', 'name' => "Edu'Action", 'domain' => 'Éducation',
                'objective' => 'Soutenir l’apprentissage et l’épanouissement des jeunes à travers l’éducation et l’orientation.',
                'mode' => 'HYBRID', 'location' => 'Dakar', 'members' => 28,
                'welcome' => ZumraGroup::WELCOME_PROGRESSIVELY,
                'activities' => [],
            ],
            [
                'founder' => 'DEMO-IDN-F004', 'name' => "Agri'Demain", 'domain' => 'Agriculture',
                'objective' => 'Produire autrement, transmettre et nourrir nos communautés.',
                'mode' => 'PHYSICAL', 'location' => 'Bamako', 'members' => 18,
                'welcome' => ZumraGroup::WELCOME_ALREADY_CAPABLE,
                'activities' => [['label' => 'Agriculture urbaine', 'relation_to_principal' => 'Application de l’activité agricole principale à de petites surfaces en milieu urbain.'], ['label' => 'Transformation alimentaire', 'relation_to_principal' => 'Prolongement de la production agricole vers la transformation des récoltes.']],
            ],
        ];

        $groups = [];
        foreach ($specs as $spec) {
            $this->demoPerson($spec['founder'], $charter);
            $group = $service->create($spec['founder'], [
                'name' => $spec['name'],
                'domain' => $spec['domain'],
                'founding_objective' => $spec['objective'],
                'participation_mode' => $spec['mode'],
                'welcome_capacity' => $spec['welcome'],
                'location' => $spec['location'],
                'activities' => $spec['activities'],
                'assume_primary_lead' => true,
            ], 99);
            $this->addDemoMembers($group, $charter, $spec['members'] - 1);
            $groups[$spec['name']] = $group->fresh();
        }

        return $groups;
    }

    private function populateDomains(ZumraGroupService $service, ZumraCharter $charter, array $flagship): void
    {
        $founderSeq = 100;
        $popularActivityPool = [
            'Numériques' => ['Développement web', 'Marketing digital'],
            'Artisanat' => ['Design', 'Cuir & maroquinerie'],
            'Agriculture' => ['Agriculture urbaine', 'Transformation alimentaire'],
            'Éducation' => ['Alphabétisation', 'Soutien scolaire'],
            'Santé' => ['Prévention communautaire'],
            'Social' => ['Médiation de quartier'],
        ];

        foreach (self::DOMAINS as $domain => $names) {
            foreach ($names as $name) {
                if (isset($flagship[$name])) {
                    continue;
                }

                $founder = 'DEMO-IDN-F'.str_pad((string) $founderSeq++, 3, '0', STR_PAD_LEFT);
                $this->demoPerson($founder, $charter);
                $activities = [];
                if (isset($popularActivityPool[$domain]) && random_int(0, 1) === 1) {
                    $label = $popularActivityPool[$domain][array_rand($popularActivityPool[$domain])];
                    $activities[] = ['label' => $label, 'relation_to_principal' => 'Application concrète de l’activité « '.$domain.' » portée par cette ZUMRA.'];
                }

                $group = $service->create($founder, [
                    'name' => $name,
                    'domain' => $domain,
                    'founding_objective' => 'Une équipe qui apprend, transmet et agit autour de '.mb_strtolower($domain).', au service de sa communauté.',
                    'participation_mode' => ['PHYSICAL', 'DIGITAL', 'HYBRID'][random_int(0, 2)],
                    'welcome_capacity' => [ZumraGroup::WELCOME_ALREADY_CAPABLE, ZumraGroup::WELCOME_PROGRESSIVELY, ZumraGroup::WELCOME_NEEDS_TRANSMITTERS][random_int(0, 2)],
                    'location' => self::CITIES[array_rand(self::CITIES)],
                    'activities' => $activities,
                    'assume_primary_lead' => true,
                ], 99);
                $this->addDemoMembers($group, $charter, random_int(2, 22));
            }
        }
    }

    private function addDemoMembers(ZumraGroup $group, ZumraCharter $charter, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $reference = 'DEMO-IDN-M'.Str::upper(Str::random(10));
            $this->demoPerson($reference, $charter, joinsFeed: false);
            $rows[] = [
                'id' => (string) Str::uuid(),
                'zumra_group_id' => $group->id,
                'core_identity_reference' => $reference,
                'status' => ZumraGroupMembership::STATUS_ACTIVE,
                'entry_mode' => 'REQUEST',
                'initiated_by_core_reference' => $reference,
                'joined_at' => now()->subDays(random_int(0, 90)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('dg_zumra_group_memberships')->insert($rows);
        $group->increment('active_member_count', $count);
    }

    private function demoPerson(string $reference, ZumraCharter $charter, bool $joinsFeed = true): void
    {
        // BETA-READY-003 — $reference est une référence d'identité (ex. "DEMO-IDN-F001"), jamais
        // la clé primaire UUID de la ligne : whereKey() ici plantait sur PostgreSQL
        // (SQLSTATE[22P02]), SQLite laissant passer la comparaison par accident.
        if (ZumraProgramMembership::query()->where('core_identity_reference', $reference)->exists()) {
            return;
        }

        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $reference,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);

        if ($joinsFeed) {
            PersonProfile::query()->firstOrCreate(['core_identity_reference' => $reference], [
                'discovery_reference' => (string) Str::uuid(),
                'discovery_display_name' => 'Membre DG Afrique',
                'discovery_consent' => true,
                'discovery_consented_at' => now(),
                'city' => self::CITIES[array_rand(self::CITIES)],
                'country_code' => 'CI',
            ]);
        }
    }

    private function publishedCharter(): ZumraCharter
    {
        return ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            [
                'title' => 'Charte ZUMRA',
                'body' => str_repeat('Respect, transmission et responsabilité partagée. ', 8),
                'content_hash' => hash('sha256', 'zumra-world-demo-charter'),
                'status' => ZumraCharter::STATUS_PUBLISHED,
                'published_at' => now(),
            ],
        );
    }

    private function proximityShowcase(): void
    {
        if (ZumraProximityShowcase::query()->exists()) {
            return;
        }

        $rows = [
            ['title' => 'Code Women CI', 'activity_label' => 'Développement web', 'distance_label' => '2,4 km', 'sort_order' => 1],
            ['title' => 'Green Village', 'activity_label' => 'Agriculture urbaine', 'distance_label' => '4,1 km', 'sort_order' => 2],
            ['title' => 'Makers Lab Abidjan', 'activity_label' => 'Innovation numérique', 'distance_label' => '6,8 km', 'sort_order' => 3],
            ['title' => 'Santé Pour Tous', 'activity_label' => 'Promotion de la santé', 'distance_label' => '8,2 km', 'sort_order' => 4],
        ];
        foreach ($rows as $row) {
            ZumraProximityShowcase::query()->create($row);
        }
    }

    /**
     * ZUMRA-SPACE-002 — décor strictement opt-in de l’espace RAHMAN : objets métier réels,
     * reconnaissables par leurs références DEMO et créés seulement par ce seeder de staging.
     */
    private function flagshipSpaceDecor(array $flagship): void
    {
        $rahman = $flagship['RAHMAN Technology'] ?? null;
        if (! $rahman) {
            return;
        }

        $project = Project::query()->firstOrCreate(
            ['owner_type' => Project::OWNER_GROUP, 'owner_reference' => $rahman->id, 'name' => 'Plateforme de services numériques solidaires'],
            [
                'public_reference' => (string) Str::uuid(),
                'initiator_core_reference' => $rahman->proposer_core_reference,
                'zumra_group_id' => $rahman->id,
                'source_need_id' => null,
                'summary' => 'Créer une plateforme qui connecte les artisans d’Abobo aux clients, facilite la visibilité de leurs services et stimule leur croissance.',
                'problem' => 'Les besoins numériques locaux trouvent difficilement les bonnes compétences de proximité.',
                'proposed_solution' => 'Une plateforme sobre de mise en relation et de transmission portée collectivement.',
                'beneficiaries' => 'Plus de 500 artisans connectés',
                'domain' => 'DIGITAL',
                'participation_mode' => 'HYBRID',
                'location' => 'Abidjan, Abobo',
                'objectives' => ['Relier les artisans aux clients', 'Rendre les savoir-faire locaux visibles'],
                'required_capabilities' => ['Développement web', 'Design'],
                'required_resources' => ['Document de cadrage', 'Charte graphique', 'Plan de développement'],
                'risks' => ['Disponibilité des membres'],
                'property_regime' => 'ZUMRA_COLLECTIVE',
                'visibility' => Project::VISIBILITY_GROUP,
                'status' => Project::STATUS_ADOPTED,
                'maturity' => 'ACTIVITY',
                'decided_by_core_reference' => $rahman->proposer_core_reference,
                'adopted_at' => now()->subDays(2),
                'started_at' => now()->subMonths(3),
            ],
        );

        foreach ([
            ['title' => 'Analyse & conception', 'status' => 'COMPLETED'],
            ['title' => 'Développement MVP', 'status' => 'PLANNED'],
            ['title' => 'Tests & ajustements', 'status' => 'PLANNED'],
            ['title' => 'Lancement officiel', 'status' => 'PLANNED'],
        ] as $position => $milestone) {
            ProjectMilestone::query()->firstOrCreate(
                ['project_id' => $project->id, 'position' => $position + 1],
                $milestone + ['completed_at' => $milestone['status'] === 'COMPLETED' ? now()->subMonth() : null],
            );
        }

        // Membres de démonstration réellement actifs : aucune responsabilité n’est attribuée.
        foreach (['DEMO-IDN-F001', 'DEMO-IDN-VIEWER'] as $memberReference) {
            ProjectTeamMember::query()->firstOrCreate(
                ['project_id' => $project->id, 'core_identity_reference' => $memberReference],
                ['role' => null, 'status' => ProjectTeamMember::STATUS_ACTIVE, 'entry_mode' => ProjectTeamMember::ENTRY_MODE_INVITATION, 'initiated_by_core_reference' => $rahman->proposer_core_reference, 'invited_at' => now()->subDays(2), 'joined_at' => now()->subDay()],
            );
        }

        CommunityEvent::query()->firstOrCreate(
            [
                'organizer_type' => CommunityEvent::ORGANIZER_ZUMRA_GROUP,
                'organizer_reference' => $rahman->id,
                'title' => 'Réunion de constitution',
            ],
            [
                'public_reference' => (string) Str::uuid(),
                'organizer_core_reference' => $rahman->proposer_core_reference,
                'description' => 'Temps collectif de préparation des prochaines actions de la ZUMRA.',
                'location' => 'Abidjan et en ligne',
                'visibility' => CommunityEvent::VISIBILITY_INTERNAL,
                'status' => CommunityEvent::STATUS_SCHEDULED,
                'scheduled_at' => now()->addDays(7)->setTime(19, 0),
                'decided_by_core_reference' => $rahman->proposer_core_reference,
            ],
        );
    }

    /** Une identité de démonstration avec un état personnel complet : ZUMRA rejointe, invitation, demande en attente. */
    private function demoViewerPersonalState(array $flagship): void
    {
        $viewer = 'DEMO-IDN-VIEWER';
        $charter = $this->publishedCharter();
        $this->demoPerson($viewer, $charter);

        $rahman = $flagship['RAHMAN Technology'] ?? null;
        $agri = $flagship["Agri'Demain"] ?? null;
        $edu = $flagship["Edu'Action"] ?? null;

        if ($rahman) {
            ZumraGroupMembership::query()->firstOrCreate(
                ['zumra_group_id' => $rahman->id, 'core_identity_reference' => $viewer],
                ['status' => ZumraGroupMembership::STATUS_INVITED, 'entry_mode' => 'INVITATION', 'initiated_by_core_reference' => $rahman->proposer_core_reference, 'invited_at' => now()->subHour()],
            );
        }

        if ($agri) {
            ZumraGroupMembership::query()->firstOrCreate(
                ['zumra_group_id' => $agri->id, 'core_identity_reference' => $viewer],
                ['status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $viewer, 'joined_at' => now()->subDays(10)],
            );
            $agri->increment('active_member_count');
        }

        if ($edu) {
            $secondFounder = 'DEMO-IDN-EDU-SEEKER';
            $this->demoPerson($secondFounder, $charter, joinsFeed: false);
            ZumraGroupMembership::query()->firstOrCreate(
                ['zumra_group_id' => $edu->id, 'core_identity_reference' => $viewer],
                ['status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => $viewer, 'requested_at' => now()->subHours(3), 'motivation' => 'Je souhaite transmettre mes compétences pédagogiques.'],
            );
        }
    }
}
