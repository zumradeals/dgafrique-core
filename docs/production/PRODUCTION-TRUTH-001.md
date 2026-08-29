# PRODUCTION-TRUTH-001 — vérité des données

> **Supplanté partiellement par `PRODUCTION-TRUTH-002`.** La clause autorisant les seeders de
> démonstration en local et dans les tests n'est plus valide. Les faits historiques de ce document
> restent utiles ; la politique active exige leur absence complète du dépôt exécutable.

## Décision

À compter du 27 août 2026, les règles `DEMO-FIRST` et les projections de traction sont retirées.
Toute surface DG Afrique doit afficher exclusivement :

1. une donnée persistée et autorisée ;
2. un agrégat calculé à partir de ces données ;
3. ou un état vide honnête.

## Garanties livrées

- aucune fixture JSON de démonstration n'est chargée par le runtime ;
- aucune carte de remplissage n'est injectée dans la Landing, le Fil, Besoins ou Projets ;
- aucune identité `DEMO-*` n'alimente l'annuaire ou les aperçus réseau ;
- les statistiques du Fil, de la Landing et des Projets viennent des tables métier ;
- la progression d'un Projet vient uniquement de ses jalons terminés ; sans jalon, elle vaut
  `null` et l'interface affiche « Aucun jalon défini » ;
- le décor fictif de proximité ZUMRA et sa table dédiée sont supprimés ;
- les seeders de démonstration étaient encore utilisables pour les tests et environnements locaux ;
  cette garantie intermédiaire est supprimée et remplacée par leur absence physique dans
  `PRODUCTION-TRUTH-002` ;
- `DatabaseSeeder` reste vide.

## Données déjà installées sur une bêta

Une base ayant reçu un seeder `*DemoSeeder` ne doit pas être promue telle quelle en production.
La procédure sûre consiste à créer la base de production à partir des migrations et des seuls
référentiels canoniques, puis à importer explicitement les données légitimes. Une suppression
générique par préfixe est volontairement exclue : elle risquerait d'effacer des écritures liées ou
des données devenues légitimes sans validation humaine.

## Garde de revue

Toute nouvelle donnée statique représentant une personne, un besoin, un projet, une ZUMRA, une
activité, une traction, un montant, une progression ou un impact constitue une régression. Les
textes d'aide, choix de configuration et illustrations purement décoratives ne sont pas des données
métier, mais ne doivent jamais être présentés comme des faits du réseau.
