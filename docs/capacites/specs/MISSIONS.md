# FICHE D’IMPLÉMENTATION TRANSVERSALE — MISSIONS v0.4

**Statut :** READY FOR REVIEW  
**Version :** 0.4  
**Racine référentielle :** CAP-069 — TÂCHE  
**Expression produit :** MISSIONS  
**Nouveau CAP :** non  
**Nature :** moteur transversal natif de DG Afrique  
**Base de conception :** référentiel final des 84 capacités + doctrine canonique ZUMRA + design invariants DG Afrique.

## 1. Intention

Une Mission est l’unité opérationnelle par laquelle un objet réel de DG Afrique devient une action explicite, attribuable, exécutable, traçable et vérifiable.

Une Mission répond au minimum à cinq questions :

1. Que faut-il faire ?
2. Dans quel contexte réel ?
3. Qui accepte de le faire ?
4. Qu’est-ce qui bloque ou manque ?
5. Quel résultat a été produit, puis validé par l’autorité compétente ?

Doctrine courte :

> **Une Mission transforme une intention en engagement explicite. Elle ne transforme jamais un engagement opérationnel en autorité institutionnelle, une exécution en vérité automatique, ni une activité en valeur humaine.**

Distinctions invariantes :

> **Proposer n’est pas décider. Accepter n’est pas commencer. Soumettre n’est pas valider. Participer n’est pas gouverner.**

## 2. Position dans le référentiel

MISSIONS généralise et approfondit CAP-069 sans modifier le référentiel V0.1 ni créer CAP-085.

Rattachements principaux : CAP-001, 003, 004, 005, 006, 009, 010, 011, 012, 013, 014, 015, 016, 019, 020, 021, 022, 023, 025, 026, 027, 028, 029, 030, 031, 032, 034, 035, 036, 037, 038, 040, 041, 042, 043, 045, 046, 052, 053, 054, 055, 057, 058, 059, 060, 063, 064, 065, 066, 068, 069, 070, 071, 072, 073, 074, 076, 080, 081.

MISSIONS ne remplace pas Besoin, Projet, Transmission, Preuve, Messagerie, Commentaire, Partage, Finance, Événement ou Document. Il s’y intègre.

## 3. Contextes missionnables

Une Mission appartient toujours à un contexte métier réel.

Champs canoniques :

```text
context_type
context_reference
```

Le moteur doit utiliser un registre explicite et fail-closed :

```text
MissionContextRegistry
  -> ProjectMissionContext
  -> ZumraMissionContext
  -> NeedMissionContext
  -> autres adaptateurs réels ultérieurs
```

Aucun `context_type` arbitraire n’est accepté.

Contrat minimum d’un adaptateur :

```php
interface MissionContextAdapter
{
    public function type(): string;
    public function resolve(string $reference): object;
    public function canView(object $context, string $actor): bool;
    public function canPropose(object $context, string $actor): bool;
    public function canOfficialize(object $context, string $actor): bool;
    public function canManageAssignments(object $context, string $actor): bool;
    public function canValidate(object $context, string $actor): bool;
    public function canCancel(object $context, string $actor): bool;
    public function canReopen(object $context, string $actor): bool;
    public function isOperational(object $context): bool;
    public function maxVisibility(object $context, string $actor): string;
    public function label(object $context): string;
}
```

Le lot d’implémentation doit fournir au minimum les adaptateurs correspondant aux objets métier réellement présents dans le dépôt au moment du codage. Les contextes futurs restent extensibles sans `if/elseif` dispersés dans Missions.

## 4. Autorité contextuelle

Règle générale :

- tout membre actif autorisé à contribuer au contexte peut proposer une Mission ;
- seule l’autorité compétente de ce contexte peut l’officialiser ;
- l’autorité vient toujours du parent, jamais de Missions.

### Projet

`ProjectMissionContext` réutilise `ProjectService::canView()` et `ProjectService::canDecide()` tant qu’aucune capacité d’équipe Projet plus précise n’est implémentée.

### ZUMRA

Un membre actif de la ZUMRA peut proposer. Un responsable accepté peut officialiser et décider selon les règles de gouvernance existantes.

