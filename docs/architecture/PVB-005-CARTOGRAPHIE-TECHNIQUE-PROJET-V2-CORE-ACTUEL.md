# PVB-005 — Cartographie technique Projet V2 ↔ Core actuel

**Statut :** AUDIT TECHNIQUE / CONTRAT DE RACCORDEMENT — V1  
**Branche auditée :** `feat/project-brain-v2`  
**Références :** Architecture Produit V2, PVB-001 à PVB-004  
**Objet :** confronter la vision Projet/Cerveau V2 au code réellement présent afin d'identifier ce qui peut être réutilisé, adapté ou doit être créé.

---

## 1. Conclusion exécutive

Le constat principal est très favorable : **le Core Projet actuel n'est pas à refaire**.

Il contient déjà une grande partie des briques métier dont le Cerveau a besoin : Projet, autorité, équipe, Besoins, Missions riches, Preuves, recommandations/matching, événements, Fil, accompagnement et messagerie contextuelle.

La V2 doit donc être conçue comme une **nouvelle couche d'expérience et d'orchestration au-dessus des services existants**, et non comme un second moteur Projet.

Le plus gros manque n'est pas la logique métier : ce sont les couches spécifiques au Cerveau :

- conversation Projet de type assistant, distincte de la messagerie humaine existante ;
- mémoire Projet sélective et provenance ;
- registre de Tools/adapters ;
- brouillons et confirmations ;
- assemblage de contexte ;
- surface UX trois zones ;
- création progressive remplaçant le formulaire obligatoire actuel.

---

## 2. Architecture réelle observée

Le dépôt possède une séparation applicative significative sous `app/Application` avec notamment :

- `Projects`
- `Needs`
- `Missions`
- `Proof`
- `Recommendation`
- `Messaging`
- `Activity`
- `Zumra`

Les modèles Eloquent restent sous `app/Models`.

Cette structure est compatible avec PVB-004 : les futurs Tools peuvent devenir de minces adapters vers ces services applicatifs plutôt que de réimplémenter leurs règles.

---

## 3. Projet — moteur actuel

### Existant

`app/Application/Projects/ProjectService.php`

Le service sait déjà :

- créer un Projet ;
- gérer porteur personnel ou ZUMRA ;
- relier un besoin source ;
- contrôler des limites de projets actifs ;
- appliquer le régime de propriété ;
- gérer visibilité ;
- créer les jalons initiaux ;
- gérer les transitions PROPOSED / ADOPTED / IN_PROGRESS / COMPLETED / ARCHIVED ;
- produire des `ProjectEvent`.

### Autorité

`app/Application/Projects/ProjectAuthority.php`

Il centralise déjà :

- `canView` ;
- `canDecide` ;
- appartenance active à une ZUMRA.

C'est une excellente frontière pour le Cerveau : **ne pas créer une nouvelle notion d'autorité IA**.

### Écart PVB

`ProjectService::create()` exige actuellement beaucoup de données immédiatement : nom, résumé, problème, solution, bénéficiaires, domaine, mode, objectifs, capacités, ressources, risques, régime, visibilité, jalons, etc.

C'est incompatible avec l'invariant UX PVB-002 :

> ne pas exiger aujourd'hui une information qui peut être apprise demain sans risque métier.

### Décision

Ne pas supprimer `ProjectService`.

Créer une évolution contrôlée du contrat de création permettant un **Projet brouillon/minimal** ou un workflow de préparation distinct, puis enrichissement progressif.

---

## 4. Équipe Projet — déjà solide

### Existant

`app/Application/Projects/ProjectTeamService.php`

Le service couvre déjà :

- demande pour rejoindre ;
- invitation ;
- acceptation d'invitation ;
- approbation d'une demande ;
- départ volontaire ;
- retrait par autorité ;
- événements `TEAM_MEMBER_*`.

Il réutilise l'autorité Projet existante.

### Correspondance PVB-004

| Tool conceptuel | Core réel |
|---|---|
| `team.get` | `ProjectTeamMember` + lecture Projet |
| `team.prepare_invitation` | nouveau adapter/brouillon |
| `team.invite` | `ProjectTeamService::invite()` |
| accepter invitation | `ProjectTeamService::acceptInvitation()` |
| approuver demande | `ProjectTeamService::approveRequest()` |
| quitter | `ProjectTeamService::leave()` |
| retirer | `ProjectTeamService::remove()` |

### Conclusion

**Réutilisation forte.** Le Cerveau ne doit surtout pas recréer l'adhésion équipe.

---

## 5. Besoins — directement raccordables

### Existant

`app/Application/Needs/NeedService.php`

Il sait déjà créer un Besoin pour :

- une personne ;
- une ZUMRA ;
- un Projet.

Pour un Projet, il contrôle qu'un acteur est contributeur éligible et distingue :

