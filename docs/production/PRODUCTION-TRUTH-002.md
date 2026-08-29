# PRODUCTION-TRUTH-002 — suppression littérale des univers fictifs

Statut : `CERTIFIÉ — PASS` par `ENGINE-TRUTH-FINAL-001` au commit
`f9ab5fc404fa231b21758ae7854923c64f70610b`.

## Décision active

DG Afrique ne conserve plus de données de démonstration exécutables dans le dépôt. La protection
conditionnelle par `APP_ENV=production` est retirée au profit d'une garantie plus simple : les
seeders concernés n'existent plus.

Cette décision supplante la clause de `PRODUCTION-TRUTH-001` qui permettait encore leur usage en
local et dans les tests.

## Suppression effectuée

- 12 seeders d'univers fictifs supprimés ;
- la classe de base `DemoSeeder` supprimée ;
- 9 suites de tests exclusivement dépendantes de ces univers supprimées, soit 47 scénarios ;
- 2 scénarios ZUMRA dépendants du seeder retirés d'une suite mixte ;
- les 2 scénarios indépendants de cette suite mixte conservés ;
- `ProductionTruthTest` vérifie désormais l'absence physique de tout `*DemoSeeder.php` ;
- `DatabaseSeeder` demeure l'unique seeder et son exécution demeure vide ;
- les anciennes fixtures JSON runtime restent absentes.

Les suites supprimées validaient la carrosserie historique ou l'orchestration des seeders. Elles
ne constituaient pas l'unique preuve des machines métier : Missions, Besoins, Personnes, Projets,
Transmissions, Preuves et ZUMRA conservent leurs tests de service, d'autorité, de workflow, de
visibilité et de fumée indépendants de ces univers.

## Frontière honnête

Les données synthétiques créées directement par un test et détruites avec sa base transactionnelle
ne sont pas des données produit ni des fixtures de démonstration. Elles restent autorisées car un
test moteur doit créer les états minimaux nécessaires à sa preuve. Elles ne sont jamais seedées,
livrées ou affichées comme des faits réels du réseau.

Cette suppression ne prouve pas, à elle seule, qu'une ancienne base déjà peuplée est propre. Avant
toute promotion d'une base existante, un inventaire nominatif et relationnel doit distinguer les
écritures légitimes des anciens décors. Aucun effacement générique par préfixe n'est autorisé : il
pourrait détruire des données liées ou devenues légitimes. La stratégie de production recommandée
reste une base reconstruite depuis les migrations, puis un import explicitement validé des seules
données légitimes.

## Porte de régression

La certification finale refuse le candidat si :

1. un fichier `database/seeders/*DemoSeeder.php` existe ;
2. `database/seeders` contient autre chose que `DatabaseSeeder.php` ;
3. une référence `DemoSeeder` subsiste dans le PHP exécutable ou les tests ;
4. la suite moteur PostgreSQL ne réussit pas après la suppression.
