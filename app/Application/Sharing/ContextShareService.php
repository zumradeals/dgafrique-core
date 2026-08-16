<?php

declare(strict_types=1);

namespace App\Application\Sharing;

use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Models\ContextShare;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\Project;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ContextShareService
{
    public const MAX_CONTEXT_LENGTH = 800;
    private const INBOX_LIMIT = 80;
    private const PEOPLE_LIMIT = 100;

    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
    ) {}

    public function needComposer(Need $need, string $actor): array
    {
        $this->assertShareable($need, $actor);

        return $this->composer($need, $actor, route('shares.need.store', $need));
    }

    public function projectComposer(Project $project, string $actor): array
    {
        $this->assertShareable($project, $actor);

        return $this->composer($project, $actor, route('shares.project.store', $project));
    }

    public function shareNeed(Need $need, string $actor, string $targetType, string $targetReference, string $context): ContextShare
    {
        return $this->share($need, $actor, $targetType, $targetReference, $context);
    }

    public function shareProject(Project $project, string $actor, string $targetType, string $targetReference, string $context): ContextShare
    {
        return $this->share($project, $actor, $targetType, $targetReference, $context);
    }

    public function personalInbox(string $actor): array
    {
        $shares = ContextShare::query()
            ->where('target_type', ContextShare::TARGET_PERSON)
            ->where('target_reference', $actor)
            ->latest('shared_at')
            ->limit(self::INBOX_LIMIT)
            ->get();

        return [
            'title' => 'Partages reçus',
            'intro' => 'Des besoins et projets vous ont été transmis avec leur contexte d’origine. Un partage ne modifie jamais vos droits d’accès.',
            'backUrl' => route('member.space'),
            'backLabel' => 'Retour à mon espace',
            'shares' => $this->visibleShares($shares, $actor),
        ];
    }

    public function groupInbox(ZumraGroup $group, string $actor): array
    {
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 404);
        abort_unless($this->isActiveGroupMember($group, $actor), 403);

        $shares = ContextShare::query()
            ->where('target_type', ContextShare::TARGET_ZUMRA)
            ->where('target_reference', $group->public_reference)
            ->latest('shared_at')
            ->limit(self::INBOX_LIMIT)
            ->get();

        return [
            'title' => 'Partages utiles — '.$group->name,
            'intro' => 'Ces objets ont été transmis à la ZUMRA pour soutenir une action réelle. Chaque membre ne voit que les sources auxquelles il a encore accès.',
            'backUrl' => route('zumra.groups.show', $group),
            'backLabel' => 'Retour à la ZUMRA',
            'shares' => $this->visibleShares($shares, $actor),
        ];
    }

    private function composer(Need|Project $source, string $actor, string $storeUrl): array
    {
        $people = PersonProfile::query()
            ->where('core_identity_reference', '!=', $actor)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_reference')
            ->whereNotNull('discovery_display_name')
            ->orderBy('discovery_display_name')
            ->limit(self::PEOPLE_LIMIT)
            ->get(['core_identity_reference', 'discovery_reference', 'discovery_display_name'])
            ->filter(fn (PersonProfile $profile): bool => $this->canView($source, $profile->core_identity_reference))
            ->map(static fn (PersonProfile $profile): array => [
                'reference' => $profile->discovery_reference,
                'label' => $profile->discovery_display_name,
            ])
            ->values();

        $groups = ZumraGroup::query()
            ->where('state', '!=', ZumraGroup::STATE_SUSPENDED)
            ->whereHas('memberships', static fn ($query) => $query
                ->where('core_identity_reference', $actor)
                ->where('status', ZumraGroupMembership::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'public_reference', 'name'])
            ->map(static fn (ZumraGroup $group): array => [
                'reference' => $group->public_reference,
                'label' => $group->name,
            ])
            ->values();

        return [
            'source' => $this->descriptor($source),
            'storeUrl' => $storeUrl,
            'people' => $people,
            'groups' => $groups,
            'maxContextLength' => self::MAX_CONTEXT_LENGTH,
        ];
    }

    private function share(Need|Project $source, string $actor, string $targetType, string $targetReference, string $context): ContextShare
    {
        $this->assertShareable($source, $actor);

        $context = trim($context);
        abort_if($context === '' || mb_strlen($context) < 2, 422, 'Expliquez brièvement pourquoi ce partage est utile.');
        abort_if(mb_strlen($context) > self::MAX_CONTEXT_LENGTH, 422, 'Le contexte du partage est trop long.');

        $targetType = strtoupper(trim($targetType));
        $targetReference = trim($targetReference);
        abort_unless(in_array($targetType, [ContextShare::TARGET_PERSON, ContextShare::TARGET_ZUMRA], true), 422, 'La destination du partage est invalide.');
        abort_if($targetReference === '', 422, 'Choisissez une destination.');

        $storedTarget = match ($targetType) {
            ContextShare::TARGET_PERSON => $this->personTarget($source, $actor, $targetReference),
            ContextShare::TARGET_ZUMRA => $this->groupTarget($actor, $targetReference),
        };

        $descriptor = $this->descriptor($source);

        return ContextShare::query()->firstOrCreate([
            'source_type' => $descriptor['type'],
            'source_reference' => $descriptor['reference'],
            'sharer_core_reference' => $actor,
            'target_type' => $targetType,
            'target_reference' => $storedTarget,
        ], [
            'public_reference' => (string) Str::uuid(),
            'context_note' => $context,
            'shared_at' => now(),
        ]);
    }

    private function personTarget(Need|Project $source, string $actor, string $publicReference): string
    {
        $profile = PersonProfile::query()
            ->where('discovery_reference', $publicReference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first();

        abort_unless($profile !== null, 422, 'Cette personne n’est pas disponible pour un partage.');
        abort_if(hash_equals($actor, $profile->core_identity_reference), 422, 'Vous n’avez pas besoin de vous partager cet objet.');
        abort_unless($this->canView($source, $profile->core_identity_reference), 422, 'Cette personne n’a pas accès à cet objet dans sa visibilité actuelle.');

        return $profile->core_identity_reference;
    }

    private function groupTarget(string $actor, string $publicReference): string
    {
        $group = ZumraGroup::query()->where('public_reference', $publicReference)->first();
        abort_unless($group !== null, 422, 'Cette ZUMRA n’est pas disponible pour un partage.');
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 409, 'Une ZUMRA suspendue ne reçoit pas de partage.');
        abort_unless($this->isActiveGroupMember($group, $actor), 403);

        return $group->public_reference;
    }

    private function assertShareable(Need|Project $source, string $actor): void
    {
        abort_unless($this->canView($source, $actor), 404);

        if ($source instanceof Need) {
            abort_if($source->status === Need::STATUS_ARCHIVED, 409, 'Un besoin archivé ne circule plus comme possibilité d’action.');
        } else {
            abort_if($source->status === Project::STATUS_ARCHIVED, 409, 'Un projet archivé ne circule plus comme possibilité d’action.');
        }
    }

    private function canView(Need|Project $source, string $actor): bool
    {
        return $source instanceof Need
            ? $this->needs->canView($source, $actor)
            : $this->projects->canView($source, $actor);
    }

    private function visibleShares(Collection $shares, string $actor): Collection
    {
        return $shares
            ->map(fn (ContextShare $share): ?array => $this->render($share, $actor))
            ->filter()
            ->values();
    }

    private function render(ContextShare $share, string $actor): ?array
    {
        $source = match ($share->source_type) {
            ContextShare::SOURCE_NEED => Need::query()->where('public_reference', $share->source_reference)->first(),
            ContextShare::SOURCE_PROJECT => Project::query()->where('public_reference', $share->source_reference)->first(),
            default => null,
        };

        if (!$source instanceof Need && !$source instanceof Project) {
            return null;
        }
        if (!$this->canView($source, $actor)) {
            return null;
        }

        $descriptor = $this->descriptor($source);

        return [
            'public_reference' => $share->public_reference,
            'source_type' => $descriptor['type'],
            'source_label' => $descriptor['label'],
            'source_title' => $descriptor['title'],
            'source_summary' => $descriptor['summary'],
            'source_url' => $descriptor['url'],
            'context_note' => $share->context_note,
            'shared_at' => $share->shared_at,
            'sharer_label' => $this->sharerLabel($share->sharer_core_reference, $actor),
        ];
    }

    private function descriptor(Need|Project $source): array
    {
        if ($source instanceof Need) {
            return [
                'type' => ContextShare::SOURCE_NEED,
                'reference' => $source->public_reference,
                'label' => 'Besoin',
                'title' => $source->title,
                'summary' => Str::limit(trim($source->context), 260),
                'url' => route('needs.show', $source),
            ];
        }

        return [
            'type' => ContextShare::SOURCE_PROJECT,
            'reference' => $source->public_reference,
            'label' => 'Projet',
            'title' => $source->name,
            'summary' => Str::limit(trim($source->summary), 260),
            'url' => route('projects.show', $source),
        ];
    }

    private function sharerLabel(string $reference, string $actor): string
    {
        if (hash_equals($reference, $actor)) {
            return 'Vous';
        }

        $profile = PersonProfile::query()
            ->where('core_identity_reference', $reference)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->first(['discovery_display_name']);

        return $profile?->discovery_display_name ?: 'Membre DG Afrique';
    }

    private function isActiveGroupMember(ZumraGroup $group, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->exists();
    }
}