- décideur Projet → besoin ouvert ;
- contributeur non décideur → besoin proposé.

Il possède également :

- transitions OPEN / IN_PROGRESS / RESOLVED / ARCHIVED ;
- contrôle de visibilité ;
- contrôle de décision ;
- `NeedEvent` ;
- note obligatoire lors de la résolution.

### Correspondance

| Tool | Core |
|---|---|
| `need.prepare_create` | à créer côté Brain, sans mutation |
| `need.create` | adapter → `NeedService::create()` |
| `need.list/get` | query/read model avec `canView()` |
| démarrer/résoudre/archiver | adapter → `NeedService::transition()` |
| `need.get_matches` | moteurs matching/recommandation existants à raccorder selon contexte |

### Conclusion

C'est probablement la **première tranche verticale idéale** pour prouver le Cerveau : langage naturel → brouillon Need → confirmation → `NeedService` → événement réel.

---

## 6. Missions — moteur beaucoup plus riche que la maquette

### Existant

`app/Application/Missions` contient déjà :

- `MissionService`
- `MissionWorkflow`
- `MissionAssignmentService`
- `MissionBlockerService`
- `MissionDependencyService`
- `MissionMatchingEngine`
- `MissionNeedLinkService`
- `MissionRecurrenceService`
- `MissionSubmissionService`
- `MissionVisibilityService`
- registre de contextes et support.

Les routes actuelles permettent déjà création contextuelle depuis Projet/ZUMRA/Besoin, sous-missions, machine d'états, blocages, expression d'un Besoin depuis un blocage, soumission/validation, affectations, invitations, checklist, dépendances, capacités/ressources, contributions, matching et récurrence.

### Conséquence importante

La vue « Missions » du Cerveau doit être une **simplification de ce moteur puissant**, pas un nouveau mini-module Mission.

Le Cerveau peut devenir particulièrement utile ici : il peut cacher la complexité tant qu'elle n'est pas nécessaire.

### Correspondance

`mission.prepare_create` → nouveau brouillon Brain  
`mission.create` → adapter vers `MissionService`  
`mission.complete` conceptuel → doit respecter la vraie `MissionWorkflow`, pas inventer une transition simplifiée  
`mission.get_matches` → `MissionMatchingEngine`  
blocage → `MissionBlockerService`  
Besoin depuis blocage → `MissionNeedLinkService`

### Écart PVB-004 à corriger

PVB-004 parle conceptuellement de `mission.complete`. Le Core réel possède une machine d'états plus riche. Le futur registre Tool devra exposer les **transitions réelles autorisées** et traduire humainement leur sens.

---

## 7. Preuves — moteur existant

### Existant

`app/Application/Proof` contient :

- `ProofService`
- `ProofContextService`
- `ProofVisibilityService`
- `ProofWorkflow`

### Décision

Le Cerveau doit uniquement ajouter :

- préparation du brouillon ;
- explication ;
- confirmation ;
- adapter.

La création/validation/visibilité restent dans le domaine Proof.

Ceci confirme PVB-003 : **une pièce jointe conversationnelle n'est pas une preuve** tant qu'elle n'est pas passée par ce domaine.

---

## 8. Recommandation et matching — déjà présents

### Existant

`app/Application/Recommendation/PersonRecommendationEngine.php`

ainsi que :

- `ProjectMatchingEngine`
- `MissionMatchingEngine`
- mécanismes de matching liés aux Besoins/Discovery selon le Core existant.

### Décision

Le Cerveau n'invente aucun matching.

Créer des adapters qui transforment les résultats existants en une structure explicable : résultat + raisons + visibilité + action suivante.

Le panneau « Opportunités » PVB-002 doit consommer ces moteurs.

---

## 9. Activité / Fil — existants

### Existant

`app/Application/Activity/ActivityFeedService.php`

et `ActivityFeedController`.

Le Core produit déjà des événements Projet/Need/Mission et possède un Fil V2 testé.

### Décision

Conserver la chaîne :

`Cerveau → service métier → événement → Activity/Fil`

Ne créer aucun `BrainPost` ou voie sociale parallèle.

La zone « Aujourd'hui » du Projet doit être un read model synthétique construit à partir de l'état et des événements réels.

---

## 10. Messagerie — surprise importante de l'audit

### Existant

Le Core possède déjà :

- `MessageConversation`
- `MessageEntry`
- `MessageParticipant`
- `MessagingService`
- `MessagingController`
- routes de conversation liées aux Personnes, Besoins, ZUMRA, Invitations, Projets, Missions, Transmissions et support DG Afrique.

`MessageConversation` possède déjà `CONTEXT_PROJECT` et un `context_reference`.

### Mais attention

