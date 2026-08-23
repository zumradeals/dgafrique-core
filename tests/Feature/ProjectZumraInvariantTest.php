<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Missions\MissionWorkflow;
use App\Application\ProjectBrain\ProjectBrainProjectBirthService;
use App\Application\Projects\ProjectAuthority;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectFundingService;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectBrainIntent;
use App\Models\ProjectDraft;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * PROJET-ZUMRA-INVARIANT-001 — tout nouveau Projet appartient toujours à une ZUMRA
 * (`zumra_group_id`), orthogonalement à `owner_type`/`owner_reference` qui restent la seule
 * question de gouvernance. Aucune ZUMRA n'est jamais créée silencieusement ; les Projects
 * historiques sans ancrage restent lisibles et fonctionnels. Couvre les 12 catégories du mandat.
 */
final class ProjectZumraInvariantTest extends TestCase
{
    use RefreshDatabase;

    // ===== 1. Nouveau Projet PERSON avec ZUMRA obligatoire =====

    public function test_a_new_person_owned_project_requires_a_zumra_anchor(): void
    {
        $this->activateProgram('IDN-INV-PERSON');

        $this->expectException(HttpException::class);
        app(ProjectService::class)->create('IDN-INV-PERSON', $this->payload(['zumra_group_reference' => null]), (new ProjectConfiguration)->defaults());
    }

    public function test_a_new_person_owned_project_succeeds_once_anchored(): void
    {
        $this->activateProgram('IDN-INV-PERSON2');
        $zumra = $this->zumraFor('IDN-INV-PERSON2');

        $project = app(ProjectService::class)->create('IDN-INV-PERSON2', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());

        self::assertSame($zumra->id, $project->zumra_group_id);
        self::assertSame(Project::OWNER_PERSON, $project->owner_type);
        self::assertSame('IDN-INV-PERSON2', $project->owner_reference);
    }

    public function test_an_unavailable_zumra_reference_is_rejected(): void
    {
        $this->activateProgram('IDN-INV-BADREF');

        $this->expectException(ModelNotFoundException::class);
        app(ProjectService::class)->create('IDN-INV-BADREF', $this->payload(['zumra_group_reference' => (string) Str::uuid()]), (new ProjectConfiguration)->defaults());
    }

    // ===== 2. Nouveau Projet GROUP avec ZUMRA =====

    public function test_a_new_group_owned_project_is_anchored_in_its_governing_zumra(): void
    {
        $this->activateProgram('IDN-INV-LEADER');
        $group = app(ZumraGroupService::class)->create('IDN-INV-LEADER', $this->zumraFields('IDN-INV-LEADER'));

        $project = app(ProjectService::class)->create('IDN-INV-LEADER', $this->payload([
            'owner_type' => 'GROUP', 'group_reference' => $group->public_reference, 'property_regime' => 'ZUMRA_COLLECTIVE',
        ]), (new ProjectConfiguration)->defaults());

        self::assertSame(Project::OWNER_GROUP, $project->owner_type);
        self::assertSame($group->id, $project->owner_reference);
        self::assertSame($group->id, $project->zumra_group_id, 'La ZUMRA gouvernante est aussi la ZUMRA d’ancrage — pas deux choix distincts.');
    }

    // ===== 3. Initiateur distinct de l'ancrage =====

