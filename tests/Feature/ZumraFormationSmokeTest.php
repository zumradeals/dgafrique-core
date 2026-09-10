<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CapabilityStatement;
use App\Models\Transmission;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ZumraFormationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_sees_own_learning_goals_and_visible_zumra_transmissions_only(): void
    {
        $identity = 'IDN-FORMATION-MEMBER';
        $this->signIn($identity);

        $group = $this->group('GAMAD Technology', 'gamad-technology-formation');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $identity,
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => $identity,
            'joined_at' => now(),
        ]);

        CapabilityStatement::query()->create([
            'holder_type' => CapabilityStatement::HOLDER_PERSON,
            'core_identity_reference' => $identity,
            'kind' => CapabilityStatement::KIND_LEARNING,
            'label' => 'Développement web',
            'normalized_label' => 'developpement web',
            'status' => CapabilityStatement::STATUS_DECLARED,
            'visibility' => CapabilityStatement::VISIBILITY_PRIVATE,
            'matching_consent' => false,
            'source' => 'PROFILE',
        ]);

        $visible = Transmission::query()->create([
            'public_reference' => (string) Str::uuid(),
            'capability_label' => 'Laravel pratique',
            'normalized_label' => 'laravel pratique',
            'learning_objective' => 'Être capable de construire puis tester une petite fonctionnalité Laravel utile au projet.',
            'origin_type' => Transmission::ORIGIN_ZUMRA,
            'origin_reference' => $group->public_reference,
            'context_type' => Transmission::CONTEXT_ZUMRA,
            'context_reference' => $group->public_reference,
            'visibility' => Transmission::VISIBILITY_CONTEXT,
            'status' => Transmission::STATUS_PROPOSED,
            'proposed_by_core_reference' => 'IDN-OTHER-TRANSMITTER',
            'proposed_at' => now(),
        ]);

        Transmission::query()->create([
            'public_reference' => (string) Str::uuid(),
            'capability_label' => 'Transmission privée invisible',
            'normalized_label' => 'transmission privee invisible',
            'learning_objective' => 'Cette Transmission privée ne doit pas être révélée à un membre qui n’est ni participant ni responsable.',
            'origin_type' => Transmission::ORIGIN_ZUMRA,
            'origin_reference' => $group->public_reference,
            'context_type' => Transmission::CONTEXT_ZUMRA,
            'context_reference' => $group->public_reference,
            'visibility' => Transmission::VISIBILITY_PRIVATE,
            'status' => Transmission::STATUS_PROPOSED,
            'proposed_by_core_reference' => 'IDN-PRIVATE-TRANSMITTER',
            'proposed_at' => now(),
        ]);

        $response = $this->get(route('zumra.groups.formation', $group))
            ->assertOk()
            ->assertSee('FORMATION · TRAVAIL · ADORATION')
            ->assertSee('Apprendre. Pratiquer. Transmettre.')
            ->assertSee('Développement web')
            ->assertSee('Laravel pratique')
            ->assertSee(route('transmissions.show', $visible), false)
            ->assertDontSee('Transmission privée invisible')
            ->assertDontSee('65%');

        if (getenv('ASTRA_VISUAL_EXPORT') === '1') {
            $directory = storage_path('app/astra-visual');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($directory.'/zumra-formation.html', $response->getContent());
        }
    }

    public function test_transmission_creation_prefills_only_a_zumra_context_the_member_can_access(): void
    {
        $identity = 'IDN-FORMATION-CREATE';
        $this->signIn($identity);
        $group = $this->group('ZUMRA Formation', 'zumra-formation-create');

        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id,
            'core_identity_reference' => $identity,
            'status' => ZumraGroupMembership::STATUS_ACTIVE,
            'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => $identity,
            'joined_at' => now(),
        ]);

        $choice = Transmission::CONTEXT_ZUMRA.'|'.$group->public_reference;

        $this->get(route('transmissions.create', ['context' => $choice, 'role' => 'LEARNER']))
            ->assertOk()
            ->assertSee('Faire circuler un savoir.')
            ->assertSee('ZUMRA · ZUMRA Formation')
            ->assertSee('name="context_choice" value="'.$choice.'"', false)
            ->assertSee('Je veux apprendre')
            ->assertSee('Proposer la Transmission');
    }

    private function group(string $name, string $slug): ZumraGroup
    {
        return ZumraGroup::query()->create([
            'public_reference' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'domain' => 'Technologie',
            'founding_objective' => 'Former des personnes, développer leurs capacités puis les mettre en mouvement dans des projets utiles et durables.',
            'participation_mode' => 'HYBRID',
            'welcome_capacity' => ZumraGroup::WELCOME_PROGRESSIVELY,
            'location' => 'Abidjan, Côte d’Ivoire',
            'state' => ZumraGroup::STATE_CONSTITUTING,
            'maturity' => ZumraGroup::MATURITY_EMERGING,
            'proposer_core_reference' => 'IDN-FORMATION-FOUNDER',
            'active_member_count' => 1,
        ]);
    }

    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => Http::response(['jeton' => 'bearer-'.$reference, 'entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-20T23:59:00+00:00'], 201),
            'core.test/api/v1/identites/*' => Http::response(['reference' => $reference, 'type' => 'personne', 'libelle' => 'Membre GAMAD', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']),
            'core.test/api/v1/sessions/current' => Http::response(['entite' => $reference, 'assurance' => 'AS1', 'expire_le' => '2026-09-20T23:59:00+00:00']),
        ]);

        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
