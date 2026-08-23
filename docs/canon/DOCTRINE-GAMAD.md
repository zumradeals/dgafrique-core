# Doctrine humaine de GAMAD

## Statut du document

- Statut : **CANONIQUE — ADOPTÉ**
- Version : **1.1**
- Date d'adoption : **22 août 2026**
- Mission d'origine : **DOCTRINE-GAMAD-001**, arbitrage ZUMRA rendu par **DOCTRINE-GAMAD-001D**
- Portée : GAMAD dans son ensemble — antérieure et supérieure à DG Afrique, GAMAD Core, ZUMRA,
  Organisation et tout outil spécialisé, qui en sont des expressions ou des traductions
  numériques, jamais la source.

Ce document occupe la couche **00 — DOCTRINE / INVARIANTS** de la hiérarchie documentaire
décrite dans `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §30, au même niveau fondateur que ce
dernier document, dont il précède et éclaire le sens. `docs/foundation/DG-AFRIQUE-DOCTRINE.md`
et `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` restent chacun l'autorité opérationnelle de leur
propre périmètre (produit DG Afrique pour le premier, Programme ZUMRA pour le second) ; ce
document ne les remplace pas et ne modifie aucune de leurs règles opérationnelles. Il fixe la
raison d'être humaine que ces deux documents traduisent chacun à leur niveau.

**Une contradiction entre ce document et une règle opérationnelle déjà adoptée ailleurs n'est
jamais résolue silencieusement par ce texte.** L'arbitrage ZUMRA rendu au §6.4 clarifie que la
transmission sur la naissance d'une ZUMRA à une seule personne ne contredisait pas le cycle de vie
opérationnel de `ZumraGroup` (`ZUMRA-DOCTRINE-INVARIANTE.md` §10) : les deux textes décrivent des
axes distincts (existence/activité générale vs. gouvernance collective, voir §6.4). Le seul point
encore ouvert — la présentation/le nommage futur des états du runtime — est signalé explicitement
au §6.5 et attend une décision de l'autorité GAMAD compétente suivant la procédure de gouvernance
propre au document concerné.

## Méthode de lecture — quatre provenances distinctes

Ce document distingue en permanence quatre statuts de provenance, jamais fusionnés :

- **SOURCE PRIMAIRE DOCUMENTAIRE** — ce qui est attesté par les Statuts et le Règlement intérieur
  historiques du Mouvement GAMAD. Ce texte historique **n'est pas versionné dans ce dépôt** ; ce
  document ne prétend donc jamais citer les Statuts eux-mêmes, seulement rapporter ce que la
  mission DOCTRINE-GAMAD-001 en a communiqué.
- **TRANSMISSION DU DÉPOSITAIRE** — ce que Djakaridja KONE, dit Zakaria Le SOUFI, a explicitement
  transmis dans le cadre de cette mission comme reçu de Cheikh Gaoussou DRAME.
- **CONSTAT LOGICIEL** — ce que `dgafrique-core` et `gamad-core` implémentent réellement
  aujourd'hui, vérifié par lecture directe du code et des tests, jamais supposé.
- **INTERPRÉTATION ARCHITECTURALE** — les conclusions que ce document tire pour guider les
  systèmes numériques futurs, explicitement marquées comme telles, jamais présentées comme une
  doctrine reçue.

Aucune transmission spirituelle ou historique n'est ici transformée en fait technique. Aucune
extrapolation théologique n'est ajoutée au-delà de ce qui a été explicitement communiqué.

---

## 1. Identité officielle de GAMAD

**SOURCE PRIMAIRE DOCUMENTAIRE / TRANSMISSION DU DÉPOSITAIRE, à conserver mot pour mot :**

- Nom développé : **« Globales Activités et Mouvements en Actions pour le Développement »**
- Slogan officiel : **« Vivre La Religion de Dieu pour Chaque Mouvement Religieux. »**
- Devise : **FORMATION — TRAVAIL — ADORATION**

Ces trois formulations sont des éléments d'identité et ne doivent jamais être modernisées,
remplacées ou substituées par une formulation produit plus contemporaine, dans aucun document ni
aucune interface qui prétendrait les citer comme identité officielle.

`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §1 cite déjà le nom développé de GAMAD et la devise ;
ce document n'y ajoute rien de nouveau sur ce point précis, il en documente la source et le sens
(§4 ci-dessous). Le slogan religieux, en revanche, n'apparaissait dans aucun document du dépôt
avant cette mission — il est enregistré ici pour la première fois comme élément d'identité
historique, **sans devenir un texte affiché dans l'expérience DG Afrique**, cohérente avec
l'invariant de confidentialité institutionnelle déjà posé par `ZUMRA-DOCTRINE-INVARIANTE.md`
(introduction : *« DG Afrique est l'unique vitrine visible de l'écosystème. »*).

