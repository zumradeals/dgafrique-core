<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MessageEntry extends Model
{
    use HasUuids;

    protected $table = 'dg_message_entries';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id', 'sender_core_reference', 'body', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'immutable_datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MessageConversation::class, 'conversation_id');
    }
}
