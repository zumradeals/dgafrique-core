<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Transmission\TransmissionWorkflow;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ZUMRA-SPACE-001 — le carrefour /zumra devient un vrai carrefour de découverte par activité, et
 * zumra.groups.show devient un véritable Espace ZUMRA organisé autour de l'humain plutôt que de
 * la gouvernance. Aucune nouvelle taxonomie, aucun nouveau système de formation, aucun deuxième
 * Fil : chaque capacité testée réutilise une plomberie runtime déjà confirmée par l'audit.
 */
final class ZumraSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_directory_can_be_searched_by_activity(): void
    {
        $this->group('IDN-SPACE-A', 'Couture', 'Atelier couture');
        $this->group('IDN-SPACE-B', 'Numérique', 'Atelier numérique');
        $this->signIn('IDN-SPACE-VIEWER');

        $content = $this->get('/zumra/groupes?q=Couture')->assertOk()->getContent();

        self::assertStringContainsString('Atelier couture', $content);
        self::assertStringNotContainsString('Atelier numérique', $content);
    }

    public function test_a_search_with_no_match_shows_an_honest_empty_state(): void
    {
        $this->group('IDN-SPACE-C', 'Menuiserie', 'Atelier bois');
        $this->signIn('IDN-SPACE-VIEWER2');

        $this->get('/zumra/groupes?q=Agriculture')->assertOk()
            ->assertSee('Aucune ZUMRA ne correspond à')
            ->assertDontSee('Atelier bois');
    }

    public function test_the_hub_surfaces_a_real_search_form_and_activity_discovery(): void
    {
        $this->group('IDN-SPACE-D', 'Agriculture', 'Coopérative maraîchère');
        $this->signIn('IDN-SPACE-VIEWER3');

        $this->get('/zumra')->assertOk()
            ->assertSee('Explorer par activité')
            ->assertSee('Agriculture')
            ->assertDontSee('Cinq responsabilités, cinq personnes');
    }

    public function test_the_espace_zumra_surfaces_the_principal_activity_high_and_governance_low(): void
    {
        $group = $this->group('IDN-SPACE-E', 'Textile', 'Atelier textile solidaire');
        $this->signIn('IDN-SPACE-VIEWER4');

        $content = $this->get(route('zumra.groups.show', $group))->assertOk()->getContent();

        $activityPosition = mb_strpos($content, 'Activité principale : Textile');
        $governancePosition = mb_strpos($content, 'Gouvernance fondatrice');
        self::assertNotFalse($activityPosition);
        self::assertNotFalse($governancePosition);
        self::assertLessThan($governancePosition, $activityPosition, 'L’activité principale doit apparaître avant la gouvernance dans l’Espace ZUMRA.');
    }

    public function test_transmissions_attached_to_a_zumra_are_surfaced_using_existing_plumbing(): void
    {
        $group = $this->group('IDN-SPACE-F', 'Couture', 'Atelier couture avancé');
        $transmission = app(TransmissionWorkflow::class)->create('IDN-SPACE-F', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Couture main',
            'learning_objective' => 'Transmettre les bases de la couture main aux nouveaux membres.',
            'origin_type' => Transmission::ORIGIN_ZUMRA,
            'context_type' => Transmission::CONTEXT_ZUMRA,
            'context_reference' => $group->public_reference,
            'visibility' => Transmission::VISIBILITY_CONTEXT,
        ]);

        $this->signIn('IDN-SPACE-F');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Couture main')
            ->assertSee('Proposée');

        self::assertSame(Transmission::CONTEXT_ZUMRA, $transmission->context_type);
    }

    public function test_a_non_member_never_sees_a_private_transmission_attached_to_the_zumra(): void
    {
        // Construit la ZUMRA et la Transmission par service direct — jamais une seconde
        // connexion HTTP dans ce test : Http::fake() résout par motif d'URL, pas par en-tête,
        // donc une deuxième session simulée dans le même test réutiliserait la première identité.
        $this->programMember('IDN-SPACE-G');
        $group = app(ZumraGroupService::class)->create('IDN-SPACE-G', [
            'name' => 'Atelier couture fermé '.Str::random(4),
            'domain' => 'Couture',
            'founding_objective' => 'Un objectif fondateur suffisamment détaillé pour ce test réel.',
            'participation_mode' => 'HYBRID',
            'assume_primary_lead' => true,
        ], 3);
        app(TransmissionWorkflow::class)->create('IDN-SPACE-G', TransmissionParticipant::ROLE_TRANSMITTER, [
            'capability_label' => 'Broderie privée',
            'learning_objective' => 'Transmission réservée, jamais visible hors contexte.',
            'origin_type' => Transmission::ORIGIN_ZUMRA,
            'context_type' => Transmission::CONTEXT_ZUMRA,
            'context_reference' => $group->public_reference,
            'visibility' => Transmission::VISIBILITY_PRIVATE,
        ]);

        $this->programMember('IDN-SPACE-OUTSIDER');
        $this->signIn('IDN-SPACE-OUTSIDER');
        $this->get(route('zumra.groups.show', $group))->assertOk()->assertDontSee('Broderie privée');
    }

    public function test_the_birth_moment_never_mentions_the_charter(): void
    {
        $this->programMember('IDN-SPACE-BIRTH');
        $this->signIn('IDN-SPACE-BIRTH');

        $content = $this->get('/zumra/groupes/proposer')->assertOk()->getContent();
        self::assertStringNotContainsStringIgnoringCase('charte', $content);
    }

    public function test_the_birth_route_is_reachable_and_returns_to_the_zumra_world(): void
    {
        $this->programMember('IDN-SPACE-BIRTH2');
        $this->signIn('IDN-SPACE-BIRTH2');

        $this->get('/zumra/groupes/proposer')->assertOk()->assertSee(route('zumra.index'), false);
    }

    private function group(string $founder, string $domain, string $name): ZumraGroup
    {
        $this->programMember($founder);
        $this->signIn($founder);
        $this->post('/zumra/groupes', [
            'name' => $name.' '.Str::random(4),
            'domain' => $domain,
            'founding_objective' => 'Un objectif fondateur suffisamment détaillé pour ce test réel.',
            'participation_mode' => 'HYBRID',
            'assume_primary_lead' => '1',
        ])->assertRedirect();

        return ZumraGroup::query()->latest()->firstOrFail();
    }

    private function programMember(string $identity): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $identity],
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