Cette messagerie est actuellement un système de communication entre participants/contextes. Elle ne doit pas être confondue automatiquement avec la **Conversation Cerveau** de PVB-003.

Le besoin PVB est différent :

- plusieurs conversations intellectuelles par Projet ;
- assistant comme interlocuteur système ;
- mémoire sélective ;
- brouillons/actions ;
- provenance ;
- privé vs partagé ;
- résumés contextuels.

### Décision

**Ne pas détourner brutalement `dg_message_conversations` pour le Brain.**

Avant implémentation, définir une couche Brain dédiée ou une extension explicite si et seulement si les invariants de Messaging restent intacts.

La solution la plus sûre pour V1 est un domaine léger `ProjectBrain` avec ses propres conversations/messages/mémoires, pouvant référencer la messagerie humaine mais ne la remplaçant pas.

---

## 11. Frontend actuel

Le frontend observé est principalement Blade :

- `resources/views/projects/create.blade.php`
- `index.blade.php`
- `show.blade.php`
- `matching.blade.php`
- `accompaniment.blade.php`
- etc.

`resources/js` ne contient actuellement qu'un `app.js` global, donc le Projet V2 n'est pas aujourd'hui une SPA conversationnelle complexe.

### Décision

Ne pas imposer immédiatement une réécriture globale du portail.

Le shell PVB-002 peut être livré progressivement dans l'architecture Laravel/Blade actuelle avec JavaScript ciblé, endpoints JSON dédiés et composants serveur, puis évoluer si la complexité le justifie.

**Invariant : architecture UX V2 ≠ obligation de changer tout le framework frontend.**

---

## 12. Tests — base déjà importante

Le dépôt contient des tests Feature pour le Fil, invariants design, commentaires/contextes et de nombreuses capacités métier.

Pour Projet V2, il faudra ajouter une suite dédiée couvrant au minimum :

- isolation Projet/conversation ;
- permissions Brain = permissions Core ;
- brouillon sans mutation ;
- confirmation puis mutation unique ;
- idempotence ;
- Need créé via Brain identique à Need créé manuellement ;
- invitation ≠ adhésion ;
- Mission terminée/validée selon workflow réel ;
- pièce jointe ≠ Proof ;
- aucune conversation privée dans le Fil ;
- mode sans IA ;
- panne/timeout sans faux succès.

---

## 13. Matrice de raccordement V2

| Surface PVB-002 | Core actuel | État | Travail V2 |
|---|---|---|---|
| Liste Projets | `Project` / vues Projects | EXISTE | read model compact |
| Projet actif | `ProjectService`, `ProjectAuthority` | EXISTE | adapter/read model |
| Aujourd'hui | Events + états | PARTIEL | nouveau agrégateur |
| Équipe | `ProjectTeamService` | EXISTE | adapter + UX |
| Besoins | `NeedService` | EXISTE | adapter Brain |
| Missions | moteur Missions | EXISTE++ | adapter + simplification UX |
| Preuves | domaine Proof | EXISTE | adapter Brain |
| Opportunités | Recommendation + matching | EXISTE | agrégation explicable |
| Activité | Project/Need/Mission events + Activity | EXISTE | projection Projet |
| Conversations Cerveau | Messaging proche mais différent | MANQUE SPÉCIFIQUE | domaine Brain |
| Mémoire Projet | aucun modèle identifié | MANQUE | nouveau domaine léger |
| Brouillons | aucun modèle Brain identifié | MANQUE | nouveau contrat/stockage |
| Confirmations | workflows métier existent, pas orchestration Brain | PARTIEL | couche Brain |
| Tool Registry | absent | MANQUE | nouveau registre |
| Context Builder | absent | MANQUE | nouveau service |
| IA Provider | non figé | VOLONTAIREMENT ABSENT | adapter ultérieur |
| Création progressive | `ProjectService::create` exige beaucoup | ÉCART | refactor contrôlé |
| Mode sans IA | UI métier actuelle | EXISTE | préserver |

---

## 14. Domaine technique proposé : `ProjectBrain`

Sans modifier les domaines métier existants :

```text
app/Application/ProjectBrain/
    ProjectBrainContextService
    ProjectBrainToolRegistry
    ProjectBrainOrchestrator
    ProjectBrainDraftService
    ProjectBrainMemoryService
    Tools/
        Project/
        Needs/
        Missions/
        Team/
        Proof/
        Recommendation/
```

Modèles possibles, à confirmer par conception SQL :

```text
ProjectBrainConversation
ProjectBrainMessage
ProjectBrainMemory
ProjectBrainDraft
ProjectBrainActionRun
```

Le terme « possible » est important : PVB-005 ne fige pas encore les tables.

---

## 15. Frontière d'adapter

Exemple `CreateProjectNeedTool` :

