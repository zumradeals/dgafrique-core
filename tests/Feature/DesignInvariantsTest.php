<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupEvent;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Garde-fous du chantier « design identity reset » : navigation réelle, absence de mécaniques
 * de popularité, aucune donnée métier fictive, états vides honnêtes, permissions réelles.
 */
final class DesignInvariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_navigation_names_the_six_real_entries_and_tools_stay_secondary(): void
    {
        $this->signIn('IDN-NAV');

        $this->get('/activite')
            ->assertOk()
            ->assertSeeInOrder(['Fil', 'Mon espace', 'Personnes', 'Besoins', 'Projets', 'ZUMRA'])
            ->assertSee(route('needs.index'), false)
            ->assertSee(route('projects.index'), false)
            ->assertSee(route('people.index'), false)
            ->assertSee(route('zumra.index'), false)
            ->assertSee('Mes outils')
            ->assertSee('GamaDrive')
            ->assertSee(route('federation.continue.gamadrive'), false);
    }

    public function test_mobile_tabbar_has_five_entries_and_besoins_projets_are_not_top_level_tabs(): void
    {
        $this->signIn('IDN-NAV-MOBILE');

        $content = $this->get('/activite')->assertOk()->getContent();
        self::assertMatchesRegularExpression('/<nav class="dg-tabbar".*?<\/nav>/s', $content);
        preg_match('/<nav class="dg-tabbar".*?<\/nav>/s', $content, $matches);
        $tabbar = $matches[0];

        self::assertSame(1, substr_count($tabbar, 'dg-tabbar__act'), 'Un seul bouton « Agir » central.');
        foreach (['Fil', 'Personnes', 'ZUMRA', 'Moi', 'Agir'] as $label) {
            self::assertStringContainsString($label, $tabbar);
        }
        // Besoins/Projets restent des filtres du Fil (CAP-019), jamais des onglets de premier niveau.
        self::assertStringNotContainsString('Besoins', $tabbar);
        self::assertStringNotContainsString('Projets', $tabbar);
    }

    public function test_fil_and_mon_espace_never_show_popularity_mechanics(): void
    {
        $need = $this->need('Besoin sans score', Need::VISIBILITY_PUBLIC);
        $this->needEvent($need, 'NEED_PUBLISHED');
        $this->signIn('IDN-NO-SCORE');

        foreach (['/activite', '/espace'] as $uri) {
            $content = $this->get($uri)->assertOk()->getContent();
            self::assertStringNotContainsStringIgnoringCase('J’aime', $content);
            self::assertStringNotContainsStringIgnoringCase('abonnés', $content);
            self::assertStringNotContainsStringIgnoringCase('followers', $content);
            self::assertStringNotContainsStringIgnoringCase('classement des personnes', $content);
        }
    }

    public function test_landing_example_cards_never_pass_for_real_business_data(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        self::assertStringContainsString('· Exemple', $content);
        // Les fixtures d'exemple ne doivent jamais être présentes dans les tables métier.
        self::assertSame(0, Need::query()->count());
        self::assertSame(0, Project::query()->count());
        self::assertSame(0, ZumraGroup::query()->count());
    }

    public function test_fil_shows_an_honest_empty_state_for_a_fresh_identity(): void
    {
        $this->signIn('IDN-FRESH');

        $this->get('/activite')
            ->assertOk()
            ->assertSee('Rien ne bouge encore près de vous.')
            ->assertSee('Exprimer un besoin');
    }

    public function test_mon_espace_shows_honest_empty_state_when_nothing_needs_a_decision(): void
    {
        $profile = PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-QUIET',
            'country_code' => 'CI', 'city' => 'Abidjan', 'phone' => '+225000000',
            'current_activity' => 'Test', 'education_level' => 'Test',
            'existing_skills' => ['Test'], 'starts_without_skill' => false,
            'transmission_offers' => ['Test'], 'learning_goals' => ['Test'],
            'experience_highlights' => ['Test'], 'experience_proofs' => [],
            'declared_needs' => ['Test'], 'interest_domains' => ['Test'],
            'intentions' => ['Test'], 'participation_mode' => 'HYBRID',
            'collaboration_preferences' => ['Test'], 'orientation_consent' => true,
            'orientation_consented_at' => now(),
        ]);
        $this->signIn('IDN-QUIET');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Rien ne réclame une décision maintenant.');
    }

    public function test_withdrawn_orientation_consent_never_forces_a_profile_priority(): void
    {
        // Le consentement d'orientation est volontaire et révocable (CAP-004). Un profil par
        // ailleurs complet mais sans consentement d'orientation ne doit jamais être présenté
        // comme « incomplet », ni pousser une priorité invitant à (re)donner ce consentement.
        PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-NO-CONSENT',
            'country_code' => 'CI', 'city' => 'Abidjan', 'phone' => '+225000000',
            'current_activity' => 'Test', 'education_level' => 'Test',
            'existing_skills' => ['Test'], 'starts_without_skill' => false,
            'transmission_offers' => ['Test'], 'learning_goals' => ['Test'],
            'experience_highlights' => ['Test'], 'experience_proofs' => [],
            'declared_needs' => ['Test'], 'interest_domains' => ['Test'],
            'intentions' => ['Test'], 'participation_mode' => 'HYBRID',
            'collaboration_preferences' => ['Test'],
            'orientation_consent' => false,
            'orientation_consented_at' => null,
        ]);
        $this->signIn('IDN-NO-CONSENT');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Rien ne réclame une décision maintenant.')
            // Le consentement d'orientation ne fait pas partie des critères de complétion : avec
            // tous les autres champs du fixture remplis, le profil reste affiché comme complet.
            ->assertSee('profil 100 %')
            ->assertDontSee('Continuer votre profil')
            ->assertDontSee('Continuer mon profil')
            ->assertDontSee('Déclarez une chose que vous savez faire.')
            ->assertDontSee('Commencer mon profil');
    }

    public function test_mon_espace_priority_reflects_a_need_proposed_to_a_zumra_awaiting_decision(): void
    {
        // Un besoin personnel s'ouvre toujours directement en OPEN (NeedService::create) : le seul
        // besoin « proposé et en attente d'une décision » réel est un besoin porté par une ZUMRA,
        // proposé par un membre qui n'en est pas responsable.
        $group = $this->group('ZUMRA du proposant');
        $proposed = Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_GROUP,
            'owner_reference' => $group->id,
            'author_core_reference' => 'IDN-PROPOSER',
            'title' => 'Besoin proposé à la ZUMRA',
            'context' => 'Un contexte suffisamment précis pour ce besoin proposé à la ZUMRA.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_GROUP,
            'status' => Need::STATUS_PROPOSED,
            'decided_by_core_reference' => null,
            'published_at' => null,
        ]);
        $this->signIn('IDN-PROPOSER');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Besoin proposé à la ZUMRA')
            ->assertSee('attend une décision des responsables')
            ->assertSee('Voir mon besoin')
            ->assertDontSee('attend d’être publié')
            ->assertDontSee('Ouvrir mon besoin');
    }

    public function test_need_contact_action_is_hidden_from_its_own_owner(): void
    {
        $need = $this->need('Mon propre besoin', Need::VISIBILITY_PUBLIC, 'IDN-OWNER-VIEWER');
        $this->needEvent($need, 'NEED_PUBLISHED');
        $this->signIn('IDN-OWNER-VIEWER');

        $this->get('/activite')
            ->assertOk()
            ->assertSee('Mon propre besoin')
            ->assertDontSee('Je peux aider');
    }

    public function test_zumra_card_action_depends_on_real_membership_state(): void
    {
        $group = $this->group('ZUMRA permissions');
        $this->groupEvent($group, 'GROUP_PROPOSED');

        // Non-membre : peut demander à rejoindre, ne peut pas contacter un responsable.
        $this->signIn('IDN-OUTSIDER');
        $this->get('/activite')->assertOk()
            ->assertSee('Demander à rejoindre')
            ->assertDontSee('Contacter un responsable');
    }

    public function test_active_zumra_member_can_contact_a_responsible_instead_of_requesting_to_join(): void
    {
        $group = $this->group('ZUMRA membre actif');
        $this->groupEvent($group, 'GROUP_PROPOSED');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => 'IDN-ACTIVE-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-ACTIVE-MEMBER',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $this->signIn('IDN-ACTIVE-MEMBER');
        $this->get('/activite')->assertOk()
            ->assertSee('Contacter un responsable')
            ->assertDontSee('Demander à rejoindre');
    }

    private function need(string $title, string $visibility, string $owner = 'IDN-OWNER'): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $owner,
            'author_core_reference' => $owner,
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour rendre cette activité utile dans le réseau.',
            'category' => 'SKILL',
            'capability_label' => 'Gestion de projet',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => $visibility,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $owner,
            'published_at' => now(),
        ]);
    }

    private function needEvent(Need $need, string $event): void
    {
        NeedEvent::query()->create([
            'need_id' => $need->id,
            'event' => $event,
            'actor_core_reference' => $need->author_core_reference,
            'from_status' => null,
            'to_status' => $need->status,
            'context' => [],
            'occurred_at' => now(),
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
            'proposer_core_reference' => 'IDN-OWNER',
            'active_member_count' => 3,
        ]);
    }

    private function groupEvent(ZumraGroup $group, string $event): void
    {
        ZumraGroupEvent::query()->create([
            'zumra_group_id' => $group->id,
            'event' => $event,
            'actor_core_reference' => 'IDN-OWNER',
            'context' => [],
            'occurred_at' => now(),
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