### Besoin

`NeedMissionContext` réutilise `NeedService::canView()` et `NeedService::canDecide()`.

### Contexte sans autorité définie

Un contexte n’entre pas dans le registre de production tant que l’on ne sait pas déterminer son autorité. Le code ne choisit jamais silencieusement un propriétaire ou administrateur de substitution.

## 5. Machine d’états Mission

États :

```text
DRAFT
PROPOSED
CHANGES_REQUESTED
REJECTED
OPEN
IN_PROGRESS
BLOCKED
SUBMITTED
COMPLETED
CANCELLED
```

Transitions autorisées :

```text
DRAFT -> PROPOSED
PROPOSED -> CHANGES_REQUESTED
CHANGES_REQUESTED -> PROPOSED
PROPOSED -> REJECTED
PROPOSED -> OPEN
OPEN -> IN_PROGRESS
IN_PROGRESS -> BLOCKED
BLOCKED -> IN_PROGRESS
IN_PROGRESS -> SUBMITTED
SUBMITTED -> IN_PROGRESS        # correction demandée
SUBMITTED -> COMPLETED
OPEN|IN_PROGRESS|BLOCKED|SUBMITTED -> CANCELLED
COMPLETED -> IN_PROGRESS        # réouverture motivée, événement MISSION_REOPENED
```

Contraintes :

- `expected_result` obligatoire avant `OPEN` ;
- `REJECTED`, `CANCELLED`, demande de correction et réouverture exigent une raison ;
- `SUBMITTED` nécessite une soumission de résultat ;
- `COMPLETED` nécessite une soumission validée ;
- aucune transition sensible ne se fait par simple `update()` depuis un contrôleur.

Service central proposé :

```text
MissionWorkflow
```

Méthodes attendues : `propose`, `requestChanges`, `resubmitProposal`, `reject`, `officialize`, `start`, `block`, `resolveBlocker`, `submitResult`, `requestCorrection`, `complete`, `cancel`, `reopen`.

Chaque méthode vérifie état + autorité, exécute sous transaction, écrit l’événement append-only et déclenche uniquement les effets secondaires autorisés.

## 6. Participation et affectations

Objet : `MissionAssignment`.

Statuts :

```text
OFFERED
INVITED
ACCEPTED
DECLINED
WITHDRAWN
RELEASED
REMOVED
```

Règles :

- `Je me propose` crée `OFFERED` ;
- une autorité accepte ou décline ;
- une invitation crée `INVITED` ;
- la personne invitée accepte ou décline elle-même ;
- aucun silence ne vaut acceptation ;
- aucune affectation automatique ;
- un membre peut retirer une offre avant décision ;
- un exécutant peut être libéré ou retiré avec raison et audit ;
- plusieurs exécutants sont autorisés.

Rôles opérationnels possibles :

```text
EXECUTOR
CO_EXECUTOR
COORDINATOR
LEARNER
OBSERVER
```

`COORDINATOR` est limité à la Mission et ne crée aucun rôle Projet/ZUMRA/organisationnel.

Paramètres Mission :

```text
min_executors default 1
max_executors nullable
```

`ACCEPTED` n’implique pas `IN_PROGRESS`. Le démarrage est explicite.

## 7. Modèle de données proposé

Toutes les tables utilisent des UUID internes, `public_reference` pour les objets exposés, et les conventions `dg_*` déjà utilisées par le dépôt.

### `dg_missions`

Champs :

```text
id uuid PK
public_reference uuid UNIQUE
context_type varchar(40)
context_reference varchar(191)
parent_mission_id uuid nullable FK dg_missions
recurrence_id uuid nullable
occurrence_key varchar(100) nullable
created_by_core_reference varchar(191)
title varchar(180)
description text
expected_result text
acceptance_criteria json nullable
participation_mode varchar(20) nullable
location varchar(200) nullable
visibility varchar(20)
status varchar(30)
min_executors unsigned smallint default 1
max_executors unsigned smallint nullable
starts_at timestamp nullable
due_at timestamp nullable
proposed_at timestamp nullable
officialized_at timestamp nullable
started_at timestamp nullable
submitted_at timestamp nullable
completed_at timestamp nullable
cancelled_at timestamp nullable
created_at
updated_at
```

