# CAP-017 — Maturité

## Finalité canonique

Décrire l’état d’avancement d’un projet sans le confondre avec un statut juridique.

La maturité représente la progression de l’idée vers une structure potentiellement autonome.

## Repères canoniques

1. Idée
2. Exploration
3. Équipe
4. Prototype / expérimentation
5. Projet structuré
6. Activité
7. Structure potentielle
8. Satellite potentiel

Codes techniques conservés :

- `IDEA`
- `EXPLORATION`
- `TEAM`
- `EXPERIMENT`
- `STRUCTURED`
- `ACTIVITY`
- `POTENTIAL_STRUCTURE`
- `POTENTIAL_SATELLITE`

## Invariant

> Ces états sont des repères de capacité, pas des décrets institutionnels.

CAP-017 ne crée donc :

- aucun statut juridique ;
- aucune entreprise, organisation ou structure ;
- aucun satellite ;
- aucun financement ;
- aucun changement de propriétaire ou de contrôle ;
- aucun score humain ou score opaque de projet ;
- aucun passage automatique à CAP-018.

## Implémentation bornée

Le porteur habilité peut repositionner explicitement le repère de maturité.

Chaque changement :

- est indépendant du statut opérationnel du projet (`PROPOSED`, `ADOPTED`, `IN_PROGRESS`, etc.) ;
- peut aller vers un repère plus avancé ou revenir vers un repère antérieur ;
- produit un événement append-only `PROJECT_MATURITY_CHANGED` avec ancien repère, nouveau repère et note factuelle facultative ;
- ne modifie ni la propriété, ni le contrôle, ni l’accompagnement, ni le financement.

Les huit repères et leurs codes sont fixes dans CAP-017.

## Frontière avec CAP-044

CAP-017 fournit le vocabulaire et la représentation explicite de maturité.

CAP-044 — « Maturité calculée par signes, pas par décret » introduira plus tard une estimation fondée sur des signaux observables. CAP-017 n’invente donc aucune formule, aucun score et aucun critère automatique.

## Frontière avec CAP-018

`POTENTIAL_SATELLITE` signifie seulement que le projet peut explorer l’étape suivante.

Ce repère ne crée pas un satellite et ne déclenche aucune fédération ou intégration Core.

## Preuve attendue

- route de changement de maturité chargée sous middleware `web` et `core.member` ;
- huit repères canoniques disponibles ;
- seul le porteur habilité peut repositionner la maturité ;
- historique append-only dans `dg_project_events` ;
- maturité indépendante du statut du projet ;
- `POTENTIAL_SATELLITE` sans création de satellite, finance, accompagnement ou contrôle ;
- tests CAP-017 ciblés verts ;
- régression complète verte ;
- préproduction HTTP 200.
