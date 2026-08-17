<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Activity\ActivityFeedService;
use App\Application\Recommendation\PersonRecommendationEngine;
use App\Application\Recommendation\RecommendationConfiguration;
use App\Application\Sharing\ContextShareService;
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
    ): View {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $profile = PersonProfile::query()->find($identity->reference);
        $zumraMembership = ZumraProgramMembership::query()->where('core_identity_reference', $identity->reference)->first();
        $isAdministrator = PortalAdministrator::query()->whereKey($identity->reference)->exists();
        $greetingName = preg_split('/\s+/u', trim($identity->label))[0] ?? $identity->label;
        $profileCompletion = $this->profileCompletion($profile);
        $activityPreview = $activity->preview($identity->reference, 6);

        $ownNeed = Need::query()
            ->where('owner_type', Need::OWNER_PERSON)
            ->where('owner_reference', $identity->reference)
            ->where('status', Need::STATUS_PROPOSED)
            ->latest('created_at')
            ->first();

        $ownProject = Project::query()
            ->where('owner_type', Project::OWNER_PERSON)
            ->where('owner_reference', $identity->reference)
            ->where('status', Project::STATUS_ADOPTED)
            ->latest('created_at')
            ->first();

        $priority = $this->priority($ownNeed, $ownProject, $zumraMembership, $activityPreview, $profile, $profileCompletion);

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
        ?Need $ownNeed,
        ?Project $ownProject,
        ?ZumraProgramMembership $zumraMembership,
        Collection $activityPreview,
        ?PersonProfile $profile,
        int $profileCompletion,
    ): ?array {
        if ($ownNeed) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => 'Votre besoin « '.$ownNeed->title.' » attend d’être publié.',
                'body' => 'Il est proposé aux responsables mais n’est pas encore visible dans le réseau. Publiez-le officiellement, ou ajustez-le avant de le rendre public.',
                'primary' => ['label' => 'Ouvrir mon besoin', 'href' => route('needs.show', $ownNeed)],
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

        if (! $profile || $profileCompletion < 100) {
            return [
                'label' => 'Aujourd’hui — une seule chose compte',
                'heading' => $profile ? 'Continuer votre profil de capacités.' : 'Déclarez une chose que vous savez faire.',
                'body' => 'Une seule capacité suffit pour commencer. Déclarer que vous débutez est une réponse valide.',
                'primary' => ['label' => $profile ? 'Continuer mon profil' : 'Commencer mon profil', 'href' => route('member.profile.edit')],
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
            $profile->orientation_consent,
        ];

        return (int) round((count(array_filter($completed)) / count($completed)) * 100);
    }
}