Indexes : `(context_type, context_reference)`, `status`, `due_at`, `created_by_core_reference`, `parent_mission_id`.

### `dg_mission_assignments`

```text
id uuid PK
mission_id uuid FK
core_identity_reference varchar(191)
role varchar(30)
status varchar(30)
initiated_by_core_reference varchar(191)
accepted_by_core_reference varchar(191) nullable
reason text nullable
offered_at timestamp nullable
invited_at timestamp nullable
accepted_at timestamp nullable
declined_at timestamp nullable
withdrawn_at timestamp nullable
released_at timestamp nullable
removed_at timestamp nullable
created_at
updated_at
```

Une seule relation courante par `(mission_id, core_identity_reference)` ; les réinvitations passent par workflow + événements, sans duplication incontrôlée.

### `dg_mission_events`

Append-only.

```text
id uuid PK
mission_id uuid FK
event varchar(80)
actor_core_reference varchar(191)
subject_type varchar(40) default MISSION
subject_reference varchar(191) nullable
from_state varchar(40) nullable
to_state varchar(40) nullable
context json
occurred_at timestamp
```

Pas de modification/suppression depuis l’UI.

### `dg_mission_checklist_items`

```text
id uuid PK
mission_id uuid FK
label varchar(300)
position unsigned smallint
is_required boolean
completed_by_core_reference varchar(191) nullable
completed_at timestamp nullable
created_at
updated_at
```

100 % checklist ne clôture jamais automatiquement la Mission.

### `dg_mission_capability_requirements`

```text
id uuid PK
mission_id uuid FK
catalog_item_id uuid nullable
label varchar(200)
normalized_label varchar(200)
requirement_level varchar(30) default REQUIRED
quantity unsigned smallint nullable
context text nullable
created_at
updated_at
```

Le matching réutilise les `CapabilityStatement` consentis et découvrables ; il ne crée aucun score humain public.

### `dg_mission_resource_requirements`

```text
id uuid PK
mission_id uuid FK
type varchar(40)
label varchar(200)
quantity decimal nullable
unit varchar(40) nullable
is_required boolean
context text nullable
external_reference varchar(191) nullable
created_at
updated_at
```

Aucun financement n’est créé par cette table.

### `dg_mission_blockers`

```text
id uuid PK
mission_id uuid FK
type varchar(50)
description text
opened_by_core_reference varchar(191)
opened_at timestamp
resolved_by_core_reference varchar(191) nullable
resolved_at timestamp nullable
resolution_note text nullable
created_at
updated_at
```

Types initiaux : `MISSING_CAPABILITY`, `MISSING_RESOURCE`, `MISSING_DECISION`, `MISSING_DOCUMENT`, `MISSING_AUTHORIZATION`, `MISSING_FINANCING`, `PERSON_UNAVAILABLE`, `EXTERNAL_DEPENDENCY`, `TECHNICAL_PROBLEM`, `SAFETY_ISSUE`, `OTHER`.

Une Mission reste `BLOCKED` tant qu’au moins un blocker actif subsiste.

### `dg_mission_dependencies`

```text
id uuid PK
mission_id uuid FK
depends_on_mission_id uuid FK
type varchar(40)
created_by_core_reference varchar(191)
created_at
updated_at
```

Types : `FINISH_BEFORE_START`, `RESULT_REQUIRED`, `RESOURCE_DEPENDENCY`, `DECISION_DEPENDENCY`.

Contraintes : mission différente de dépendance, unicité logique, détection de cycle avant écriture.

### `dg_mission_contributions`

```text
id uuid PK
mission_id uuid FK
assignment_id uuid FK
summary text
evidence_context json nullable
submitted_at timestamp
created_at
```

Une contribution personnelle ne soumet pas automatiquement le résultat global.

### `dg_mission_submissions`

```text
id uuid PK
mission_id uuid FK
version unsigned integer
result_summary text
submitted_by_core_reference varchar(191)
submitted_at timestamp
decision varchar(30) nullable
decided_by_core_reference varchar(191) nullable
decision_note text nullable
decided_at timestamp nullable
created_at
updated_at
```

