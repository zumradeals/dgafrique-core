<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProofReference extends Model
{
    use HasUuids;

    public const TYPE_EXTERNAL_URL = 'EXTERNAL_URL';
    public const TYPE_FREE_TEXT = 'FREE_TEXT';
    public const TYPE_GAMADRIVE_FEDERATED = 'GAMADRIVE_FEDERATED';

    /** GAMADRIVE_FEDERATED reste réservé mais désactivé : aucune fédération documentaire réelle n'existe (fiche §14). */
    public const USABLE_TYPES = [self::TYPE_EXTERNAL_URL, self::TYPE_FREE_TEXT];

    protected $table = 'dg_proof_references';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['proof_id', 'type', 'value', 'label'];
}
