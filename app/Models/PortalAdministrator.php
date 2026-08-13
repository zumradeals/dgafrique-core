<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PortalAdministrator extends Model
{
    protected $table = 'portal_administrators';
    protected $primaryKey = 'core_identity_reference';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['core_identity_reference', 'granted_by_core_reference'];
}