Unique `(mission_id, version)`.

Décisions : `ACCEPTED`, `CHANGES_REQUESTED`.

### `dg_mission_recurrences`

```text
id uuid PK
source_mission_id uuid FK
rrule text
timezone varchar(80)
is_active boolean
next_occurrence_at timestamp nullable
created_by_core_reference varchar(191)
created_at
updated_at
```

Chaque occurrence réelle est une Mission distincte. Idempotence par `(recurrence_id, occurrence_key)`.

## 8. Sous-Missions

`parent_mission_id` permet la décomposition. Profondeur maximale configurable, valeur initiale recommandée : 3.

Règles :

- aucun cycle ;
- une sous-Mission n’augmente jamais la visibilité de son parent/contexte ;
- l’annulation d’un parent ne supprime pas les enfants ;
- le système exige une décision explicite pour les enfants encore actifs ;
- une sous-Mission conserve le même contexte racine sauf adaptateur explicitement autorisé.

## 9. Visibilité et confidentialité

Une Mission ne peut jamais être plus visible que son contexte.

Valeurs Mission :

```text
PRIVATE
CONTEXT
PROGRAM
PUBLIC
```

L’accès final est l’intersection :

```text
parent canView(actor)
AND
mission visibility allows(actor)
```

`PRIVATE` = autorités + créateur + assignments concernés selon règle métier.  
`CONTEXT` = membres/participants autorisés du contexte.  
`PROGRAM` = uniquement si le parent autorise au moins PROGRAM.  
`PUBLIC` = uniquement si le parent autorise PUBLIC.

Les contextes suspendus, archivés ou non opérationnels peuvent rendre les Missions read-only ou invisibles conformément à leur adaptateur.

## 10. Besoin issu d’un blocage

Une Mission bloquée peut proposer l’action UI : `Exprimer ce manque comme besoin`.

Le Need n’est créé qu’après confirmation humaine via `NeedService`. La relation source doit être explicite, par exemple `source_mission_reference` ou une relation contextuelle dédiée si le schéma Need évolue.

Aucun blocker ne crée automatiquement un Need.

## 11. Matching Missions ↔ personnes/capacités

Service proposé : `MissionMatchingEngine`.

Sources autorisées : capacités consenties/découvrables, disponibilité contextuelle, localisation consentie, mode de participation, contexte Mission.

Sortie : recommandations explicables, jamais classement humain.

Chaque suggestion fournit des raisons :

```text
- capacité correspondante
- disponibilité compatible
- même zone consentie
- expérience contextualisée pertinente
```

Interdits : auto-assignment, « meilleur candidat », score de valeur humaine, contribution financière comme facteur de compatibilité.

Le membre peut masquer une suggestion ; ce choix est scoppé à lui et à la Mission.

## 12. Transmission

MISSIONS expose un point d’intégration pour `LEARNER` et les futures relations de Transmission, mais n’implémente pas à lui seul le workflow Transmission.

Une personne `LEARNER` n’est pas automatiquement un exécutant principal. La fiche TRANSMISSION définira le contrat complet.

## 13. Preuves et documents

Une `MissionSubmission` peut référencer des preuves contextuelles. Le Carnet de preuves sera l’autorité métier de preuve ; GamaDrive peut fournir le stockage documentaire via références fédérées.

MISSIONS ne certifie jamais automatiquement la vérité d’une preuve et ne devient pas un stockage documentaire généraliste.

## 14. Commentaires, partage et messagerie

CAP-021 doit être étendu pour accepter `MISSION` comme contexte commentable, avec les mêmes invariants : append-only, visibilité revalidée, pas de popularité.

CAP-022 doit être étendu pour partager une Mission avec contexte sans duplication et sans octroyer d’accès.

CAP-020/CAP-058 peuvent ouvrir une conversation contextualisée Mission. La conversation n’est jamais la source de vérité des décisions Mission.

## 15. Fil d’action

Il n’existe qu’un seul Fil DG Afrique.

Événements Mission éligibles :

```text
MISSION_OFFICIALIZED
MISSION_NEEDS_EXECUTORS
MISSION_BLOCKED
MISSION_COMPLETED
```

