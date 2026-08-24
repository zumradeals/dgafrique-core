<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ProjectEvent extends Model
{
    use HasUuids;

    // Libellés d'affichage pour le fil « Activité récente » du dossier projet (fiche V2). Un
    // code non listé retombe sur une forme humanisée générique — jamais une erreur d'affichage.
    public const EVENT_LABELS = [
        'PROJECT_PROPOSED' => 'Projet proposé',
        'PROJECT_ADOPTED' => 'Projet adopté',
        'PROJECT_IN_PROGRESS' => 'Projet démarré',
        'PROJECT_COMPLETED' => 'Projet marqué réalisé',
        'PROJECT_ARCHIVED' => 'Projet archivé',
        'PROJECT_MATURITY_CHANGED' => 'Repère de maturité mis à jour',
        'TEAM_MEMBER_REQUESTED' => 'Demande pour rejoindre l’équipe',
        'TEAM_MEMBER_INVITED' => 'Invitation envoyée à rejoindre l’équipe',
        'TEAM_MEMBER_JOINED' => 'Nouveau membre dans l’équipe',
        'TEAM_MEMBER_LEFT' => 'Un membre a quitté l’équipe',
        'TEAM_MEMBER_REMOVED' => 'Un membre a été retiré de l’équipe',
        'FUNDING_DECLARATION_CREATED' => 'Besoin financier déclaré',
        'FUNDING_DECLARATION_UPDATED' => 'Déclaration financière mise à jour',
        'FUNDING_DECLARATION_CLOSED' => 'Déclaration financière clôturée',
        'FUNDING_DECLARATION_CANCELLED' => 'Déclaration financière annulée',
        'FUNDING_CONTRIBUTION_RECEIVED' => 'Contribution ZAHAB reçue',
        'FUNDING_DECLARATION_FUNDED' => 'Cible financière atteinte',
        'ACCOMPANIMENT_ACTIVATED' => 'Accompagnement DG Afrique activé',
        'ACCOMPANIMENT_ENDED' => 'Accompagnement DG Afrique terminé',
        'ACCOMPANIMENT_ACTION_RECORDED' => 'Action d’accompagnement enregistrée',
        'ACCOMPANIMENT_REQUEST_SUBMITTED' => 'Demande d’accompagnement soumise',
        'ACCOMPANIMENT_REQUEST_ACKNOWLEDGED' => 'Demande d’accompagnement prise en compte',
        'ACCOMPANIMENT_REQUEST_CLOSED' => 'Demande d’accompagnement close',
        'AUTONOMY_PATHWAY_OPENED' => 'Parcours d’autonomie ouvert',
        'AUTONOMY_PATHWAY_CLOSED' => 'Parcours d’autonomie clos',
    ];

    protected $table = 'dg_project_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['project_id', 'event', 'actor_core_reference', 'context', 'occurred_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    public function label(): string
    {
        return self::EVENT_LABELS[$this->event] ?? Str::ucfirst(Str::lower(str_replace('_', ' ', $this->event)));
    }
}
