<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Needs\NeedConfiguration;
use App\Application\Needs\NeedService;
use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Application\Zumra\ZumraGroupService;
use App\Models\Need;
use App\Models\ZumraCharter;
use App\Models\ZumraProgramMembership;
use Illuminate\Database\Seeder;

/** Opt-in : php artisan db:seed --class=Database\\Seeders\\NeedsHarmonyDemoSeeder */
final class NeedsHarmonyDemoSeeder extends Seeder
{
    public function run(NeedService $needs, ZumraGroupService $groups, ProjectService $projects): void
    {
        $this->command?->warn('UX-HARMONY-BESOINS-001 : installation opt-in de 24 besoins DEMO-NEED-*');
        $titles=['Local pour centre d’initiation informatique','Kits solaires pour kiosques communautaires','Forage d’un puits villageois','Mobilier scolaire pour une école primaire','Formation en comptabilité associative','Appui technique pour plateforme agricole','Transport frigorifique mutualisé','Formateur en couture et design','Matériel médical pour dispensaire','Réparation d’une pompe à eau','Toiture pour salle de classe','Accompagnement juridique coopératif','Ordinateurs pour bibliothèque mobile','Semences adaptées au climat','Expertise en cybersécurité','Partenaire pour centre de formation','Équipement de transformation du karité','Connexion internet pour espace jeunesse','Diagnostic énergétique communal','Logistique pour campagne de santé','Formation numérique ZUMRA','Ressources pédagogiques ZUMRA','Appui au prototype agricole','Compétences marketing pour projet'];
        $places=['Bouaké, Côte d’Ivoire','Tambacounda, Sénégal','Ségou, Mali','Djidja, Bénin','Ouagadougou, Burkina Faso','Abidjan, Côte d’Ivoire','Conakry, Guinée','Bamako, Mali','Katiola, Côte d’Ivoire','Kolda, Sénégal','Ouahigouya, Burkina Faso','Cotonou, Bénin','Dakar, Sénégal','Korhogo, Côte d’Ivoire','Kigali, Rwanda','Douala, Cameroun','Bobo-Dioulasso, Burkina Faso','Saint-Louis, Sénégal','Lomé, Togo','Yaoundé, Cameroun','Abidjan, Côte d’Ivoire','Dakar, Sénégal','Bouaké, Côte d’Ivoire','Accra, Ghana'];
        $categories=['RESOURCE','TECHNICAL','RESOURCE','RESOURCE','TRAINING','TECHNICAL','LOGISTICS','TRAINING','RESOURCE','TECHNICAL','RESOURCE','PARTNER','RESOURCE','RESOURCE','SKILL','PARTNER','RESOURCE','TECHNICAL','TECHNICAL','LOGISTICS','TRAINING','RESOURCE','TECHNICAL','SKILL'];
        $charter=$this->charter();$needConfig=(new NeedConfiguration)->defaults();
        foreach($titles as $i=>$title){if(Need::query()->where('title',$title.' · Démo')->exists())continue;$actor='DEMO-NEED-'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT);$this->member($actor,$charter);$owner=Need::OWNER_PERSON;$groupRef=null;$projectRef=null;
            if($i>=20){$group=$groups->create($actor,['name'=>'ZUMRA Démo Besoin '.($i+1),'domain'=>'Développement local','founding_objective'=>'Réunir des personnes pour répondre à un besoin concret, apprendre et transmettre ensemble.','participation_mode'=>'HYBRID','location'=>$places[$i],'welcome_capacity'=>'PROGRESSIVELY','activities'=>[],'assume_primary_lead'=>true],99);$owner=Need::OWNER_GROUP;$groupRef=$group->public_reference;
                if($i>=22){$project=$projects->create($actor,['owner_type'=>'PERSON','group_reference'=>null,'zumra_group_reference'=>$group->public_reference,'source_need_reference'=>null,'name'=>'Projet démo besoin '.($i+1),'summary'=>'Projet démonstratif suffisamment décrit pour rattacher un besoin réel.','problem'=>'Une situation locale nécessite une réponse structurée et progressive.','proposed_solution'=>'Construire une réponse testable avec une équipe et des étapes claires.','beneficiaries'=>'Communautés locales et membres du réseau.','domain'=>'COMMUNITY','participation_mode'=>'HYBRID','location'=>$places[$i],'objectives'=>'Produire un résultat utile','required_capabilities'=>'Gestion de projet','required_resources'=>'Ressources locales','risks'=>'Disponibilité','milestones'=>"Former l’équipe\nLancer le pilote",'property_regime'=>'PERSONAL_SUPPORTED','visibility'=>'PUBLIC'],(new ProjectConfiguration)->defaults());$owner=Need::OWNER_PROJECT;$groupRef=null;$projectRef=$project->public_reference;}}
            $need=$needs->create($actor,['owner_type'=>$owner,'group_reference'=>$groupRef,'project_reference'=>$projectRef,'title'=>$title.' · Démo','context'=>'Ce besoin de démonstration décrit une situation concrète, ses bénéficiaires et le résultat attendu afin de tester une lecture riche de l’annuaire Besoins.','category'=>$categories[$i],'capability_label'=>match($categories[$i]){'TRAINING'=>'Pédagogie','SKILL'=>'Expertise métier','PARTNER'=>'Partenariat',default=>'Coordination locale'},'collaboration_mode'=>['LOCAL','REMOTE','HYBRID','ANY'][$i%4],'location'=>$places[$i],'visibility'=>Need::VISIBILITY_PUBLIC],$needConfig);
            if($i%4===2)$needs->transition($need,$actor,Need::STATUS_IN_PROGRESS);if($i%6===3)$needs->transition($need,$actor,Need::STATUS_RESOLVED,'Le besoin a été satisfait dans le scénario de démonstration.');}
        $this->command?->info('24 besoins : 6 catégories, 16 localisations, porteurs Personne/ZUMRA/Projet et trois états réels.');
    }
    private function charter(): ZumraCharter{$body=str_repeat('Respect, transmission et responsabilité partagée. ',5);return ZumraCharter::query()->firstOrCreate(['version'=>'DEMO-NEEDS-2026.1'],['title'=>'Charte ZUMRA — Démonstration Besoins','body'=>$body,'content_hash'=>hash('sha256',$body),'status'=>ZumraCharter::STATUS_PUBLISHED,'published_at'=>now()]);}
    private function member(string $actor,ZumraCharter $charter):void{ZumraProgramMembership::query()->firstOrCreate(['core_identity_reference'=>$actor],['status'=>ZumraProgramMembership::STATUS_ACTIVE,'accepted_charter_id'=>$charter->id,'accepted_charter_version'=>$charter->version,'accepted_charter_hash'=>$charter->content_hash,'charter_accepted_at'=>now(),'submitted_at'=>now(),'activated_at'=>now()]);}
}
