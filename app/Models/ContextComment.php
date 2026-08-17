<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ContextComment extends Model
{
    use HasUuids;

    public const CONTEXT_NEED = 'NEED';
    public const CONTEXT_PROJECT = 'PROJECT';
    public const CONTEXT_ZUMRA_ACTIVITY = 'ZUMRA_ACTIVITY';
    public const CONTEXT_MISSION = 'MISSION';
    public const CONTEXT_TRANSMISSION = 'TRANSMISSION';
    public const CONTEXT_PROOF = 'PROOF';

    public const PURPOSES = [
        'QUESTION' => 'Question',
        'PRECISION' => 'Précision',
        'ADVICE' => 'Conseil',
        'RESOURCE' => 'Ressource',
        'COORDINATION' => 'Coordination',
    ];

    protected $table = 'dg_context_comments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_reference', 'context_type', 'context_reference',
        'author_core_reference', 'purpose', 'body', 'posted_at',
    ];

    protected function casts(): array
    {
        return ['posted_at' => 'immutable_datetime'];
    }
}
