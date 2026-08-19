<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Need;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fil V2 : correctifs fonctionnels du 19 août 2026 (voir docs/design/handoffs/
 * DG-AFRIQUE-FIL-V2-HANDOFF.md et l'addendum daté §17 de DESIGN-INVARIANTS.md).
 */
final class FilV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_demo_cards_appear_marked_as_example_when_the_fil_has_no_real_data(): void
    {
        $this->signIn('IDN-FILV2-EMPTY');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringContainsString('Besoin · Exemple', $content);
        self::assertStringContainsString('Projet · Exemple', $content);
        self::assertStringContainsString('ZUMRA · Exemple', $content);
        self::assertStringContainsString('Objet de démonstration — aucune action réelle n’est rattachée.', $content);
    }

    public function test_demo_cards_disappear_as_soon_as_real_data_exists_for_the_active_filter(): void
    {
        $this->makeVisibleNeed('IDN-FILV2-REAL-OWNER');
        $this->signIn('IDN-FILV2-REAL-VIEWER');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringNotContainsString('Besoin · Exemple', $content);
        self::assertStringNotContainsString('Projet · Exemple', $content);
        self::assertStringNotContainsString('ZUMRA · Exemple', $content);
    }

    public function test_composer_only_links_real_creation_routes_and_disables_the_rest_with_a_reason(): void
    {
        $this->signIn('IDN-FILV2-COMPOSER');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringContainsString(route('projects.create'), $content);
        self::assertStringContainsString(route('zumra.groups.create'), $content);
        self::assertMatchesRegularExpression(
            '/<span class="dg-fil-composer__type" aria-disabled="true" title="Une Mission se crée depuis un Projet, une ZUMRA ou un Besoin existant\.">Mission<\/span>/',
            $content
        );
        self::assertMatchesRegularExpression(
            '/<span class="dg-fil-composer__type" aria-disabled="true" title="Aucun objet Événement n’existe encore dans le produit\.">Événement<\/span>/u',
            $content
        );
    }

    public function test_left_rail_contribution_intents_link_to_real_routes(): void
    {
        $this->signIn('IDN-FILV2-CONTRIBUTE');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringContainsString(route('needs.index'), $content);
        self::assertStringContainsString(route('needs.create'), $content);
        self::assertStringContainsString(route('transmissions.create'), $content);
    }

    public function test_right_rail_shows_what_matters_instead_of_fabricated_traction_numbers(): void
    {
        $this->signIn('IDN-FILV2-MATTERS');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringContainsString('Ce qui compte', $content);
        self::assertStringContainsString('Agir utilement', $content);
        self::assertStringContainsString('Collaborer simplement', $content);
        self::assertStringContainsString('Transmettre', $content);
        self::assertStringNotContainsString('25K+', $content);
        self::assertStringNotContainsString('1 200+', $content);
        self::assertStringNotContainsString('45 pays', $content);
    }

    public function test_fil_never_shows_popularity_mechanics(): void
    {
        $this->signIn('IDN-FILV2-NOSCORE');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringNotContainsStringIgnoringCase('J’aime', $content);
        self::assertStringNotContainsStringIgnoringCase('abonnés', $content);
        self::assertStringNotContainsStringIgnoringCase('followers', $content);
        self::assertStringNotContainsStringIgnoringCase('classement des', $content);
        self::assertStringNotContainsStringIgnoringCase('trending', $content);
    }

    public function test_the_redundant_publish_button_is_gone_and_the_composer_is_the_first_visible_section(): void
    {
        // Retour post-déploiement du 19 août 2026 : le bouton « + Publier une action » du
        // rail gauche était redondant avec le composeur, déjà visible sans interaction
        // supplémentaire — retiré. Le composeur doit rester la toute première section
        // visible dans <main>, avant les filtres et le flux (mobile comme desktop, puisque
        // les deux rails restent masqués sous 1024px).
        $this->signIn('IDN-FILV2-MOBILE-FIRST');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertStringNotContainsString('Publier une action', $content);

        $mainStart = strpos($content, '<main');
        $composerPosition = strpos($content, 'id="dg-fil-composer"');
        $toolbarPosition = strpos($content, 'class="dg-fil-toolbar"');
        $feedPosition = strpos($content, 'class="dg-feed"');

        self::assertNotFalse($mainStart);
        self::assertNotFalse($composerPosition);
        self::assertGreaterThan($mainStart, $composerPosition);
        self::assertLessThan($toolbarPosition, $composerPosition, 'Le composeur doit précéder la barre de filtres.');
        self::assertLessThan($feedPosition, $composerPosition, 'Le composeur doit précéder le flux.');
    }

    public function test_both_side_rails_stay_hidden_below_the_desktop_breakpoint(): void
    {
        $this->signIn('IDN-FILV2-RAILS-HIDDEN');

        $content = $this->get('/activite')->assertOk()->getContent();

        self::assertSame(2, substr_count($content, 'class="hidden lg:flex dg-fil-rail"'));
    }

    public function test_fil_v2_css_never_forces_display_on_elements_hidden_by_tailwind_outside_a_media_query(): void
    {
        // Régression réelle observée sur mobile : `.dg-fil-rail { display: flex }` déclaré
        // en dehors de tout @media écrasait l'utilitaire Tailwind `hidden` (même
        // spécificité, mais fil-v2.css est chargé après app.css) — les rails restaient
        // visibles au-dessus du composeur sur téléphone malgré `hidden lg:flex` dans le
        // markup. Toute règle CSS ciblant un sélecteur utilisé avec `hidden` dans le Fil
        // doit rester à l'intérieur d'un @media, jamais au niveau racine de la feuille.
        $css = file_get_contents(resource_path('css/fil-v2.css'));
        self::assertNotFalse($css);

        // Supprime le contenu des blocs @media pour n'analyser que les règles racine.
        $rootLevelCss = preg_replace('/@media[^{]*\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/s', '', $css);
        self::assertNotNull($rootLevelCss);

        foreach (['.dg-fil-rail', '.dg-fil-toolbar__sort'] as $selector) {
            self::assertDoesNotMatchRegularExpression(
                '/'.preg_quote($selector, '/').'\s*\{[^}]*display\s*:/s',
                $rootLevelCss,
                "$selector ne doit jamais fixer `display` en dehors d'un @media : cela écraserait l'utilitaire Tailwind `hidden` posé dans le markup."
            );
        }
    }

    public function test_end_of_feed_message_appears_once_real_pagination_is_exhausted(): void
    {
        $this->makeVisibleNeed('IDN-FILV2-EOF-OWNER');
        $this->signIn('IDN-FILV2-EOF-VIEWER');

        $this->get('/activite')
            ->assertOk()
            ->assertSee('Le fil s’arrête ici. Revenez quand une action réelle aura avancé.');
    }

    private function makeVisibleNeed(string $owner): void
    {
        $this->signIn($owner);
        $this->post('/besoins', [
            'owner_type' => Need::OWNER_PERSON,
            'group_reference' => null,
            'title' => 'Besoin réel visible dans le Fil V2',
            'context' => 'Un contexte suffisamment détaillé pour ce besoin réel utilisé par les tests du Fil V2.',
            'category' => 'TRAINING',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_PUBLIC,
        ])->assertRedirect();
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
