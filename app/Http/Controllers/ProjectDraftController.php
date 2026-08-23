<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Projects\ProjectAuthority;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectDraftService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupConfiguration;
use App\Application\Zumra\ZumraGroupService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Need;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ProjectDraft;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * UIUX-009B — parcours déterministe, progressif et sauvegardable de naissance d'un Projet.
 * Indépendant du Cerveau (ProjectBrain*, jamais référencé ici). Converge exclusivement vers
 * ProjectDraftService::confirm() -> ProjectService::create(), inchangé.
 */
final class ProjectDraftController
{
    /** Point d'entrée unique : reprend le brouillon actif de l'acteur ou en démarre un nouveau. */
    public function entry(Request $request, ProjectDraftService $drafts, ProjectService $projects): View|RedirectResponse
    {
        $identity = $this->identity($request);

        if (! $projects->hasActiveProgramMembership($identity->reference)) {
            $isAdministrator = $this->isAdministrator($identity);

            return view('projects.draft.membership-required', compact('identity', 'isAdministrator'));
        }

        $draft = $drafts->findOrStart($identity->reference);

        // Préselection depuis une fiche ZUMRA ou Mon espace (?group=...) — seulement sur un
        // brouillon tout juste démarré, jamais en écrasant une réponse déjà donnée. La ZUMRA
        // choisie devient à la fois l'ancrage (toujours requis) et la gouvernance proposée.
        if ($request->filled('group') && $draft->status === ProjectDraft::STATUS_DRAFT && empty($draft->payload)) {
            $group = ZumraGroup::query()->where('public_reference', $request->query('group'))->first();
            if ($group && app(ProjectAuthority::class)->isActiveGroupMember($group->id, $identity->reference)) {
                $drafts->saveStep($draft, 'audience', ['owner_type' => Project::OWNER_GROUP, 'zumra_group_reference' => $group->public_reference], false);
            }
        }

        return redirect()->route('projects.draft.show', [$draft, $draft->current_step]);
    }

    public function show(Request $request, ProjectDraft $draft, string $step, ProjectDraftService $drafts, ProjectConfiguration $configuration): View|RedirectResponse
    {
        $identity = $this->identity($request);
        $drafts->assertOwner($draft, $identity->reference);

        if ($draft->status === ProjectDraft::STATUS_CONFIRMED && $draft->project_id) {
            return redirect()->route('projects.show', $draft->project);
        }
        if ($draft->status !== ProjectDraft::STATUS_DRAFT) {
            return redirect()->route('projects.create');
        }
        abort_unless(in_array($step, ProjectDraftService::STEPS, true), 404);

        $isAdministrator = $this->isAdministrator($identity);
        $payload = $draft->payload ?? [];
        $groups = ZumraGroup::query()
            ->whereIn('id', ZumraGroupMembership::query()->where('core_identity_reference', $identity->reference)->where('status', ZumraGroupMembership::STATUS_ACTIVE)->pluck('zumra_group_id'))
            ->orderBy('name')->get();
        // Besoin d'origine facultatif (restauré depuis l'ancien formulaire unique, §'nom') — jamais
        // une obligation, seulement une possibilité de rattachement offerte si elle existe déjà.
        $needs = Need::query()->where('owner_type', Need::OWNER_PERSON)->where('owner_reference', $identity->reference)
            ->whereIn('status', [Need::STATUS_OPEN, Need::STATUS_IN_PROGRESS])->orderBy('title')->get();
        $config = $configuration->get();
        $previousStep = ProjectDraftService::nextStep('audience') === $step ? null : ProjectDraftService::previousStep($step);

        return view('projects.draft.step-'.$step, compact('identity', 'isAdministrator', 'draft', 'payload', 'groups', 'needs', 'config', 'previousStep'));
    }

    public function update(Request $request, ProjectDraft $draft, string $step, ProjectDraftService $drafts, ProjectService $projects, ProjectConfiguration $configuration): RedirectResponse
    {
        $identity = $this->identity($request);
        $drafts->assertOwner($draft, $identity->reference);
        abort_if($draft->status !== ProjectDraft::STATUS_DRAFT, 409);
        abort_unless(in_array($step, ProjectDraftService::STEPS, true), 404);

        $intent = (string) $request->input('_intent', 'continue');

        // Les étapes à collections dynamiques gèrent elles-mêmes ajout/retrait/« je ne sais pas
        // encore » sans jamais imposer de minimum sur les listes différables.
        if (in_array($step, ['objectifs', 'besoins'], true)) {
            return $this->updateCollectionStep($request, $draft, $step, $drafts, $intent);
        }

        if ($intent === 'back') {
            $drafts->saveStep($draft, $step, $this->rawAnswers($request, $step), false);

            return redirect()->route('projects.draft.show', [$draft, ProjectDraftService::previousStep($step) ?? $step]);
        }

        if ($intent === 'save_later') {
            $drafts->saveStep($draft, $step, $this->rawAnswers($request, $step), false);

            return redirect()->route('member.space')->with('status', 'Votre idée est enregistrée. Reprenez-la quand vous voulez depuis Mon espace.');
        }

        $answers = $this->validateStep($request, $step, $draft, $projects, $configuration->get());
        $drafts->saveStep($draft, $step, $answers, true);

        return redirect()->route('projects.draft.show', [$draft, $draft->fresh()->current_step]);
    }

