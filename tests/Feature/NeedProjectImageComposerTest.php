<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectDraft;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Publication directe dans le Fil + image rapide, extension additive de CAP-013/014/019
 * (docs/capacites/specs/CAP-013-014-019-fil-publication-directe.md).
 */
final class NeedProjectImageComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_image_is_stored_and_attached_to_a_need(): void
    {
        Storage::fake('public');
        $this->signIn('IDN-AUTHOR');

        $this->post('/besoins', $this->needPayload([
            'image' => UploadedFile::fake()->image('besoin.jpg', 400, 300),
        ]))->assertRedirect();

        $need = Need::query()->sole();
        self::assertNotNull($need->image_path);
        Storage::disk('public')->assertExists($need->image_path);
    }

    public function test_an_oversized_image_is_rejected_on_a_need(): void
    {
        Storage::fake('public');
        $this->signIn('IDN-AUTHOR');

        $this->post('/besoins', $this->needPayload([
            'image' => UploadedFile::fake()->create('besoin.jpg', 4200, 'image/jpeg'),
        ]))->assertSessionHasErrors('image');
        self::assertSame(0, Need::query()->count());
    }

    public function test_a_non_image_file_is_rejected_on_a_need(): void
    {
        Storage::fake('public');
        $this->signIn('IDN-AUTHOR');

        $this->post('/besoins', $this->needPayload([
            'image' => UploadedFile::fake()->create('besoin.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('image');
        self::assertSame(0, Need::query()->count());
    }

    public function test_a_need_published_without_image_leaves_image_path_null(): void
    {
        $this->signIn('IDN-AUTHOR');
        $this->post('/besoins', $this->needPayload())->assertRedirect();

        self::assertNull(Need::query()->sole()->image_path);
    }

    public function test_the_fil_composer_publishes_a_need_identical_in_shape_to_the_full_form(): void
    {
        $this->signIn('IDN-AUTHOR');

        // Le composeur du Fil poste vers la même route « /besoins », avec les mêmes défauts
        // (PERSON/ANY/PUBLIC) que ceux préremplis dans le formulaire complet.
        $this->post('/besoins', [
            'owner_type' => Need::OWNER_PERSON,
            'collaboration_mode' => 'ANY',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'title' => 'Un manque exprimé depuis le fil',
            'context' => 'Ce contexte explique le manque réel avec assez de détails pour être compris par autrui.',
            'category' => 'SKILL',
        ])->assertRedirect();

        $need = Need::query()->sole();
        self::assertSame(Need::OWNER_PERSON, $need->owner_type);
        self::assertSame(Need::STATUS_OPEN, $need->status);
        self::assertSame(Need::VISIBILITY_PUBLIC, $need->visibility);
        self::assertSame('ANY', $need->collaboration_mode);
        self::assertSame('IDN-AUTHOR', $need->author_core_reference);
    }

    public function test_a_valid_image_is_stored_and_attached_to_a_project(): void
    {
        Storage::fake('public');
        $this->member('IDN-OWNER');
        $this->signIn('IDN-OWNER');
        $draft = $this->walkThroughProjectIdea('IDN-OWNER');

        $this->post(route('projects.draft.confirm', $draft), [
            'image' => UploadedFile::fake()->image('projet.jpg', 400, 300),
        ])->assertRedirect();

        $project = Project::query()->sole();
        self::assertNotNull($project->image_path);
        Storage::disk('public')->assertExists($project->image_path);
    }

    public function test_an_invalid_image_is_rejected_on_a_project(): void
    {
        Storage::fake('public');
        $this->member('IDN-OWNER');
        $this->signIn('IDN-OWNER');
        $draft = $this->walkThroughProjectIdea('IDN-OWNER');

        $this->post(route('projects.draft.confirm', $draft), [
            'image' => UploadedFile::fake()->create('projet.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('image');
        self::assertSame(0, Project::query()->count());
    }

    public function test_doctrinal_information_remains_present_after_the_disclosure_rollout(): void
    {
        $this->member('IDN-OWNER');
        $this->signIn('IDN-OWNER');
        $draft = $this->walkThroughProjectIdea('IDN-OWNER');
        $this->post(route('projects.draft.confirm', $draft))->assertRedirect();
        $project = Project::query()->sole();

        $show = $this->get('/projets/'.$project->public_reference);
        $show->assertOk();
        $show->assertSee('Aucun financement n’est ouvert ici.', false);
        $show->assertSee('ne constitue ni une collecte, ni une promesse d’accompagnement', false);

        $accompaniment = $this->get('/projets/'.$project->public_reference.'/accompagnement');
        $accompaniment->assertOk();
        $accompaniment->assertSee('Le projet reste autonome.', false);
        $accompaniment->assertSee('ni propriété, ni contrôle, ni pouvoir de décision', false);
    }

    private function needPayload(array $overrides = []): array
    {
        return array_replace([
            'owner_type' => Need::OWNER_PERSON,
            'title' => 'Un besoin réel exprimé avec une image',
            'context' => 'Ce contexte explique le manque réel avec assez de détails pour être compris par autrui.',
            'category' => 'SKILL',
            'collaboration_mode' => 'ANY',
            'visibility' => Need::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    /**
     * Parcourt le brouillon UIUX-009B jusqu'à « relire » — l'upload d'image reste vérifié à sa
     * toute dernière étape (confirmation), là où il vit désormais.
     */
    private function walkThroughProjectIdea(string $actor): ProjectDraft
    {
        $this->get(route('projects.create'))->assertRedirect();
        $draft = ProjectDraft::query()->where('actor_core_reference', $actor)->where('status', ProjectDraft::STATUS_DRAFT)->latest('created_at')->firstOrFail();

        $zumra = app(ZumraGroupService::class)->create($actor, [
            'name' => 'ZUMRA '.$actor.' '.Str::random(6), 'domain' => 'Numérique',
            'founding_objective' => str_repeat('Ancrer ce projet dans une ZUMRA réelle. ', 2),
            'participation_mode' => 'HYBRID', 'internal_charter' => str_repeat('Respect, transmission et responsabilité partagée. ', 4),
            'assume_primary_lead' => true,
        ]);
        $this->post(route('projects.draft.update', [$draft, 'audience']), ['owner_type' => 'PERSON', 'zumra_group_reference' => $zumra->public_reference, '_intent' => 'continue'])->assertRedirect();
        $steps = [
            'nom' => ['name' => 'Atelier numérique communautaire'],
            'resume' => ['summary' => 'Créer un espace pratique où des jeunes peuvent apprendre ensemble et produire des services utiles.'],
            'probleme' => ['problem' => 'Des jeunes motivés disposent de peu de cadres pratiques pour apprendre et transformer leurs acquis.'],
            'solution' => ['proposed_solution' => 'Mettre en place un atelier progressif avec transmission entre pairs et accompagnement réel.'],
            'beneficiaires' => ['beneficiaries' => 'Jeunes débutants et personnes en reconversion dans la commune.'],
            'logistique' => ['domain' => 'DIGITAL', 'participation_mode' => 'HYBRID'],
        ];
        foreach ($steps as $step => $answers) {
            $this->post(route('projects.draft.update', [$draft, $step]), $answers + ['_intent' => 'continue'])->assertRedirect();
        }
        $this->post(route('projects.draft.update', [$draft, 'objectifs']), ['objectives' => ['Former une première équipe', 'Produire trois services pilotes'], '_intent' => 'continue'])->assertRedirect();
        $this->post(route('projects.draft.update', [$draft, 'besoins']), ['_intent' => 'continue'])->assertRedirect();

        return $draft->fresh();
    }

    private function member(string $id): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
        ZumraProgramMembership::query()->create([
            'core_identity_reference' => $id,
            'status' => ZumraProgramMembership::STATUS_ACTIVE,
            'accepted_charter_id' => $charter->id,
            'accepted_charter_version' => $charter->version,
            'accepted_charter_hash' => $charter->content_hash,
            'charter_accepted_at' => now(),
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre DG Afrique', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