## 2. Provenance doctrinale déclarée

**TRANSMISSION DU DÉPOSITAIRE**, rapportée telle que communiquée à cette mission, sans
extrapolation :

- Le Mouvement GAMAD est né à l'initiative de Cheikh Gaoussou DRAME.
- Djakaridja KONE, dit Zakaria Le SOUFI, se déclare et reste disciple hamalliste de Cheikh
  Gaoussou DRAME.
- Cheikh Gaoussou DRAME est décrit comme lui-même disciple de Cheikh Hamallah.
- Cheikh Gaoussou DRAME est présenté comme le fondateur de GAMAD.
- Djakaridja KONE indique avoir rédigé les Statuts et le Règlement intérieur de GAMAD sous la
  dictée de Cheikh Gaoussou DRAME.

**Ce que ce document ne fait pas** : il ne vérifie pas indépendamment cette généalogie, ne
documente aucun élément biographique ou théologique supplémentaire, et ne présente cette
transmission ni comme une preuve historique absolue ni comme sujette à doute — il la rapporte
fidèlement comme provenance déclarée du dépositaire actuel de la doctrine, dans le cadre exact où
elle a été communiquée à cette mission.

---

## 3. Principe fondateur — la Personne précède la structure

**INTERPRÉTATION ARCHITECTURALE, directement guidée par la TRANSMISSION DU DÉPOSITAIRE (§9 du
mandat DOCTRINE-GAMAD-001) :**

GAMAD commence par la **personne volontaire**. Il ne commence pas par une ZUMRA, une
Organisation, un Projet, DG Afrique, GAMAD Core, un satellite, une institution ou une
application. Ces structures et outils existent pour augmenter les possibilités offertes aux
personnes — apprendre, développer leurs capacités, transmettre, travailler, agir, entreprendre,
collaborer, répondre à des besoins, construire des projets, trouver des opportunités, contribuer,
participer à la solidarité, progresser humainement — jamais pour fabriquer des utilisateurs à
leur service.

> **GAMAD ne crée pas des personnes pour alimenter ses produits. Les produits et structures de
> l'écosystème doivent augmenter les possibilités des personnes.**

