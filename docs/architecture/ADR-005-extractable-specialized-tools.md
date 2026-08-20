# ADR-005 — Outils spécialisés : module extractible avant satellite

- **Statut :** Accepté
- **Portée :** DG Afrique / ZUMRA / outils spécialisés GAMAD
- **Décision :** Invariant d’architecture

## Invariant

> **On ne construit pas un satellite parce qu’un outil pourrait devenir énorme.**
>
> **On construit d’abord un module extractible. Il devient satellite lorsqu’il a besoin de vivre indépendamment.**

Cette règle est normative pour `dgafrique-core`.

## Conséquence immédiate

G-POS et GamaDrive doivent, dans leur première phase au sein de l’écosystème DG Afrique/ZUMRA, être considérés comme des **modules spécialisés extractibles** et non comme des satellites obligatoirement autonomes par anticipation.

Un module extractible peut être très grand. Sa taille fonctionnelle ne justifie pas, à elle seule, un dépôt, un runtime et une base de données autonomes.

## Règles de conception

1. **Frontière métier explicite.** Chaque outil possède son domaine, ses services applicatifs, ses règles, ses migrations et son interface utilisateur clairement identifiables.
2. **Pas de couplage sauvage.** Le métier d’un outil ne doit pas dépendre directement de l’implémentation interne des autres domaines lorsqu’un contrat/gateway peut matérialiser la frontière.
3. **Propriété des données.** Les tables et écritures appartenant à un outil restent sous la responsabilité de ce module, même si elles partagent initialement la même infrastructure PostgreSQL.
4. **Identité commune.** Un outil spécialisé ne recrée pas une identité membre parallèle : il consomme l’identité et les autorisations prévues par l’écosystème GAMAD/DG Afrique.
5. **UX unifiée.** L’utilisateur découvre et ouvre les outils depuis DG Afrique/ZUMRA, notamment via « Mes outils », sans que l’architecture physique soit exposée dans l’expérience produit.
6. **Extractibilité testable.** Les dépendances externes du module doivent pouvoir être remplacées par des adapters/API sans réécriture de son cœur métier.
7. **Autonomie différée.** Repository, déploiement, workers, cache ou base autonomes ne sont introduits que lorsqu’un besoin réel le justifie.

## Quand un module peut devenir satellite

L’extraction devient pertinente lorsqu’au moins une contrainte réelle apparaît : besoin de déploiement indépendant, charge ou disponibilité propres, isolation forte des données ou des risques, cadence d’évolution autonome, équipe dédiée, contraintes transactionnelles/infrastructure spécifiques, intégration externe nécessitant une frontière réseau, ou coût du couplage devenu supérieur au coût de l’autonomie.

La décision d’extraction doit faire l’objet d’un ADR dédié et d’un plan de migration. Elle ne doit pas être motivée uniquement par l’hypothèse que l’outil « pourrait devenir énorme ».

## Stratégie de données

Au démarrage, plusieurs modules peuvent partager une même infrastructure PostgreSQL afin de réduire le coût opérationnel. Leurs données doivent toutefois rester logiquement possédées et identifiables par domaine. Lors d’une extraction, la propriété logique devient une propriété physique : les données du module sont migrées vers sa base autonome et les appels internes deviennent des contrats réseau/API lorsque nécessaire.

## Application à G-POS et GamaDrive

- **G-POS** : outil spécialisé économique/commercial, conçu comme un domaine fortement isolé et extractible. Son importance et son ampleur ne suffisent pas à imposer immédiatement une infrastructure autonome.
- **GamaDrive** : outil spécialisé de fichiers/contenus, soumis au même invariant. Il peut devenir satellite plus tard si les contraintes de stockage, de charge, de sécurité ou d’exploitation le nécessitent.

Les dépôts ou prototypes autonomes préexistants peuvent servir de corpus de conception et de source de code lors de la modularisation. Leur existence ne constitue pas une obligation de maintenir une architecture distribuée.

## Doctrine produit

DG Afrique distingue désormais trois niveaux :

**Fonction interne → Module spécialisé extractible → Satellite autonome.**

Le passage d’un niveau au suivant répond à un besoin observé et documenté, jamais à une anticipation de taille seule.
