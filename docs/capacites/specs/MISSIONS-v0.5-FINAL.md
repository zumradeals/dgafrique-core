# MISSIONS v0.5 — VERROUILLAGE FINAL D’IMPLÉMENTATION

**Statut : READY FOR IMPLEMENTATION**  
**Version : 0.5**  
**Racine référentielle : CAP-069 — TÂCHE**  
**Expression produit : MISSIONS**  
**Nouveau CAP : non**  
**Branche de conception : `docs/missions-v0.4`**

## 1. Autorité documentaire

Le contrat d’implémentation MISSIONS v0.5 est constitué de :

1. `docs/capacites/specs/MISSIONS.md` — spécification détaillée v0.4 ;
2. le présent fichier — décisions finales v0.5.

Le présent fichier **complète et verrouille** la v0.4. En cas de divergence, **v0.5 prévaut**. Claude Code doit lire les deux fichiers avant de modifier le métier.

La doctrine reste :

> **Proposer n’est pas décider. Accepter n’est pas commencer. Soumettre n’est pas valider. Participer n’est pas gouverner.**

MISSIONS reste un moteur transversal complet. Le fait que seuls certains adaptateurs soient activés immédiatement ne réduit pas le moteur à un MVP : le registre est volontairement extensible et fail-closed.

---

## 2. Contextes activés dans le premier codage

Le registre de production initial contient **exactement** :

```text
PROJECT
ZUMRA
NEED
```

avec :

```text
ProjectMissionContext
ZumraMissionContext
NeedMissionContext
```

Ce choix n’est pas une limitation conceptuelle de MISSIONS. Une Mission peut appartenir à tout objet métier réel de DG Afrique **dès lors qu’un adaptateur explicite définit visibilité, contribution, autorité, état opérationnel et plafond de visibilité**.

Claude ne doit pas ajouter opportunité, événement, accompagnement, organisation, satellite, transmission ou autre contexte simplement parce qu’un modèle voisin existe. Un nouveau contexte exige un contrat d’autorité clair et une revue explicite.

### 2.1 Règle commune de proposition

Pour `PROJECT` et `NEED` :

```text
actor possède une adhésion Programme ZUMRA ACTIVE
AND context.canView(actor)
AND context isOperational
```

=> il peut **proposer** une Mission.

Pour `ZUMRA` :

```text
membership de groupe ACTIVE
AND groupe non SUSPENDED
```

=> il peut **proposer** une Mission.

La visibilité publique d’un Projet ou d’un Besoin ne donne donc pas à un simple visiteur non membre actif le droit de créer une proposition.

### 2.2 Officialisation

- `PROJECT` : `ProjectService::canDecide()`.
- `NEED` : `NeedService::canDecide()`.
- `ZUMRA` : `ZumraGroupService::isLeader()` selon la sémantique actuelle des rôles acceptés.

Aucune administration de substitution.

### 2.3 État opérationnel

- Projet : opérationnel tant qu’il n’est pas `ARCHIVED`.
- Besoin : opérationnel tant qu’il n’est pas `ARCHIVED`.
- ZUMRA : mutations ordinaires refusées en `SUSPENDED`; autres états suivent les permissions réelles existantes. Une règle spéciale de réhabilitation ne doit être ajoutée que si elle existe explicitement ailleurs.

---

## 3. Création, proposition et officialisation

Toute Mission commence par une création explicite dans un contexte réel.

Le workflow canonique reste :

```text
DRAFT -> PROPOSED -> OPEN
```

Même une autorité ne doit pas contourner silencieusement la distinction proposition/décision.

L’UI peut offrir à une autorité une expérience courte « Proposer puis officialiser », mais le service doit produire les deux décisions/audits distincts, même si elles surviennent dans la même interaction utilisateur.

`OPEN` exige :

```text
title
description
expected_result
context valide
visibility valide
```

---

## 4. Visibilité — règle finale

Ordre de visibilité :

```text
PRIVATE < CONTEXT < PROGRAM < PUBLIC
```

Une Mission peut être aussi visible ou plus restrictive que son contexte, jamais plus large.

Si l’utilisateur demande une visibilité supérieure au maximum du contexte, **refuser en 422**. Ne pas rétrograder silencieusement.

### 4.1 PROJECT / NEED

Le plafond provient de la visibilité réelle du parent et de `canView()`.

### 4.2 ZUMRA

Dans le premier adaptateur, le plafond Mission est `CONTEXT`. Les Missions ZUMRA sont destinées à l’espace opérationnel du groupe ; le moteur ne fabrique pas une visibilité publique ZUMRA inexistante dans le métier courant.

