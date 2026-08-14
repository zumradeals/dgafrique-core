<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PersonProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_creates_and_reads_a_profile_independent_from_zumra(): void
    {
        $this->signIn();

        $this->put('/espace/profil', [
            'country_code' => 'ci',
            'city' => 'Abidjan',
            'current_activity' => 'Artisane',
            'existing_skills_text' => "Couture\nGestion de caisse",
            'transmission_offers_text' => "Bases de la couture\nOrganisation d’un atelier",
            'learning_goals_text' => "Marketing numérique\nComptabilité",
            'experience_highlights_text' => "Trois ans dans un atelier\nFormation de deux apprenties",
            'experience_proofs_text' => 'https://exemple.test/portfolio',
            'declared_needs_text' => "Trouver un mentor\nAccéder à un espace de travail",
            'interest_domains_text' => "Entrepreneuriat\nNumérique",
            'intentions_text' => "Développer mon atelier\nFormer deux jeunes",
            'participation_mode' => 'HYBRIDE',
            'collaboration_preferences_text' => "Disponible le week-end\nPetits groupes",
            'orientation_consent' => '1',
            'core_identity_reference' => 'IDN-PER-ATTAQUANT',
        ])->assertRedirect('/espace/profil');

        $profile = PersonProfile::query()->sole();
        self::assertSame('IDN-PER-000000005', $profile->core_identity_reference);
        self::assertSame(['Couture', 'Gestion de caisse'], $profile->existing_skills);
        self::assertSame(['Bases de la couture', 'Organisation d’un atelier'], $profile->transmission_offers);
        self::assertSame(['Trois ans dans un atelier', 'Formation de deux apprenties'], $profile->experience_highlights);
        self::assertSame(['Trouver un mentor', 'Accéder à un espace de travail'], $profile->declared_needs);
        self::assertSame(['Disponible le week-end', 'Petits groupes'], $profile->collaboration_preferences);
        self::assertTrue($profile->orientation_consent);
        self::assertNotNull($profile->orientation_consented_at);
        self::assertFalse(Schema::hasTable('zumra_memberships'));
        self::assertFalse(Schema::hasColumn('dg_person_profiles', 'score'));

        $this->get('/espace/profil')
            ->assertOk()
            ->assertSee('Artisane')
            ->assertSee('Couture')
            ->assertSee('Ce que vous pouvez transmettre')
            ->assertSee('Vos expériences et preuves')
            ->assertSee('Vos besoins et objectifs')
            ->assertSee('Vos préférences de collaboration')
            ->assertDontSee('GAMAD');
    }

    public function test_a_member_can_explicitly_start_without_a_declared_skill(): void
    {
        $this->signIn();
        $this->put('/espace/profil', ['starts_without_skill' => '1'])
            ->assertRedirect('/espace/profil');

        $profile = PersonProfile::query()->sole();
        self::assertTrue($profile->starts_without_skill);
        self::assertSame([], $profile->existing_skills);
    }

    public function test_declared_skills_take_precedence_over_the_empty_skill_flag(): void
    {
        $this->signIn();
        $this->put('/espace/profil', [
            'starts_without_skill' => '1',
            'existing_skills_text' => 'Cuisine',
        ]);

        $profile = PersonProfile::query()->sole();
        self::assertFalse($profile->starts_without_skill);
        self::assertSame(['Cuisine'], $profile->existing_skills);
    }

    public function test_orientation_consent_can_be_withdrawn(): void
    {
        $this->signIn();
        $this->put('/espace/profil', ['orientation_consent' => '1']);
        $this->put('/espace/profil', []);

        $profile = PersonProfile::query()->sole();
        self::assertFalse($profile->orientation_consent);
        self::assertNull($profile->orientation_consented_at);
    }

    public function test_another_core_identity_cannot_overwrite_the_first_profile(): void
    {
        PersonProfile::query()->create([
            'core_identity_reference' => 'IDN-PER-000000005',
            'city' => 'Abidjan',
        ]);

        $this->signIn('IDN-PER-000000006', 'Membre Deux');
        $this->put('/espace/profil', [
            'city' => 'Bouaké',
            'core_identity_reference' => 'IDN-PER-000000005',
        ])->assertRedirect('/espace/profil');

        self::assertSame(2, PersonProfile::query()->count());
        self::assertSame('Abidjan', PersonProfile::query()->findOrFail('IDN-PER-000000005')->city);
        self::assertSame('Bouaké', PersonProfile::query()->findOrFail('IDN-PER-000000006')->city);
    }

    private function signIn(string $reference = 'IDN-PER-000000005', string $label = 'Membre Profil'): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response([
                'jeton' => 'member-bearer-'.$reference,
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-14T23:59:00+00:00',
            ], 201),
            'core.test/api/v1/identites/*' => Http::response([
                'reference' => $reference,
                'type' => 'personne',
                'libelle' => $label,
                'etat' => 'ACTIF',
                'source' => 'CORE',
                'regime' => 'INSCRIT_AU_REGISTRE',
            ]),
            'core.test/api/v1/sessions/current' => Http::response([
                'entite' => $reference,
                'assurance' => 'AS1',
                'expire_le' => '2026-08-14T23:59:00+00:00',
            ]),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'member-secret'])
            ->assertRedirect('/espace');
    }
}
