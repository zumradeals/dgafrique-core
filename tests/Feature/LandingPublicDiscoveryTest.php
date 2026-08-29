<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UIUX-001 §5 « Découvrir » : un visiteur anonyme bénéficie d'une découverte publique limitée et
 * réelle sur la Landing (/decouvrir), strictement bornée à ce que Need::canView()/Project::canView()
 * autorisent déjà pour la visibilité PUBLIC — aucune règle d'autorisation nouvelle et aucune
 * donnée de remplissage quand le portail est vide.
 */
final class LandingPublicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_honest_empty_state_appears_when_no_real_public_object_exists(): void
    {
        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringContainsString('Le réseau public démarre ici.', $content);
        self::assertStringNotContainsString('· Exemple', $content);
    }

    public function test_a_real_public_need_appears_for_an_anonymous_visitor(): void
    {
        $this->need('Un vrai besoin visible sans compte', Need::VISIBILITY_PUBLIC, Need::STATUS_OPEN);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringContainsString('Un vrai besoin visible sans compte', $content);
        self::assertStringNotContainsString('Formation en entrepreneuriat pour jeunes femmes', $content);
    }

    public function test_a_real_public_project_appears_for_an_anonymous_visitor(): void
    {
        $this->project('Un vrai projet visible sans compte', Project::VISIBILITY_PUBLIC, Project::STATUS_ADOPTED);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringContainsString('Un vrai projet visible sans compte', $content);
    }

    public function test_a_private_need_is_never_discoverable_anonymously(): void
    {
        $this->need('Besoin privé jamais public', Need::VISIBILITY_PRIVATE, Need::STATUS_OPEN);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringNotContainsString('Besoin privé jamais public', $content);
    }

    public function test_a_group_scoped_need_is_never_discoverable_anonymously(): void
    {
        $this->need('Besoin réservé à une ZUMRA', Need::VISIBILITY_GROUP, Need::STATUS_OPEN);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringNotContainsString('Besoin réservé à une ZUMRA', $content);
    }

    public function test_a_program_scoped_need_is_never_discoverable_anonymously(): void
    {
        $this->need('Besoin réservé au Programme ZUMRA', Need::VISIBILITY_PROGRAM, Need::STATUS_OPEN);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringNotContainsString('Besoin réservé au Programme ZUMRA', $content);
    }

    public function test_a_proposed_public_need_is_never_discoverable_anonymously(): void
    {
        $this->need('Besoin encore proposé, pas encore publié', Need::VISIBILITY_PUBLIC, Need::STATUS_PROPOSED);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringNotContainsString('Besoin encore proposé, pas encore publié', $content);
    }

    public function test_a_private_project_is_never_discoverable_anonymously(): void
    {
        $this->project('Projet privé jamais public', Project::VISIBILITY_PRIVATE, Project::STATUS_ADOPTED);

        $content = $this->get('/decouvrir')->assertOk()->getContent();

        self::assertStringNotContainsString('Projet privé jamais public', $content);
    }

    public function test_the_route_never_requires_authentication(): void
    {
        $this->get('/decouvrir')->assertOk();
    }

    private function need(string $title, string $visibility, string $status): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_PERSON,
            'owner_reference' => 'IDN-LANDING-OWNER',
            'author_core_reference' => 'IDN-LANDING-OWNER',
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour ce besoin utilisé par les tests de découverte publique.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'location' => 'Abidjan',
            'visibility' => $visibility,
            'status' => $status,
            'decided_by_core_reference' => 'IDN-LANDING-OWNER',
            'published_at' => $status === Need::STATUS_OPEN ? now() : null,
        ]);
    }

    private function project(string $name, string $visibility, string $status): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => 'IDN-LANDING-OWNER',
            'initiator_core_reference' => 'IDN-LANDING-OWNER',
            'source_need_id' => null,
            'name' => $name,
            'summary' => 'Un résumé suffisamment précis pour ce projet utilisé par les tests de découverte publique.',
            'problem' => 'Un problème réel à décrire pour ce test de découverte publique.',
            'proposed_solution' => 'Une solution proposée pour ce test de découverte publique.',
            'beneficiaries' => 'Des bénéficiaires réels pour ce test.',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'image_path' => null,
            'objectives' => [],
            'required_capabilities' => [],
            'required_resources' => [],
            'risks' => [],
            'property_regime' => 'PERSONAL_SUPPORTED',
            'visibility' => $visibility,
            'status' => $status,
            'maturity' => 'IDEA',
            'decided_by_core_reference' => $status === Project::STATUS_ADOPTED ? 'IDN-LANDING-OWNER' : null,
            'adopted_at' => $status === Project::STATUS_ADOPTED ? now() : null,
        ]);
    }
}
