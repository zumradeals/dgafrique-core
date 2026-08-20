<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\Need;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Garde-fous de la Phase 2 : propagation de l'identité DG Afrique aux écrans Besoins, Projets,
 * Personnes/Profil et ZUMRA. DG Afrique n'a qu'un seul Fil social (/activite) ; ces écrans
 * l'étendent visuellement, jamais n'en recréent un second (CAP-019).
 */
final class DesignInvariantsPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_besoins_projets_personnes_zumra_all_share_the_common_navigation(): void
    {
        $this->signIn('IDN-P2-NAV');

        foreach (['/besoins', '/projets', '/personnes', '/zumra'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSeeInOrder(['Fil', 'Mon espace', 'Personnes', 'Besoins', 'Projets', 'ZUMRA'])
                ->assertSee('dg-tabbar', false);
        }
    }

    public function test_besoins_projets_personnes_filter_forms_actually_submit(): void
    {
        // x-dg.btn rend <button type="button"> par défaut : un bouton de filtre GET sans
        // type="submit" explicite est visuellement correct mais ne soumet jamais le formulaire.
        $this->signIn('IDN-P2-FILTER-SUBMIT');

        $cases = [
            '/besoins' => 'Filtrer',
            '/projets' => 'Explorer',
            '/personnes' => 'Découvrir',
        ];

        foreach ($cases as $uri => $label) {
            $content = $this->get($uri)->assertOk()->getContent();
            self::assertMatchesRegularExpression('/<form[^>]*method="GET"[^>]*class="dg-filters"[^>]*>.*?<\/form>/s', $content, "Formulaire de filtre introuvable sur $uri");
            preg_match('/<form[^>]*method="GET"[^>]*class="dg-filters"[^>]*>.*?<\/form>/s', $content, $matches);
            $form = $matches[0];

            self::assertStringContainsString($label, $form, "Bouton « $label » introuvable dans le formulaire de $uri");
            self::assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*>\s*'.preg_quote($label, '/').'/s', $form, "Le bouton « $label » sur $uri doit être type=\"submit\" pour soumettre le formulaire GET.");
        }
    }

    public function test_zumra_hub_no_longer_exposes_a_second_autonomous_feed(): void
    {
        $this->signIn('IDN-P2-HUB');

        $content = $this->get('/zumra')->assertOk()->getContent();

        self::assertStringNotContainsString('Fil ZUMRA', $content);
        self::assertStringNotContainsString('Le réseau se construit sur des données réelles.', $content);
        self::assertStringNotContainsString('Profils à suivre', $content);
        self::assertStringNotContainsString('Projets tendances', $content);
        // Les mouvements ZUMRA restent représentés par le Fil global filtré, jamais un fil autonome.
        self::assertStringContainsString(route('activity.index', ['type' => 'ZUMRA']), $content);
    }

    public function test_zumra_activity_link_reuses_the_single_global_feed_filter(): void
    {
        $this->signIn('IDN-P2-FILTER');

        $this->get(route('activity.index', ['type' => 'ZUMRA']))->assertOk();
    }

    public function test_besoins_projets_personnes_zumra_never_show_popularity_mechanics(): void
    {
        $this->signIn('IDN-P2-NOSCORE');

        foreach (['/besoins', '/projets', '/personnes', '/zumra', '/zumra/groupes', '/activite'] as $uri) {
            $content = $this->get($uri)->assertOk()->getContent();
            self::assertStringNotContainsStringIgnoringCase('J’aime', $content);
            self::assertStringNotContainsStringIgnoringCase('abonnés', $content);
            self::assertStringNotContainsStringIgnoringCase('followers', $content);
            self::assertStringNotContainsStringIgnoringCase('classement des personnes', $content);
        }
    }

    public function test_besoins_directory_shows_an_honest_empty_state(): void
    {
        $this->signIn('IDN-P2-BESOINS-EMPTY');

        $this->get('/besoins')
            ->assertOk()
            ->assertSee('Aucun besoin visible ici');
    }

    public function test_projets_directory_shows_an_honest_empty_state(): void
    {
        // Depuis l'addendum daté du 20 août 2026 (DESIGN-INVARIANTS.md §18), le portail Projets
        // comble l'absence de données réelles par des cartes de démonstration (DEMO-FIRST,
        // REAL-DATA-TAKES-OVER — voir ProjectsDirectoryDemoTest). L'état vide honnête reste donc
        // vérifiable pour un domaine qu'aucune carte réelle ni carte d'exemple ne couvre.
        $this->signIn('IDN-P2-PROJETS-EMPTY');

        $this->get('/projets?domain=HEALTH')
            ->assertOk()
            ->assertSee('Aucun projet visible');
    }

    public function test_zumra_groups_directory_shows_an_honest_empty_state(): void
    {
        $this->signIn('IDN-P2-ZUMRA-EMPTY');

        $this->get('/zumra/groupes')
            ->assertOk()
            ->assertSee('La première ZUMRA peut commencer ici.');
    }

    public function test_learning_intention_stays_disabled_with_its_documented_reason_on_besoins_screens(): void
    {
        $need = Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => 'IDN-P2-OWNER',
            'author_core_reference' => 'IDN-P2-OWNER',
            'title' => 'Besoin visible pour audit des actions',
            'context' => 'Un contexte suffisamment précis pour ce besoin visible dans la liste.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => 'IDN-P2-OWNER',
            'published_at' => now(),
        ]);
        $this->signIn('IDN-P2-VIEWER');

        $this->get('/besoins/'.$need->public_reference)
            ->assertOk()
            ->assertSee('Coordonner autour de ce besoin')
            ->assertDontSee('Je veux apprendre');
    }

    public function test_zumra_creation_action_is_disabled_with_its_reason_for_a_member_without_active_program_membership(): void
    {
        $this->signIn('IDN-P2-NOPROGRAM');

        $this->get('/zumra')
            ->assertOk()
            ->assertSee('Réservé aux membres dont l’adhésion au Programme ZUMRA est active.', false);
    }

    public function test_zumra_group_show_hides_leader_only_sections_from_a_non_leader(): void
    {
        $group = $this->group('ZUMRA visibilité permissions');
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id,
            'role' => 'PRIMARY_LEAD',
            'core_identity_reference' => 'IDN-P2-LEADER',
            'status' => ZumraGroupRole::STATUS_ACCEPTED,
        ]);
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => 'IDN-P2-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-P2-MEMBER',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $this->signIn('IDN-P2-MEMBER');

        $this->get('/zumra/groupes/'.$group->public_reference)
            ->assertOk()
            ->assertSee('Cinq responsabilités, cinq personnes.')
            ->assertDontSee('Inviter un adhérent')
            ->assertDontSee('Demandes en attente');
    }

    public function test_zumra_group_show_exposes_leader_only_sections_to_the_accepted_leader(): void
    {
        $group = $this->group('ZUMRA visibilité responsable');
        ZumraGroupRole::query()->create([
            'zumra_group_id' => $group->id,
            'role' => 'PRIMARY_LEAD',
            'core_identity_reference' => 'IDN-P2-LEADER2',
            'status' => ZumraGroupRole::STATUS_ACCEPTED,
        ]);
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => 'IDN-P2-LEADER2',
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-P2-LEADER2',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $this->signIn('IDN-P2-LEADER2');

        $this->get('/zumra/groupes/'.$group->public_reference)
            ->assertOk()
            ->assertSee('Inviter un adhérent');
    }

    public function test_personnes_discovery_shows_an_honest_empty_state(): void
    {
        $this->signIn('IDN-P2-PERSONNES-EMPTY');

        $this->get('/personnes')->assertOk();
    }

    public function test_project_maturity_stagewalk_shows_all_eight_stages_not_a_percentage(): void
    {
        $this->activateProgramMembership('IDN-P2-PROJECT-OWNER');
        $project = app(ProjectService::class)->create('IDN-P2-PROJECT-OWNER', [
            'owner_type' => 'PERSON',
            'group_reference' => null,
            'source_need_reference' => null,
            'name' => 'Projet pour audit de maturité',
            'summary' => 'Un résumé suffisamment long pour ce projet destiné à vérifier le chemin de maturité.',
            'problem' => 'Un problème suffisamment détaillé pour respecter la validation métier existante et son contexte.',
            'proposed_solution' => 'Une réponse suffisamment détaillée pour respecter la validation métier existante et son contexte.',
            'beneficiaries' => 'Des bénéficiaires suffisamment décrits pour respecter la validation métier existante.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'objectives' => "Former une première équipe\nProduire un premier résultat",
            'required_capabilities' => 'Formation numérique',
            'required_resources' => 'Ordinateurs',
            'risks' => 'Disponibilité irrégulière',
            'milestones' => "Constituer l’équipe\nPréparer le lieu\nLancer le pilote",
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => 'PUBLIC',
        ], (new ProjectConfiguration)->defaults());
        $this->signIn('IDN-P2-PROJECT-OWNER');

        $content = $this->get('/projets/'.$project->public_reference)->assertOk()->getContent();

        foreach (['Idée', 'Exploration', 'Équipe', 'Prototype / expérimentation', 'Projet structuré', 'Activité', 'Structure potentielle', 'Satellite potentiel'] as $stageLabel) {
            self::assertStringContainsString($stageLabel, $content);
        }
        // La maturité est un chemin de repères, jamais un pourcentage (CAP-017).
        self::assertMatchesRegularExpression('/<div class="dg-stagewalk">.*?<\/div>\s*<\/div>\s*<\/div>/s', $content);
        preg_match('/<div class="dg-stagewalk">.*?<\/div>\s*<\/div>\s*<\/div>/s', $content, $matches);
        self::assertStringNotContainsString('%', $matches[0]);
    }

    private function activateProgramMembership(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 5);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]
        );
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
    }

    private function group(string $name): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'domain' => 'Numérique',
            'founding_objective' => 'Rassembler des personnes pour apprendre, transmettre et produire une action utile ensemble.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => str_repeat('Respect, responsabilité et transmission. ', 3),
            'state' => ZumraGroup::STATE_ACTIVE,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-P2-OWNER',
            'active_member_count' => 1,
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'bearer-'.$reference,
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference,
                'type' => 'personne',
                'libelle' => 'Membre DG Afrique',
                'etat' => 'ACTIF',
                'source' => 'CORE',
                'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-16T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', [
            'identifier' => $reference,
            'secret' => 'secret',
        ])->assertRedirect('/espace');
    }
}