### 4.3 PRIVATE

Une Mission `PRIVATE` est visible uniquement par :

- son créateur ;
- les autorités courantes du contexte ;
- les personnes ayant une `MissionAssignment` courante `OFFERED`, `INVITED` ou `ACCEPTED` sur cette Mission.

Une personne `WITHDRAWN`, `DECLINED`, `RELEASED` ou `REMOVED` ne conserve pas automatiquement l’accès si le contexte lui-même ne le permet plus.

### 4.4 Identité des exécutants

La visibilité d’une Mission **n’implique jamais la publicité des identités des exécutants**.

`MissionVisibilityService` doit exposer séparément :

```text
canViewMission(actor, mission)
canExposeParticipantIdentity(viewer, assignment)
```

Un écran public peut montrer un nombre de participants sans exposer leurs noms lorsque les règles de découvrabilité/consentement ne l’autorisent pas.

Tout partage revalide l’accès ; partager ne donne jamais un droit supplémentaire.

---

## 5. Sous-Missions — règle finale

Profondeur maximale :

```text
3
```

Dans v0.5, une sous-Mission doit conserver **exactement le même `context_type` et le même `context_reference` que sa Mission parente**.

Les sous-Missions inter-contextes sont interdites. Si une action doit exister dans un autre contexte, créer une Mission indépendante et utiliser une dépendance explicite si nécessaire.

Règles :

- aucun cycle parent/enfant ;
- aucun enfant plus visible que le parent ;
- une Mission avec enfant non terminal ne peut pas être `COMPLETED` ;
- une Mission avec enfant non terminal ne peut pas être `CANCELLED` ;
- l’UI indique les enfants à terminer ou annuler ;
- aucune cascade destructive ou annulation silencieuse ;
- la clôture de tous les enfants ne clôture pas automatiquement le parent.

États enfants terminaux :

```text
REJECTED
COMPLETED
CANCELLED
```

---

## 6. Retrait des exécutants — règle finale

La liberté de participation est préservée. Une affectation acceptée peut devenir `RELEASED` selon le workflow audité.

### Mission OPEN

Si le dernier exécutant accepté quitte avant démarrage :

```text
Mission reste OPEN
```

### Mission IN_PROGRESS

Si le dernier exécutant d’exécution (`EXECUTOR`, `CO_EXECUTOR`, `COORDINATOR`) quitte :

```text
Mission -> BLOCKED
```

et un blocker `PERSON_UNAVAILABLE` est créé si aucun blocker actif équivalent n’existe.

Ce blocker est une conséquence métier explicable du retrait. Il **ne crée aucun Need**.

### Mission BLOCKED

Elle reste `BLOCKED`; un blocker `PERSON_UNAVAILABLE` doit représenter l’absence d’exécutant si nécessaire.

### Mission SUBMITTED

Le retrait reste possible : la soumission est un snapshot historique du travail présenté et ne disparaît pas. Le retrait ne remet pas automatiquement la Mission en travail ni n’invalide la soumission. L’autorité décide `COMPLETED` ou `IN_PROGRESS` via correction.

### Mission terminale

Après `COMPLETED`, `CANCELLED` ou `REJECTED`, les affectations sont historiques et ne sont plus mutées depuis l’UI.

---

## 7. Multi-exécutants, contributions et soumission consolidée

Une Mission peut avoir plusieurs exécutants acceptés.

Chaque exécutant peut déposer une `MissionContribution`. Une contribution personnelle n’est jamais la soumission globale.

### 7.1 Qui peut soumettre le résultat global ?

Règle canonique :

1. s’il existe un `COORDINATOR` accepté, seul un `COORDINATOR` accepté peut consolider et soumettre le résultat global ;
2. sinon, tout `EXECUTOR` ou `CO_EXECUTOR` accepté peut soumettre ;
3. `LEARNER` et `OBSERVER` ne peuvent jamais soumettre le résultat global au seul titre de ces rôles.

Il n’y a pas de vote automatique de tous les exécutants. La séparation `MissionContribution` / `MissionSubmission` conserve les apports individuels, puis l’autorité contextuelle reste la décision finale.

Quand une Mission passe `SUBMITTED`, les autres exécutants acceptés sont informés, mais leur absence de réaction ne vaut ni validation ni refus.

### 7.2 Versionnement et concurrence

Le numéro de version d’une `MissionSubmission` est alloué sous verrou de ligne de la Mission.

Une seule transition valide vers `SUBMITTED` peut gagner en cas de concurrence. Une tentative concurrente obsolète reçoit `409` et ne crée pas de version fantôme.

