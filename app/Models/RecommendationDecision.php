<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RecommendationDecision extends Model
{
    use HasUuids;

    public const SUBJECT_PERSON = 'PERSON';
    public const DECISION_HIDDEN = 'HIDDEN';

    protected $table = 'dg_recommendation_decisions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'core_identity_reference', 'subject_type', 'subject_reference', 'decision',
    ];
}
