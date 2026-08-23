<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Projects\ProjectHubPresentation;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Opt-in uniquement : php artisan db:seed --class=Database\\Seeders\\ProjectHubDemoSeeder */
final class ProjectHubDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('PROJECT-HUB-001 : installation du décor DEMO opt-in, jamais exécuté implicitement.');
        $charter = $this->charter();
        $sequence = 0;
        foreach (ProjectHubPresentation::PROJECTS as $index => $spec) {
            $sequence++;
            $founder = 'DEMO-PH-FOUNDER-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $this->member($founder, $charter);
            $group = ZumraGroup::query()->where('name', $spec['zumra'])->first();
            if (! $group) {
                $group = app(ZumraGroupService::class)->create($founder, [
                    'name' => $spec['zumra'], 'domain' => $spec['category'],
                    'founding_objective' => 'Apprendre, transmettre et agir ensemble autour de '.$spec['category'].'.',
                    'participation_mode' => 'HYBRID', 'location' => $spec['location'],
                    'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY, 'activities' => [],
                    'assume_primary_lead' => true,
                ], 99);
            }
            $project = Project::query()->firstOrCreate(['name' => $index, 'zumra_group_id' => $group->id], [
                'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_GROUP,
                'owner_reference' => $group->id, 'initiator_core_reference' => $group->proposer_core_reference,
                'summary' => $this->summary($index), 'problem' => 'Un besoin concret exprimé par les communautés locales.',
                'proposed_solution' => $this->summary($index), 'beneficiaries' => 'Communautés locales concernées',
                'domain' => $spec['domain'], 'participation_mode' => 'HYBRID', 'location' => $spec['location'],
                'objectives' => ['Produire un résultat utile et observable'], 'required_capabilities' => [],
                'required_resources' => [], 'risks' => [], 'property_regime' => 'ZUMRA_COLLECTIVE',
                'visibility' => Project::VISIBILITY_PUBLIC,
                'status' => $spec['display_status'] === 'Nouveau' ? Project::STATUS_PROPOSED : Project::STATUS_IN_PROGRESS,
                'maturity' => $spec['progress'] >= 50 ? 'ACTIVITY' : 'EXPERIMENT',
                'decided_by_core_reference' => $spec['display_status'] === 'Nouveau' ? null : $group->proposer_core_reference,
                'adopted_at' => $spec['display_status'] === 'Nouveau' ? null : now()->subMonths(2),
                'started_at' => $spec['display_status'] === 'Nouveau' ? null : now()->subMonth(),
            ]);
            for ($member = 1; $member <= $spec['members']; $member++) {
                $reference = 'DEMO-PH-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $member, 2, '0', STR_PAD_LEFT);
                ProjectTeamMember::query()->firstOrCreate(['project_id' => $project->id, 'core_identity_reference' => $reference], [
                    'role' => null, 'status' => ProjectTeamMember::STATUS_ACTIVE,
                    'entry_mode' => ProjectTeamMember::ENTRY_MODE_INVITATION,
                    'initiated_by_core_reference' => $group->proposer_core_reference,
                    'invited_at' => now()->subDays(10), 'joined_at' => now()->subDays(8),
                ]);
            }
        }
        $this->command?->info('PROJECT-HUB-001 : 8 projets démonstratifs disponibles.');
    }

    private function summary(string $name): string
    {
        return match ($name) {
            'Plateforme numérique pour artisans d’Abobo' => 'Connecter les artisans d’Abobo aux clients, faciliter la visibilité de leurs services et stimuler leur croissance.',
            'Installation solaire pour écoles rurales' => 'Fournir de l’énergie propre et durable à 20 écoles rurales pour améliorer les conditions d’étude.',
            'Centre de formation couture et design' => 'Former les jeunes filles en couture et design pour favoriser leur autonomie économique.',
            'Reboisement communautaire de Tambacounda' => 'Planter 50 000 arbres avec les communautés locales pour restaurer nos écosystèmes.',
            'Bibliothèque numérique mobile' => 'Apporter des ressources éducatives numériques dans les zones éloignées.',
            'Marché en ligne des producteurs' => 'Permettre aux producteurs locaux de vendre leurs produits directement aux consommateurs.',
            'Accès à l’eau potable' => 'Construire des points d’eau potable dans 10 villages pour améliorer la santé et la vie.',
            default => 'Accompagner 100 jeunes dans l’acquisition de compétences numériques et l’entrepreneuriat.',
        };
    }

    private function charter(): ZumraCharter
    {
        $body = str_repeat('Respect, transmission et responsabilité partagée. ', 5);
        return ZumraCharter::query()->firstOrCreate(['version' => 'DEMO-PH-2026.1'], ['title' => 'Charte ZUMRA — Démonstration', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
    }

    private function member(string $reference, ZumraCharter $charter): void
    {
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $reference], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }
}
