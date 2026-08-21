<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ActivityFeedService;
use App\Application\Missions\MissionService;
use App\Application\Notifications\NotificationService;
use App\Application\Proof\ProofService;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Application\Sharing\ContextShareService;
use App\Application\Transmission\TransmissionService;
use App\Application\Zumra\ZumraAttentionSource;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use App\Models\ZumraProgramMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class MemberSpaceController
{
    public function __invoke(
        Request $request,
        ActivityFeedService $activity,
        PersonRecommendationEngine $recommendationEngine,
        RecommendationConfiguration $recommendationConfiguration,
        ContextShareService $shares,
        MissionService $missions,
        TransmissionService $transmissions,
        ProofService $proofs,
        ZumraAttentionSource $zumraAttention,
        NotificationService $notifications,
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $profile = PersonProfile::query()->find($identity->reference);
        $zumraMembership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $greetingName = preg_split('/\s+/u', trim($identity->label))[0] ?? $identity->label;
        $profileCompletion = $this->profileCompletion($profile);
        $activityPreview = $activity->preview($identity->reference, 6);

        // UIUX-002 — décision #3 : même source que NotificationSourceRegistry/ZumraSpaceController,
        // jamais une requête ZUMRA recalculée ici. Une responsabilité proposée personnellement
        // prime sur une demande d'adhésion à décider pour un collectif (les deux restent, par
        // construction, adressées explicitement au membre).
        $zumraRoleProposal = $zumraAttention->myPendingRoleProposals($identity->reference)->first();
        $zumraJoinRequest = $zumraRoleProposal ? null : $zumraAttention->pendingJoinRequestsForLeader($identity->reference)->first();

        // Un besoin personnel s'ouvre toujours directement en OPEN (NeedService::create) : il n'y a
        // pas de « besoin personnel proposé ». Le seul cas réel d'un besoin proposé par ce membre et
        // en attente d'une décision est un besoin porté par une ZUMRA dont il n'est pas responsable.
        $ownProposedNeed = Need::query()
            ->where('owner_type', Need::OWNER_GROUP)
            ->where('author_core_reference', $identity->reference)
            ->where('status', Need::STATUS_PROPOSED)
            ->latest('created_at')
            ->first();

        $ownProject = Project::query()
            ->where('owner_type', Project::OWNER_PERSON)
            ->where('owner_reference', $identity->reference)
            ->where('status', Project::STATUS_ADOPTED)
            ->latest('created_at')
            ->first();

        $nextMissionAction = $missions->nextAction($identity->reference);
        $nextTransmissionAction = $transmissions->nextAction($identity->reference);
        $nextProofAction = $proofs->nextAction($identity->reference);

        $priority = $this->priority($ownProposedNeed, $ownProject, $zumraMembership, $zumraRoleProposal, $zumraJoinRequest, $nextMissionAction, $nextTransmissionAction, $nextProofAction, $activityPreview, $profile);

        $usedKey = $priority['source_key'] ?? null;
        // UIUX-002 : « Pour vous maintenant »/« Cette semaine » ne montrent plus que des items
        // adossés à une relation personnelle réelle (relevance_reason, CAP-055) — jamais une
        // activité générique du réseau présentée comme personnellement destinée au membre.
        $rest = $activityPreview
            ->reject(fn (array $item): bool => $item['key'] === $usedKey)
            ->filter(fn (array $item): bool => ($item['relevance_reason'] ?? null) !== null);

        $myGroups = ZumraGroup::query()
            ->whereIn('id', ZumraGroupMembership::query()
                ->where('core_identity_reference', $identity->reference)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
                ->pluck('zumra_group_id'))
            ->orderBy('name')
            ->limit(4)
            ->get();

        $recommendedPeople = array_slice(
            $recommendationEngine->forIdentity($identity->reference, $recommendationConfiguration->get())['recommendations'],
            0,
            2,
        );

        $receivedShares = array_slice($shares->personalInbox($identity->reference)['shares']->all(), 0, 2);

        $isNewMember = $this->isNewMember(
            $identity->reference,
            $ownProject,
            $zumraMembership,
            $myGroups,
            $nextMissionAction,
            $nextTransmissionAction,
            $nextProofAction,
            $activityPreview,
        );

        // UIUX-002 — décision #2 : un signal discret vers /notifications, jamais un badge ni une
        // seconde liste ici. Réutilise NotificationService::sections() tel quel — aucun recalcul.
        $hasOtherAttention = $notifications->sections($identity->reference)['a_traiter']->isNotEmpty();

        return view('member.space', [
            'identity' => $identity,
            'profile' => $profile,
            'zumraMembership' => $zumraMembership,
            'isAdministrator' => $isAdministrator,
            'greetingName' => $greetingName,
            'profileCompletion' => $profileCompletion,
            'priority' => $priority,
            'isNewMember' => $isNewMember,
            'hasOtherAttention' => $hasOtherAttention,
            'nextItems' => $rest->where('kind', 'NEEDS')->where('event', '!=', 'NEED_RESOLVED')->take(2)->values(),
            'weekItems' => $rest->whereIn('kind', ['PROJECTS', 'ZUMRA'])->take(2)->values(),
            'myGroups' => $myGroups,
            'recommendedPeople' => $recommendedPeople,
            'receivedShares' => $receivedShares,
        ]);
    }

    /**
     * UIUX-001 : détermine si le membre est encore sans relation réelle avec le réseau, à partir
     * des données métier existantes uniquement — jamais un champ `onboarding_completed` séparé
     * qui pourrait diverger de la réalité. Un membre déjà actif (une seule relation réelle suffit)
     * ne repasse jamais par le routeur de « première intention » : il garde les actions rapides
     * habituelles, y compris l'accès à ZUMRA.
     */
    private function isNewMember(
        string $actor,
        ?Project $ownProject,
        ?ZumraProgramMembership $zumraMembership,
        Collection $myGroups,
        ?array $nextMissionAction,
        ?array $nextTransmissionAction,
        ?array $nextProofAction,
        Collection $activityPreview,
    ): bool {
        if ($ownProject !== null || $zumraMembership !== null || $myGroups->isNotEmpty()) {
            return false;
        }
        if ($nextMissionAction !== null || $nextTransmissionAction !== null || $nextProofAction !== null) {
            return false;
        }
        if ($activityPreview->contains(static fn (array $item): bool => ($item['relevance_reason'] ?? null) !== null)) {
            return false;
        }
        if (CapabilityStatement::query()->where('core_identity_reference', $actor)->whereNull('archived_at')->exists()) {
            return false;
        }
        if (Need::query()->where('author_core_reference', $actor)->exists()) {
            return false;
        }
        if (Project::query()->where('initiator_core_reference', $actor)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Une seule priorité dominante, déduite d'objets réellement disponibles — jamais un moteur
     * de décision fictif (docs/design/DESIGN-INVARIANTS.md §7). Retourne null quand rien ne
     * réclame une décision : l'écran affiche alors l'état vide honnête.
     */
    private function priority(
        ?Need $ownProposedNeed,
        ?Project $ownProject,
        ?ZumraProgramMembership $zumraMembership,
        ?array $zumraRoleProposal,
        ?array $zumraJoinRequest,
        ?array $nextMissionAction,
        ?array $nextTransmissionAction,
        ?array $nextProofAction,
        Collection $activityPreview,
        ?PersonProfile $profile,
    ): ?array {
        if ($ownProposedNeed) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Votre besoin « '.$ownProposedNeed->title.' » attend une décision des responsables.',
                'body' => 'Il a été proposé à votre ZUMRA et n’est pas encore publié officiellement. Vous pouvez consulter son état en attendant leur décision.',
                'primary' => ['label' => 'Voir mon besoin', 'href' => route('needs.show', $ownProposedNeed)],
                'secondary' => null,
            ];
        }

        if ($ownProject) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Votre projet « '.$ownProject->name.' » est adopté et prêt à démarrer.',
                'body' => 'Rien ne se passe tant qu’il n’est pas mis en action. Ouvrez-le pour démarrer ou revoir ce qu’il propose.',
                'primary' => ['label' => 'Ouvrir mon projet', 'href' => route('projects.show', $ownProject)],
                'secondary' => null,
            ];
        }

        if ($zumraMembership && $zumraMembership->status === ZumraProgramMembership::STATUS_PENDING_PAYMENT) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Votre adhésion au Programme ZUMRA attend d’être finalisée.',
                'body' => 'Le dossier est prêt mais la contribution n’a pas encore été réglée.',
                'primary' => ['label' => 'Finaliser mon adhésion', 'href' => route('zumra.membership.show')],
                'secondary' => null,
            ];
        }

        // UIUX-002 — décision #3 : une responsabilité fondatrice qui vous est personnellement
        // proposée, ou une demande d'adhésion que votre responsabilité vous demande de décider,
        // sont toutes deux des obligations explicitement adressées au membre (art. 7
        // ZUMRA-DOCTRINE-INVARIANTE.md) — insérée ici, à côté du seul autre maillon ZUMRA déjà
        // présent dans cette chaîne, sans réordonner les maillons Mission/Transmission/Preuve/Fil
        // déjà établis (évolution minimale, cf. rapport UIUX-002 Phase A).
        if ($zumraRoleProposal) {
            /** @var ZumraGroup $group */
            $group = $zumraRoleProposal['group'];
            /** @var ZumraGroupRole $role */
            $role = $zumraRoleProposal['role'];

            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Une responsabilité vous est proposée dans « '.$group->name.' ».',
                'body' => 'Accepter reste entièrement votre choix.',
                'primary' => ['label' => 'Voir la proposition', 'href' => route('zumra.groups.show', $group)],
                'secondary' => null,
            ];
        }

        if ($zumraJoinRequest) {
            /** @var ZumraGroup $group */
            $group = $zumraJoinRequest['group'];

            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Une demande d’adhésion attend votre décision dans « '.$group->name.' ».',
                'body' => 'Votre responsabilité dans cette ZUMRA vous permet d’examiner cette demande.',
                'primary' => ['label' => 'Examiner la demande', 'href' => route('zumra.groups.show', $group).'#demandes'],
                'secondary' => null,
            ];
        }

        if ($nextMissionAction) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => $nextMissionAction['heading'],
                'body' => $nextMissionAction['body'],
                'primary' => $nextMissionAction['primary'],
                'secondary' => null,
            ];
        }

        if ($nextTransmissionAction) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => $nextTransmissionAction['heading'],
                'body' => $nextTransmissionAction['body'],
                'primary' => $nextTransmissionAction['primary'],
                'secondary' => null,
            ];
        }

        if ($nextProofAction) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => $nextProofAction['heading'],
                'body' => $nextProofAction['body'],
                'primary' => $nextProofAction['primary'],
                'secondary' => null,
            ];
        }

        // UIUX-001 : un objet sans relation personnelle réelle (relevance_reason absent, CAP-055)
        // ne peut jamais devenir la priorité dominante du membre — sinon Mon espace présenterait
        // l'activité d'un inconnu comme « la seule chose qui compte aujourd'hui ». Seule une
        // activité réellement rattachée à ce membre (besoin publié, projet porté ou rejoint,
        // mission assignée, ZUMRA active…) peut occuper ce rang ; le reste du réseau reste visible
        // ailleurs (Fil, sections « Pour vous »/« Cette semaine »), jamais ici.
        $relevantPreview = $activityPreview->filter(
            static fn (array $item): bool => ($item['relevance_reason'] ?? null) !== null,
        );

        if ($relevantPreview->isNotEmpty()) {
            $item = $relevantPreview->first();

            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => $item['title'],
                'body' => $item['summary'],
                'primary' => ['label' => $item['action_label'], 'href' => $item['action_url']],
                'secondary' => null,
                'source_key' => $item['key'],
            ];
        }

        // Une fois qu'un profil existe, sa complétion (y compris le consentement d'orientation,
        // volontaire et révocable) reste purement informative — elle n'impose plus de priorité.
        // Seule l'absence totale de profil justifie encore l'invitation à en commencer un.
        if (! $profile) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Déclarez une chose que vous savez faire.',
                'body' => 'Une seule capacité suffit pour commencer. Déclarer que vous débutez est une réponse valide.',
                'primary' => ['label' => 'Commencer mon profil', 'href' => route('member.profile.edit')],
                'secondary' => null,
            ];
        }

        return null;
    }

    private function profileCompletion(?PersonProfile $profile): int
    {
        if (! $profile) {
            return 0;
        }

        // Le consentement d'orientation est volontaire et révocable (CAP-004) : il ne fait pas
        // partie des critères de complétion, pour qu'un refus ou un retrait ne réduise jamais ce
        // pourcentage informatif.
        $completed = [
            filled($profile->country_code) || filled($profile->city),
            filled($profile->phone),
            filled($profile->current_activity),
            filled($profile->education_level),
            filled($profile->existing_skills) || $profile->starts_without_skill,
            filled($profile->transmission_offers),
            filled($profile->learning_goals),
            filled($profile->experience_highlights) || filled($profile->experience_proofs),
            filled($profile->declared_needs),
            filled($profile->interest_domains),
            filled($profile->intentions),
            filled($profile->participation_mode),
            filled($profile->collaboration_preferences),
        ];

        return (int) round((count(array_filter($completed)) / count($completed)) * 100);
    }
}
