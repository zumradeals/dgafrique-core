<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Profile\ProfileConfiguration;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PortalAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_provisioned_core_identity_can_open_administration(): void
    {
        $this->signIn('IDN-PER-000000004');
        $this->get('/administration')->assertForbidden();

        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-PER-000000004']);
        $this->get('/administration')->assertOk()->assertSee('Configurer le profil de capacités');
    }

    public function test_an_administrator_configures_member_profile_without_a_deployment(): void
    {
        $defaults = (new ProfileConfiguration())->defaults();
        PortalAdministrator::query()->create(['core_identity_reference' => 'IDN-PER-000000003']);
        $this->signIn('IDN-PER-000000003');

        $this->put('/administration/profil-capacites', [
            'title' => 'Votre avenir commence ici',
            'introduction' => 'Une présentation administrable.',
            'sections' => [
                'situation' => ['enabled' => '1', 'order' => 20, 'title' => 'Contexte', 'help' => 'Votre réalité'],
                'skills' => ['enabled' => '1', 'order' => 10, 'title' => 'Talents', 'help' => 'Vos pratiques'],
                'learning' => ['order' => 30, 'title' => 'Apprentissages', 'help' => 'Vos objectifs'],
                'intentions' => ['enabled' => '1', 'order' => 40, 'title' => 'Intentions', 'help' => 'Votre projet'],
            ],
            'participation_modes_text' => "LOCAL|Sur place\nDISTANCE|À distance",
            'country_suggestions_text' => "CI\nSN",
            'field_labels' => $defaults['field_labels'],
            'required_fields' => ['city' => '1'],
            'orientation_consent_label' => 'Conseils personnalisés',
            'orientation_consent_help' => 'Consentement révocable.',
            'submit_label' => 'Sauvegarder',
            'no_score_notice' => 'Aucun score.',
        ])->assertRedirect();

        self::assertSame('IDN-PER-000000003', PortalSetting::query()->findOrFail(ProfileConfiguration::KEY)->updated_by_core_reference);
        $this->get('/espace/profil')->assertOk()->assertSee('Votre avenir commence ici')->assertSee('Talents')->assertDontSee('Apprentissages');
        $this->put('/espace/profil', [])->assertSessionHasErrors('city');
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer', 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-14T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Administrateur DG', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-08-14T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
