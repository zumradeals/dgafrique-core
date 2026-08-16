# CAP-019 — Fil d’activité

## Finalité canonique

Faire circuler l’activité utile du réseau.

## Capacité canonique

Le fil peut présenter des événements pertinents : nouveaux projets, ateliers, besoins, opportunités, étapes franchies ou activités des ZUMRA.

## Points clés

- prioriser l’action et la pertinence ;
- éviter le modèle d’attention infinie comme finalité.

## Garde-fou canonique

> Le fil est une capacité de communication au service de l’action.

## Marqueur historique « PLUS TARD »

Le référentiel V0.1 portait CAP-019 avec le marqueur `[PLUS TARD]`. Ce marqueur décrivait le séquencement produit d’origine. La reconstruction ayant désormais atteint CAP-019, la capacité est implémentée dans son périmètre propre sans anticiper CAP-020 à CAP-022.

## Implémentation retenue

CAP-019 est un fil **en lecture**, composé à partir des journaux métier déjà existants. Il ne crée pas une deuxième source de vérité et ne permet pas de publier un « post » libre.

Sources actuellement raccordées :

- `dg_need_events` : publication, mise en action et résolution d’un besoin ;
- `dg_project_events` : proposition, adoption, démarrage, accompagnement, maturité, autonomie préparatoire et achèvement ;
- `dg_zumra_group_events` : création d’une ZUMRA et mouvements collectifs sûrs à exposer.

Les ateliers et opportunités rejoindront ce même fil lorsqu’une source métier canonique les produira. CAP-019 n’invente aucune donnée pour combler une capacité encore absente.

## Pertinence

Le fil utilise des priorités métier simples et explicites :

1. besoins ouverts demandant une action ;
2. besoins/projets en mouvement ;
3. étapes de projet et accompagnements ;
4. activité ZUMRA ;
5. événements de clôture ou résultat.

À priorité égale, l’événement le plus récent apparaît d’abord. Le système ne calcule aucun score de popularité, d’engagement ou d’influence.

Pour réduire le bruit, un seul événement utile — le plus récent — est retenu par objet dans la fenêtre de lecture.

## Visibilité

La visibilité n’est jamais reconstruite dans CAP-019 :

- un besoin passe par `NeedService::canView()` ;
- un projet passe par `ProjectService::canView()` ;
- une ZUMRA suspendue n’est pas diffusée ;
- les événements de demandes d’adhésion, invitations nominatives et départs ne sont pas diffusés dans le fil.

Les objets privés restent donc privés et les droits existants demeurent l’autorité.

## Expérience

- route membre `GET /activite` ;
- filtres : Tout, Besoins, Projets, ZUMRA ;
- page limitée à 12 éléments ;
- pagination classique, jamais de défilement infini ;
- chaque élément renvoie vers l’objet réel pour agir ;
- aperçu de l’activité utile dans `Mon espace`.

## Frontières

CAP-019 n’implémente pas :

- publication sociale libre ;
- like/réaction ;
- compteur de vues ou d’engagement ;
- followers ;
- commentaire — CAP-021 ;
- partage — CAP-022 ;
- messagerie — CAP-020 ;
- classement social ou viralité.

Aucune nouvelle table n’est nécessaire : le fil est une projection des événements déjà persistés.
