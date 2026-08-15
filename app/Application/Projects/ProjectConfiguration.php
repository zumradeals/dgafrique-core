<?php
declare(strict_types=1);
namespace App\Application\Projects;
use App\Models\PortalSetting;
final class ProjectConfiguration
{
    public const KEY='projects.configuration';
    public function get(): array { return array_replace($this->defaults(),PortalSetting::query()->find(self::KEY)?->value??[]); }
    public function defaults(): array { return ['directory_title'=>'Des idées structurées pour produire des résultats réels.','directory_intro'=>'Un projet relie un problème, des personnes, des capacités, des ressources et des étapes. Sa publication ne promet aucun financement.','creation_title'=>'Structurer une proposition de projet','domains'=>['DIGITAL'=>'Numérique','EDUCATION'=>'Éducation et formation','HEALTH'=>'Santé','AGRICULTURE'=>'Agriculture','CRAFT'=>'Artisanat','SOCIAL'=>'Action sociale','CULTURE'=>'Culture','OTHER'=>'Autre'],'max_active_personal_projects'=>8,'max_active_group_projects'=>20,'directory_page_size'=>12]; }
}
