<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProofWitness extends Model
{
    use HasUuids;

    public const STATUS_INVITED = 'INVITED';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_DECLINED = 'DECLINED';

    public const STATUS_LABELS = [
        self::STATUS_INVITED => 'Invité·e à témoigner',
        self::STATUS_CONFIRMED => 'A confirmé',
        self::STATUS_DECLINED => 'A décliné',
    ];

    protected $table = 'dg_proof_witnesses';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['proof_id', 'core_identity_reference', 'status', 'invited_by_core_reference', 'responded_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'immutable_datetime'];
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(Proof::class, 'proof_id');
    }
}