```text
Brain input
   ↓
validation schema Brain
   ↓
resolve Project + actor
   ↓
ProjectAuthority / Need rules
   ↓
map vers tableau attendu par NeedService
   ↓
NeedService::create(actor, data, configuration)
   ↓
Need réel + NeedEvent
   ↓
BrainActionRun success
   ↓
UX
```

Le Tool ne contient aucune copie des règles de statut/visibilité/décision de `NeedService`.

---

## 16. Premier vertical slice recommandé

### PVB-I01 — Shell Projet V2 + Besoin via Cerveau

Livrer une tranche réellement utilisable :

1. nouveau shell desktop trois zones ;
2. colonne gauche = vrais Projets ;
3. panneau droit = état Projet + vrais Besoins ;
4. conversation Brain minimale persistée ;
5. utilisateur écrit : « Il nous faut deux développeurs Laravel à Bouaké » ;
6. Cerveau/parseur produit un brouillon Need ;
7. carte **Créer ce besoin** ;
8. confirmation ;
9. adapter appelle `NeedService::create()` ;
10. le vrai Need apparaît immédiatement à droite ;
11. événement normal conservé ;
12. aucun besoin n'est créé avant confirmation.

Cette tranche prouve toute l'architecture PVB sans devoir implémenter immédiatement Missions, Proof, mémoire avancée ou Finance.

---

## 17. Création Projet : décision de migration

La page actuelle `projects/create.blade.php` reste temporairement comme voie de secours.

Créer ensuite une entrée V2 :

> Qu'est-ce que vous voulez réaliser ?

Deux stratégies techniques possibles :

### A — Projet DRAFT réel

Ajouter un statut/contrat permettant une création minimale puis enrichissement.

### B — Brain Draft avant Projet réel

Conserver l'intention dans `ProjectBrainDraft`, puis créer le Projet canonique lorsque le minimum métier est atteint.

### Recommandation

Pour la première tranche, préférer **B** si l'ajout d'un statut DRAFT risque d'impacter trop de capacités existantes. Après audit des dépendances au statut Projet, décider si DRAFT doit devenir canonique.

Cela protège le Core tout en supprimant le grand formulaire côté expérience.

---

## 18. Point économique/ZUMRA observé

`ProjectService::create()` exige actuellement une adhésion ZUMRA active avant création d'un Projet.

Cela entre potentiellement en tension avec la doctrine V2 discutée où un utilisateur DG Afrique peut utiliser certaines fonctions du portail sans adhérer au programme ZUMRA.

PVB-005 ne modifie pas cette règle automatiquement.

Elle doit être traitée comme **décision métier explicite** avant refactor de `ProjectService::create()` :

- création de brouillon Brain accessible à tout membre DG Afrique ?
- création du Projet canonique réservée au programme ?
- ou Projet DG Afrique général puis avantages ZUMRA séparés ?

Ne pas changer cette frontière par simple nécessité UX.

---

## 19. Risques principaux

### Risque 1 — Dupliquer le Core

Le plus grave serait de créer `BrainNeedService`, `BrainMissionService`, etc. avec leurs propres règles.

**Réponse : adapters minces uniquement.**

### Risque 2 — Confondre Messaging et Brain

La messagerie existante est réelle et utile.

**Réponse : séparation conceptuelle, intégration ultérieure explicite.**

### Risque 3 — Simplifier les Missions au point de casser leur workflow

**Réponse : l'UX simplifie le langage, jamais la machine d'états.**

### Risque 4 — Refondre tout le frontend avant preuve

**Réponse : tranche verticale progressive.**

### Risque 5 — IA avant orchestration

**Réponse : construire Tool Registry, drafts et confirmations avant de dépendre d'un fournisseur IA.**

---

## 20. Verdict PVB-005

La vision PVB-001 → PVB-004 est **techniquement compatible avec le Core actuel**.

Mieux : plusieurs noms que nous utilisions comme exemples architecturaux existent réellement (`ProjectService`, `NeedService`, `MissionService`, `ProofService`, etc.).

Le chantier n'est donc pas :

> refaire Projet.

Il est :

> **donner à Projet une nouvelle interface cognitive qui orchestre les moteurs déjà construits.**

C'est une différence fondamentale en coût, risque et faisabilité.

---

## 21. Suite recommandée

Avant l'IA réelle :

**PVB-006 — Architecture technique du domaine `ProjectBrain` + premier vertical slice.**

PVB-006 doit figer :

- frontières de namespace ;
- modèles/tables minimaux ;
- Tool Registry V1 ;
- Context Service V1 ;
- Draft/Confirmation contract ;
- endpoints ;
- stratégie Blade/JS du shell ;
- tests ;
- plan de livraison PVB-I01.

Après PVB-006, l'implémentation peut commencer sans ambiguïté architecturale majeure.
