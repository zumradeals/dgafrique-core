# CAP-011 — ZUMRA / Groupe humain

- **Domaine :** ZUMRA & Capabilities
- **Propriétaire d’exécution :** DG Afrique
- **Statut :** implémenté — preuve VPS requise
- **Sources :** doctrine ZUMRA v1.1, CAP-007 à CAP-010

## Finalité

Permettre à des adhérents actifs de constituer une unité humaine organisée autour d’un domaine, d’un objectif fondateur, d’une charte et d’une gouvernance réelle.

Une ZUMRA est une unité particulière du réseau. Elle ne doit jamais être confondue avec le Programme ZUMRA dans son ensemble.

## Constitution

Tout adhérent actif peut proposer une ZUMRA. Le dossier contient un nom, un domaine principal, un objectif fondateur, un mode physique, numérique ou hybride et une charte interne conforme à la Charte générale.

Le proposant devient membre fondateur. Il choisit explicitement s’il accepte le rôle de premier responsable ; ce rôle ne lui est pas attribué silencieusement.

La création ouvre l’état `CONSTITUTING`. Elle ne vaut ni validation, ni financement, ni reconnaissance officielle.

## Gouvernance fondatrice

Les cinq sièges invariants sont créés dès le dossier : premier responsable, deux adjoints distincts, responsable financier et responsable des relations, affaires sociales et religieuses.

Un siège vacant reste visible comme vacant. Aucun profil fictif, matching ou automatisme ne peut accepter un rôle au nom d’une personne. La readiness et la validation complète seront raccordées avec les contrats de nomination et d’acceptation.

## Appartenance

- une demande reste `REQUESTED` jusqu’à approbation d’un responsable ;
- une invitation reste `INVITED` jusqu’à acceptation du destinataire ;
- une personne peut appartenir à plusieurs ZUMRA ;
- un membre sans responsabilité active peut partir librement ;
- un responsable transmet d’abord sa charge afin de ne pas créer une vacance silencieuse ;
- tous les changements produisent des événements conservés.

## Cycle et maturité

Le statut opérationnel et la maturité sont distincts. Le seuil initial de 50 membres produit la maturité `ESTABLISHED`, sans plafonner la croissance. Le seuil est administrable ; les cinq responsabilités et le consentement ne le sont pas.

Une ZUMRA suspendue devient invisible de l’annuaire, sans suppression de son histoire.

## Invariants

1. adhésion active au Programme exigée pour proposer, demander ou accepter ;
2. aucune adhésion directe sans approbation ou acceptation ;
3. aucune nomination automatique ;
4. aucune identité canonique exposée dans l’interface ;
5. aucune contribution utilisée comme droit d’entrée, score ou pouvoir ;
6. nom, domaine, objectif et charte ne sont jamais inventés ;
7. actions sensibles limitées et journalisées ;
8. politique opérationnelle configurable sans affaiblir la doctrine.

## Critères de preuve

- membre inactif bloqué à la création ;
- dossier réel avec cinq sièges et un seul rôle accepté explicitement ;
- demande sans adhésion automatique puis approbation ;
- invitation sans adhésion automatique puis acceptation ;
- départ libre et protection des responsabilités actives ;
- configuration admin persistée ;
- migration, tests ciblés, non-régression et build verts sur VPS.
