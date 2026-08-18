<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Project;
use App\Models\ZumraCharter;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraProgramMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre CAP-037 (micro-espace de travail) et CAP-038 (tableau de bord collectif) : la fiche
 * ZUMRA agrège ce qu'elle porte réellement, et une seule priorité dominante — jamais un mur de
 * statistiques, jamais un siège vacant fabriqué en fausse action.
 */
final class ZumraGroupWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_collective_priority_prioritizes_a_pending_membership_request_for_the_leader(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->programMember('IDN-APPLICANT');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-APPLICANT',
            'status' => ZumraGroupMembership::STATUS_REQUESTED, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-APPLICANT', 'requested_at' => now(),
        ]);
        $this->groupNeed($group, Need::STATUS_PROPOSED);

        $this->signIn('IDN-LEADER');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Aujourd’hui — une seule chose compte pour cette ZUMRA')
            ->assertSee('Une demande d’adhésion attend une décision.')
            ->assertSee(route('zumra.groups.show', $group).'#demandes', false);
    }

    public function test_collective_priority_falls_back_to_a_proposed_group_need(): void
    {
        $group = $this->group('IDN-LEADER');
        $need = $this->groupNeed($group, Need::STATUS_PROPOSED, 'Former les bénévoles');

        $this->signIn('IDN-LEADER');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Le besoin « Former les bénévoles » attend une décision de publication.')
            ->assertSee(route('needs.show', $need), false);
    }

    public function test_collective_priority_falls_back_to_a_proposed_group_project(): void
    {
        $group = $this->group('IDN-LEADER');
        $project = $this->groupProject($group, Project::STATUS_PROPOSED, 'Atelier mobile');

        $this->signIn('IDN-LEADER');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Le projet « Atelier mobile » attend une décision d’adoption.')
            ->assertSee(route('projects.show', $project), false);
    }

    public function test_collective_priority_is_never_shown_to_a_non_leader_member(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->groupNeed($group, Need::STATUS_PROPOSED);
        $this->programMember('IDN-MEMBER');
        ZumraGroupMembership::query()->create([
            'zumra_group_id' => $group->id, 'core_identity_reference' => 'IDN-MEMBER',
            'status' => ZumraGroupMembership::STATUS_ACTIVE, 'entry_mode' => 'REQUEST',
            'initiated_by_core_reference' => 'IDN-MEMBER', 'joined_at' => now(),
        ]);

        session()->flush();
        $this->signIn('IDN-MEMBER');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertDontSee('Aujourd’hui — une seule chose compte pour cette ZUMRA');
    }

    public function test_a_proposed_group_need_is_hidden_from_a_non_member_but_visible_to_an_active_member(): void
    {
        $group = $this->group('IDN-LEADER');
        $this->groupNeed($group, Need::STATUS_PROPOSED, 'Besoin réservé aux membres');

        $this->programMember('IDN-OUTSIDER');
        $this->signIn('IDN-OUTSIDER');
        $this->get(route('zumra.groups.show', $group))->assertOk()->assertDontSee('Besoin réservé aux membres');

        session()->flush();
        $this->signIn('IDN-LEADER');
        $this->get(route('zumra.groups.show', $group))->assertOk()->assertSee('Besoin réservé aux membres');
    }

    public function test_empty_state_when_the_group_has_no_project_or_need(): void
    {
        $group = $this->group('IDN-LEADER');

        $this->signIn('IDN-LEADER');
        $this->get(route('zumra.groups.show', $group))->assertOk()
            ->assertSee('Cette ZUMRA ne porte encore aucun Projet ni Besoin visible.');
    }

    private function groupNeed(ZumraGroup $group, string $status, string $title = 'Besoin du groupe'): Need
    {
        return Need::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Need::OWNER_GROUP,
            'owner_reference' => $group->id,
            'author_core_reference' => 'IDN-LEADER',
            'title' => $title,
            'context' => 'Un contexte suffisamment précis pour ce besoin porté par la ZUMRA.',
            'category' => 'SKILL',
            'collaboration_mode' => 'LOCAL',
            'visibility' => Need::VISIBILITY_GROUP,
            'status' => $status,
            'decided_by_core_reference' => $status === Need::STATUS_OPEN ? 'IDN-LEADER' : null,
            'published_at' => $status === Need::STATUS_OPEN ? now() : null,
        ]);
    }

    private function groupProject(ZumraGroup $group, string $status, string $name = 'Projet du groupe'): Project
    {
        return Project::query()->create([
            'public_reference' => (string) Str::uuid(),
            'owner_type' => Project::OWNER_GROUP,
            'owner_reference' => $group->id,
            'initiator_core_reference' => 'IDN-LEADER',
            'source_need_id' => null,
            'name' => $name,
            'summary' => 'Un projet concret porté par la ZUMRA.',
            'problem' => 'Un problème réel à résoudre collectivement.',
            'proposed_solution' => 'Une solution progressive et testable.',
            'beneficiaries' => 'Communauté locale',
            'domain' => 'DIGITAL',
            'participation_mode' => 'HYBRID',
            'location' => 'Abidjan',
            'objectives' => ['Agir'],
            'required_capabilities' => ['Coordination'],
            'required_resources' => ['Temps'],
            'risks' => ['Disponibilité'],
            'property_regime' => 'ZUMRA_COLLECTIVE',
            'visibility' => Project::VISIBILITY_GROUP,
            'status' => $status,
            'maturity' => 'IDEA',
            'decided_by_core_reference' => $status === Project::STATUS_ADOPTED ? 'IDN-LEADER' : null,
        ]);
    }

    private function group(string $leader): ZumraGroup
    {
        $this->programMember($leader);
        $this->signIn($leader);
        $this->post('/zumra/groupes', $this->groupPayload())->assertRedirect();

        return ZumraGroup::query()->latest()->firstOrFail();
    }

    private function groupPayload(): array
    {
        return [
            'name' => 'Atelier numérique solidaire '.Str::random(5),
            'domain' => 'Numérique',
            'founding_objective' => 'Former une équipe qui transmet les outils numériques et réalise des solutions utiles aux communautés locales.',
            'participation_mode' => 'HYBRID',
            'internal_charter' => 'Chaque membre respecte la dignité, la hiérarchie, la transmission et les décisions responsables. Les admissions sont approuvées, le départ reste libre et toute exclusion doit être motivée.',
            'assume_primary_lead' => '1',
        ];
    }

    private function programMember(string $identity): void
    {
        $charter = ZumraCharter::query()->firstOrCreate(['version' => '2026.1'], ['title' => 'Charte ZUMRA', 'body' => str_repeat('Respect et transmission. ', 8), 'content_hash' => hash('sha256', 'charter'), 'status' => ZumraCharter::STATUS_PUBLISHED, 'published_at' => now()]);
        ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference' => $identity], ['status' => ZumraProgramMembership::STATUS_ACTIVE, 'accepted_charter_id' => $charter->id, 'accepted_charter_version' => $charter->version, 'accepted_charter_hash' => $charter->content_hash, 'charter_accepted_at' => now(), 'submitted_at' => now(), 'activated_at' => now()]);
    }

    /**
     * Les closures lisent l'identité réellement demandée dans chaque requête (au lieu de la figer
     * dans une réponse statique) : un test qui se connecte successivement sous plusieurs identités
     * verrait sinon la toute première réponse enregistrée gagner pour toujours (Http::fake empile
     * les callbacks, il ne les remplace pas).
     */
    private function signIn(string $reference): void
    {
        Http::fake([
            'core.test/api/v1/sessions' => function ($request) {
                $entite = $request['identifiant'] ?? $request['entite'];

                return Http::response(['jeton' => 'bearer-'.$entite, 'entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00'], 201);
            },
            'core.test/api/v1/identites/*' => function ($request) {
                $entite = Str::afterLast($request->url(), '/');

                return Http::response(['reference' => $entite, 'type' => 'personne', 'libelle' => 'Membre ZUMRA', 'etat' => 'ACTIF', 'source' => 'CORE', 'regime' => 'INSCRIT_AU_REGISTRE']);
            },
            'core.test/api/v1/sessions/current' => function ($request) {
                $entite = Str::after((string) $request->header('Authorization')[0], 'bearer-');

                return Http::response(['entite' => $entite, 'assurance' => 'AS1', 'expire_le' => '2026-08-16T23:59:00+00:00']);
            },
        ]);
        $this->post('/connexion', ['identifier' => $reference, 'secret' => 'secret'])->assertRedirect('/espace');
    }
}
