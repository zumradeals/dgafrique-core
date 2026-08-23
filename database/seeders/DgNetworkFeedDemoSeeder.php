<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\PersonProfile;use Illuminate\Database\Seeder;use Illuminate\Support\Str;
/** FEED-001 : décor opt-in ; aucune exécution depuis DatabaseSeeder. */
final class DgNetworkFeedDemoSeeder extends Seeder
{
 public function run():void{
  $this->call(ProjectHubDemoSeeder::class);
  foreach([['DEMO-FEED-AMINA','Amina Diop','Gestion de projet'],['DEMO-FEED-FATOU','Fatoumata K.','Designer UI/UX'],['DEMO-FEED-IBRAHIM','Ibrahim T.','Développeur'],['DEMO-FEED-AWA','Awa Diarra','Experte en marketing'],['DEMO-FEED-MOUSSA','Moussa B.','Expert énergie']] as [$ref,$name,$activity]) PersonProfile::query()->firstOrCreate(['core_identity_reference'=>$ref],['discovery_reference'=>(string)Str::uuid(),'discovery_display_name'=>$name,'discovery_consent'=>true,'discovery_consented_at'=>now(),'current_activity'=>$activity,'city'=>'Abidjan','country_code'=>'CI']);
  $this->command?->info('FEED-001 : monde démonstratif opt-in installé.');
 }
}