Ce principe ne redéfinit aucune règle opérationnelle existante. Il en explicite la raison d'être :
`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §2-3 (« L'humain précède le formulaire »,
`Personne → Capacité → Besoin → Projet → Relation → ZUMRA → Action`) et
`docs/design/DESIGN-INVARIANTS.md` §2 (« Une identité. Des personnes. Des capacités. Des actions.
Des outils spécialisés. ») l'exprimaient déjà, sans le nommer explicitement comme principe
fondateur de GAMAD lui-même plutôt que de DG Afrique seul. Ce document comble cet écart de
formulation, sans modifier la substance de ces deux textes.

---

## 4. Formation — Travail — Adoration

**INTERPRÉTATION ARCHITECTURALE bâtie sur la TRANSMISSION DU DÉPOSITAIRE.** Cette section fixe,
pour la première fois dans le corpus, le sens de la devise citée depuis plusieurs documents sans
jamais être définie (constat de l'audit Phase A DOCTRINE-HUMAIN-001).

**Avertissement structurant, à ne jamais oublier :** la devise ne représente **pas** trois états
logiciels. Elle ne doit jamais devenir `FORMATION → TRAVAIL → ADORATION` comme trois statuts
obligatoires d'un utilisateur, ni un workflow, ni une progression à cocher. Ce sont trois
dimensions structurantes de la doctrine humaine de GAMAD, pas un tunnel.

### 4.1 Formation

Une personne volontaire, en quête de développement et d'une meilleure stabilité morale,
économique et spirituelle, doit d'abord pouvoir apprendre. La Formation comprend notamment
connaissance, apprentissage, développement des capacités, encadrement, éducation, développement
personnel, et — selon la doctrine historique transmise — des dimensions intellectuelles, morales
et spirituelles.

Une conséquence de la Formation est la **Transmission** : celui qui a reçu une connaissance ou
développé une capacité peut à son tour transmettre. Le logiciel peut **permettre et faciliter**
la transmission ; il ne doit **jamais** la transformer en obligation automatique ni calculer une
dette morale de transmission.

### 4.2 Travail

La Formation doit pouvoir déboucher sur l'action. Le Travail se comprend largement : emploi,
mission, activité, projet, entreprise, organisation, coopération, partenariat, activité
économique, service rendu, production, action collective, entrepreneuriat — la possibilité de
mobiliser réellement une capacité dans la vie. Une personne formée peut également être éligible
à travailler dans une structure de l'écosystème GAMAD lorsque les conditions métier
correspondantes existent, mais ceci ne devient jamais une promesse automatique d'emploi.

### 4.3 Adoration

La recherche économique et professionnelle ne constitue pas à elle seule la finalité humaine de
GAMAD. La doctrine transmise recherche un équilibre entre le Travail et la croyance religieuse,
dans le respect de la liberté religieuse de chaque personne — selon l'explication transmise, peu
importe la religion de la personne, la recherche porte sur des valeurs telles que l'amour, la
paix et la justice.

**TRANSMISSION DU DÉPOSITAIRE**, formule rapportée pour éclairer l'intention, jamais pour devenir
une règle :

> « Une personne endettée sur sa natte de prière n'est pas concentrée dans sa prière. »

Cette phrase exprime une idée humaine : les difficultés matérielles, économiques ou morales
peuvent affecter profondément la disponibilité intérieure d'une personne. Elle **ne doit jamais**
devenir une règle financière ou religieuse du logiciel. Le logiciel ne mesure jamais la foi, la
piété, l'adoration, la moralité, la proximité avec Dieu ou la qualité religieuse d'une personne.

---

## 5. La stabilité est une finalité humaine, pas un modèle

**Rectification explicite de l'audit DOCTRINE-HUMAIN-001** : l'absence de « stabilité » comme
objet logiciel dans le corpus n'est pas un manque à combler par un nouveau modèle de données.

La stabilité — morale, économique, intérieure, spirituelle — est une **finalité humaine
recherchée** par la dynamique Formation—Travail—Adoration. Le système peut créer des conditions
ou des possibilités qui y contribuent (formation, capacité d'agir, contribution communautaire,
solidarité). **Il ne doit jamais prétendre la mesurer.**

Sont explicitement interdits : `stability_score`, `moral_score`, `spiritual_score`, tout niveau de
foi, tout indice ou classement de stabilité, tout statut « personne stable ». La stabilité
appartient aux résultats humains recherchés, jamais à la notation produit.

---

## 6. ZUMRA — le collectif peut commencer par une seule personne

### 6.1 La transmission

**TRANSMISSION DU DÉPOSITAIRE**, rapportée telle que communiquée, selon Cheikh Gaoussou DRAME :
une ZUMRA peut commencer avec une seule personne. Une personne volontaire peut créer une ZUMRA ;
cette ZUMRA n'a pas besoin d'attendre 2, 5, 10 ou 50 personnes pour être une ZUMRA réelle. La
pluralité est une vocation de développement de la ZUMRA, jamais une condition préalable à son
existence. Le premier responsable en constitue le point de départ humain, avec pour mission
notamment de rechercher des collaborateurs et de développer progressivement sa ZUMRA. Selon la
transmission rapportée, Cheikh Gaoussou DRAME enseignait également qu'il est du devoir de tout
citoyen GAMAD d'inviter des membres à rejoindre GAMAD à travers sa ZUMRA — un devoir qui
appartient à la doctrine et à la gouvernance humaine, **jamais** à transformer automatiquement en
contrainte logicielle (aucune mécanique de recrutement imposée).

### 6.2 Ce que le produit peut faciliter

Inviter une personne, présenter sa ZUMRA, trouver des collaborateurs pertinents, accueillir une
demande d'adhésion, partager un besoin, créer un projet, proposer une mission, transmettre une
connaissance, organiser une activité, collaborer avec une Organisation — sans jamais imposer une
mécanique de recrutement, un score de recrutement, un quota de membres pour être « active », ou
un classement des responsables selon le nombre de personnes recrutées.

### 6.3 CONSTAT LOGICIEL — audit du runtime actuel (`ZumraGroup`, `ZumraGroupService`, CAP-011)

Vérifié par lecture directe du code, des tests et de `docs/capacites/specs/CAP-011-zumra-groupe-humain.md` — sans aucune modification :

- **Une seule personne peut aujourd'hui créer une ZUMRA.** `CAP-011` §Constitution : *« Tout
  adhérent actif peut proposer une ZUMRA [...] Le proposant devient membre fondateur. »* La
  création ouvre immédiatement l'état `ZumraGroup::STATE_CONSTITUTING`.
- **Cette ZUMRA n'est pas techniquement à l'état `ACTIVE` dès sa création.** Le cycle de vie
  opérationnel du code est `CONSTITUTING → READY → VALIDATED → ACTIVE → ...` ; l'état `ACTIVE`
  n'est atteint qu'après acceptation des cinq responsabilités fondatrices distinctes (`READY`),
  puis une décision explicite de l'autorité DG Afrique/GAMAD (`VALIDATED` puis `ACTIVE`) —
  jamais automatiquement à la seule création.
- **`CONSTITUTING` n'est cependant ni bloqué ni présenté péjorativement.** La fiche de la ZUMRA
  (`resources/views/zumra/groups/show.blade.php`) affiche honnêtement l'état réel sous un badge
  neutre (« En constitution »), jamais « vide » ou « incomplète ». Aucun gate d'état `ZumraGroup`
  n'a été trouvé dans les services `NeedService`, `ProjectService`, `CommunityEventService` ni
  dans `TransmissionController` — seul l'état `SUSPENDED` bloque Messagerie, Partage, Commentaire
  et Mission (confirmé par `CAP-011` §Cycle de vie opérationnel). Concrètement, **un responsable
  seul peut déjà, dès la constitution** : inviter, rechercher des collaborateurs (via demande
  d'adhésion), accueillir une demande, exprimer un besoin, créer un projet, proposer une mission,
  transmettre une connaissance et organiser un événement communautaire — sans attendre les cinq
  responsabilités ni la validation.
- **Le seuil de 50 membres n'est pas un seuil d'existence.** Confirmé identiquement par
  `ZUMRA-DOCTRINE-INVARIANTE.md` §9 et `CAP-011` §Cycle et maturité : il produit uniquement la
  maturité `ESTABLISHED`, jamais un plafond ni une condition de validité. **Aucune contradiction
  sur ce point précis.**
- **Le seul gate métier substantiel réellement trouvé au-delà de `SUSPENDED`** concerne
  l'engagement de contribution financière collective d'une ZUMRA (CAP-061) :
  `ContributionService::ZUMRA_ELIGIBLE_STATES` exige `VALIDATED` ou au-delà. Ce gate implémente
  littéralement `ZUMRA-DOCTRINE-INVARIANTE.md` §6.3 (« Condition : ZUMRA validée ») et §8 (cinq
  responsabilités distinctes comme garde-fou contre une décision financière collective
  unilatérale) — un texte au statut **CANONIQUE — ADOPTÉ (v1.1, 14 août 2026)**, sa propre
  procédure de gouvernance (§26) restant seule habilitée à en faire évoluer le sens. Aucun autre
  domaine métier (Besoin, Projet, Mission, Transmission, Événement, Partnership, Fil,
  Notifications) ne dépend de `READY`/`VALIDATED`/`ACTIVE`.

### 6.4 Arbitrage rendu — quatre axes distincts, jamais confondus

**Arbitrage doctrinal (DOCTRINE-GAMAD-001D, 22 août 2026)**, rendu après l'audit runtime
complet ci-dessus : **une ZUMRA est une ZUMRA réelle, vivante et opérationnelle dès sa création
avec son premier responsable.** Elle peut commencer avec une seule personne. La pluralité humaine
est une vocation de développement et de gouvernance, jamais une condition préalable à son
existence ou à son activité générale.

Cet arbitrage **ne supprime pas** la règle des cinq responsabilités distinctes, qui reste
validée — mais cesse d'être lue comme la condition permettant à une ZUMRA de commencer à vivre ou
à agir. Quatre axes conceptuels distincts sont canonisés :

- **A — Existence / activité générale.** Commence dès la création par le premier responsable.
  Une ZUMRA solo est une vraie ZUMRA : elle peut vivre, agir, chercher des collaborateurs et
  développer son activité. Confirmé par le constat §6.3 : c'est déjà, dans les faits, le
  comportement du runtime pour tous les domaines sauf un.
- **B — Structuration / gouvernance collective.** Progressive. Les cinq responsabilités doivent
  être occupées par cinq personnes distinctes pour atteindre le jalon de gouvernance prévu par la
  doctrine (§8/§10 `ZUMRA-DOCTRINE-INVARIANTE.md`). Cette exigence ne remet jamais en cause
  l'existence de la ZUMRA ; elle conditionne légitimement les actes qui nécessitent plusieurs
  regards et responsabilités — aujourd'hui, dans le code, exclusivement l'engagement de
  contribution financière collective (§6.3 ci-dessus, CAP-061).
- **C — Maturité.** `ESTABLISHED` à 50 membres (§9 `ZUMRA-DOCTRINE-INVARIANTE.md`). Aucune
  modification, aucune remise en cause : ce seuil ne signifie jamais « la ZUMRA existe enfin ».
- **D — Discipline.** `WARNED`/`SUSPENDED`/`REHABILITATING` — un axe distinct de modération,
  jamais confondu avec l'existence, la structuration ou la maturité.

**EXISTENCE ≠ GOUVERNANCE VALIDÉE ≠ MATURITÉ ≠ DISCIPLINE.**

### 6.5 Dette documentée — arbitrage futur du runtime, non tranché ici

Le problème résiduel identifié par l'archéologie est **principalement sémantique** : le runtime
permet déjà à une ZUMRA `CONSTITUTING` d'agir dans pratiquement tous les domaines (axe A), mais le
mot affiché — « En constitution » — et le nom technique du cycle de vie
(`CONSTITUTING → READY → VALIDATED → ACTIVE`) peuvent laisser entendre qu'il ne s'agit pas encore
d'une ZUMRA pleinement réelle ou active humainement, alors qu'elle l'est déjà. Le seul gate métier
substantiel (contribution financière collective) reste justifié par l'exigence de gouvernance
validée (axe B) et n'est pas remis en cause.

**Cette mission ne modifie pas le runtime.** Une future mission dédiée devra déterminer,
explicitement et sans supposition, laquelle de ces pistes retenir :

- **A.** Conserver les états internes actuels mais humaniser leur présentation (vocabulaire
  affiché, jamais le nom technique).
- **B.** Renommer certains états pour refléter directement les quatre axes ci-dessus.
- **C.** Séparer explicitement, dans le modèle, le cycle de vie opérationnel (axe A/D) de la
  gouvernance collective (axe B).
- **D.** Conserver `VALIDATED` comme jalon suffisant et exclusif pour les actes financiers, sans
  jamais donner à `ACTIVE` le sens de « commence enfin à vivre ».

Aucune de ces quatre pistes n'est décidée par ce document.

### 6.6 Invariant UX — à observer dès maintenant, sans écran imposé

**Une ZUMRA composée d'un seul responsable ne doit jamais être présentée comme vide, inexistante,
invalide ou inutile.** L'expérience doit plutôt exprimer, dans son esprit sinon sa formulation
exacte (décision UI future, non tranchée ici) :

> « Votre ZUMRA est créée et peut agir. Construisez maintenant son collectif. »

Le produit peut faciliter trouver des collaborateurs, inviter, accueillir, attribuer
progressivement les responsabilités, lancer des actions, transmettre, créer Besoins/Projets/
Missions/Événements, collaborer avec des Organisations — **sans jamais gamifier le recrutement**
(cohérent avec §6.2 et §14).

### 6.7 La ZUMRA comme mouvement, pas comme tunnel

```
PERSONNE VOLONTAIRE
        │
        ▼
