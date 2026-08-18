<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Discovery\PeopleDiscoveryConfiguration;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PeopleDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_and_orientation_only_profiles_are_never_discovered(): void
    {
        $this->profile('IDN-PER-PRIVATE', '11111111-1111-4111-8111-111111111111', 'Profil privé', false);
        $this->profile('IDN-PER-ORIENTATION', '22222222-2222-4222-8222-222222222222', 'Orientation seulement', false, true);
        $this->signIn();

        $this->get('/personnes')->assertOk()
            ->assertDontSee('Profil privé')
            ->assertDontSee('Orientation seulement')
            ->assertSee('Aucun profil ne correspond encore');
    }

    public function test_discovery_requires_orientation_and_a_chosen_public_name(): void
    {
        $this->signIn();

        $this->put('/espace/profil', ['discovery_consent' => '1'])
            ->assertRedirect('/espace/profil');
        self::assertFalse(PersonProfile::query()->sole()->discovery_consent);

        $this->put('/espace/profil', ['orientation_consent' => '1', 'discovery_consent' => '1'])
            ->assertSessionHasErrors('discovery_display_name');
        self::assertFalse(PersonProfile::query()->sole()->discovery_consent);
    }

    public function test_a_member_discovers_a_real_profile_by_visible_capability(): void
    {
        $profile = $this->profile('IDN-PER-COUTURE', '33333333-3333-4333-8333-333333333333', 'Aïcha Atelier', true, true);
        $profile->update(['phone' => '+22500000000', 'experience_proofs' => ['preuve-secrete.test']]);
        $this->statement($profile, 'Couture', CapabilityStatement::KIND_POSSESSED, true);
        $this->statement($profile, 'Comptabilité', CapabilityStatement::KIND_LEARNING, true);
        $this->signIn();

        $this->get('/personnes?q=couture')->assertOk()
            ->assertSee('Aïcha Atelier')
            ->assertSee('Couture')
            ->assertSee('Pourquoi ce profil apparaît')
            ->assertDontSee('+22500000000')
            ->assertDontSee('preuve-secrete.test')
            ->assertDontSee('IDN-PER-COUTURE')
            ->assertSee('Sans classement de valeur');

        $this->get('/personnes/'.$profile->discovery_reference)->assertOk()
            ->assertSee('Capacités actuelles')
            ->assertSee('Apprentissage')
            ->assertDontSee('+22500000000')
            ->assertDontSee('preuve-secrete.test');
    }

    public function test_declared_availability_is_shown_on_the_discovery_profile(): void
    {
        $profile = $this->profile('IDN-PER-AVAILABLE', '66666666-6666-4666-8666-666666666666', 'Disponible Maintenant', true, true);
        $profile->update([
            'availability_status' => PersonProfile::AVAILABILITY_OPEN,
            'availability_note' => 'disponible le week-end uniquement',
        ]);
        $this->signIn();

        $this->get('/personnes/'.$profile->discovery_reference)->assertOk()
            ->assertSee('Disponible pour de nouvelles sollicitations')
            ->assertSee('disponible le week-end uniquement');
    }

    public function test_unset_availability_shows_an_honest_empty_state(): void
    {
        $profile = $this->profile('IDN-PER-UNSET', '77777777-7777-4777-8777-777777777777', 'Sans Disponibilité', true, true);
        $this->signIn();

        $this->get('/personnes/'.$profile->discovery_reference)->assertOk()->assertSee('Non précisée');
    }

    public function test_withdrawing_discovery_consent_hides_profile_and_capabilities_immediately(): void
    {
        $this->signIn('IDN-PER-OWNER', 'Propriétaire');
        $this->put('/espace/profil', [
            'existing_skills_text' => 'Menuiserie',
            'orientation_consent' => '1',
            'discovery_display_name' => 'Atelier Bois',
            'discovery_bio' => 'Je travaille le bois.',
            'discovery_consent' => '1',
        ])->assertRedirect('/espace/profil');

        $profile = PersonProfile::query()->sole();
        self::assertNotNull($profile->discovery_reference);
        self::assertSame(CapabilityStatement::VISIBILITY_DISCOVERABLE, CapabilityStatement::query()->sole()->visibility);

        $this->put('/espace/profil', [
            'existing_skills_text' => 'Menuiserie',
            'orientation_consent' => '1',
            'discovery_display_name' => 'Atelier Bois',
        ]);
        self::assertFalse($profile->refresh()->discovery_consent);
        self::assertSame(CapabilityStatement::VISIBILITY_PRIVATE, CapabilityStatement::query()->sole()->visibility);

        $this->signIn('IDN-PER-VIEWER', 'Visiteur');
        $this->get('/personnes/'.$profile->discovery_reference)->assertNotFound();
        $this->get('/personnes?q=menuiserie')->assertOk()->assertDontSee('Atelier Bois');
    }

    public function test_discovery_filters_country_and_participation_mode_without_scoring_people(): void
    {
        $ci = $this->profile('IDN-PER-CI', '44444444-4444-4444-8444-444444444444', 'Profil Abidjan', true, true, 'CI', 'HYBRIDE');
        $sn = $this->profile('IDN-PER-SN', '55555555-5555-4555-8555-555555555555', 'Profil Dakar', true, true, 'SN', 'EN_LIGNE');
        $this->statement($ci, 'Design', CapabilityStatement::KIND_POSSESSED, true);
        $this->statement($sn, 'Design', CapabilityStatement::KIND_POSSESSED, true);
        $this->signIn();

        $this->get('/personnes?q=design&country=CI&mode=HYBRIDE')->assertOk()
            ->assertSee('Profil Abidjan')->assertDontSee('Profil Dakar')
            ->assertSee('Sans classement de valeur');
    }

    public function test_an_administrator_configures_discovery_without_deployment(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-PER-ADMIN']);
        $this->signIn('IDN-PER-ADMIN', 'Administrateur');

        $this->put('/administration/decouverte-personnes', [
            'title' => 'Trouver les bonnes capacités',
            'introduction' => 'Une introduction administrable.',
            'privacy_notice' => 'Une notice administrable.',
            'empty_title' => 'Personne pour le moment',
            'empty_text' => 'Revenez plus tard.',
            'detail_button' => 'Découvrir ce profil',
            'page_size' => 18,
            'country_filter' => '1',
        ])->assertRedirect();

        $setting = PortalSetting::query()->findOrFail(PeopleDiscoveryConfiguration::KEY);
        self::assertSame('IDN-PER-ADMIN', $setting->updated_by_core_reference);
        self::assertSame(18, $setting->value['page_size']);
        self::assertFalse($setting->value['mode_filter']);
        $this->get('/personnes')->assertOk()->assertSee('Trouver les bonnes capacités');
    }

    private function profile(string $identity, string $reference, string $name, bool $discovery, bool $orientation = false, string $country = 'CI', string $mode = 'HYBRIDE'): PersonProfile
    {
        return PersonProfile::query()->create([
            'core_identity_reference' => $identity,
            'discovery_reference' => $reference,
            'discovery_display_name' => $name,
            'discovery_bio' => 'Profil utile et volontaire.',
            'discovery_consent' => $discovery,
            'discovery_consented_at' => $discovery ? now() : null,
            'orientation_consent' => $orientation,
            'orientation_consented_at' => $orientation ? now() : null,
            'country_code' => $country,
            'city' => $country === 'CI' ? 'Abidjan' : 'Dakar',
            'participation_mode' => $mode,
        ]);
    }

    private function statement(PersonProfile $profile, string $label, string $kind, bool $visible): void
    {
        CapabilityStatement::query()->create([
            'core_identity_reference' => $profile->core_identity_reference,
            'kind' => $kind,
            'label' => $label,
            'normalized_label' => mb_strtolower($label),
            'matching_consent' => $visible,
            'visibility' => $visible ? CapabilityStatement::VISIBILITY_DISCOVERABLE : CapabilityStatement::VISIBILITY_PRIVATE,
        ]);
    }

    private function signIn(string $reference = 'IDN-PER-VIEWER', string $label = 'Membre visiteur'): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => $label, 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-15T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
