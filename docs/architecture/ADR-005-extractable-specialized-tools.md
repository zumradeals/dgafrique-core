# ADR-005 — Outils spécialisés : module extractible avant satellite

- **Statut :** Accepté
- **Portée :** DG Afrique / ZUMRA / outils spécialisés GAMAD
- **Décision :** Invariant d’architecture

## Position produit

DG Afrique est un **réseau social d’action** qui accompagne le développement humain et ZUMRA. Il relie personnes, capacités, besoins, apprentissages, transmissions, groupes, projets, contributions et opportunités afin de transformer les interactions en actions utiles.

DG Afrique n’est pas un portail de lancement de satellites et un projet n’a pas pour destination naturelle de devenir un satellite.

## Invariant

> **On ne construit pas un satellite parce qu’un outil pourrait devenir énorme.**
>
> **On construit d’abord un module spécialisé extractible. Il devient satellite autonome lorsqu’un besoin réel d’autonomie le justifie.**

Doctrine :

**Fonction interne → Module spécialisé extractible → Satellite autonome.**

Le mot « satellite » décrit donc une **forme technique d’autonomie d’un outil spécialisé**, pas une étape de maturité d’un projet humain ou économique.

## Règles de conception

1. Chaque outil possède une frontière métier explicite : domaine, services applicatifs, règles, migrations et UI identifiables.
2. Le métier d’un outil ne dépend pas sauvagement des implémentations internes des autres domaines ; des contrats/gateways matérialisent les frontières utiles.
3. Les données restent logiquement possédées par leur module, même lorsque plusieurs modules partagent PostgreSQL au démarrage.
4. Les outils consomment l’identité et les autorisations communes de l’écosystème ; ils ne recréent pas une identité membre parallèle.
5. L’UX reste unifiée : l’utilisateur découvre les outils depuis DG Afrique/ZUMRA sans devoir connaître leur architecture physique.
6. L’extractibilité doit être testable : les dépendances externes peuvent devenir adapters/API sans réécrire le cœur métier.
7. Repository, runtime, workers, cache ou base autonomes ne sont introduits qu’en présence d’une contrainte réelle.

## Critères d’extraction

Un module peut devenir satellite lorsque l’autonomie apporte une valeur concrète : déploiement indépendant, charge ou disponibilité propres, isolation forte des données ou des risques, cadence d’évolution autonome, équipe dédiée, infrastructure spécifique, frontière réseau nécessaire ou coût du couplage devenu supérieur au coût de l’autonomie.

Toute extraction fait l’objet d’un ADR et d’un plan de migration.

## Projet ≠ satellite

Le cycle d’un projet reste dans le domaine Projet : idée, structuration, équipe, besoins, accompagnement, maturité et autonomie organisationnelle/économique éventuelle. Cette autonomie n’implique aucune extraction logicielle.

Le mécanisme historiquement nommé `ProjectSatelliteLauncherService` a été renommé `ProjectAutonomyPathwayService` (REF-001/REF-001B) : plus aucune classe, route, vue ou test actif ne porte ce nom de dette. Ce nom historique n’a jamais dicté la doctrine produit.

## Application aux outils spécialisés

G-POS, GamaDrive et les futurs outils spécialisés suivent cet invariant. Ils peuvent commencer dans `dgafrique-core` comme modules fortement isolés et extractibles. Leur importance fonctionnelle ne suffit jamais à imposer une infrastructure autonome.
