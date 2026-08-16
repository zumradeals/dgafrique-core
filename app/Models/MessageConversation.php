<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MessageConversation extends Model
{
    use HasUuids;

    public const KIND_DIRECT = 'DIRECT';
    public const KIND_GROUP = 'GROUP';

    public const CONTEXT_NEED = 'NEED';
    public const CONTEXT_ZUMRA = 'ZUMRA';
    public const CONTEXT_INVITATION = 'INVITATION';
    public const CONTEXT_PROJECT = 'PROJECT';
    public const CONTEXT_DG_AFRIQUE = 'DG_AFRIQUE';

    protected $table = 'dg_message_conversations';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'kind', 'subject', 'context_type', 'context_reference',
        'created_by_core_reference', 'dedupe_key', 'last_message_at',
    ];

    protected function casts(): array
    {
        return ['last_message_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MessageParticipant::class, 'conversation_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MessageEntry::class, 'conversation_id');
    }
}
