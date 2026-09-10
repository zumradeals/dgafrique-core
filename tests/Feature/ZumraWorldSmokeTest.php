<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ZumraWorldSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hub_renders_without_seeded_product_data(): void
    {
        $this->programMember('IDN-SMOKE-EMPTY');
        $this->signIn('IDN-SMOKE-EMPTY');

        $response = $this->get('/zumra')
            ->assertOk()
            ->assertSee('Grandir et agir ensemble.')
            ->assertSee('Mes ZUMRA')
            ->assertSee('ZUMRA à découvrir')
            ->assertSee('Les premières ZUMRA apparaîtront ici.')
            ->assertSee('Explorer les territoires')
            ->assertSee('Les ZUMRA en chiffres')
            ->assertSee('Explorer par territoire')
            ->assertSee('Qu’est-ce qu’une ZUMRA ?')
            ->assertSee('Une communauté')
            ->assertSee('Un projet commun')
            ->assertSee('Une ZUMRA peut faire naître et porter un projet')
            ->assertDontSee('Un projet principal')
            ->assertSee('Un monde d’action')
            ->assertSee('Créer une ZUMRA')
            ->assertSee('Abidjan')
            ->assertSee('Yamoussoukro')
            ->assertDontSee('+ 320')
            ->assertDontSee('+ 18 000')
            ->assertDontSee('+ 1 200');

        if (getenv('ASTRA_VISUAL_EXPORT') === '1') {
            $directory = storage_path('app/astra-visual');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($directory.'/zumra-hub.html', $response->getContent());
        }
    }

    public function test_an_active_program_member_can_open_and_really_create_a_zumra(): void
    {
        $identity = 'IDN-SMOKE-CREATE';
        $this->programMember($identity);
        $this->signIn($identity);

        $this->get('/zumra/groupes/proposer')
            ->assertOk()
            ->assertSee('Faire naître une ZUMRA')
            ->assertSee('Nom de la ZUMRA')
            ->assertSee('Objectif fondateur')
            ->assertSee('Faire naître la ZUMRA')
            ->assertSee('action="'.route('zumra.groups.store').'"', false);

        $response = $this->post('/zumra/groupes', [
            'name' => 'ZUMRA Test Création',
            'domain' => 'Agriculture',
            'founding_objective' => 'Construire ensemble un dispositif agricole local durable qui crée des solutions concrètes pour les membres et leur territoire.',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan, Côte d’Ivoire',
            'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY,
            'assume_primary_lead' => '1',
        ]);

        $group = ZumraGroup::query()->where('name', 'ZUMRA Test Création')->sole();

        $response->assertRedirect(route('zumra.groups.show', $group));
        self::assertSame(ZumraGroup::STATE_CONSTITUTING, $group->state);
        self::assertSame($identity, $group->proposer_core_reference);
        self::assertSame(1, (int) $group->active_member_count);
        self::assertTrue(
            ZumraGroupMembership::query()
                ->where('zumra_group_id', $group->id)
                ->where('core_identity_reference', $identity)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->exists(),
        );
        self::assertTrue(
            ZumraGroupRole::query()
                ->where('zumra_group_id', $group->id)
                ->where('role', 'PRIMARY_LEAD')
                ->where('core_identity_reference', $identity)
                ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
                ->exists(),
        );

        $world = $this->get(route('zumra.groups.show', $group))
            ->assertOk()
            ->assertSee('ZUMRA Test Création')
            ->assertSee('FORMATION · TRAVAIL · ADORATION')
            ->assertSee('Formation')
            ->assertSee('Apprendre, progresser, transmettre.')
            ->assertSee('La première mission d’une ZUMRA est de faire grandir ses membres.')
            ->assertSee('Donnez vie au premier projet')
            ->assertSee('Créer un projet')
            ->assertSee('À faire maintenant')
            ->assertSee('Besoins actuels')
            ->assertSee('Autres projets')
            ->assertSee('Aucun autre projet pour le moment')
            ->assertSee('Une ZUMRA peut très bien avancer avec un seul projet')
            ->assertDontSee('Projet principal')
            ->assertDontSee('Projets dérivés');

        if (getenv('ASTRA_VISUAL_EXPORT') === '1') {
            $directory = storage_path('app/astra-visual');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($directory.'/zumra-world.html', $world->getContent());
        }
    }

    public function test_the_legacy_directory_preserves_its_redirect_contract(): void
    {
        $this->programMember('IDN-SMOKE-REDIRECT');
        $this->signIn('IDN-SMOKE-REDIRECT');
        $this->get('/zumra/groupes?mode=PHYSICAL&location=Yamoussoukro')
            ->assertRedirect(route('zumra.index', ['mode' => 'PHYSICAL', 'location' => 'Yamoussoukro']));
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
