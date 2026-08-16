<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MessageParticipant extends Model
{
    use HasUuids;

    protected $table = 'dg_message_participants';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id', 'core_identity_reference', 'joined_at', 'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'immutable_datetime',
            'last_read_at' => 'immutable_datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MessageConversation::class, 'conversation_id');
    }
}