CRÉATION D'UNE ZUMRA
        │
        ▼
UNE ZUMRA RÉELLE, DÈS LE PREMIER RESPONSABLE
        │
        ▼
RECHERCHE / INVITATION / ACCUEIL
        │
        ▼
COLLABORATEURS
        │
        ▼
FORMATION • TRANSMISSION • ACTION • SOLIDARITÉ
        │
        ▼
DÉVELOPPEMENT DU COLLECTIF
```

Ce schéma ne constitue pas un tunnel obligatoire. Une ZUMRA n'est jamais « incomplète » parce
qu'elle se trouve encore au début de ce mouvement.

---

## 7. La boucle humaine de GAMAD

La boucle humaine n'est pas une machine à états. Représentation conceptuelle :

```
PERSONNE VOLONTAIRE
        │
        ├──────────────► peut créer une ZUMRA immédiatement
        │                         │
        │                    ZUMRA RÉELLE
        │                         │
        │                  cherche / accueille
        │                   des collaborateurs
        │                         │
        ▼                         ▼
FORMATION / APPRENTISSAGE ◄── TRANSMISSION
        │
        ▼
CAPACITÉ
        │
        ▼
ACTION / TRAVAIL / PROJET
        │
        ▼
PREUVE / EXPÉRIENCE
        │
        ▼