Pas de Fil Missions autonome. Pas d’événements bruités pour chaque édition/checklist/vue.

`ActivityFeedService` doit revalider la visibilité du contexte au moment de projection.

## 16. Mon espace et Mes Missions

Route globale :

```text
GET /missions
```

Sections attendues : À décider, Mes propositions, Invitations, Je me suis proposé, Mes engagements, Bloquées, À soumettre, À valider, Terminées.

Mon espace peut utiliser Missions pour la prochaine action, avec raison explicite : invitation à décider, résultat à valider, Mission bloquée, échéance proche, correction demandée.

Pas de métriques de productivité humaine.

## 17. Routes proposées

Fichier dédié recommandé :

```text
routes/cap069.php
```

Routes principales :

```text
GET    /missions
GET    /missions/{mission}
POST   /missions/{mission}/proposer
POST   /missions/{mission}/demander-modifications
POST   /missions/{mission}/rejeter
POST   /missions/{mission}/officialiser
POST   /missions/{mission}/demarrer
POST   /missions/{mission}/bloquer
POST   /missions/{mission}/blocages/{blocker}/resoudre
POST   /missions/{mission}/soumettre
POST   /missions/{mission}/demander-correction
POST   /missions/{mission}/valider
POST   /missions/{mission}/annuler
POST   /missions/{mission}/reouvrir

POST   /missions/{mission}/me-proposer
POST   /missions/{mission}/propositions/{assignment}/accepter
POST   /missions/{mission}/propositions/{assignment}/refuser
POST   /missions/{mission}/propositions/{assignment}/retirer
POST   /missions/{mission}/inviter
POST   /missions/{mission}/invitations/{assignment}/accepter
POST   /missions/{mission}/invitations/{assignment}/refuser
POST   /missions/{mission}/assignments/{assignment}/liberer

POST   /missions/{mission}/checklist
PUT    /missions/{mission}/checklist/{item}
POST   /missions/{mission}/dependances
DELETE /missions/{mission}/dependances/{dependency}
POST   /missions/{mission}/contributions
```

Création contextuelle :

```text
GET  /projets/{project}/missions/creer
POST /projets/{project}/missions
GET  /zumra/groupes/{group}/missions/creer
POST /zumra/groupes/{group}/missions
GET  /besoins/{need}/missions/creer
POST /besoins/{need}/missions
```

Les futurs contextes ajoutent leurs routes d’entrée mais réutilisent le même moteur.

Middleware : `core.member` + throttles dédiés lecture/écriture sensibles. Aucun guard Laravel membre parallèle.

## 18. Contrôleurs et services

Contrôleurs fins :

```text
MissionController
MissionWorkflowController
MissionAssignmentController
MissionChecklistController
MissionDependencyController
MissionContributionController
```

Services métier :

```text
MissionService
MissionWorkflow
MissionAssignmentService
MissionContextRegistry
MissionVisibilityService
MissionBlockerService
MissionDependencyService
MissionSubmissionService
MissionRecurrenceService
MissionMatchingEngine
```

Les contrôleurs ne portent ni autorité métier complexe ni machine d’états.

## 19. Événements append-only

Minimum :

```text
MISSION_DRAFTED
MISSION_PROPOSED
MISSION_CHANGES_REQUESTED
MISSION_PROPOSAL_RESUBMITTED
MISSION_REJECTED
MISSION_OFFICIALIZED
ASSIGNMENT_OFFERED
ASSIGNMENT_INVITED
ASSIGNMENT_ACCEPTED
ASSIGNMENT_DECLINED
ASSIGNMENT_WITHDRAWN
ASSIGNMENT_RELEASED
ASSIGNMENT_REMOVED
MISSION_STARTED
MISSION_BLOCKED
MISSION_BLOCKER_RESOLVED
MISSION_UNBLOCKED
MISSION_CONTRIBUTION_SUBMITTED
MISSION_RESULT_SUBMITTED
MISSION_CORRECTION_REQUESTED
MISSION_COMPLETED
MISSION_CANCELLED
MISSION_REOPENED
MISSION_DEPENDENCY_ADDED
MISSION_DEPENDENCY_REMOVED
MISSION_RECURRENCE_CREATED
MISSION_OCCURRENCE_CREATED
```