`SUBMITTED -> IN_PROGRESS` conserve la version précédente. La prochaine soumission crée `version + 1`.

`SUBMITTED -> COMPLETED` reste exclusivement une décision de l’autorité du contexte.

---

## 8. Récurrence — règle finale

La récurrence est dans le périmètre complet et ne doit pas être reportée.

### 8.1 Autorité

Seule une autorité capable d’officialiser la Mission dans son contexte peut :

```text
create recurrence
activate recurrence
pause recurrence
resume recurrence
stop recurrence
```

Créer/activer une récurrence constitue une **autorisation humaine permanente et explicite** pour les occurrences futures, jusqu’à pause/arrêt/révocation.

### 8.2 Création d’une occurrence

Chaque occurrence est une nouvelle `Mission` distincte et auditée.

Elle peut être créée directement `OPEN` car l’autorité a préalablement donné l’autorisation permanente via la récurrence.

L’événement indique clairement :

```text
actor = SYSTEM:MISSION_RECURRENCE
authorized_by_core_reference = recurrence.created_by_core_reference
```

Le système ne doit jamais présenter le compte de l’autorité comme s’il avait personnellement cliqué au moment de la génération.

Avant génération, revalider :

```text
context exists
context is operational
visibility still allowed
recurrence is active
```

Si le contexte n’est plus opérationnel, aucune occurrence n’est créée. L’erreur est tracée sur la récurrence et l’autorité est notifiée de manière non compulsive.

### 8.3 Ce qui est cloné

Une occurrence peut copier depuis le modèle de récurrence :

- titre ;
- description ;
- résultat attendu ;
- critères d’acceptation ;
- visibilité ;
- modalités/localisation ;
- minimum/maximum d’exécutants ;
- capacités requises ;
- ressources requises ;
- checklist modèle ;
- décalage d’échéance défini par la récurrence.

Ne jamais copier :

- assignments acceptés ;
- invitations ;
- contributions ;
- submissions ;
- blockers ;
- commentaires ;
- conversations ;
- preuves ;
- finance ;
- état `COMPLETED` de l’ancienne occurrence.

Chaque occurrence repart avec consentement humain pour sa participation.

### 8.4 Idempotence

Contrainte DB obligatoire :

```text
UNIQUE(recurrence_id, occurrence_key)
```

La génération concurrente ou rejouée ne crée jamais deux occurrences.

Les modifications d’une récurrence ne réécrivent jamais les occurrences déjà créées.

### 8.5 Champs additionnels de récurrence

Ajouter au besoin :

```text
status ACTIVE|PAUSED|STOPPED
last_generated_at nullable
last_error_at nullable
last_error_code nullable
updated_at
```

`is_active` seul n’est pas suffisant pour distinguer pause et arrêt historique.

---

## 9. Besoin créé depuis un blocage — relation finale

Ne pas ajouter un champ générique `source_mission_reference` directement dans `dg_needs` pour ce cas transversal.

Créer :

```text
dg_mission_need_links
```

Champs :

```text
id uuid PK
mission_id uuid FK
blocker_id uuid nullable FK
need_id uuid FK
created_by_core_reference varchar(191)
created_at
```

Un Need est toujours créé via `NeedService`, après confirmation humaine, puis lié à la Mission.

Résoudre le Need ne débloque pas automatiquement la Mission. Le système peut signaler :

> Le besoin lié semble résolu. Confirmer que ce blocage est levé ?

Le déblocage reste une action métier explicite.

---

## 10. Matching — consentement et masquage

Une capacité individuelle est utilisable par `MissionMatchingEngine` uniquement si :

```text
CapabilityStatement.visibility = DISCOVERABLE
AND matching_consent = true
AND archived_at IS NULL
```

Le matching produit des raisons compréhensibles, pas un score humain public et jamais une affectation.

Le masquage d’une suggestion est persisté de manière mission-scopée :

```text
dg_mission_matching_hides
```

Champs minimum :

```text
id uuid PK
mission_id uuid FK
viewer_core_reference varchar(191)
subject_core_reference varchar(191)
created_at
UNIQUE(mission_id, viewer_core_reference, subject_core_reference)
```

Masquer une suggestion ne modifie ni le profil ni la disponibilité de la personne masquée.

---

## 11. Concurrence et idempotence — obligatoire

Utiliser `lockForUpdate()` ou mécanisme équivalent sur les transitions critiques :

- workflow Mission ;
- décision d’une offre/invitation ;
- allocation d’une version de submission ;
- retrait du dernier exécutant ;
- résolution du dernier blocker ;
- création d’une occurrence récurrente.

