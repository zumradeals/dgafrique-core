# CAP-018 — Lanceur de satellites

## Finalité canonique

Permettre à DG Afrique d’accompagner certaines initiatives jusqu’à une autonomie organisationnelle plus forte.

Un projet suffisamment mature peut être accompagné vers une structure distincte : entreprise, association, coopérative, startup, plateforme ou autre forme pertinente.

## Points clés canoniques

- identifier les projets mûrs ;
- accompagner leur structuration ;
- préserver la connexion possible au Core.

## Garde-fou canonique

> Un satellite peut être autonome ; son émergence ne signifie pas automatiquement propriété par GAMAD.

## Interprétation bornée dans la nouvelle stack

CAP-018 est un **parcours de préparation à l’autonomie**, pas encore le satellite lui-même.

Un projet est considéré comme éligible à l’ouverture de ce parcours uniquement lorsqu’il porte l’un des repères explicites de CAP-017 :

- `POTENTIAL_STRUCTURE` ;
- `POTENTIAL_SATELLITE`.

Cette sélection est déterministe. CAP-018 n’invente aucun score, aucun seuil caché et aucun calcul de maturité. CAP-044 traitera plus tard l’estimation de maturité par signes observables.

Le porteur habilité du projet peut :

- ouvrir volontairement un parcours d’autonomie ;
- indiquer une forme envisagée parmi entreprise, association, coopérative, startup, plateforme ou autre forme précisée ;
- fermer le parcours ;
- le rouvrir si le projet reste éligible.

L’ouverture et la fermeture sont tracées dans la chronologie du projet.

## Accompagnement

CAP-018 ne duplique pas CAP-016.

Le parcours d’autonomie est relié au projet et l’interface renvoie vers **Accompagnement DG Afrique** pour les interventions de structuration déjà prévues par CAP-016.

Aucun accompagnement n’est activé automatiquement.

## Connexion future aux primitives communes

CAP-018 conserve la provenance du projet et les références d’identité déjà attestées afin de ne pas fermer une connexion future.

Il ne crée toutefois :

- aucune relation satellite ↔ Core ;
- aucune identité organisationnelle ;
- aucune session satellite ;
- aucune fédération d’identité ;
- aucune primitive financière.

La relation satellite ↔ Core appartient à CAP-049 et l’identité organisationnelle à CAP-067.

## Frontières avec CAP-047 et CAP-048

CAP-047 définira le changement de nature où un projet devient réellement une structure distincte.

CAP-048 introduira le registre des satellites.

Par conséquent, la table CAP-018 se nomme `dg_project_autonomy_pathways` et ne constitue ni un registre de satellites ni une table d’organisations.

## Invariants

CAP-018 ne doit jamais :

- créer automatiquement une entreprise, association, coopérative, startup, plateforme ou satellite ;
- choisir la forme juridique réelle ;
- transférer la propriété ou le contrôle du projet ;
- modifier la maturité du projet ;
- ouvrir automatiquement un accompagnement ;
- créer un financement ;
- appeler une API de création d’organisation dans le Core ;
- considérer une connexion future au Core comme une fusion de données ou de gouvernance.

## Preuve attendue

- migration `dg_project_autonomy_pathways` appliquée ;
- routes CAP-018 sous `web` et `core.member` ;
- seuls les projets `POTENTIAL_STRUCTURE` ou `POTENTIAL_SATELLITE` sont éligibles ;
- seul le porteur habilité ouvre ou ferme le parcours ;
- six formes envisagées couvertes, avec précision obligatoire pour `OTHER` ;
- liste d’administration des projets mûrs ;
- aucun satellite, organisation, financement ou accompagnement automatique créé ;
- aucun appel de création d’organisation vers le Core ;
- événements append-only d’ouverture et fermeture ;
- tests ciblés et régression complète verts ;
- préproduction HTTP 200.