    public function test_initiator_stays_distinct_from_the_zumra_anchor_and_from_the_owner(): void
    {
        $this->activateProgram('IDN-INV-INITIATOR');
        // La ZUMRA existe et a un autre membre actif que l'initiateur du Projet.
        $group = app(ZumraGroupService::class)->create('IDN-INV-FOUNDER', $this->zumraFields('IDN-INV-FOUNDER'));
        ZumraGroupMembership::query()->create(['zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-INV-INITIATOR', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-INV-INITIATOR', 'joined_at' => now()]);

        $project = app(ProjectService::class)->create('IDN-INV-INITIATOR', $this->payload(['zumra_group_reference' => $group->public_reference]), (new ProjectConfiguration)->defaults());

        self::assertSame('IDN-INV-INITIATOR', $project->initiator_core_reference);
        self::assertSame('IDN-INV-INITIATOR', $project->owner_reference, 'Gouvernance personnelle : le porteur reste l’initiateur.');
        self::assertSame($group->id, $project->zumra_group_id, 'Ancrage : une ZUMRA fondée par quelqu’un d’autre, dont l’initiateur est simplement membre actif.');
        self::assertNotSame($project->zumra_group_id, $project->owner_reference, 'L’ancrage (une ZUMRA) et la gouvernance personnelle (une Personne) restent deux identités de nature différente.');
    }

    // ===== 4. ZUMRA solo =====

    public function test_a_solo_zumra_is_a_valid_anchor_without_any_founder_roles_filled(): void
    {
        $this->activateProgram('IDN-INV-SOLO');
        $solo = app(ZumraGroupService::class)->create('IDN-INV-SOLO', $this->zumraFields('IDN-INV-SOLO'));

        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $solo->state);
        self::assertSame(1, $solo->active_member_count);

        $project = app(ProjectService::class)->create('IDN-INV-SOLO', $this->payload(['zumra_group_reference' => $solo->public_reference]), (new ProjectConfiguration)->defaults());

        self::assertSame($solo->id, $project->zumra_group_id, 'Une ZUMRA en constitution, non validée, reste un ancrage parfaitement valide.');
    }

    // ===== 5. Membre sans ZUMRA =====

    public function test_a_member_with_no_zumra_is_never_offered_a_project_for_myself_escape(): void
    {
        $this->activateProgram('IDN-INV-NOZUMRA');
        $this->signIn('IDN-INV-NOZUMRA');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-NOZUMRA')->firstOrFail();
        $content = $this->get(route('projects.draft.show', [$draft, 'audience']))->assertOk()->getContent();

        self::assertStringNotContainsString('Pour moi', $content, 'Aucune échappatoire « projet pour moi-même » sans ZUMRA.');
        self::assertStringContainsString('Vous n’êtes pas encore membre d’une ZUMRA', $content);
        self::assertStringContainsString(route('projects.draft.zumra.create', $draft), $content);

        // Et le serveur l'interdit structurellement, pas seulement la copie de l'écran.
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', '_intent' => 'continue'])
            ->assertSessionHasErrors('zumra_group_reference');
    }

    // ===== 6. Naissance explicite ZUMRA → retour au brouillon Projet =====

    public function test_starting_a_solo_zumra_from_the_draft_returns_to_the_same_draft_without_loss(): void
    {
        $this->activateProgram('IDN-INV-RETURN');
        $this->signIn('IDN-INV-RETURN');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-RETURN')->firstOrFail();

        $response = $this->post(route('projects.draft.zumra.store', $draft), $this->zumraFields('IDN-INV-RETURN'));
        $response->assertRedirect(route('projects.draft.show', [$draft, 'audience']));

        $draft->refresh();
        self::assertSame(ProjectDraft::STATUS_DRAFT, $draft->status, 'Le brouillon Projet n’est jamais perdu pendant ce détour.');
        self::assertNotEmpty($draft->payload['zumra_group_reference'] ?? null, 'La ZUMRA vient de naître et est déjà présélectionnée au retour.');

        $group = ZumraGroup::query()->where('public_reference', $draft->payload['zumra_group_reference'])->firstOrFail();
        self::assertSame('IDN-INV-RETURN', $group->proposer_core_reference);
        self::assertTrue(app(ProjectAuthority::class)->isActiveGroupMember($group->id, 'IDN-INV-RETURN'));
    }

    public function test_no_zumra_is_ever_created_silently_by_the_project_flow(): void
    {
        $this->activateProgram('IDN-INV-NOSILENT');
        $this->signIn('IDN-INV-NOSILENT');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-NOSILENT')->firstOrFail();
        $this->get(route('projects.draft.show', [$draft, 'audience']));

        self::assertSame(0, ZumraGroup::query()->count(), 'Visiter l’étape ne crée jamais de ZUMRA : seule une soumission explicite du formulaire dédié le fait.');
    }

    // ===== 7. Reprise du brouillon =====

    public function test_the_draft_resumes_correctly_after_the_zumra_detour(): void
    {
        $this->activateProgram('IDN-INV-RESUME2');
        $this->signIn('IDN-INV-RESUME2');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-RESUME2')->firstOrFail();
        $this->post(route('projects.draft.zumra.store', $draft), $this->zumraFields('IDN-INV-RESUME2'));
        $draft->refresh();
        $zumraReference = $draft->payload['zumra_group_reference'];

        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', 'zumra_group_reference' => $zumraReference, '_intent' => 'continue'])->assertRedirect();
        $this->post(route('projects.draft.update', [$draft, 'nom']), ['name' => 'Idée reprise après détour ZUMRA', '_intent' => 'continue'])->assertRedirect();

        // Un nouveau passage par l'entrée reprend le même brouillon, à l'étape où il en était.
        $this->get(route('projects.create'))->assertRedirect(route('projects.draft.show', [$draft, 'resume']));
        $draft->refresh();
        self::assertSame('resume', $draft->current_step);
        self::assertSame('Idée reprise après détour ZUMRA', $draft->payload['name']);
    }

    // ===== 8. Besoin d'origine =====

    public function test_a_project_can_still_be_attached_to_an_origin_need(): void
    {
        $this->activateProgram('IDN-INV-NEEDORIGIN');
        $this->signIn('IDN-INV-NEEDORIGIN');
        $need = Need::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Need::OWNER_PERSON, 'owner_reference' => 'IDN-INV-NEEDORIGIN',
            'author_core_reference' => 'IDN-INV-NEEDORIGIN', 'title' => 'Un local pour démarrer',
            'context' => 'Contexte suffisamment détaillé pour respecter la validation métier existante ici.',
            'category' => 'RESOURCE', 'collaboration_mode' => 'ANY', 'visibility' => Need::VISIBILITY_PUBLIC, 'status' => Need::STATUS_OPEN,
        ]);
        $zumra = $this->zumraFor('IDN-INV-NEEDORIGIN');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-NEEDORIGIN')->firstOrFail();
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', 'zumra_group_reference' => $zumra->public_reference, '_intent' => 'continue']);

