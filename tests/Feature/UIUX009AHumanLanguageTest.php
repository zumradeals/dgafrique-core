<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionBlocker;
use App\Models\MissionDependency;
use App\Models\Proof;
use App\Models\ProofReference;
use App\Models\ProofWitness;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroupMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UIUX-009A — fondation humaine et identité GAMAD. Ces tests vérifient explicitement les deux
 * garanties mécaniques ajoutées par cette mission : (1) chaque constante d'état/rôle/type des
 * modèles touchés a désormais une traduction humaine complète (aucune valeur brute oubliée),
 * (2) plus aucun badge « CAP-0XX » brut n'est visible sur les écrans généraux touchés. Ni l'un ni
 * l'autre ne modifie une seule règle métier — voir git diff pour confirmation.
 */
final class UIUX009AHumanLanguageTest extends TestCase
{
    use RefreshDatabase;

    // ===== Chaque constante d'état/rôle/type a une traduction humaine complète =====

    public function test_transmission_participant_status_labels_cover_every_status(): void
    {
        foreach ([
            TransmissionParticipant::STATUS_INVITED,
            TransmissionParticipant::STATUS_OFFERED,
            TransmissionParticipant::STATUS_ACCEPTED,
            TransmissionParticipant::STATUS_DECLINED,
            TransmissionParticipant::STATUS_WITHDRAWN,
            TransmissionParticipant::STATUS_REMOVED,
        ] as $status) {
            self::assertArrayHasKey($status, TransmissionParticipant::STATUS_LABELS);
            self::assertNotSame($status, TransmissionParticipant::STATUS_LABELS[$status]);
        }
    }

    public function test_transmission_visibility_and_origin_labels_cover_every_value(): void
    {
        foreach (Transmission::ORIGIN_TYPES as $origin) {
            self::assertArrayHasKey($origin, Transmission::ORIGIN_LABELS, "Origine {$origin} sans traduction.");
        }
        foreach ([Transmission::VISIBILITY_PRIVATE, Transmission::VISIBILITY_CONTEXT, Transmission::VISIBILITY_PROGRAM] as $visibility) {
            self::assertArrayHasKey($visibility, Transmission::VISIBILITY_LABELS);
        }
    }

    public function test_proof_witness_reference_and_visibility_labels_cover_every_value(): void
    {
        foreach ([ProofWitness::STATUS_INVITED, ProofWitness::STATUS_CONFIRMED, ProofWitness::STATUS_DECLINED] as $status) {
            self::assertArrayHasKey($status, ProofWitness::STATUS_LABELS);
        }
        foreach ([ProofReference::TYPE_EXTERNAL_URL, ProofReference::TYPE_FREE_TEXT, ProofReference::TYPE_GAMADRIVE_FEDERATED] as $type) {
            self::assertArrayHasKey($type, ProofReference::TYPE_LABELS);
        }
        foreach ([Proof::VISIBILITY_PRIVATE, Proof::VISIBILITY_DISCOVERABLE] as $visibility) {
            self::assertArrayHasKey($visibility, Proof::VISIBILITY_LABELS);
        }
    }

    public function test_mission_assignment_blocker_dependency_and_visibility_labels_cover_every_value(): void
    {
        $roles = [...MissionAssignment::EXECUTION_ROLES, MissionAssignment::ROLE_LEARNER, MissionAssignment::ROLE_OBSERVER];
        foreach ($roles as $role) {
            self::assertArrayHasKey($role, MissionAssignment::ROLE_LABELS);
        }
        foreach ([
            MissionAssignment::STATUS_OFFERED, MissionAssignment::STATUS_INVITED, MissionAssignment::STATUS_ACCEPTED,
            MissionAssignment::STATUS_DECLINED, MissionAssignment::STATUS_WITHDRAWN,
            MissionAssignment::STATUS_RELEASED, MissionAssignment::STATUS_REMOVED,
        ] as $status) {
            self::assertArrayHasKey($status, MissionAssignment::STATUS_LABELS);
        }
        foreach (MissionBlocker::TYPES as $type) {
            self::assertArrayHasKey($type, MissionBlocker::TYPE_LABELS, "Type de blocage {$type} sans traduction — resterait affiché brut dans le <select>.");
            // Le mandat UIUX-009A interdit explicitement un remplacement aveugle : chaque libellé
            // doit être une vraie phrase humaine, jamais juste le nom de la constante reformaté.
            self::assertNotSame($type, MissionBlocker::TYPE_LABELS[$type]);
        }
        foreach (MissionDependency::TYPES as $type) {
            self::assertArrayHasKey($type, MissionDependency::TYPE_LABELS);
        }
        foreach ([Mission::VISIBILITY_PRIVATE, Mission::VISIBILITY_CONTEXT, Mission::VISIBILITY_PROGRAM, Mission::VISIBILITY_PUBLIC] as $visibility) {
            self::assertArrayHasKey($visibility, Mission::VISIBILITY_LABELS);
        }
    }

