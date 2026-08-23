<?php
declare(strict_types=1);
namespace App\Application\Activity;
final class FeedDemoPresentation
{
    public function cards(): array{return [
        ['kind'=>'project','actor'=>'RAHMAN Technology','verb'=>'fait avancer un projet','time'=>'il y a 2 heures','type'=>'PROJET','title'=>'Plateforme numérique pour artisans d’Abobo','text'=>'Nous venons de terminer la phase de développement MVP. La plateforme est prête pour les tests utilisateurs.','tags'=>['Développement','Innovation numérique','Abidjan, Côte d’Ivoire'],'progress'=>65,'state'=>'En avance','image'=>'rahman-project.svg'],
        ['kind'=>'resource','actor'=>'Amina Diop','verb'=>'transmet une ressource','time'=>'il y a 3 heures','type'=>'RESSOURCE','title'=>'Guide pratique : Mobiliser sa communauté autour d’un projet','text'=>'Un guide simple pour impliquer les bonnes personnes et faire avancer vos initiatives ensemble.','tags'=>['Gestion de projet','Leadership','Communauté'],'image'=>'community-guide.svg'],
        ['kind'=>'need','actor'=>'Excellence ZUMRA','verb'=>'exprime un besoin','time'=>'il y a 4 heures','type'=>'BESOIN','title'=>'Nous recherchons un formateur en design graphique','text'=>'Pour accompagner les jeunes de notre centre de formation pendant 2 mois.','tags'=>['Design graphique','Formation','Dakar, Sénégal'],'priority'=>'Haute','deadline'=>'15 mai 2024'],
        ['kind'=>'milestone','actor'=>'AgriZUMRA','verb'=>'a atteint un jalon','time'=>'il y a 5 heures','type'=>'PROJET','title'=>'Marché en ligne des producteurs','text'=>'Jalon 2/4 atteint : intégration des moyens de paiement 🎉 Prochaine étape : Campagne de lancement.','tags'=>[],'progress'=>70,'state'=>'En avance','image'=>'agri-project.svg'],
    ];}
    public function stats():array{return ['groups'=>'1 248','projects'=>128,'needs'=>342,'members'=>'9 842','countries'=>11];}
}
