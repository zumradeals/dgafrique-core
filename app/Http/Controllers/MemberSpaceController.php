<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ActivityFeedService;
use App\Application\Missions\MissionService;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Application\Sharing\ContextShareService;
use App\Application\Transmission\TransmissionService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
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
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $profile = PersonProfile::query()->find($identity->reference);
        $zumraMembership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $greetingName = preg_split('/\s+/u', trim($identity->label))[0] ?? $identity->label;
        $profileCompletion = $this->profileCompletion($profile);
        $activityPreview = $activity->preview($identity->reference, 6);

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

        $priority = $this->priority($ownProposedNeed, $ownProject, $zumraMembership, $nextMissionAction, $nextTransmissionAction, $activityPreview, $profile);

        $usedKey = $priority['source_key'] ?? null;
        $rest = $activityPreview->reject(fn (array $item): bool => $item['key'] === $usedKey);

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

        return view('member.space', [
            'identity' => $identity,
            'profile' => $profile,
            'zumraMembership' => $zumraMembership,
            'isAdministrator' => $isAdministrator,
            'greetingName' => $greetingName,
            'profileCompletion' => $profileCompletion,
            'priority' => $priority,
            'nextItems' => $rest->where('kind', 'NEEDS')->where('event', '!=', 'NEED_RESOLVED')->take(2)->values(),
            'weekItems' => $rest->whereIn('kind', ['PROJECTS', 'ZUMRA'])->take(2)->values(),
            'myGroups' => $myGroups,
            'recommendedPeople' => $recommendedPeople,
            'receivedShares' => $receivedShares,
        ]);
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
        ?array $nextMissionAction,
        ?array $nextTransmissionAction,
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

        if ($activityPreview->isNotEmpty()) {
            $item = $activityPreview->first();

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