    public function test_the_rrule_recurrence_field_is_deliberately_untouched(): void
    {
        // Garde-fou de périmètre : UIUX-009A traduit les enums transversaux mais ne reconstruit
        // jamais le champ de récurrence (texte libre RRULE) — cette reconstruction reste réservée
        // à une future Phase B « parcours », conformément au mandat §16.
        $view = file_get_contents(resource_path('views/missions/show.blade.php'));
        self::assertStringContainsString('FREQ=WEEKLY;BYDAY=MO', $view);
    }

    // ===== Plus aucun badge CAP-0XX brut sur les écrans généraux touchés =====

    public function test_no_raw_cap_code_badge_remains_on_touched_screens(): void
    {
        $this->signIn('IDN-U9A-JARGON');

        $pages = [
            fn () => $this->get(route('transmissions.index')),
            fn () => $this->get(route('transmissions.create')),
            fn () => $this->get(route('proofs.index')),
            fn () => $this->get(route('proofs.create')),
            fn () => $this->get(route('missions.index')),
            fn () => $this->get(route('notifications.index')),
        ];

        foreach ($pages as $visit) {
            $content = $visit()->assertOk()->getContent();
            self::assertDoesNotMatchRegularExpression('/CAP-\d{3}\s*·/', $content);
        }
    }

    // ===== ZUMRA : le moment le plus vulnérable (exclusion/suspension) n'affiche plus l'anglais brut =====

    public function test_zumra_membership_status_match_covers_excluded_and_suspended(): void
    {
        $view = file_get_contents(resource_path('views/zumra/groups/show.blade.php'));
        self::assertStringContainsString("'".ZumraGroupMembership::STATUS_EXCLUDED."' => ", $view);
        self::assertStringContainsString("'".ZumraGroupMembership::STATUS_SUSPENDED."' => ", $view);
        self::assertStringNotContainsString(
            "'EXCLUDED' => 'EXCLUDED'",
            $view,
            'Le statut EXCLUDED ne doit jamais retomber sur sa propre valeur brute.'
        );
    }

    // ===== La couleur de fond canonique n'est plus décidée par un accident de cascade =====

    public function test_only_one_file_declares_the_dg_shell_background_rule(): void
    {
        $dg = file_get_contents(resource_path('css/dg.css'));
        self::assertStringContainsString('.dg, .portal-body { margin: 0; background: var(--dg-ivory)', $dg);

        $identity = file_get_contents(resource_path('css/identity-v2.css'));
        self::assertStringNotContainsString('.portal-body', $identity, 'identity-v2.css ne doit plus jamais redéclarer le fond de page — c\'est exactement la régression que UIUX-009A corrige.');
        self::assertStringNotContainsString('.dg {', $identity);

        $gateway = file_get_contents(resource_path('css/gateway-v2.css'));
        self::assertStringNotContainsString('background: #f7f4ee', $gateway);
    }

    public function test_the_gamad_brand_tokens_are_declared_and_used_as_the_canonical_source(): void
    {
        $dg = file_get_contents(resource_path('css/dg.css'));
        self::assertStringContainsString('--gamad-yellow: #f8d40a', $dg);
        self::assertStringContainsString('--gamad-blue: #0875a2', $dg);
        self::assertStringContainsString('--gamad-green: #007a43', $dg);
        self::assertStringContainsString('--dg-saffron: var(--gamad-yellow)', $dg);
        self::assertStringContainsString('--dg-night: var(--gamad-blue)', $dg);
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
