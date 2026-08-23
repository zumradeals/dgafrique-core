<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Models\Project;
use App\Models\ProjectDraft;
use Illuminate\Support\Facades\DB;

/**
 * UIUX-009B — brouillon déterministe de naissance d'un Projet, indépendant du Cerveau. Un
 * brouillon ne devient jamais une ligne `dg_projects` : la conversion finale converge strictement
 * vers `ProjectService::create()`, inchangé, exactement comme le fait déjà
 * `ProjectBrainProjectBirthService::confirm()` pour le Cerveau. Voir docs/design/DESIGN-INVARIANTS.md
 * et docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md §31 pour la justification complète.
 */
final class ProjectDraftService
{
    /** Ordre réel du parcours — une question utile à la fois, jamais un formulaire reconstitué. */
    public const STEPS = [
        'audience', 'nom', 'resume', 'probleme', 'solution', 'beneficiaires',
        'logistique', 'objectifs', 'besoins', 'relire',
    ];

    /** Repère court affiché dans l'indicateur de progression (jamais un pourcentage). */
    public const STEP_LABELS = [
        'audience' => 'Pour qui',
        'nom' => 'Votre idée',
        'resume' => 'Votre idée',
        'probleme' => 'Votre idée',
        'solution' => 'Votre idée',
        'beneficiaires' => 'À qui',
        'logistique' => 'Où et comment',
        'objectifs' => 'Objectifs',
        'besoins' => 'Besoins',
        'relire' => 'Relire',
    ];

    public function __construct(private readonly ProjectService $projects, private readonly ProjectConfiguration $configuration) {}

    /** Reprend le brouillon actif de l'acteur s'il existe, sinon en démarre un nouveau. Jamais deux brouillons actifs simultanés. */
    public function findOrStart(string $actor): ProjectDraft
    {
        return ProjectDraft::query()
            ->where('actor_core_reference', $actor)
            ->where('status', ProjectDraft::STATUS_DRAFT)
            ->latest('created_at')
            ->first()
            ?? ProjectDraft::query()->create([
                'actor_core_reference' => $actor,
                'status' => ProjectDraft::STATUS_DRAFT,
                'current_step' => self::STEPS[0],
                'payload' => [],
            ]);
    }

    public function assertOwner(ProjectDraft $draft, string $actor): void
    {
        abort_unless(hash_equals($draft->actor_core_reference, $actor), 404);
    }

    public static function nextStep(string $step): ?string
    {
        $index = array_search($step, self::STEPS, true);

        return $index === false || ! isset(self::STEPS[$index + 1]) ? null : self::STEPS[$index + 1];
    }

    public static function previousStep(string $step): ?string
    {
        $index = array_search($step, self::STEPS, true);

        return $index === false || $index === 0 ? null : self::STEPS[$index - 1];
    }

    /**
     * Enregistre les réponses d'une étape. `$advance` = false pour « Enregistrer et continuer plus
     * tard » (aucune validation de complétude n'est jamais imposée ici — la validation par étape
     * reste au contrôleur ; les réponses partielles sont toujours autorisées à ce stade).
     */
    public function saveStep(ProjectDraft $draft, string $step, array $answers, bool $advance): void
    {
        abort_if($draft->status !== ProjectDraft::STATUS_DRAFT, 409);

        $payload = array_replace($draft->payload ?? [], $answers);
        $next = $advance ? (self::nextStep($step) ?? $step) : $draft->current_step;

        $draft->update(['payload' => $payload, 'current_step' => $next]);
    }

    public function abandon(ProjectDraft $draft): void
    {
        abort_if($draft->status !== ProjectDraft::STATUS_DRAFT, 409);

        $draft->update(['status' => ProjectDraft::STATUS_ABANDONED, 'abandoned_at' => now()]);
    }

    /**
     * Convergence finale — idempotente : un second appel sur un brouillon déjà confirmé renvoie le
     * Projet déjà créé plutôt que d'en créer un second (double clic, retour navigateur).
     */
    public function confirm(ProjectDraft $draft, string $actor, ?string $imagePath = null): Project
    {
        $this->assertOwner($draft, $actor);

        if ($draft->status === ProjectDraft::STATUS_CONFIRMED && $draft->project_id) {
            return Project::query()->findOrFail($draft->project_id);
        }

        abort_if($draft->status === ProjectDraft::STATUS_ABANDONED, 409, 'Ce brouillon a été abandonné.');

        return DB::transaction(function () use ($draft, $actor, $imagePath): Project {
            $project = $this->projects->create($actor, $this->toProjectPayload($draft->payload ?? [], $imagePath), $this->configuration->get());
            $draft->update(['status' => ProjectDraft::STATUS_CONFIRMED, 'project_id' => $project->id, 'confirmed_at' => now()]);

            return $project;
        });
    }

    /**
     * Traduit le brouillon vers exactement la forme attendue par `ProjectService::create()`,
     * inchangée. Le régime de propriété et la visibilité ne sont jamais demandés dans ce parcours
     * (voir audit Phase A) : ils sont calculés silencieusement à partir du porteur, sans jamais
     * devenir une question. Les jalons restent hors de la naissance du Projet — `ProjectList::fromText`
     * accepte déjà une chaîne vide et ne crée alors aucun jalon (comportement inchangé).
     */
    private function toProjectPayload(array $payload, ?string $imagePath = null): array
    {
        $ownerType = $payload['owner_type'] ?? Project::OWNER_PERSON;

        return [
            'owner_type' => $ownerType,
            'group_reference' => $payload['group_reference'] ?? null,
            'source_need_reference' => null,
            'name' => (string) ($payload['name'] ?? ''),
            'summary' => (string) ($payload['summary'] ?? ''),
            'problem' => (string) ($payload['problem'] ?? ''),
            'proposed_solution' => (string) ($payload['proposed_solution'] ?? ''),
            'beneficiaries' => (string) ($payload['beneficiaries'] ?? ''),
            'domain' => (string) ($payload['domain'] ?? ''),
            'participation_mode' => (string) ($payload['participation_mode'] ?? 'PHYSICAL'),
            'location' => $payload['location'] ?? null,
            'image_path' => $imagePath,
            'objectives' => implode("\n", $payload['objectives'] ?? []),
            'required_capabilities' => implode("\n", $payload['required_capabilities'] ?? []),
            'required_resources' => implode("\n", $payload['required_resources'] ?? []),
            'risks' => implode("\n", $payload['risks'] ?? []),
            'milestones' => '',
            'property_regime' => $ownerType === Project::OWNER_GROUP ? 'ZUMRA_COLLECTIVE' : 'PERSONAL_SUPPORTED',
            'visibility' => Project::VISIBILITY_PRIVATE,
        ];
    }
}