        $content = $this->get(route('projects.draft.show', [$draft, 'nom']))->assertOk()->getContent();
        self::assertStringContainsString('Un local pour démarrer', $content, 'Le besoin existant est proposé, jamais imposé.');

        $this->post(route('projects.draft.update', [$draft, 'nom']), ['name' => 'Projet issu d’un besoin', 'source_need_reference' => $need->public_reference, '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'resume']), ['summary' => str_repeat('Résumé suffisant pour ce test précis. ', 2), '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'probleme']), ['problem' => str_repeat('Problème suffisant pour ce test précis. ', 2), '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'solution']), ['proposed_solution' => str_repeat('Solution suffisante pour ce test précis. ', 2), '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'beneficiaires']), ['beneficiaries' => 'Des bénéficiaires suffisamment décrits pour ce test.', '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'logistique']), ['domain' => 'EDUCATION', 'participation_mode' => 'PHYSICAL', '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Un objectif'], '_intent' => 'continue']);
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue']);
        $this->post(route('projects.draft.confirm', $draft));

        $project = Project::query()->where('name', 'Projet issu d’un besoin')->firstOrFail();
        self::assertSame($need->id, $project->source_need_id);
    }

    public function test_the_origin_need_field_stays_optional(): void
    {
        $this->activateProgram('IDN-INV-NONEED');
        $this->signIn('IDN-INV-NONEED');
        $zumra = $this->zumraFor('IDN-INV-NONEED');

        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', 'IDN-INV-NONEED')->firstOrFail();
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', 'zumra_group_reference' => $zumra->public_reference, '_intent' => 'continue']);

        $this->post(route('projects.draft.update', [$draft, 'nom']), ['name' => 'Projet sans besoin d’origine', '_intent' => 'continue'])
            ->assertSessionDoesntHaveErrors();
    }

    // ===== 9. Cerveau sans ZUMRA =====

    public function test_the_brain_cannot_confirm_a_project_without_a_chosen_zumra(): void
    {
        $this->activateProgram('IDN-INV-BRAIN');
        $intent = $this->readyBrainIntent('IDN-INV-BRAIN', withZumra: false);

        $birth = app(ProjectBrainProjectBirthService::class);
        self::assertTrue($birth->contentReady($intent), 'Le contenu narratif est bien structuré par le Cerveau.');
        self::assertFalse($birth->ready($intent), 'Mais la ZUMRA reste une décision humaine manquante.');

        $this->expectException(HttpException::class);
        $birth->confirm($intent, 'IDN-INV-BRAIN');
    }

    public function test_the_brain_shows_an_explicit_human_decision_when_no_zumra_is_chosen(): void
    {
        $this->activateProgram('IDN-INV-BRAINUI');
        $this->signIn('IDN-INV-BRAINUI');
        $intent = $this->readyBrainIntent('IDN-INV-BRAINUI', withZumra: false);

        $content = $this->get(route('projects.brain.start.show', $intent))->assertOk()->getContent();

        self::assertStringContainsString('Aucun projet ne naît sans ZUMRA', $content);
        self::assertStringContainsString(route('projects.brain.start.zumra.create', $intent), $content);
        self::assertStringNotContainsString('Prêt à devenir un vrai Projet', $content, 'Le clic de confirmation ne doit jamais être présenté avant la ZUMRA.');
    }

    public function test_the_brain_never_creates_a_zumra_automatically(): void
    {
        $this->activateProgram('IDN-INV-BRAINNOAUTO');
        $this->signIn('IDN-INV-BRAINNOAUTO');
        $this->readyBrainIntent('IDN-INV-BRAINNOAUTO', withZumra: false);

        self::assertSame(0, ZumraGroup::query()->count());
    }

    public function test_the_brain_confirms_once_a_real_zumra_is_explicitly_chosen(): void
    {
        $this->activateProgram('IDN-INV-BRAINOK');
        $this->signIn('IDN-INV-BRAINOK');
        $intent = $this->readyBrainIntent('IDN-INV-BRAINOK', withZumra: true);

        $response = $this->post(route('projects.brain.start.confirm', $intent));
        $project = Project::query()->where('name', 'Projet Cerveau ancré')->firstOrFail();
        $response->assertRedirect(route('projects.brain.show', $project));
        self::assertNotNull($project->zumra_group_id);
    }

    public function test_the_brain_can_start_its_own_solo_zumra_and_return_to_the_conversation(): void
    {
        $this->activateProgram('IDN-INV-BRAINZUMRA');
        $this->signIn('IDN-INV-BRAINZUMRA');
        $intent = $this->readyBrainIntent('IDN-INV-BRAINZUMRA', withZumra: false);

        $response = $this->post(route('projects.brain.start.zumra.store', $intent), $this->zumraFields('IDN-INV-BRAINZUMRA'));
        $response->assertRedirect(route('projects.brain.start.show', $intent));

        $intent->refresh();
        self::assertNotEmpty($intent->context['zumra_group_reference'] ?? null);
        self::assertSame(0, ProjectDraft::query()->count(), 'Le Cerveau ne crée jamais de ProjectDraft.');

        $birth = app(ProjectBrainProjectBirthService::class);
        self::assertTrue($birth->ready($intent), 'La conversation redevient confirmable une fois la ZUMRA choisie.');
    }

    // ===== 10. Ancien Projet PERSON sans zumra_group_id toujours fonctionnel =====

    public function test_a_legacy_project_without_a_zumra_anchor_stays_fully_readable(): void
    {
        $this->activateProgram('IDN-INV-LEGACY');
        $legacy = Project::query()->create($this->rawLegacyAttributes('IDN-INV-LEGACY'));
        self::assertNull($legacy->zumra_group_id);

        $this->signIn('IDN-INV-LEGACY');
        $this->get(route('projects.show', $legacy))->assertOk();

        self::assertTrue(app(ProjectAuthority::class)->canView($legacy, 'IDN-INV-LEGACY'));
        self::assertTrue(app(ProjectAuthority::class)->canDecide($legacy, 'IDN-INV-LEGACY'));
    }

    // ===== 11. Permissions / Fil =====

    public function test_group_visibility_is_granted_through_the_zumra_anchor_for_group_governed_projects(): void
    {
        $this->activateProgram('IDN-INV-VIS-OWNER');
        $this->activateProgram('IDN-INV-VIS-MEMBER');
        $this->activateProgram('IDN-INV-VIS-OUTSIDER');
        $zumra = app(ZumraGroupService::class)->create('IDN-INV-VIS-OWNER', $this->zumraFields('IDN-INV-VIS-OWNER'));
        ZumraGroupMembership::query()->create(['zumra_group_id' => $zumra->id, 'core_identity_reference' => 'IDN-INV-VIS-MEMBER', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-INV-VIS-MEMBER', 'joined_at' => now()]);

        $project = app(ProjectService::class)->create('IDN-INV-VIS-OWNER', $this->payload([
            'owner_type' => 'GROUP', 'group_reference' => $zumra->public_reference, 'property_regime' => 'ZUMRA_COLLECTIVE', 'visibility' => 'GROUP',
        ]), (new ProjectConfiguration)->defaults());

        $authority = app(ProjectAuthority::class);
        self::assertTrue($authority->canView($project, 'IDN-INV-VIS-MEMBER'), 'Un membre actif de la ZUMRA d’ancrage voit le Projet.');
        self::assertFalse($authority->canView($project, 'IDN-INV-VIS-OUTSIDER'), 'Un non-membre ne le voit pas.');
    }

    public function test_membership_in_the_anchor_zumra_alone_never_leaks_a_private_person_governed_project(): void
    {
        // Rappel d'un comportement préexistant, inchangé par cet invariant : le repliement
        // owner_type=PERSON + visibilité GROUP -> PRIVATE reste entier (ProjectService::create()) ;
        // l'ancrage ne devient jamais, à lui seul, une porte de visibilité supplémentaire.
        $this->activateProgram('IDN-INV-VIS-PERSON');
        $this->activateProgram('IDN-INV-VIS-ZMEMBER');
        $zumra = app(ZumraGroupService::class)->create('IDN-INV-VIS-PERSON', $this->zumraFields('IDN-INV-VIS-PERSON'));
        ZumraGroupMembership::query()->create(['zumra_group_id' => $zumra->id, 'core_identity_reference' => 'IDN-INV-VIS-ZMEMBER', 'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST', 'initiated_by_core_reference' => 'IDN-INV-VIS-ZMEMBER', 'joined_at' => now()]);

        $project = app(ProjectService::class)->create('IDN-INV-VIS-PERSON', $this->payload([
            'zumra_group_reference' => $zumra->public_reference, 'visibility' => 'PRIVATE',
        ]), (new ProjectConfiguration)->defaults());

        self::assertFalse(app(ProjectAuthority::class)->canView($project, 'IDN-INV-VIS-ZMEMBER'), 'Être membre de la ZUMRA d’ancrage ne rend jamais visible un Projet privé gouverné par une seule Personne.');
    }

    public function test_group_visibility_never_leaks_on_a_legacy_project_without_an_anchor(): void
    {
        $this->activateProgram('IDN-INV-VIS-LEGACY');
        $legacy = Project::query()->create($this->rawLegacyAttributes('IDN-INV-VIS-LEGACY', ['visibility' => 'GROUP']));

        self::assertFalse(app(ProjectAuthority::class)->canView($legacy, 'IDN-INV-SOMEONE-ELSE'));
    }

    // ===== 12. Non-régression Missions / Matching / financement / Partnerships =====

    public function test_a_zumra_anchored_project_still_supports_missions_and_funding_via_unchanged_authority(): void
    {
        $this->activateProgram('IDN-INV-DELEGATE');
        $zumra = $this->zumraFor('IDN-INV-DELEGATE');
        $project = app(ProjectService::class)->create('IDN-INV-DELEGATE', $this->payload(['zumra_group_reference' => $zumra->public_reference]), (new ProjectConfiguration)->defaults());
        $projects = app(ProjectService::class);
        $projects->transition($project, 'IDN-INV-DELEGATE', Project::STATUS_ADOPTED);

        $mission = app(MissionWorkflow::class)->create('IDN-INV-DELEGATE', 'PROJECT', $project->public_reference, [
            'title' => 'Mission de non-régression', 'description' => 'Une mission concrète pour vérifier la délégation d’autorité.',
        ]);
        self::assertSame($project->public_reference, $mission->context_reference, 'Missions délègue toujours à ProjectAuthority sans logique parallèle.');

        $funding = app(ProjectFundingService::class)->create($project->fresh(), 'IDN-INV-DELEGATE', [
            'target_amount' => 500000, 'currency' => 'XOF',
            'purpose' => 'Achat de matériel', 'intended_use' => 'Financer le matériel nécessaire au démarrage réel.',
        ]);
        self::assertSame('OPEN', $funding->status);
    }

    // ===== Helpers =====

    /** @return array<string,mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => 'PERSON', 'group_reference' => null, 'source_need_reference' => null,
            'name' => 'Atelier '.Str::random(6),
            'summary' => str_repeat('Résumé suffisamment long pour la contrainte de validation ici. ', 2),
            'problem' => str_repeat('Problème suffisamment détaillé pour la contrainte de validation ici. ', 2),
            'proposed_solution' => str_repeat('Solution suffisamment détaillée pour la contrainte de validation ici. ', 2),
            'beneficiaries' => 'Des bénéficiaires suffisamment décrits pour ce test.',
            'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID', 'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire un résultat",
            'required_capabilities' => '', 'required_resources' => '', 'risks' => '',
            'milestones' => '', 'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => 'PUBLIC',
        ], $overrides);
    }

    private function rawLegacyAttributes(string $actor, array $overrides = []): array
    {
        return array_replace([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON, 'owner_reference' => $actor,
            'zumra_group_id' => null, 'initiator_core_reference' => $actor, 'name' => 'Ancien projet '.Str::random(6),
            'summary' => str_repeat('Résumé suffisant pour la contrainte de longueur. ', 2),
            'problem' => str_repeat('Problème suffisant pour la contrainte de longueur. ', 2),
            'proposed_solution' => str_repeat('Solution suffisante pour la contrainte de longueur. ', 2),
            'beneficiaries' => 'Des habitants du quartier concernés.',
            'domain' => 'EDUCATION', 'participation_mode' => 'PHYSICAL',
            'property_regime' => 'PERSONAL_SUPPORTED', 'visibility' => Project::VISIBILITY_PRIVATE,
            'status' => Project::STATUS_PROPOSED, 'maturity' => 'IDEA',
        ], $overrides);
    }

    /** @return array<string,mixed> */
    private function zumraFields(string $actor): array
    {
        return [
            'name' => 'ZUMRA '.$actor.' '.Str::random(6), 'domain' => 'Général',
            'founding_objective' => str_repeat('Ancrer les projets de test dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ];
    }

    private function zumraFor(string $actor): ZumraGroup
    {
        return app(ZumraGroupService::class)->create($actor, $this->zumraFields($actor));
    }

    private function readyBrainIntent(string $actor, bool $withZumra): ProjectBrainIntent
    {
        $context = [
            'project_state' => [
                'name' => 'Projet Cerveau ancré', 'summary' => str_repeat('Résumé suffisamment long pour passer la validation minimale. ', 2),
                'problem' => str_repeat('Problème suffisamment détaillé pour passer la validation minimale. ', 2),
                'proposed_solution' => str_repeat('Solution suffisamment détaillée pour passer la validation minimale. ', 2),
                'beneficiaries' => ['Des habitants du quartier'],
                'activity' => 'informatique', 'mode' => 'hybride', 'goal' => 'Un objectif clair',
                'milestones' => ['Première étape clé'],
                'ready_for_confirmation' => true,
            ],
        ];
        if ($withZumra) {
            $context['zumra_group_reference'] = $this->zumraFor($actor)->public_reference;
        }

        return ProjectBrainIntent::query()->create([
            'actor_core_reference' => $actor, 'title' => 'Idée test', 'messages' => [], 'context' => $context, 'status' => 'DRAFT',
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

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-20T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
