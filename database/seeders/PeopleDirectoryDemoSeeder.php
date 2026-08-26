<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use Illuminate\Database\Seeder;

/** Opt-in : php artisan db:seed --class=Database\\Seeders\\PeopleDirectoryDemoSeeder */
final class PeopleDirectoryDemoSeeder extends Seeder
{
    public function run(CapabilityStatementSynchronizer $statements): void
    {
        $this->command?->warn('UX-HARMONY-PERSONNES-001 : installation opt-in de 24 profils consentis DEMO-PEOPLE-*');
        $viewer = PersonProfile::query()->firstOrNew(['core_identity_reference' => 'DEMO-IDN-VIEWER']);
        $viewer->fill(['country_code'=>'CI','city'=>'Abidjan','current_activity'=>'Entrepreneur numérique','interest_domains'=>['Numérique','Gestion de projet'],'participation_mode'=>'HYBRIDE','orientation_consent'=>true,'orientation_consented_at'=>$viewer->orientation_consented_at ?? now()])->save();
        $statements->sync($viewer->core_identity_reference,[CapabilityStatement::KIND_POSSESSED=>['Gestion de projet'],CapabilityStatement::KIND_LEARNING=>['Développement web','Communication'],CapabilityStatement::KIND_TRANSMISSION=>['Entrepreneuriat']],true,false);

        $names=['Amina Diop','Ibrahim Traoré','Fatoumata Diallo','Moussa Koné','Seynabou Ndiaye','David Koffi','Marie-Claire Ekomé','Jean-Baptiste N’Guessan','Awa Ouédraogo','Mamadou Bah','Grâce Mbuyi','Oumar Cissé','Nadia El Fassi','Kadiatou Camara','Yao Kouamé','Ruth Atieno','Samuel Mensah','Mariama Sow','Patrick Mwangi','Fanta Keïta','Eric Zinsou','Salma Benali','Cheikh Fall','Clarisse Uwimana'];
        $activities=['Designer produit','Développeur web','Formatrice','Spécialiste finance','Responsable communication','Analyste de données','Ingénieure agronome','Chef de projet','Artisane textile','Technicien solaire','Architecte','Logisticien','Entrepreneure sociale','Comptable','Développeur mobile','Chercheuse en santé','Juriste','Responsable RH','Ingénieur eau','Photographe','Expert cybersécurité','UX researcher','Coordinateur associatif','Consultante marketing'];
        $places=[['Dakar','SN'],['Abidjan','CI'],['Conakry','GN'],['Bouaké','CI'],['Ouagadougou','BF'],['Abidjan','CI'],['Yaoundé','CM'],['Yamoussoukro','CI'],['Bamako','ML'],['Conakry','GN'],['Kinshasa','CD'],['Dakar','SN'],['Casablanca','MA'],['Abidjan','CI'],['San-Pédro','CI'],['Nairobi','KE'],['Accra','GH'],['Saint-Louis','SN'],['Mombasa','KE'],['Bamako','ML'],['Cotonou','BJ'],['Rabat','MA'],['Thiès','SN'],['Kigali','RW']];
        $skills=[['Design UX','Recherche utilisateur'],['Développement web','API'],['Formation','Pédagogie'],['Finance','Analyse'],['Communication','Stratégie éditoriale'],['Analyse de données','Tableaux de bord'],['Agriculture durable','Gestion de projet'],['Gestion de projet','Coordination'],['Couture','Design'],['Énergie solaire','Maintenance'],['Architecture','Construction'],['Logistique','Achats'],['Entrepreneuriat','Impact social'],['Comptabilité','Finance'],['Développement mobile','API'],['Santé publique','Recherche'],['Droit des affaires','Médiation'],['Ressources humaines','Formation'],['Accès à l’eau','Ingénierie'],['Photographie','Communication'],['Cybersécurité','Développement web'],['Recherche utilisateur','Design UX'],['Coordination','Gestion de projet'],['Marketing','Communication']];
        foreach($names as $i=>$name){$ref='DEMO-PEOPLE-'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT);$profile=PersonProfile::query()->firstOrNew(['core_identity_reference'=>$ref]);$profile->fill(['discovery_reference'=>$profile->discovery_reference ?? sprintf('91000000-0000-4000-8000-%012d',$i+1),'discovery_display_name'=>$name,'discovery_bio'=>$i%5===4?null:'Profil de démonstration volontairement visible pour explorer les capacités du réseau.','discovery_consent'=>true,'discovery_consented_at'=>now()->subDays(($i*7)%120),'orientation_consent'=>true,'orientation_consented_at'=>now()->subDays(150),'country_code'=>$places[$i][1],'city'=>$i===23?null:$places[$i][0],'current_activity'=>$activities[$i],'interest_domains'=>$i%6===5?[]:[$skills[$i][0]],'participation_mode'=>['HYBRIDE','EN_LIGNE','PRESENTIEL'][$i%3],'availability_status'=>match($i%4){0,1=>PersonProfile::AVAILABILITY_OPEN,2=>PersonProfile::AVAILABILITY_LIMITED,default=>PersonProfile::AVAILABILITY_PAUSED},'availability_note'=>$i%4===0?'Disponible pour échanger sur un projet concret.':null])->save();$statements->sync($ref,[CapabilityStatement::KIND_POSSESSED=>$skills[$i],CapabilityStatement::KIND_LEARNING=>[$i%2===0?'Communication':'Entrepreneuriat'],CapabilityStatement::KIND_TRANSMISSION=>[$i%3===0?'Développement web':$skills[$i][0]]],true,true);}
        $this->command?->info('24 profils : 12 villes, 12 pays, 24 métiers, disponibilités et anciennetés variées.');
    }
}
