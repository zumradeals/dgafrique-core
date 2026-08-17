<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Missions\MissionVisibilityService;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectService;
use App\Application\Transmission\TransmissionVisibilityService;
use App\Models\MessageConversation;
use App\Models\MessageEntry;
use App\Models\MessageParticipant;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\Need;
use App\Models\PersonProfile;
use App\Models\PortalAdministrator;
use App\Models\Project;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use App\Models\ZumraGroup;
use App\Models\ZumraGroupMembership;
use App\Models\ZumraGroupRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MessagingService
{
    private const MAX_BODY_LENGTH = 3000;
    private const INBOX_LIMIT = 100;
    private const THREAD_LIMIT = 100;

    public function __construct(
        private readonly NeedService $needs,
        private readonly ProjectService $projects,
        private readonly MissionVisibilityService $missionVisibility,
        private readonly TransmissionVisibilityService $transmissionVisibility,
    ) {
    }

    public function startDirect(string $actor, string $discoveryReference): MessageConversation
    {
        $recipient = PersonProfile::query()
            ->where('discovery_reference', $discoveryReference)
            ->where('core_identity_reference', '!=', $actor)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->firstOrFail();

        return $this->ensureConversation(
            MessageConversation::KIND_DIRECT,
            [$actor, $recipient->core_identity_reference],
            null,
            null,
            null,
            $actor,
            false,
        );
    }

    public function openNeed(string $actor, Need $need): MessageConversation
    {
        abort_unless($this->needs->canView($need, $actor), 404);
        abort_if($need->status === Need::STATUS_ARCHIVED, 409, 'Ce besoin est archivé.');

        $recipients = $need->owner_type === Need::OWNER_PERSON
            ? collect([$need->owner_reference])
            : $this->groupLeaders((string) $need->owner_reference);

        if ($recipients->isEmpty()) {
            $recipients = collect([$need->author_core_reference]);
        }

        $participants = collect([$actor])->merge($recipients)->filter()->unique()->values();
        abort_if($participants->count() < 2, 409, 'Aucun autre interlocuteur n’est disponible pour ce besoin.');

        return $this->ensureConversation(
            $participants->count() === 2 ? MessageConversation::KIND_DIRECT : MessageConversation::KIND_GROUP,
            $participants->all(),
            'Besoin · '.$need->title,
            MessageConversation::CONTEXT_NEED,
            $need->public_reference,
            $actor,
            false,
        );
    }

    public function openZumra(string $actor, ZumraGroup $group): MessageConversation
    {
        abort_if($group->state === ZumraGroup::STATE_SUSPENDED, 409, 'Cette ZUMRA est suspendue.');
        abort_unless($this->isActiveGroupMember($group->id, $actor), 403);

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            $this->activeGroupMembers($group->id)->all(),
            'ZUMRA · '.$group->name,
            MessageConversation::CONTEXT_ZUMRA,
            $group->public_reference,
            $actor,
            true,
        );
    }

    public function openInvitation(string $actor, ZumraGroup $group): MessageConversation
    {
        $invitation = ZumraGroupMembership::query()
            ->where('zumra_group_id', $group->id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_INVITED)
            ->firstOrFail();

        $leaders = $this->groupLeaders($group->id);
        if ($leaders->isEmpty()) {
            $leaders = collect([$group->proposer_core_reference]);
        }

        $participants = collect([$actor])->merge($leaders)->filter()->unique()->values();
        abort_if($participants->count() < 2, 409, 'Aucun responsable n’est disponible pour cette invitation.');

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            $participants->all(),
            'Invitation · '.$group->name,
            MessageConversation::CONTEXT_INVITATION,
            $invitation->id,
            $actor,
            true,
        );
    }

    public function openProject(string $actor, Project $project): MessageConversation
    {
        abort_unless($this->projects->canDecide($project, $actor), 403);
        abort_if($project->status === Project::STATUS_ARCHIVED, 409, 'Ce projet est archivé.');

        $participants = collect([$actor, $project->initiator_core_reference]);
        if ($project->owner_type === Project::OWNER_PERSON) {
            $participants->push($project->owner_reference);
        }

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            $participants->filter()->unique()->values()->all(),
            'Projet · '.$project->name,
            MessageConversation::CONTEXT_PROJECT,
            $project->public_reference,
            $actor,
            true,
        );
    }

    /**
     * La conversation garde le contexte Mission mais ne devient jamais la source de
     * vérité des décisions Mission (v0.4 §14) : elle coordonne, elle ne statue pas.
     */
    public function openMission(string $actor, Mission $mission): MessageConversation
    {
        abort_unless($this->missionVisibility->canViewMission($mission, $actor), 404);
        abort_if(in_array($mission->status, Mission::TERMINAL_STATUSES, true), 409, 'Cette Mission est terminée.');

        $participants = MissionAssignment::query()
            ->where('mission_id', $mission->id)
            ->where('status', MissionAssignment::STATUS_ACCEPTED)
            ->pluck('core_identity_reference')
            ->push($mission->created_by_core_reference)
            ->push($actor)
            ->filter()
            ->unique()
            ->values();

        abort_if($participants->count() < 2, 409, 'Aucun autre interlocuteur n’est disponible pour cette Mission.');

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            $participants->all(),
            'Mission · '.$mission->title,
            MessageConversation::CONTEXT_MISSION,
            $mission->public_reference,
            $actor,
            true,
        );
    }

    /**
     * La conversation garde le contexte Transmission mais ne devient jamais la source de
     * vérité de son statut (fiche §21) : elle coordonne, elle ne statue pas.
     */
    public function openTransmission(string $actor, Transmission $transmission): MessageConversation
    {
        abort_unless($this->transmissionVisibility->canView($transmission, $actor), 404);
        abort_if(in_array($transmission->status, Transmission::TERMINAL_STATUSES, true), 409, 'Cette Transmission est terminée.');

        $participants = TransmissionParticipant::query()
            ->where('transmission_id', $transmission->id)
            ->where('status', TransmissionParticipant::STATUS_ACCEPTED)
            ->pluck('core_identity_reference')
            ->push($transmission->proposed_by_core_reference)
            ->push($actor)
            ->filter()
            ->unique()
            ->values();

        abort_if($participants->count() < 2, 409, 'Aucun autre interlocuteur n’est disponible pour cette Transmission.');

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            $participants->all(),
            'Transmission · '.$transmission->capability_label,
            MessageConversation::CONTEXT_TRANSMISSION,
            $transmission->public_reference,
            $actor,
            true,
        );
    }

    public function addProjectParticipant(MessageConversation $conversation, string $actor, string $discoveryReference): void
    {
        $this->assertAccess($conversation, $actor);
        abort_unless($conversation->context_type === MessageConversation::CONTEXT_PROJECT, 409, 'Cette conversation n’est pas liée à un projet.');

        $project = Project::query()->where('public_reference', $conversation->context_reference)->firstOrFail();
        abort_unless($this->projects->canDecide($project, $actor), 403);

        $profile = PersonProfile::query()
            ->where('discovery_reference', $discoveryReference)
            ->where('core_identity_reference', '!=', $actor)
            ->where('orientation_consent', true)
            ->where('discovery_consent', true)
            ->whereNotNull('discovery_display_name')
            ->firstOrFail();

        abort_unless(
            $this->projects->canView($project, $profile->core_identity_reference),
            422,
            'Cette personne ne peut pas accéder au projet dans son niveau de visibilité actuel.'
        );

        $this->addParticipants($conversation, [$profile->core_identity_reference]);
    }

    public function openSupport(string $actor): MessageConversation
    {
        $administrators = PortalAdministrator::query()->pluck('core_identity_reference')->filter()->values();
        abort_if($administrators->isEmpty(), 409, 'Aucun interlocuteur DG Afrique n’est disponible pour le moment.');

        return $this->ensureConversation(
            MessageConversation::KIND_GROUP,
            collect([$actor])->merge($administrators)->unique()->values()->all(),
            'Échange avec DG Afrique',
            MessageConversation::CONTEXT_DG_AFRIQUE,
            $actor,
            $actor,
            true,
        );
    }

    public function send(MessageConversation $conversation, string $actor, string $body): MessageEntry
    {
        $this->assertAccess($conversation, $actor);
        $body = trim($body);
        abort_if($body === '', 422, 'Le message ne peut pas être vide.');
        abort_if(mb_strlen($body) > self::MAX_BODY_LENGTH, 422, 'Le message est trop long.');

        return DB::transaction(function () use ($conversation, $actor, $body): MessageEntry {
            $sentAt = now();
            $entry = MessageEntry::query()->create([
                'conversation_id' => $conversation->id,
                'sender_core_reference' => $actor,
                'body' => $body,
                'sent_at' => $sentAt,
            ]);

            $conversation->update(['last_message_at' => $sentAt]);
            MessageParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('core_identity_reference', $actor)
                ->update(['last_read_at' => $sentAt]);

            return $entry;
        });
    }

    public function inbox(string $actor): Collection
    {
        return MessageConversation::query()
            ->whereHas('participants', static fn ($query) => $query->where('core_identity_reference', $actor))
            ->with('participants')
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->limit(self::INBOX_LIMIT)
            ->get()
            ->filter(fn (MessageConversation $conversation): bool => $this->canAccess($conversation, $actor))
            ->map(function (MessageConversation $conversation) use ($actor): array {
                $participant = $conversation->participants->firstWhere('core_identity_reference', $actor);
                $last = MessageEntry::query()->where('conversation_id', $conversation->id)->latest('sent_at')->first();
                $context = $this->contextData($conversation, $actor);

                return [
                    'conversation' => $conversation,
                    'title' => $this->conversationTitle($conversation, $actor),
                    'context_label' => $context['label'],
                    'context_url' => $context['url'],
                    'last_message' => $last?->body,
                    'last_message_at' => $last?->sent_at ?? $conversation->created_at,
                    'unread' => $last !== null
                        && $last->sender_core_reference !== $actor
                        && ($participant?->last_read_at === null || $last->sent_at->isAfter($participant->last_read_at)),
                ];
            })
            ->values();
    }

    public function thread(MessageConversation $conversation, string $actor): array
    {
        $this->assertAccess($conversation, $actor);
        $this->markRead($conversation, $actor);
        $conversation->load('participants');

        $entries = MessageEntry::query()
            ->where('conversation_id', $conversation->id)
            ->latest('sent_at')
            ->limit(self::THREAD_LIMIT)
            ->get()
            ->reverse()
            ->values();

        $refs = $conversation->participants->pluck('core_identity_reference')
            ->merge($entries->pluck('sender_core_reference'))
            ->unique()
            ->values();
        $labels = $this->labelsFor($refs, $actor);
        $context = $this->contextData($conversation, $actor);

        return [
            'conversation' => $conversation,
            'entries' => $entries,
            'title' => $this->conversationTitle($conversation, $actor),
            'participantLabels' => $conversation->participants
                ->map(fn (MessageParticipant $participant): string => $labels[$participant->core_identity_reference] ?? 'Membre DG Afrique')
                ->unique()
                ->values(),
            'senderLabels' => $labels,
            'contextLabel' => $context['label'],
            'contextUrl' => $context['url'],
            'canManageParticipants' => $context['can_manage_participants'],
        ];
    }

    public function canAccess(MessageConversation $conversation, string $actor): bool
    {
        if (! MessageParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('core_identity_reference', $actor)
            ->exists()) {
            return false;
        }

        return match ($conversation->context_type) {
            MessageConversation::CONTEXT_NEED => $this->canAccessNeedContext($conversation, $actor),
            MessageConversation::CONTEXT_ZUMRA => $this->canAccessZumraContext($conversation, $actor),
            MessageConversation::CONTEXT_INVITATION => $this->canAccessInvitationContext($conversation, $actor),
            MessageConversation::CONTEXT_PROJECT => $this->canAccessProjectContext($conversation, $actor),
            MessageConversation::CONTEXT_MISSION => $this->canAccessMissionContext($conversation, $actor),
            MessageConversation::CONTEXT_TRANSMISSION => $this->canAccessTransmissionContext($conversation, $actor),
            MessageConversation::CONTEXT_DG_AFRIQUE => $this->canAccessSupportContext($conversation, $actor),
            null => true,
            default => false,
        };
    }

    private function ensureConversation(
        string $kind,
        array $participants,
        ?string $subject,
        ?string $contextType,
        ?string $contextReference,
        string $actor,
        bool $contextScoped,
    ): MessageConversation {
        $participants = collect($participants)->filter()->unique()->sort()->values();
        abort_if($participants->isEmpty(), 422, 'Une conversation doit avoir au moins un participant.');

        $dedupeParts = [$kind, $contextType ?? '-', $contextReference ?? '-'];
        if (! $contextScoped) {
            $dedupeParts = array_merge($dedupeParts, $participants->all());
        }
        $dedupeKey = hash('sha256', implode('|', $dedupeParts));

        return DB::transaction(function () use ($kind, $participants, $subject, $contextType, $contextReference, $actor, $dedupeKey): MessageConversation {
            $conversation = MessageConversation::query()->firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'public_reference' => (string) Str::uuid(),
                    'kind' => $kind,
                    'subject' => $subject,
                    'context_type' => $contextType,
                    'context_reference' => $contextReference,
                    'created_by_core_reference' => $actor,
                ]
            );

            $this->addParticipants($conversation, $participants->all());

            return $conversation->fresh('participants');
        });
    }

    private function addParticipants(MessageConversation $conversation, array $references): void
    {
        foreach (collect($references)->filter()->unique() as $reference) {
            MessageParticipant::query()->firstOrCreate(
                ['conversation_id' => $conversation->id, 'core_identity_reference' => $reference],
                ['joined_at' => now()]
            );
        }
    }

    private function markRead(MessageConversation $conversation, string $actor): void
    {
        MessageParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('core_identity_reference', $actor)
            ->update(['last_read_at' => now()]);
    }

    private function assertAccess(MessageConversation $conversation, string $actor): void
    {
        abort_unless($this->canAccess($conversation, $actor), 404);
    }

    private function canAccessNeedContext(MessageConversation $conversation, string $actor): bool
    {
        $need = Need::query()->where('public_reference', $conversation->context_reference)->first();

        return $need !== null
            && $need->status !== Need::STATUS_ARCHIVED
            && $this->needs->canView($need, $actor);
    }

    private function canAccessZumraContext(MessageConversation $conversation, string $actor): bool
    {
        $group = ZumraGroup::query()->where('public_reference', $conversation->context_reference)->first();
        if (! $group || $group->state === ZumraGroup::STATE_SUSPENDED) {
            return false;
        }

        return $this->isActiveGroupMember($group->id, $actor);
    }

    private function canAccessInvitationContext(MessageConversation $conversation, string $actor): bool
    {
        $membership = ZumraGroupMembership::query()->find($conversation->context_reference);
        if (! $membership) {
            return false;
        }

        if ($membership->core_identity_reference === $actor) {
            return in_array($membership->status, [ZumraGroupMembership::STATUS_INVITED, ZumraGroupMembership::STATUS_ACTIVE], true);
        }

        return ZumraGroupRole::query()
            ->where('zumra_group_id', $membership->zumra_group_id)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->exists();
    }

    private function canAccessProjectContext(MessageConversation $conversation, string $actor): bool
    {
        $project = Project::query()->where('public_reference', $conversation->context_reference)->first();

        return $project !== null
            && $project->status !== Project::STATUS_ARCHIVED
            && $this->projects->canView($project, $actor);
    }

    private function canAccessMissionContext(MessageConversation $conversation, string $actor): bool
    {
        $mission = Mission::query()->where('public_reference', $conversation->context_reference)->first();

        return $mission !== null && $this->missionVisibility->canViewMission($mission, $actor);
    }

    private function canAccessTransmissionContext(MessageConversation $conversation, string $actor): bool
    {
        $transmission = Transmission::query()->where('public_reference', $conversation->context_reference)->first();

        return $transmission !== null && $this->transmissionVisibility->canView($transmission, $actor);
    }

    private function canAccessSupportContext(MessageConversation $conversation, string $actor): bool
    {
        return $conversation->context_reference === $actor
            || PortalAdministrator::query()->where('core_identity_reference', $actor)->exists();
    }

    private function contextData(MessageConversation $conversation, string $actor): array
    {
        if ($conversation->context_type === MessageConversation::CONTEXT_NEED) {
            $need = Need::query()->where('public_reference', $conversation->context_reference)->first();
            return ['label' => $need ? 'Besoin · '.$need->title : 'Besoin', 'url' => $need ? route('needs.show', $need) : null, 'can_manage_participants' => false];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_ZUMRA) {
            $group = ZumraGroup::query()->where('public_reference', $conversation->context_reference)->first();
            return ['label' => $group ? 'ZUMRA · '.$group->name : 'ZUMRA', 'url' => $group ? route('zumra.groups.show', $group) : null, 'can_manage_participants' => false];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_INVITATION) {
            $membership = ZumraGroupMembership::query()->find($conversation->context_reference);
            $group = $membership ? ZumraGroup::query()->find($membership->zumra_group_id) : null;
            return ['label' => $group ? 'Invitation · '.$group->name : 'Invitation ZUMRA', 'url' => $group ? route('zumra.groups.show', $group) : null, 'can_manage_participants' => false];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_PROJECT) {
            $project = Project::query()->where('public_reference', $conversation->context_reference)->first();
            return [
                'label' => $project ? 'Projet · '.$project->name : 'Projet',
                'url' => $project ? route('projects.show', $project) : null,
                'can_manage_participants' => $project !== null && $this->projects->canDecide($project, $actor),
            ];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_MISSION) {
            $mission = Mission::query()->where('public_reference', $conversation->context_reference)->first();
            return ['label' => $mission ? 'Mission · '.$mission->title : 'Mission', 'url' => $mission ? route('missions.show', $mission) : null, 'can_manage_participants' => false];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_TRANSMISSION) {
            $transmission = Transmission::query()->where('public_reference', $conversation->context_reference)->first();
            return ['label' => $transmission ? 'Transmission · '.$transmission->capability_label : 'Transmission', 'url' => $transmission ? route('transmissions.show', $transmission) : null, 'can_manage_participants' => false];
        }
        if ($conversation->context_type === MessageConversation::CONTEXT_DG_AFRIQUE) {
            return ['label' => 'DG Afrique', 'url' => route('member.space'), 'can_manage_participants' => false];
        }

        return ['label' => 'Conversation directe', 'url' => null, 'can_manage_participants' => false];
    }

    private function conversationTitle(MessageConversation $conversation, string $actor): string
    {
        if ($conversation->subject) {
            return $conversation->subject;
        }

        $other = MessageParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('core_identity_reference', '!=', $actor)
            ->value('core_identity_reference');

        if (! $other) {
            return 'Conversation';
        }

        return $this->labelsFor(collect([$other]), $actor)[$other] ?? 'Membre DG Afrique';
    }

    private function labelsFor(Collection $references, string $actor): array
    {
        $profiles = PersonProfile::query()
            ->whereIn('core_identity_reference', $references)
            ->get()
            ->keyBy('core_identity_reference');
        $administrators = PortalAdministrator::query()
            ->whereIn('core_identity_reference', $references)
            ->pluck('core_identity_reference')
            ->flip();

        $labels = [];
        foreach ($references as $reference) {
            if ($reference === $actor) {
                $labels[$reference] = 'Vous';
                continue;
            }
            if ($administrators->has($reference)) {
                $labels[$reference] = 'Équipe DG Afrique';
                continue;
            }
            $labels[$reference] = $profiles->get($reference)?->discovery_display_name ?: 'Membre DG Afrique';
        }

        return $labels;
    }

    private function activeGroupMembers(string $groupId): Collection
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $groupId)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->pluck('core_identity_reference')
            ->filter()
            ->values();
    }

    private function groupLeaders(string $groupId): Collection
    {
        return ZumraGroupRole::query()
            ->where('zumra_group_id', $groupId)
            ->where('status', ZumraGroupRole::STATUS_ACCEPTED)
            ->whereNotNull('core_identity_reference')
            ->pluck('core_identity_reference')
            ->filter()
            ->unique()
            ->values();
    }

    private function isActiveGroupMember(string $groupId, string $actor): bool
    {
        return ZumraGroupMembership::query()
            ->where('zumra_group_id', $groupId)
            ->where('core_identity_reference', $actor)
            ->where('status', ZumraGroupMembership::STATUS_ACTIVE)
            ->exists();
    }
}