AUTONOMIE ET STABILITÉ RECHERCHÉES
        │
        ▼
CONTRIBUTION / SOLIDARITÉ
        │
        ├────────► PERSONNES
        ├────────► ZUMRA
        ├────────► ORGANISATIONS / PROJETS
        └────────► COMMUNAUTÉ
                         │
                         ▼
              NOUVELLES POSSIBILITÉS
                         │
                         ▼
                      PERSONNE
```

Ce schéma représente des relations et des possibilités, pas un ordre obligatoire. Une personne
peut créer sa ZUMRA avant d'avoir déclaré une capacité, rejoindre une ZUMRA existante, travailler
sans appartenir à une ZUMRA, transmettre avant d'avoir un Projet, apprendre après plusieurs années
d'expérience, contribuer sans être jugée « stable », créer un Projet avant de rejoindre un
collectif, collaborer avec une Organisation, ou n'utiliser un outil spécialisé que lorsqu'il lui
devient utile. **La boucle est un mouvement, pas un tunnel.**

---

## 8. Principe de retour de valeur

> **Les outils peuvent créer de nouvelles possibilités pour les personnes et les collectifs ; ils
> restent des moyens, jamais la finalité.**

La valeur produite dans l'écosystème doit pouvoir revenir vers les personnes et les communautés
qui le composent — connaissance, capacité, expérience, relation, opportunité, activité, revenu,
preuve, service, solidarité, infrastructure. Ce document **ne canonise aucun mécanisme financier
particulier** : il n'écrit pas que Wasplex doit obligatoirement financer les contributions ZUMRA,
que les revenus publicitaires doivent financer GAMAD, ni que G-POS deviendrait obligatoire pour
l'autonomie économique d'une personne. Ce sont des possibilités qui pourront faire l'objet de
politiques ou produits spécialisés futurs, jamais une doctrine adoptée par ce seul document.

## 9. Contribution et solidarité

La contribution fait partie de la dynamique collective, mais ne signifie pas uniquement l'argent.
Elle peut prendre plusieurs formes selon les règles et contextes : transmission, connaissance,
temps, travail, capacité, aide, engagement, contribution financière, soutien collectif. Le
logiciel ne transforme jamais la contribution en mesure de valeur humaine ; une personne qui
contribue davantage financièrement n'est jamais présentée comme « meilleure », et aucun score de
mérite social n'en est déduit — cohérent avec les garde-fous déjà posés par
`ZUMRA-DOCTRINE-INVARIANTE.md` §4 et §6 et `docs/capacites/OVERRIDES.md` OVR-003, que ce document
ne modifie pas.

## 10. Place des Organisations

Une Organisation n'est pas une ZUMRA. Une Organisation est un acteur déjà établi qui peut
disposer d'une identité dans l'écosystème, déclarer des capacités, fournir, collaborer,
participer à des projets, répondre à des besoins, établir des partenariats, organiser certaines
activités, utiliser des outils spécialisés. Une Organisation peut elle-même être issue
historiquement ou économiquement d'une dynamique GAMAD/ZUMRA, mais cela ne doit jamais être
supposé pour toutes les Organisations. Personne, ZUMRA, Organisation et Projet restent quatre
concepts distincts, jamais confondus — cohérent avec `docs/capacites/specs/CAP-066-organisation.md`
(*« créée volontairement par une personne réelle [...] jamais produite automatiquement par un
autre agrégat »*), que ce document ne modifie pas.

## 11. Place des outils spécialisés

Les outils spécialisés augmentent certaines possibilités particulières de la boucle humaine. Ils
ne deviennent jamais le centre doctrinal de GAMAD. Exemples illustratifs, **non contractuels** :

- **G-POS** peut augmenter la découvrabilité commerciale, les catalogues et les relations entre
  vendeurs, clients, fournisseurs et Organisations.
- **Wasplex** peut augmenter les possibilités liées aux médias, à la publicité, à la visibilité,
  aux partenaires et à certaines formes de création/redistribution de valeur économique.
- Un futur **G-TUBE** ou système vidéo pourrait augmenter les possibilités de transmission,
  documentation, démonstration, communication ou preuve.

Ces exemples servent à comprendre l'architecture ; leur rôle futur supposé ne devient jamais un
contrat métier actuel par ce seul document. Un satellite reste extractible (cohérent avec
`docs/capacites/OVERRIDES.md` OVR-005 et `docs/architecture/ADR-005-extractable-specialized-tools.md`).
Aucun satellite n'est propriétaire du parcours global d'une personne — **CONSTAT LOGICIEL** :
`gamad-core/docs/01-architecture-core-portail-satellites.md` §5 confirme que chaque satellite ne
détient qu'une référence locale opaque vers l'identité canonique du Core, jamais l'identité
elle-même.

## 12. Séparation des responsabilités

- **GAMAD** = doctrine, mouvement, finalité humaine, gouvernance.
- **GAMAD Core** = infrastructure canonique transverse, invisible autant que possible : identité,
  organisations canoniques, produits, autorisations, fédération, contrats transversaux, matching
  transversal lorsque pertinent. Le Core ne devient jamais un moteur de notation spirituelle,
  morale ou humaine.
- **DG Afrique** = portail humain, social et relationnel : personnes, capacités, besoins, projets,
  missions, preuves, transmissions, ZUMRA, Organisations projetées, opportunités, relations,
  activité.
- **ZUMRA** = moteur collectif/social de proximité pouvant commencer avec une seule personne
  responsable et se développer par collaboration.
- **Organisation** = acteur établi capable d'apporter des capacités et d'agir dans le réseau.
- **Outils spécialisés** = capacités métier spécialisées mobilisées lorsqu'elles sont utiles.

Cette séparation confirme, sans les modifier, celles déjà posées par
`ZUMRA-DOCTRINE-INVARIANTE.md` §3 et `gamad-core/docs/00-vision-ecosysteme-gamad.md` §2.

## 13. Conséquence UX future — orientation, pas écran obligatoire

**Orientation produit pour une future mission (nommément UIUX-007, non commencée ici) :** DG
Afrique doit progressivement permettre à une personne de comprendre naturellement ce qu'elle peut
apprendre, ce qu'elle sait faire, ce qu'elle peut transmettre, où elle peut agir, quelles
opportunités correspondent à ses capacités, avec quelles personnes elle peut collaborer, quelles
ZUMRA elle peut rejoindre, comment créer sa propre ZUMRA si elle souhaite initier un collectif,
comment trouver les premiers collaborateurs de cette ZUMRA, avec quelles Organisations elle peut
collaborer, à quels projets elle peut contribuer, comment ses actions et expériences enrichissent
son parcours, et comment elle peut contribuer à son tour — sans jamais devenir un onboarding
linéaire obligatoire.

## 14. Protection absolue contre la gamification humaine

Cette doctrine ne doit jamais produire, et aucune future implémentation ne doit introduire :

- score de développement personnel, score moral, score spirituel, score religieux ;
- score de « bon citoyen GAMAD » ;
- classement des personnes ou des ZUMRA par vertu ;
- compétition de recrutement ;
- progression religieuse calculée, niveau d'adoration ;
- obligation automatique de transmission, de contribution ou d'utilisation d'un satellite ;
- score de stabilité (voir §5).

Les preuves prouvent des actions, réalisations ou faits. Elles ne prouvent jamais la valeur
intrinsèque d'un être humain.

---

## Gouvernance de ce document

Toute évolution doit être consciente, argumentée, documentée et distincte d'une simple décision
d'implémentation — même principe que `docs/foundation/DG-AFRIQUE-DOCTRINE.md` et
`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §26. Une contradiction entre ce document et une source
antérieure (Statuts/Règlement historiques, non versionnés ici) doit toujours se résoudre en
faveur de la source historique une fois celle-ci vérifiable ; en son absence documentaire, ce
texte reste la meilleure transcription canonique disponible de la transmission reçue par cette
mission.

**FIN — Doctrine humaine de GAMAD v1.1**
