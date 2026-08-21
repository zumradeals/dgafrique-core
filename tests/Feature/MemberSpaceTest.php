<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\NeedEvent;
use App\Models\PersonProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-001 Phase B : routeur de « première intention » sur Mon espace, capacité légère, et
 * correction de MemberSpaceController::priority() (un objet sans relation personnelle réelle ne
 * peut jamais devenir la priorité dominante du membre).
 */
final class MemberSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_brand_new_member_sees_the_first_intention_router_instead_of_zumra_as_a_first_level_action(): void
    {
        $this->signIn('IDN-SPACE-NEW');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Je peux apporter quelque chose', $content);
        self::assertStringContainsString('J’ai un besoin', $content);
        self::assertStringContainsString('Je veux découvrir', $content);
        self::assertStringContainsString('Je veux participer', $content);
        self::assertStringNotContainsString('Ouvrir ZUMRA', $content);
    }

    public function test_an_active_member_keeps_the_usual_quick_actions_and_never_repeats_the_intention_router(): void
    {
        $this->makeVisibleNeed('IDN-SPACE-ACTIVE');
        $this->signIn('IDN-SPACE-ACTIVE');

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('Ouvrir ZUMRA', $content);
        self::assertStringNotContainsString('Je veux participer', $content);
    }

    public function test_the_quick_capability_declaration_creates_a_real_capability_statement_without_a_parallel_model(): void
    {
        $this->signIn('IDN-SPACE-QUICK');

        $this->post('/espace/capacite-rapide', ['capability' => 'Je sais réparer des vélos.'])
            ->assertRedirect('/espace');

        $statement = CapabilityStatement::query()->sole();
        self::assertSame('IDN-SPACE-QUICK', $statement->core_identity_reference);
        self::assertSame(CapabilityStatement::KIND_POSSESSED, $statement->kind);
        self::assertSame('Je sais réparer des vélos.', $statement->label);
        self::assertSame(CapabilityStatement::STATUS_DECLARED, $statement->status);
        self::assertNull($statement->archived_at);

        $profile = PersonProfile::query()->sole();
        self::assertSame(['Je sais réparer des vélos.'], $profile->existing_skills);

        // Le profil complet en 7 étapes reste la voie d'approfondissement, inchangée.
        $this->get('/espace/profil')->assertOk()->assertSee('Je sais réparer des vélos.');
    }

    public function test_the_quick_capability_declaration_never_archives_a_capability_already_declared_via_the_full_profile(): void
    {
        $this->signIn('IDN-SPACE-KEEP');

        $this->put('/espace/profil', [
            'existing_skills_text' => 'Couture',
            'orientation_consent' => '0',
        ])->assertRedirect('/espace/profil');

        $this->post('/espace/capacite-rapide', ['capability' => 'Réparation de vélos'])
            ->assertRedirect('/espace');

        self::assertSame(2, CapabilityStatement::query()->whereNull('archived_at')->count());
        self::assertSame(
            ['Couture', 'Réparation de vélos'],
            PersonProfile::query()->sole()->existing_skills,
        );
    }

    public function test_priority_never_promotes_a_strangers_activity_without_a_personal_relevance_reason(): void
    {
        $this->makeVisibleNeed('IDN-SPACE-STRANGER');
        $this->signIn('IDN-SPACE-NORELATION');

        $content = $this->get('/espace')->assertOk()->getContent();

        // Aucune relation réelle avec ce besoin d'un inconnu : le titre peut légitimement
        // apparaître ailleurs sur la page (section « Pour vous maintenant », inchangée par ce
        // correctif), mais jamais comme la priorité dominante elle-même — précisément l'élément
        // marqué par son id unique `dg-space-priority-title`.
        self::assertStringNotContainsString(
            '<h2 id="dg-space-priority-title">Besoin réel visible dans le Fil</h2>',
            $content,
        );
    }

    public function test_priority_promotes_a_need_the_member_actually_authored(): void
    {
        $this->signIn('IDN-SPACE-OWNNEED');

        $this->post('/besoins', [
            'owner_type' => Need::OWNER_PERSON,
            'group_reference' => null,
            'title' => 'Mon propre besoin réel',
            'context' => 'Un contexte suffisamment détaillé pour ce besoin réellement porté par ce membre.',
            'category' => 'TRAINING',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_PUBLIC,
        ])->assertRedirect();

        $content = $this->get('/espace')->assertOk()->getContent();

        self::assertStringContainsString('une seule chose compte', $content);
        self::assertStringContainsString('Mon propre besoin réel', $content);
    }

    private function makeVisibleNeed(string $owner): void
    {
        $need = Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => $owner,
            'author_core_reference' => $owner,
            'title' => 'Besoin réel visible dans le Fil',
            'context' => 'Un contexte suffisamment précis pour rendre cette activité utile dans le réseau.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => Need::VISIBILITY_PUBLIC,
            'status' => Need::STATUS_OPEN,
            'decided_by_core_reference' => $owner,
            'published_at' => now(),
        ]);
        NeedEvent::query()->create([
            'need_id' => $need->id,
            'event' => 'NEED_PUBLISHED',
            'actor_core_reference' => $owner,
            'from_status' => null,
            'to_status' => $need->status,
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
