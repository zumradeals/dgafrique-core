<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ZumraGroupActivity extends Model
{
    use HasUuids;

    protected $table = 'dg_zumra_group_activities';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['zumra_group_id', 'label', 'normalized_label', 'relation_to_principal', 'added_by_core_reference'];
}