    /**
     * PROJET-ZUMRA-INVARIANT-001 — naissance explicite d'une ZUMRA solo depuis le parcours Projet,
     * jamais silencieuse. Réutilise ZumraGroupService::create() à l'identique de
     * ZumraGroupController::store() (mêmes champs, même autorité) : aucune logique dupliquée,
     * seulement un point d'entrée dédié qui revient au brouillon plutôt qu'à la fiche ZUMRA.
     */
    public function newZumra(Request $request, ProjectDraft $draft, ProjectDraftService $drafts): View
    {
        $identity = $this->identity($request);
        $drafts->assertOwner($draft, $identity->reference);
        abort_if($draft->status !== ProjectDraft::STATUS_DRAFT, 409);
        $isAdministrator = $this->isAdministrator($identity);

        return view('projects.draft.zumra-nouvelle', compact('identity', 'isAdministrator', 'draft'));
    }

    public function storeZumra(Request $request, ProjectDraft $draft, ProjectDraftService $drafts, ZumraGroupService $groups, ZumraGroupConfiguration $configuration): RedirectResponse
    {
        $identity = $this->identity($request);
        $drafts->assertOwner($draft, $identity->reference);
        abort_if($draft->status !== ProjectDraft::STATUS_DRAFT, 409);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:140', Rule::unique('dg_zumra_groups', 'name')],
            'domain' => ['required', 'string', 'min:3', 'max:140'],
            'founding_objective' => ['required', 'string', 'min:40', 'max:1800'],
            'participation_mode' => ['required', Rule::in(['PHYSICAL', 'DIGITAL', 'HYBRID'])],
            'internal_charter' => ['nullable', 'string', 'min:80', 'max:6000'],
        ]);
        $data['assume_primary_lead'] = true;
        $group = $groups->create($identity->reference, $data, (int) $configuration->get()['max_simultaneous_founder_roles']);

        // Retour naturel au brouillon, jamais perdu : la ZUMRA est présélectionnée, la gouvernance
        // (owner_type) reste à préciser — le parcours ne fait jamais avancer l'étape ici.
        $drafts->saveStep($draft, 'audience', ['zumra_group_reference' => $group->public_reference], false);

        return redirect()->route('projects.draft.show', [$draft, 'audience'])->with('status', 'Votre ZUMRA « '.$group->name.' » est créée. Vous pouvez maintenant y ancrer votre projet.');
    }

    public function abandon(Request $request, ProjectDraft $draft, ProjectDraftService $drafts): RedirectResponse
    {
        $identity = $this->identity($request);
        $drafts->assertOwner($draft, $identity->reference);
        $drafts->abandon($draft);

        return redirect()->route('projects.index')->with('status', 'Cette idée a été abandonnée. Rien n’a été créé.');
    }

    public function confirm(Request $request, ProjectDraft $draft, ProjectDraftService $drafts): RedirectResponse
    {
        $identity = $this->identity($request);

        // L'illustration reste facultative et à l'ajout rapide (CAP-013/014/019) — inchangée par
        // ce parcours, seulement déplacée à sa toute dernière étape plutôt qu'au formulaire unique.
        $data = $request->validate(['image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096']]);
        $imagePath = $request->hasFile('image') ? Storage::disk('public')->putFile('projects', $request->file('image')) : null;

        $project = $drafts->confirm($draft, $identity->reference, $imagePath);

        return redirect()->route('projects.show', $project)->with('status', 'Votre projet est créé. Aucun financement n’est ouvert ici.');
    }

    /** @return array<string,mixed> */
    private function validateStep(Request $request, string $step, ProjectDraft $draft, ProjectService $projects, array $config): array
    {
        return match ($step) {
            'audience' => $this->validateAudience($request, $draft, $projects, $config),
            'nom' => $this->validateNom($request),
            'resume' => $request->validate(['summary' => ['required', 'string', 'min:40', 'max:1200']]),
            'probleme' => $request->validate(['problem' => ['required', 'string', 'min:40', 'max:2400']]),
            'solution' => $request->validate(['proposed_solution' => ['required', 'string', 'min:40', 'max:2400']]),
            'beneficiaires' => $request->validate(['beneficiaries' => ['required', 'string', 'min:20', 'max:1200']]),
            'logistique' => $request->validate([
                'domain' => ['required', Rule::in(array_keys($config['domains']))],
                'participation_mode' => ['required', Rule::in(['PHYSICAL', 'DIGITAL', 'HYBRID'])],
                'location' => ['nullable', 'string', 'max:160'],
            ]),
            'relire' => [],
            default => [],
        };
    }

    /** @return array<string,mixed> */
    private function validateNom(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:5', 'max:180'],
            'source_need_reference' => ['nullable', 'string'],
        ]);
    }

    /**
     * PROJET-ZUMRA-INVARIANT-001 — un Projet appartient toujours à une ZUMRA : zumra_group_reference
     * est requis quelle que soit la gouvernance choisie, jamais seulement pour owner_type=GROUP.
     *
     * @return array<string,mixed>
     */
    private function validateAudience(Request $request, ProjectDraft $draft, ProjectService $projects, array $config): array
    {
        $data = $request->validate([
            'owner_type' => ['required', Rule::in([Project::OWNER_PERSON, Project::OWNER_GROUP])],
            'zumra_group_reference' => ['required', 'string'],
        ]);

        $identity = $this->identity($request);
        $group = ZumraGroup::query()->where('public_reference', $data['zumra_group_reference'])->first();
        $isActiveMember = $group && app(ProjectAuthority::class)->isActiveGroupMember($group->id, $identity->reference);
        if (! $isActiveMember) {
            throw ValidationException::withMessages([
                'zumra_group_reference' => 'Choisissez une ZUMRA dont vous êtes membre actif — les autres ne sont pas proposées ici.',
            ]);
        }

        $ownerReference = $data['owner_type'] === Project::OWNER_GROUP ? $group->id : $identity->reference;
        if ($projects->activeProjectQuotaExceeded($data['owner_type'], $ownerReference, $config)) {
            throw ValidationException::withMessages([
                'owner_type' => 'Ce porteur a déjà atteint le nombre maximal de projets actifs. Terminez ou archivez-en un avant d’en proposer un nouveau.',
            ]);
        }

        return $data;
    }

    private function updateCollectionStep(Request $request, ProjectDraft $draft, string $step, ProjectDraftService $drafts, string $intent): RedirectResponse
    {
        $keys = $step === 'objectifs' ? ['objectives'] : ['required_capabilities', 'required_resources', 'risks'];
        $payload = $draft->payload ?? [];

        // « remove:{clé}:{index} » retire précisément la ligne visée avant tout filtrage —
        // l'index correspond à la position DOM au moment de l'envoi, jamais recalculé après coup.
        $removeKey = null;
        $removeIndex = null;
        if (str_starts_with($intent, 'remove:')) {
            [, $removeKey, $removeIndexRaw] = array_pad(explode(':', $intent, 3), 3, null);
            $removeIndex = $removeIndexRaw !== null ? (int) $removeIndexRaw : null;
        }

        foreach ($keys as $key) {
            $raw = (array) $request->input($key, []);
            if ($key === $removeKey && $removeIndex !== null && array_key_exists($removeIndex, $raw)) {
                unset($raw[$removeIndex]);
            }
            $items = array_values(array_filter(array_map('trim', array_map('strval', $raw)), static fn (string $v): bool => $v !== ''));
            $payload[$key] = array_slice($items, 0, 20);
        }

        if ($intent === 'back') {
            $drafts->saveStep($draft, $step, $payload, false);

            return redirect()->route('projects.draft.show', [$draft, ProjectDraftService::previousStep($step) ?? $step]);
        }

        if ($intent === 'save_later') {
            $drafts->saveStep($draft, $step, $payload, false);

            return redirect()->route('member.space')->with('status', 'Votre idée est enregistrée. Reprenez-la quand vous voulez depuis Mon espace.');
        }

        if ($step === 'objectifs' && $intent === 'continue' && empty($payload['objectives'])) {
            $drafts->saveStep($draft, $step, $payload, false);

            return redirect()->route('projects.draft.show', [$draft, $step])
                ->withErrors(['objectives' => 'Ajoutez au moins un objectif — même formulé simplement.']);
        }

        // "add"/"remove:N" restent sur la même étape sans faire avancer le parcours.
        $advance = $intent === 'continue';
        $drafts->saveStep($draft, $step, $payload, $advance);

        return redirect()->route('projects.draft.show', [$draft, $advance ? $draft->fresh()->current_step : $step]);
    }

    /** @return array<string,mixed> */
    private function rawAnswers(Request $request, string $step): array
    {
        return match ($step) {
            'audience' => $request->only(['owner_type', 'zumra_group_reference']),
            'nom' => $request->only(['name', 'source_need_reference']),
            'resume' => $request->only(['summary']),
            'probleme' => $request->only(['problem']),
            'solution' => $request->only(['proposed_solution']),
            'beneficiaires' => $request->only(['beneficiaries']),
            'logistique' => $request->only(['domain', 'participation_mode', 'location']),
            default => [],
        };
    }

    private function identity(Request $request): CoreIdentity
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity;
    }

    private function isAdministrator(CoreIdentity $identity): bool
    {
        return PortalAdministrator::query()->whereKey($identity->reference)->exists();
    }
}
