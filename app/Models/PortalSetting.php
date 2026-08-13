<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PortalSetting extends Model
{
    protected $table = 'portal_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'updated_by_core_reference'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
