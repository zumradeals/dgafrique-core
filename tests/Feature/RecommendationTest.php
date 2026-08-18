<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\RecommendationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_personalized_recommendation_is_calculated_without_orientation_consent(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, false);
        $candidate = $this->profile('IDN-TRAINER', '11111111-1111-4111-8111-111111111111', 'Formatrice Couture', true, true);
        $this->statement($viewer, 'Couture', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Couture', CapabilityStatement::KIND_TRANSMISSION, true);
        $this->signIn('IDN-VIEWER');

        $this->get('/recommandations')->assertOk()
            ->assertSee('Votre autorisation est nécessaire')
            ->assertDontSee('Formatrice Couture');
    }

    public function test_learning_is_matched_with_transmission_and_explained_without_a_human_score(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, true, 'Abidjan', 'HYBRIDE');
        $candidate = $this->profile('IDN-TRAINER', '22222222-2222-4222-8222-222222222222', 'Aïcha Couture', true, true, 'Abidjan', 'HYBRIDE');
        $candidate->update(['phone' => '+22500000000', 'experience_proofs' => ['preuve-privee.test']]);
        $this->statement($viewer, 'Couture', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Couture', CapabilityStatement::KIND_TRANSMISSION, true);
        $this->signIn('IDN-VIEWER');

        $this->get('/recommandations')->assertOk()
            ->assertSee('Aïcha Couture')
            ->assertSee('Vous souhaitez apprendre « Couture »')
            ->assertSee('Vous êtes tous les deux à Abidjan')
            ->assertSee('mode hybride')
            ->assertSee('Aucun score humain')
            ->assertDontSee('+22500000000')
            ->assertDontSee('preuve-privee.test')
            ->assertDontSee('IDN-TRAINER');
    }

    public function test_context_alone_never_creates_a_recommendation(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, true, 'Abidjan', 'HYBRIDE');
        $candidate = $this->profile('IDN-OTHER', '33333333-3333-4333-8333-333333333333', 'Même ville seulement', true, true, 'Abidjan', 'HYBRIDE');
        $this->statement($viewer, 'Couture', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Mécanique', CapabilityStatement::KIND_TRANSMISSION, true);
        $this->signIn('IDN-VIEWER');

        $this->get('/recommandations')->assertOk()
            ->assertSee('Aucune recommandation suffisamment claire')
            ->assertDontSee('Même ville seulement');
    }

    public function test_availability_alone_never_creates_a_recommendation(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, true, 'Abidjan', 'HYBRIDE');
        $candidate = $this->profile('IDN-OTHER', '66666666-6666-4666-8666-666666666666', 'Seulement disponible', true, true, 'Abidjan', 'HYBRIDE');
        $candidate->update(['availability_status' => PersonProfile::AVAILABILITY_OPEN]);
        $this->statement($viewer, 'Couture', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Mécanique', CapabilityStatement::KIND_TRANSMISSION, true);
        $this->signIn('IDN-VIEWER');

        $this->get('/recommandations')->assertOk()
            ->assertSee('Aucune recommandation suffisamment claire')
            ->assertDontSee('Seulement disponible');
    }

    public function test_declared_availability_adds_an_explainable_reason_to_an_already_qualified_match(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, true);
        $candidate = $this->profile('IDN-TRAINER', '77777777-7777-4777-8777-777777777777', 'Disponible Couture', true, true);
        $candidate->update(['availability_status' => PersonProfile::AVAILABILITY_OPEN]);
        $this->statement($viewer, 'Couture', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Couture', CapabilityStatement::KIND_TRANSMISSION, true);
        $this->signIn('IDN-VIEWER');

        $this->get('/recommandations')->assertOk()
            ->assertSee('Disponible Couture')
            ->assertSee('se déclare disponible pour de nouvelles sollicitations');
    }

    public function test_hiding_is_identity_scoped_and_restoring_reopens_the_suggestion(): void
    {
        $first = $this->profile('IDN-FIRST', null, 'Premier', false, true);
        $second = $this->profile('IDN-SECOND', null, 'Second', false, true);
        $candidate = $this->profile('IDN-CANDIDATE', '44444444-4444-4444-8444-444444444444', 'Mentor Design', true, true);
        $this->statement($first, 'Design', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($second, 'Design', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Design', CapabilityStatement::KIND_TRANSMISSION, true);

        $this->signIn('IDN-FIRST');
        $this->post('/recommandations/personnes/'.$candidate->discovery_reference.'/masquer')->assertRedirect();
        self::assertSame(1, RecommendationDecision::query()->count());
        self::assertSame([], $this->recommendationsFor('IDN-FIRST'));
        self::assertCount(1, $this->recommendationsFor('IDN-SECOND'));

        $this->post('/recommandations/personnes/'.$candidate->discovery_reference.'/restaurer')->assertRedirect();
        self::assertSame(0, RecommendationDecision::query()->count());
        self::assertCount(1, $this->recommendationsFor('IDN-FIRST'));
    }

    public function test_disabling_a_reason_family_changes_matching_without_deployment(): void
    {
        $viewer = $this->profile('IDN-VIEWER', null, 'Visiteur', false, true);
        $candidate = $this->profile('IDN-TRAINER', '55555555-5555-4555-8555-555555555555', 'Transmission', true, true);
        $this->statement($viewer, 'Comptabilité', CapabilityStatement::KIND_LEARNING, false);
        $this->statement($candidate, 'Comptabilité', CapabilityStatement::KIND_TRANSMISSION, true);
        $configuration = (new RecommendationConfiguration())->defaults();
        $configuration['learning_transmission'] = false;

        self::assertSame([], app(PersonRecommendationEngine::class)->forIdentity('IDN-VIEWER', $configuration)['recommendations']);
    }

    public function test_an_administrator_configures_recommendations_without_deployment(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-ADMIN']);
        $this->signIn('IDN-ADMIN');

        $this->put('/administration/recommandations', [
            'title' => 'Vos rapprochements utiles',
            'introduction' => 'Une introduction administrable.',
            'privacy_notice' => 'Une notice administrable.',
            'empty_title' => 'Aucune piste',
            'empty_text' => 'Complétez votre profil.',
            'detail_button' => 'Voir pourquoi',
            'hide_button' => 'Ne plus proposer',
            'max_results' => 9,
            'candidate_pool' => 120,
            'max_reasons' => 3,
            'learning_transmission' => '1',
            'shared_domain' => '1',
            'location_context' => '1',
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(RecommendationConfiguration::KEY);
        self::assertSame('IDN-ADMIN', $setting->updated_by_core_reference);
        self::assertSame(9, $setting->value['max_results']);
        self::assertTrue($setting->value['learning_transmission']);
        self::assertFalse($setting->value['shared_capability']);
        $this->get('/recommandations')->assertOk()->assertSee('Vos rapprochements utiles');
    }

    /** @return list<array{profile: PersonProfile, reasons: list<string>}> */
    private function recommendationsFor(string $identity): array
    {
        return app(PersonRecommendationEngine::class)
            ->forIdentity($identity, (new RecommendationConfiguration())->defaults())['recommendations'];
    }

    private function profile(string $identity, ?string $reference, string $name, bool $discoverable, bool $orientation, string $city = 'Abidjan', string $mode = 'HYBRIDE'): PersonProfile
    {
        return PersonProfile::query()->create([
            'core_identity_reference' => $identity,
            'discovery_reference' => $reference,
            'discovery_display_name' => $name,
            'discovery_bio' => 'Présentation publique.',
            'discovery_consent' => $discoverable,
            'discovery_consented_at' => $discoverable ? now() : null,
            'orientation_consent' => $orientation,
            'orientation_consented_at' => $orientation ? now() : null,
            'country_code' => 'CI',
            'city' => $city,
            'participation_mode' => $mode,
        ]);
    }

    private function statement(PersonProfile $profile, string $label, string $kind, bool $discoverable): void
    {
        CapabilityStatement::query()->create([
            'core_identity_reference' => $profile->core_identity_reference,
            'kind' => $kind,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => true,
            'visibility' => $discoverable ? CapabilityStatement::VISIBILITY_DISCOVERABLE : CapabilityStatement::VISIBILITY_PRIVATE,
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00']),
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