## 20. Notifications

Notifier uniquement ce qui mérite l’attention : invitation, décision sur proposition, participation acceptée/refusée, blocage pertinent, soumission à valider, correction demandée, validation, annulation, dépendance devenue disponible, échéance significative.

Pas de notification pour chaque consultation, édition mineure ou item de checklist.

## 21. IA

Autorisée : reformuler une Mission vague, proposer expected_result/critères, suggérer décomposition, capacités, ressources, dépendances, résumer blocages, préparer soumission, expliquer recommandations.

Interdite : créer/officialiser sans confirmation, assigner, accepter/refuser une personne, valider, annuler, sanctionner, créer finance, certifier preuve, attribuer rôle de gouvernance, produire score humain.

Toute suggestion importante expose `Pourquoi cette suggestion ?`.

## 22. Suspension, archivage et sécurité

- ZUMRA suspendue : historique conservé ; opérations ordinaires refusées sauf règle de réhabilitation explicitement codée.
- Projet archivé : Missions en lecture seule.
- Besoin archivé : Missions en lecture seule sauf règle explicite.
- aucune suppression destructive depuis l’UI ;
- aucun rôle ZUMRA/Projet créé par Mission ;
- aucune finance créée ;
- aucun Need automatique ;
- aucune visibilité supérieure au parent ;
- toute mutation sensible sous transaction ;
- contrôle de concurrence recommandé par verrouillage de ligne lors des transitions critiques ;
- idempotence obligatoire pour récurrence et actions susceptibles d’être rejouées.

## 23. États UX obligatoires

Écrans : normal, vide, chargement, erreur, permission insuffisante, contexte suspendu, contexte archivé, proposition en attente, changements demandés, rejet motivé, invitation en attente, offre en attente, Mission ouverte sans exécutant, en cours, bloquée, soumise, correction demandée, terminée, annulée.

Design : utiliser le design system DG Afrique existant. Aucun clone de Trello/Jira, aucun nouveau réseau social, aucun nouveau langage visuel concurrent.

## 24. Tests d’acceptation obligatoires

### Autorité et contexte

- M-001 : contexte non enregistré -> création refusée.
- M-002 : outsider ne voit pas une Mission privée -> 404.
- M-003 : visibilité Mission ne dépasse jamais parent.
- M-004 : membre ZUMRA actif peut proposer.
- M-005 : non-responsable ZUMRA ne peut officialiser -> 403.
- M-006 : `ProjectService::canDecide()` pilote l’officialisation Projet.
- M-007 : `NeedService::canDecide()` pilote l’officialisation Besoin.

### Workflow

- M-010 : DRAFT -> PROPOSED auditée.
- M-011 : PROPOSED -> CHANGES_REQUESTED exige raison.
- M-012 : PROPOSED -> REJECTED exige raison.
- M-013 : OPEN impossible sans expected_result.
- M-014 : ACCEPTED n’entraîne pas IN_PROGRESS.
- M-015 : démarrage explicite OPEN -> IN_PROGRESS.
- M-016 : blocage crée blocker + événement.
- M-017 : plusieurs blockers ; résolution d’un seul maintient BLOCKED.
- M-018 : dernier blocker résolu permet retour IN_PROGRESS.
- M-019 : SUBMITTED impossible sans MissionSubmission.
- M-020 : SUBMITTED != COMPLETED.
- M-021 : correction SUBMITTED -> IN_PROGRESS conserve submission v1.
- M-022 : nouvelle soumission devient version 2.
- M-023 : validation finale -> COMPLETED.
- M-024 : réouverture motivée -> IN_PROGRESS + MISSION_REOPENED.

### Participation

- M-030 : `Je me propose` -> OFFERED, jamais ACCEPTED automatiquement.
- M-031 : seul décisionnaire accepte une offre.
- M-032 : invitation -> INVITED ; seule personne invitée accepte.
- M-033 : retrait OFFERED -> WITHDRAWN.
- M-034 : plusieurs ACCEPTED autorisés.
- M-035 : coordinateur Mission n’obtient aucun rôle ZUMRA.
- M-036 : retrait du dernier exécutant ne produit aucun rôle/finance et remet Mission dans un état déterministe.