Une action obsolète ou rejouée ne doit jamais produire un état contradictoire. Réponse attendue : état déjà atteint/idempotent lorsque sûr, sinon `409`.

Les notifications et événements secondaires doivent découler de la transaction réussie, pas d’une tentative échouée.

---

## 12. Ajustements au schéma v0.4

En plus des tables de v0.4, v0.5 impose :

```text
dg_mission_need_links
dg_mission_matching_hides
```

`dg_mission_recurrences` utilise `status` (`ACTIVE`, `PAUSED`, `STOPPED`) et les champs de diagnostic définis en section 8.

Aucun champ de rôle institutionnel n’est ajouté à `dg_missions` ou `dg_mission_assignments`.

---

## 13. Tests additionnels obligatoires v0.5

Ajouter aux tests M-xxx de la v0.4 :

```text
M-084  Premier registre production = PROJECT, ZUMRA, NEED uniquement.
M-085  Membre Programme actif + Projet visible peut proposer; non-membre ne peut pas.
M-086  Membre actif de la ZUMRA peut proposer; outsider groupe ne peut pas.
M-087  Mission ZUMRA ne peut dépasser visibilité CONTEXT.
M-088  Visibilité trop large => 422, aucun downgrade silencieux.
M-089  Mission publique n’expose pas automatiquement l’identité des exécutants.

M-090  Sous-Mission inter-contexte refusée.
M-091  Parent avec enfant actif ne peut être COMPLETED.
M-092  Parent avec enfant actif ne peut être CANCELLED.

M-093  Dernier exécutant quitte OPEN => reste OPEN.
M-094  Dernier exécutant quitte IN_PROGRESS => BLOCKED + PERSON_UNAVAILABLE.
M-095  Retrait pendant SUBMITTED ne détruit ni n’invalide la submission.

M-096  COORDINATOR accepté est seul habilité à consolider si présent.
M-097  Sans COORDINATOR, EXECUTOR/CO_EXECUTOR accepté peut soumettre.
M-098  LEARNER/OBSERVER seul ne peut soumettre le résultat global.
M-099  Deux soumissions concurrentes ne créent qu’une version valide.

M-100  Seule autorité contextuelle crée/active une récurrence.
M-101  Occurrence récurrente OPEN porte audit SYSTEM + authorized_by.
M-102  Aucune assignment acceptée n’est copiée dans une occurrence.
M-103  Contexte suspendu/archivé => occurrence non créée.
M-104  UNIQUE recurrence_id + occurrence_key garantit l’idempotence.
M-105  Modifier la récurrence ne réécrit pas les occurrences passées.

M-106  Blocker -> Need crée dg_mission_need_links après confirmation uniquement.
M-107  Résoudre Need ne débloque pas Mission automatiquement.
M-108  Matching exclut capability non discoverable, sans consentement ou archivée.
M-109  Hide matching est scoppé viewer + Mission et ne modifie pas le profil cible.
```

---

## 14. Ordre d’implémentation final pour Claude

L’ordre v0.4 reste valable, avec ces précisions :

1. schéma complet, y compris `dg_mission_need_links`, `dg_mission_matching_hides` et contraintes d’idempotence ;
2. registre fail-closed avec **PROJECT/ZUMRA/NEED seulement** ;
3. visibilité et autorité avant toute UI ;
4. workflow Mission sous verrouillage ;
5. assignments multi-exécutants + règle du dernier exécutant ;
6. sous-Missions/dépendances/blockers ;
7. contributions + submission consolidée + concurrence ;
8. capacités/ressources + matching consented + hide ;
9. récurrence complète + idempotence ;
10. UI contextuelle, `/missions`, Mon espace ;
11. extensions commentaire/partage/messagerie/Fil/notifications ;
12. intégrations GamaDrive/Transmission/Carnet de preuves uniquement dans leurs frontières autorisées ;
13. suite complète, build et documentation des écarts.

Claude ne doit pas réduire la fiche à un « premier MVP » ni supprimer les fonctions complexes pour livrer plus vite. Il peut découper son travail en commits/étapes testables, mais le lot MISSIONS n’est déclaré terminé que lorsque la Definition of Done v0.4 + les verrous v0.5 sont satisfaits.

---

## 15. Statut final

**MISSIONS v0.5 = READY FOR IMPLEMENTATION.**

Claude peut coder à partir de ce contrat, avec instruction d’arrêt obligatoire dès qu’une contradiction réelle avec doctrine, autorité ou code existant est découverte.

Aucune contradiction ne doit être arbitrée silencieusement.