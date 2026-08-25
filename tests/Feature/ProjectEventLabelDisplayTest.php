<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BETA-READY-003 (LOT 2) — la fiche Projet doit afficher le libellé humain canonique de
 * ProjectEvent::EVENT_LABELS pour chaque événement du fil « Actions récentes », jamais le code
 * technique brut simplement passé en minuscules (régression : la vue affichait auparavant
 * `str_replace('_',' ',mb_strtolower($event->event))`, ex. « funding_contribution_received »
 * au lieu de « Contribution ZAHAB reçue »).
 */
final class ProjectEventLabelDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_project_page_shows_the_canonical_event_label_not_the_raw_code(): void
    {
        $owner = 'IDN-EVT-OWNER';
        $this->programMember($owner);
        $project = $this->personProject($owner);

        ProjectEvent::query()->create([
            'project_id' => $project->id,
            'event' => 'FUNDING_CONTRIBUTION_RECEIVED',
            'actor_core_reference' => $owner,
            'context' => [],
            'occurred_at' => now(),
        ]);

        $this->signIn($owner);
        $content = $this->get(route('projects.show', $project))->assertOk()->getContent();

        self::assertStringContainsString(ProjectEvent::EVENT_LABELS['FUNDING_CONTRIBUTION_RECEIVED'], $content);
        self::assertStringNotContainsString('funding contribution received', mb_strtolower($content));
    }

    public function test_an_unmapped_event_code_still_falls_back_to_a_humanized_form_without_crashing(): void
    {
        $owner = 'IDN-EVT-OWNER2';
        $this->programMember($owner);
        $project = $this->personProject($owner);

        ProjectEvent::query()->create([
            'project_id' => $project->id,
            'event' => 'SOME_FUTURE_EVENT_CODE',
            'actor_core_reference' => $owner,
            'context' => [],
            'occurred_at' => now(),
        ]);

        $this->signIn($owner);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Some future event code');
    }

    private function personProject(string $owner): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(), 'owner_type' => Project::OWNER_PERSON,
            'owner_reference' => $owner, 'initiator_core_reference' => $owner,
            'name' => 'Projet individuel de test', 'summary' => 'Un projet concret porté par une personne.',
            'problem' => 'Un problème réel à résoudre.', 'proposed_solution' => 'Une solution progressive.',
            'beneficiaries' => 'La communauté locale.', 'domain' => 'DIGITAL', 'participation_mode' => 'HYBRID',
            'objectives' => ['Agir'], 'required_capabilities' => ['Coordination'], 'required_resources' => ['Temps'], 'risks' => [],
            'property_regime' => 'PARTNERSHIP', 'visibility' => Project::VISIBILITY_PRIVATE, 'status' => Project::STATUS_ADOPTED, 'maturity' => 'IDEA',
            'decided_by_core_reference' => $owner, 'adopted_at' => now(),
        ]);
    }

    private function programMember(string $reference): void
    {
        $body = str_repeat('Respect et transmission. ', 8);
        $charter = ZumraCharter::query()->firstOrCreate(
            ['version' => '2026.1'],
            ['title' => 'Charte ZUMRA', 'body' => $body, 'content_hash' => hash('sha256', $body), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()],
        );
        ZumraProgramMembership::query()->firstOrCreate(
            ['core_identity_reference' => $reference],
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
