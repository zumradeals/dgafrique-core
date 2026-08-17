<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class TransmissionMatchingHide extends Model
{
    use HasUuids;

    protected $table = 'dg_transmission_matching_hides';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['transmission_id', 'viewer_core_reference', 'subject_core_reference', 'created_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