### Sous-Missions / dépendances / récurrence

- M-040 : cycle parent/enfant impossible.
- M-041 : profondeur max appliquée.
- M-042 : cycle de dépendance impossible.
- M-043 : dépendance bloquante expliquée.
- M-044 : une occurrence récurrente n’est créée qu’une fois pour une occurrence_key.

### Besoins / capacités / matching

- M-050 : blocker -> Need uniquement après confirmation.
- M-051 : aucun Need automatique.
- M-052 : matching utilise uniquement capacités autorisées/consenties.
- M-053 : matching n’assigne personne.
- M-054 : raisons de recommandation visibles, aucun score humain public.

### Social utile

- M-060 : commentaire Mission respecte visibilité courante.
- M-061 : partage Mission ne duplique pas et ne donne pas d’accès.
- M-062 : conversation Mission ne modifie pas le statut métier.
- M-063 : Fil global seulement ; aucun Fil Missions.
- M-064 : seuls événements utiles sont projetés.

### Sécurité / doctrine

- M-070 : identité Core uniquement ; aucun guard membre local.
- M-071 : ZUMRA suspendue bloque mutations ordinaires.
- M-072 : Projet/Besoin archivé en lecture seule.
- M-073 : aucune Mission ne modifie `ZumraGroupRole`.
- M-074 : aucune Mission ne crée transaction/financement.
- M-075 : aucune suppression destructive d’historique.
- M-076 : suggestion IA seule ne crée aucune donnée officielle.

### Design

- M-080 : shell/navigation DG Afrique communs.
- M-081 : états vides honnêtes, aucune fixture métier présentée comme réelle.
- M-082 : aucun like/follower/score/classement.
- M-083 : mobile `Agir` ne crée pas une Mission sans contexte explicite.

## 25. Ordre d’implémentation recommandé à Claude

1. Schéma + modèles + événements + registre de contextes.
2. Adaptateurs Projet/ZUMRA/Besoin sur services existants.
3. MissionWorkflow et tests d’autorité/transitions.
4. Assignments multi-exécutants + tests.
5. Checklist, sous-Missions, dépendances, blockers.
6. Contributions + submissions versionnées + validation.
7. Capacités requises + matching explicable.
8. Récurrence + idempotence.
9. UI contextuelle + `/missions` + Mon espace.
10. Extensions CAP-019/020/021/022/054.
11. Points d’intégration GamaDrive, Transmission, Carnet de preuves.
12. Tests complets + build + documentation des écarts réels.

Chaque étape doit rester mergable/testable. Claude ne doit pas inventer un objet métier absent ; il branche uniquement les contextes réels et documente les adaptateurs futurs.

## 26. Definition of Done

MISSIONS est CLOSED seulement lorsque :

- context registry fail-closed ;
- contextes réels supportés et testés ;
- propositions + décisions contextuelles ;
- multi-exécutants et consentement explicite ;
- démarrage explicite ;
- checklist, sous-Missions, dépendances, blockers ;
- contributions + submissions versionnées ;
- `SUBMITTED != COMPLETED` garanti ;
- capacités/ressources + matching explicable ;
- récurrence idempotente ;
- commentaire/partage/messagerie contextualisés ;
- Fil unique + Mon espace + Mes Missions ;
- notifications utiles ;
- visibilité héritée ;
- audit append-only ;
- suspension/archivage respectés ;
- aucune autorité, finance, preuve ou Need créé silencieusement ;
- aucune mécanique de popularité ;
- suite de tests complète verte ;
- build frontend vert ;
- preuves d’implémentation documentées avec routes/services/vues/tests/SHA de clôture.

## 27. Instruction d’arrêt obligatoire pour Claude

Si la doctrine, cette fiche, le code existant ou une autorité contextuelle se contredisent, Claude ne tranche pas silencieusement. Il documente le conflit, conserve les données et permissions existantes, puis s’arrête pour revue.

**Cette fiche est un contrat d’implémentation, pas une source d’inspiration.**
