# FICHE D'IMPLÉMENTATION TRANSVERSALE — TRANSMISSION

**Statut :** CONCEPTION
**Version :** 0.1
**Racine référentielle :** CAP-006 — TRANSMISSION
**Expression produit :** TRANSMISSION
**Nouveau CAP :** non
**Nature :** module transversal natif de DG Afrique, construit sur la déclaration existante CAP-005/CAP-006
**Base de conception :** référentiel des 84 capacités, doctrine canonique ZUMRA (`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`), invariants de design (`docs/design/DESIGN-INVARIANTS.md`), fiches courtes existantes `CAP-005-apprentissage.md` / `CAP-006-transmission.md`, et le précédent architectural MISSIONS (`docs/capacites/specs/MISSIONS.md`, `MISSIONS-v0.5-FINAL.md`).

Ce document est un **contrat d'implémentation**, pas une source d'inspiration. Il ne contient encore aucun code : il prépare le module complet avant tout codage, conformément à la nouvelle règle de vitesse (doctrine → fiche → code → tests → une revue → merge).

---

## 1. Intention

CAP-005 et CAP-006 existent déjà et sont validés en préproduction, mais **seulement comme couche de déclaration** : une intention d'apprentissage (`LEARNING`) ou une offre de connaissance (`TRANSMISSION`) est une simple ligne `dg_capability_statements`, consommée aujourd'hui uniquement comme raison de recommandation dans `PersonRecommendationEngine`. Aucune rencontre, aucun accord, aucune session, aucune trace de ce qui s'est réellement passé n'existe.

TRANSMISSION est le module qui transforme cette paire de déclarations en **rencontre humaine organisée** :

> Une personne possède une capacité. Une autre veut apprendre. DG Afrique permet leur rencontre, organise une transmission humaine et relie cette transmission à une action réelle.

Une Transmission répond au minimum à quatre questions :

1. Qui transmet, qui apprend, sur quoi ?
2. Dans quel contexte cette transmission a-t-elle un sens (une capacité isolée, un besoin, une Mission, un Projet, une ZUMRA) ?
3. Les deux personnes ont-elles réellement consenti à cette rencontre précise ?
4. Que s'est-il passé, et qui en a gardé une trace ?

Doctrine courte :

> **Une Transmission organise une rencontre humaine autour d'une capacité. Elle ne transforme jamais une compétence en note, un enseignement en promesse de diplôme, ni une relation humaine en service automatisé.**

Distinctions invariantes (même famille que MISSIONS §1) :

> **Savoir faire n'est pas accepter de transmettre. Proposer d'apprendre n'est pas être accepté. Accepter une rencontre n'est pas la réaliser. Réaliser une transmission n'est pas la certifier.**

## 2. Ce que TRANSMISSION n'est pas

Rappel explicite, non négociable, car c'est le risque de dérive le plus probable de ce module :

- **pas** une plateforme de cours en ligne ;
- **pas** un LMS (Learning Management System) ;
- **pas** un Udemy, un Coursera ou un Moodle DG Afrique ;
- **pas** un catalogue de formations déconnecté des personnes ;
- **pas** un système de badges, diplômes ou certifications automatiques ;
- **pas** un classement de « meilleurs formateurs » ni un score de qualité pédagogique.

Il n'y a pas de « cours », pas de « module e-learning », pas de vidéo hébergée par DG Afrique, pas de quiz noté. S'il existe un contenu pédagogique, il vit ailleurs (GamaDrive, un lien externe) et TRANSMISSION s'y réfère par preuve/document, jamais ne le produit ni ne l'héberge.

## 3. Position dans le référentiel

TRANSMISSION approfondit CAP-006 (et sa contrepartie CAP-005) sans modifier le référentiel V0.1 ni créer un nouveau numéro CAP.

Rattachements principaux, par ordre d'intensité de dépendance :

- **CAP-004 — Compétences** : `dg_capability_statements` / `dg_capability_catalog` restent l'unique source de vérité du **quoi** (le libellé de capacité). TRANSMISSION ne recrée jamais un second référentiel de compétences.
- **CAP-005 — Apprentissage** et **CAP-006 — Transmission** : la déclaration `LEARNING`/`TRANSMISSION` reste l'expression d'une **intention durable et privée par défaut**. Une Transmission (le module) est un **engagement daté entre deux personnes précises**, distinct de la déclaration mais pouvant s'en inspirer (voir §8).
- **CAP-009 — Découverte de personnes** et **CAP-010 — Recommandation** : `PersonRecommendationEngine` produit déjà des raisons `learning_transmission`/`transmission_learning`. TRANSMISSION doit réutiliser ce même mécanisme d'explicabilité plutôt qu'en inventer un second (voir §14).
- **CAP-019/020/021/022 — Fil, Messagerie, Commentaire, Partage** : mêmes extensions que MISSIONS, mêmes invariants (append-only, visibilité revalidée, pas de duplication d'accès).
- **CAP-025 — Disponibilité** : n'existe pas en code aujourd'hui. TRANSMISSION ne peut pas attendre CAP-025 complet ; il expose un contrat minimal (voir §9) sans se substituer à une future capacité Disponibilité transversale.
- **CAP-027 — Prochaine action** : aucun agrégateur générique n'existe ; comme Missions, TRANSMISSION se branche à la main dans `MemberSpaceController::priority()`.
- **CAP-030 — Moteur de correspondance** et **CAP-031 — Explicabilité** : aucune abstraction partagée n'existe entre `PersonRecommendationEngine`, `ProjectMatchingEngine` et `MissionMatchingEngine`. TRANSMISSION ajoute un quatrième moteur bespoke suivant le même moule (explicable, jamais un score de valeur humaine), sans tenter de factoriser les trois existants dans cette fiche — ce serait un chantier transversal séparé, hors périmètre ici.
- **CAP-035 — Mémoire d'expérience** et **CAP-036 — Preuve de capacité** : doctrine seule, aucun code. TRANSMISSION expose un point d'intégration (référence de preuve) sans les implémenter (voir §13).
- **CAP-069 — Missions** : Missions expose déjà le rôle `LEARNER` et déclare explicitement (`MISSIONS.md §12`) : *« MISSIONS expose un point d'intégration pour LEARNER et les futures relations de Transmission, mais n'implémente pas à lui seul le workflow Transmission. »* Cette fiche honore cette promesse (voir §12).

TRANSMISSION ne remplace ni Besoin, ni Projet, ni Mission, ni Preuve, ni Messagerie, ni Commentaire, ni Partage. Il s'y intègre.

## 4. Doctrine à ne jamais casser

1. **L'humain transmet. DG Afrique organise la rencontre et le contexte. L'IA peut aider à structurer, expliquer et préparer — jamais remplacer la relation humaine.**
2. Savoir faire ne signifie pas accepter d'enseigner (CAP-006 invariant 1) — jamais d'obligation de transmettre déduite d'une capacité possédée.
3. Une intention d'apprentissage ne signifie ni incapacité ni infériorité (CAP-005 invariant 2).
4. Aucune acceptation silencieuse : proposer une transmission n'est pas être accepté ; accepter n'est pas commencer ; commencer n'est pas terminer.
5. Aucun score humain, aucun classement de transmetteurs, aucune note pédagogique. La progression se décrit, elle ne se chiffre pas (§10).
6. Aucun paiement, rang ou badge ne transforme une déclaration ou une transmission réalisée en certification (CAP-006 invariant 4).
7. Privé par défaut ; le matching et la découverte n'utilisent que ce qui est explicitement consenti.
8. Aucune donnée de démonstration réelle : pas de faux transmetteur, faux apprenant, fausse séance.
9. Il n'existe qu'un seul Fil DG Afrique (précédent établi par MISSIONS §15, repris ici à l'identique).
10. Aucun rôle ZUMRA/Projet n'est créé par TRANSMISSION. Aucune finance n'est créée par TRANSMISSION.

## 5. Acteurs

- **Transmetteur** : la personne qui accepte de transmettre une capacité précise, à une ou plusieurs personnes précises, sur une période donnée.
- **Apprenant** : la personne qui souhaite recevoir cette transmission.
- **Autorité contextuelle** (optionnelle) : si la Transmission est rattachée à un Projet, une ZUMRA ou une Mission, l'autorité de ce contexte (réutilisée telle quelle — `ProjectService::canDecide`, `ZumraGroupService::isLeader`, l'officialisateur de la Mission — jamais une nouvelle autorité) peut avoir un rôle d'officialisation contextuelle (voir §19, point à trancher).
- **DG Afrique (le produit)** : n'est jamais un acteur pédagogique. Il propose des rencontres explicables, structure un espace d'échange, et conserve la trace. Il ne note rien, ne certifie rien seul.

Un même compte peut être transmetteur sur une capacité et apprenant sur une autre, y compris simultanément.

## 6. Déclencheurs (points d'entrée)

Une Transmission peut être initiée depuis, au minimum :

1. **« Je veux apprendre »** sur une capacité, depuis un profil ou une fiche de capacité — l'apprenant initie.
2. **Une proposition volontaire de transmettre** — le transmetteur initie, en identifiant une personne précise qui a déclaré vouloir apprendre cette capacité (ou en réponse à une recommandation).
3. **Un besoin de compétence** (Besoin CAP-013 dont la catégorie relève d'une capacité manquante) — la Transmission devient une réponse possible à ce Besoin, jamais automatique (cf. CAP-034 doctrine : « apprentissage comme réponse à un besoin »).
4. **Une Mission** — un rôle `LEARNER` accepté sur une Mission peut proposer/recevoir une Transmission rattachée à cette Mission (voir §12).
5. **Un Projet** — un Projet peut faire apparaître une capacité manquante comme besoin de transmission interne à l'équipe.
6. **Une ZUMRA** — une ZUMRA peut organiser une transmission collective entre membres (voir §9).
7. **Une recommandation** (`PersonRecommendationEngine`) — la raison `learning_transmission`/`transmission_learning` porte un bouton d'action direct vers la proposition.

Dans tous les cas, l'origine (`origin_type`/`origin_reference`, ex. `NEED`, `MISSION`, `PROJECT`, `ZUMRA`, `RECOMMENDATION`, `PROFILE`) est conservée pour l'explicabilité et l'audit, mais **ne conditionne jamais** l'autorité de base (consentement mutuel, voir §7).

## 7. Proposition, demande, acceptation — jamais de consentement silencieux

Deux chemins symétriques, jamais fusionnés en une seule action :

- **Offre du transmetteur → Demande d'acceptation à l'apprenant.**
- **Demande de l'apprenant → Demande d'acceptation au transmetteur.**

Dans les deux cas :

- l'initiateur ne devient jamais automatiquement engagé tant que l'autre partie n'a pas explicitement répondu ;
- **silence ne vaut pas acceptation** (même invariant que Missions §6) ;
- refuser est toujours possible, sans justification obligatoire, sans pénalité visible ;
- retirer une proposition avant réponse est toujours possible par son auteur ;
- accepter n'entraîne aucune conséquence institutionnelle (aucun rôle, aucune finance).

État minimal de la relation (voir §17 pour le modèle complet) : `PROPOSED → ACCEPTED | DECLINED | WITHDRAWN`, puis `ACCEPTED → IN_PROGRESS → COMPLETED | INTERRUPTED`.

## 8. Lien avec la déclaration CAP-005/CAP-006

Une Transmission **peut** partir d'une déclaration existante (`dg_capability_statements` de nature `LEARNING` côté apprenant, `TRANSMISSION` côté transmetteur) mais **ne l'exige pas structurellement** : un déclenchement depuis une Mission (rôle `LEARNER`) ou un Besoin peut initier une Transmission sans qu'aucune des deux personnes n'ait pré-déclaré son profil dans ce sens.

La Transmission porte donc son propre `capability_label`/`normalized_label` (comme `MissionCapabilityRequirement` le fait déjà pour les Missions), avec une référence optionnelle vers `dg_capability_catalog.id` quand elle existe. **Aucune création automatique** de `CapabilityStatement` n'est déclenchée par une Transmission — voir point à trancher §22.

## 9. Contexte, disponibilité, individuel ou collectif

### Contexte (rattachement optionnel)

Une Transmission est **toujours valide de manière autonome** (deux personnes, une capacité, un accord) et **peut en plus** être rattachée à un contexte porteur : `NONE` (autonome), `NEED`, `MISSION`, `PROJECT`, `ZUMRA`.

Contrairement à Missions, ce rattachement **n'est pas un registre fail-closed d'autorité institutionnelle** — il n'est pas nécessaire de passer par une autorité de Projet/ZUMRA pour que deux personnes s'organisent une transmission. Le rattachement sert à :

- l'explicabilité (« cette transmission répond au besoin de compétence de X ») ;
- la visibilité contextuelle (visible aux membres de la ZUMRA plutôt que privée) ;
- l'intégration au Fil/Mon espace du contexte porteur.

Voir §19 pour le rôle exact de l'autorité contextuelle quand un rattachement existe (point à trancher).

### Disponibilité (CAP-025 — contrat minimal)

CAP-025 n'a aucune implémentation. TRANSMISSION ne peut pas construire un calendrier de disponibilité générique ici. Contrat minimal proposé : un champ libre `availability_note` (texte court, ex. « mardis soir », « selon accord mutuel ») porté par chaque déclaration `LEARNING`/`TRANSMISSION` existante ou par la Transmission elle-même — jamais un moteur de créneaux. Une vraie capacité Disponibilité transversale reste un chantier séparé.

### Individuelle ou collective

- **Transmission individuelle** : un transmetteur, un apprenant. Périmètre v1 certain.
- **Transmission collective** : un transmetteur, plusieurs apprenants (ex. une ZUMRA organise une session pour ses membres intéressés). Périmètre v1 incertain — voir point à trancher §22. Si retenue, elle réutilise la même mécanique de participation que Missions (offres/invitations/acceptations par apprenant, jamais un « auto-inscription silencieuse »).

## 10. Objectifs, étapes, progression — sans score humain

- **Objectif d'apprentissage** : un texte court obligatoire à l'acceptation (« ce que l'apprenant doit pouvoir faire à la fin »), rédigé conjointement ou proposé par l'initiateur puis confirmé par l'autre partie.
- **Étapes/séances** : **optionnelles**, jamais un moteur de planning. Une Transmission peut porter une liste plate de jalons (`TransmissionMilestone` : libellé, position, complété/non complété — même forme que `MissionChecklistItem`), utile pour les transmissions longues, ignorable pour une rencontre unique. Compléter 100 % des jalons **ne clôture jamais automatiquement** la Transmission (même invariant que la checklist Missions).
- **Progression** : représentée par le **statut** de la Transmission (`PROPOSED`/`ACCEPTED`/`IN_PROGRESS`/`COMPLETED`/`INTERRUPTED`) et, optionnellement, par les jalons complétés — jamais par un pourcentage de maîtrise, une note, un niveau ou un badge. Si un niveau de maîtrise doit être exprimé, c'est uniquement via le champ `proficiency` déjà existant sur `CapabilityStatement`, déclaré par la personne elle-même, jamais calculé par DG Afrique.

## 11. Fin et interruption

- **Fin normale** : `IN_PROGRESS → COMPLETED`, décidée conjointement (les deux parties confirment, ou une confirmation + une fenêtre de non-contestation — voir point à trancher §22) avec un résumé court obligatoire de ce qui a été transmis.
- **Interruption** : `ACCEPTED`/`IN_PROGRESS → INTERRUPTED`, par l'une ou l'autre partie, avec une raison optionnelle. Jamais présentée comme un échec chiffré. N'empêche pas une nouvelle Transmission ultérieure entre les mêmes personnes.
- Aucune suppression destructive depuis l'UI — même invariant append-only que Missions/Besoins/Projets.

## 12. Intégration Missions (CAP-069)

Point d'intégration déjà annoncé par `MISSIONS.md §12`. Contrat :

- Un rôle `LEARNER` accepté sur une Mission peut, depuis la fiche Mission, proposer ou recevoir une Transmission rattachée à cette Mission (`origin_type = MISSION`).
- La Transmission reste un objet séparé de `MissionAssignment` : accepter un rôle `LEARNER` sur une Mission n'accepte pas automatiquement une Transmission, et réciproquement.
- Le retrait d'un `LEARNER` d'une Mission n'interrompt pas automatiquement une Transmission déjà `IN_PROGRESS` liée à cette Mission — décision humaine séparée requise (même doctrine que « retirer un exécutant n'est jamais un événement silencieux »).
- Missions ne gagne aucune nouvelle capacité ; c'est TRANSMISSION qui référence `mission_id`/`mission_public_reference` en `origin_reference`, jamais l'inverse.

## 13. Intégration Carnet de preuves / GamaDrive

Ni le Carnet de preuves (CAP-036) ni une capacité de preuve transversale n'existent en code. Comme `MissionSubmission.evidence_context`, une Transmission expose un champ `evidence_context` (json libre : notes, références documentaires, liens GamaDrive fédérés) sur sa clôture. **TRANSMISSION ne certifie jamais automatiquement une preuve** et ne devient pas un stockage documentaire généraliste. Le jour où le Carnet de preuves existe, ce champ devient son point de rattachement naturel — non construit ici.

## 14. Matching explicable

Service proposé : `TransmissionMatchingEngine`, même moule que `MissionMatchingEngine`/`ProjectMatchingEngine` — pas de classe abstraite partagée inventée dans cette fiche (voir §3).

Réutilise directement la logique déjà existante et validée dans `PersonRecommendationEngine::score()` (raisons `learning_transmission`/`transmission_learning`) plutôt que de la dupliquer : ces deux moteurs doivent soit partager une fonction utilitaire d'appariement `LEARNING`↔`TRANSMISSION`, soit `TransmissionMatchingEngine` délègue explicitement une partie de son calcul à un service extrait de `PersonRecommendationEngine`. Le choix technique précis (extraction vs délégation) se décide à l'implémentation, pas ici — l'invariant est : **ne pas réécrire une seconde fois la logique d'appariement `LEARNING`/`TRANSMISSION`.**

Sources autorisées : `dg_capability_statements` de nature `LEARNING`/`TRANSMISSION` avec `visibility = DISCOVERABLE` et `matching_consent = true`, `orientation_consent`/`discovery_consent` du profil, contexte porteur le cas échéant.

Sortie : recommandations explicables, jamais un classement de personnes.

```text
- capacité recherchée correspondante
- déclaration de transmission compatible
- disponibilité compatible (note libre)
- même zone consentie
- contexte partagé (même ZUMRA / même Projet / même Mission)
```

Interdits : auto-jumelage (« matching automatique » qui créerait la Transmission sans proposition explicite), « meilleur transmetteur », score de qualité pédagogique. Le membre peut masquer une suggestion, scoppé à lui et à la capacité concernée (même mécanique que `MissionMatchingHide`).

## 15. Modèle de données proposé

Nomenclature alignée sur Missions (`dg_mission_*` → `dg_transmission_*`), tables UUID, `HasUuids`, `public_reference` pour le binding de route.

```text
dg_transmissions
  id, public_reference,
  transmitter_core_reference, learner_core_reference,
  capability_label, normalized_label, catalog_item_id (nullable, FK dg_capability_catalog),
  learning_objective (text),
  origin_type (NONE|NEED|MISSION|PROJECT|ZUMRA|RECOMMENDATION|PROFILE), origin_reference (nullable),
  context_type (nullable: PROJECT|ZUMRA), context_reference (nullable),  -- visibilité contextuelle uniquement, voir §9/§19
  mode (INDIVIDUAL|COLLECTIVE),
  visibility (PRIVATE|CONTEXT|PROGRAM),   -- jamais PUBLIC par défaut, voir §16
  status (PROPOSED|ACCEPTED|DECLINED|WITHDRAWN|IN_PROGRESS|COMPLETED|INTERRUPTED),
  availability_note (nullable text),
  proposed_by_core_reference, proposed_at,
  accepted_at, declined_at, withdrawn_at, started_at, completed_at, interrupted_at,
  evidence_context (json, nullable),
  timestamps

dg_transmission_participants        -- réservé au mode COLLECTIVE (voir §9/§22)
  id, transmission_id, core_identity_reference, role (LEARNER),
  status (INVITED|OFFERED|ACCEPTED|DECLINED|WITHDRAWN|REMOVED),
  timestamps

dg_transmission_milestones
  id, transmission_id, label, position, is_required,
  completed_by_core_reference (nullable), completed_at (nullable),
  timestamps

dg_transmission_events              -- append-only, même famille que dg_mission_events
  id, transmission_id, event, actor_core_reference,
  from_state (nullable), to_state (nullable), context (json), occurred_at,
  timestamps
```

Contraintes notables : `unique(public_reference)` ; index sur `(transmitter_core_reference)`, `(learner_core_reference)`, `(context_type, context_reference)`, `(origin_type, origin_reference)` ; `dg_transmission_participants` unique sur `(transmission_id, core_identity_reference)` (même garde-fou qu'`dg_mission_assignments`).

## 16. Visibilité et confidentialité

Une Transmission est **privée par défaut** (visible seulement du transmetteur, de l'apprenant, et de l'autorité contextuelle si rattachement + officialisation — voir §19). Elle ne devient visible plus largement (`CONTEXT`, `PROGRAM`) que par choix explicite au rattachement, jamais `PUBLIC` en v1 : une transmission reste une relation entre personnes, pas une annonce. Toute revalidation de visibilité suit le même schéma que `MissionVisibilityService::canViewMission()` : accès du contexte porteur (si rattaché) **ET** visibilité propre de la Transmission, jamais l'un sans l'autre.

## 17. Machine d'état

```text
PROPOSED --(accepte)--> ACCEPTED --(démarre)--> IN_PROGRESS --(clôture conjointe)--> COMPLETED
PROPOSED --(refuse)--> DECLINED
PROPOSED --(retire, par l'auteur)--> WITHDRAWN
ACCEPTED --(interrompt)--> INTERRUPTED
IN_PROGRESS --(interrompt)--> INTERRUPTED
```

Aucun raccourci : `PROPOSED → COMPLETED` directement est interdit, même par une seule autorité — chaque transition reste distincte et auditée (même doctrine que Missions §5, avec une machine plus courte car il n'y a pas d'officialisation institutionnelle obligatoire par défaut).

Toute transition sensible : verrouillage de ligne (`lockForUpdate`) sur la Transmission, vérification d'état source, vérification d'autorité, mutation, événement append-only — même primitive partagée que `MissionWorkflow::applyTransition()` (`TransmissionWorkflow::applyTransition()` à construire sur le même moule).

## 18. Permissions et consentement

- **Proposer** : toute personne membre (avec compte GAMAD Core actif), sous réserve de consentement d'orientation si la cible provient d'une recommandation/découverte.
- **Accepter/refuser** : exclusivement la personne destinataire de la proposition — jamais l'initiateur, jamais une autorité tierce.
- **Retirer avant réponse** : exclusivement l'auteur de la proposition.
- **Démarrer (`ACCEPTED → IN_PROGRESS`)** : l'une ou l'autre partie, dès lors que les deux ont accepté.
- **Interrompre** : l'une ou l'autre partie, à tout moment après acceptation.
- **Clôturer (`IN_PROGRESS → COMPLETED`)** : voir point à trancher §22 (confirmation conjointe vs confirmation + fenêtre de non-contestation).
- **Gérer les jalons** : transmetteur et apprenant, tous deux (pas de hiérarchie pédagogique imposée).
- **Autorité contextuelle** (Projet/ZUMRA) : jamais un droit de forcer l'acceptation ou la clôture ; au mieux un droit de visibilité et, si retenu (§19), un droit d'officialisation de la visibilité contextuelle uniquement.

Identité toujours via le mécanisme GAMAD Core existant, jamais un garde Laravel local. Comme pour l'invitation Missions (durcissement CAP-069), toute désignation d'un tiers dans l'UI passe par une `discovery_reference` consentie — **jamais une saisie ou un affichage direct de `core_identity_reference`**.

## 19. Fil unique

Un seul Fil DG Afrique (§4.9). Événements Transmission éligibles :

```text
TRANSMISSION_PROPOSED     -- uniquement si rattachée à un contexte visible (ZUMRA/Projet), jamais bruit privé
TRANSMISSION_ACCEPTED
TRANSMISSION_COMPLETED
```

Pas de Fil Transmission autonome (aucune tentation de recréer le « Fil Transmission » évoqué par la doctrine v1.1 §14 — ce point a déjà été tranché pour Missions et vaut identiquement ici : un seul Fil, `ActivityFeedService` revalide la visibilité à la projection). Pas d'événement pour chaque jalon coché.

`ActivityFeedService` gagne une méthode privée `transmissionItems()` suivant exactement le patron `missionItems()` (constantes d'événements éligibles, filtre replié dans les buckets existants ALL/NEEDS/PROJECTS/ZUMRA selon le `context_type` porté, jamais un 5ᵉ filtre).

## 20. Mon espace

Route globale proposée :

```text
GET /transmissions
```

Sections proposées, même esprit que « Mes Missions », construites depuis l'empreinte propre de l'acteur (jamais un balayage global) :

- **Propositions reçues** (à répondre) ;
- **Mes demandes** (envoyées, en attente) ;
- **En cours — je transmets** ;
- **En cours — j'apprends** ;
- **Terminées**.

`MemberSpaceController::priority()` gagne une prochaine action Transmission (proposition reçue à traiter, transmission en cours sans nouvelle depuis longtemps — à définir précisément à l'implémentation), injectée dans la même chaîne if/elseif qu'aujourd'hui pour Missions, jamais un second système de priorité. Pas de mur de statistiques, pas de compteur de transmissions réalisées présenté comme un score.

## 21. Notifications, commentaires, partage, messagerie

- **CAP-054 (Notifications)** : aucune primitive Notification n'existe dans ce dépôt (confirmé lors du chantier Missions). TRANSMISSION **ne construit pas** un second système de notifications parallèle. Ce qui exige une attention réelle (proposition reçue, transmission acceptée) reste visible via Mon espace/Fil comme aujourd'hui ; le raccordement à une vraie CAP-054 est un dépendance déclarée, pas un blocage.
- **CAP-021 (Commentaire)** : `ContextComment` gagne `CONTEXT_TRANSMISSION`, même patron que `CONTEXT_MISSION` (thread revalidant la visibilité à chaque lecture, append-only, pas de popularité).
- **CAP-022 (Partage)** : `ContextShare` gagne `SOURCE_TRANSMISSION`, même patron que `SOURCE_MISSION` — partage sans dupliquer, sans jamais octroyer un accès que le destinataire n'avait pas déjà.
- **CAP-020 (Messagerie)** : `MessageConversation` gagne `CONTEXT_TRANSMISSION` pour ouvrir une conversation contextualisée entre transmetteur et apprenant. La conversation n'est jamais la source de vérité du statut de la Transmission (même doctrine que Missions §14).

## 22. Points à trancher avant codage

Ces points ont une proposition par défaut dans cette fiche mais nécessitent une confirmation humaine explicite avant tout code, car ils changent la forme du schéma ou du workflow :

1. **Rôle de l'autorité contextuelle.** Proposition : quand une Transmission est rattachée à un Projet/ZUMRA, l'autorité contextuelle peut élargir sa visibilité (`CONTEXT`/`PROGRAM`) mais ne peut ni forcer l'acceptation, ni la clôture, ni la création. À confirmer, notamment pour le cas ZUMRA (une ZUMRA doit-elle pouvoir « officialiser » une transmission collective qu'elle organise, au sens où Missions officialise une Mission ?).
2. **Transmission collective.** Proposition : hors périmètre v1 strict (seulement `mode = INDIVIDUAL` codé d'abord), `dg_transmission_participants` posé dans le schéma mais non exploité tant que la décision n'est pas prise, pour éviter une migration disruptive plus tard. À confirmer.
3. **Déclaration préalable obligatoire ou non.** Proposition : jamais obligatoire (§8) — une Transmission peut naître d'un contexte (Mission/Besoin) sans déclaration `LEARNING`/`TRANSMISSION` préexistante, et ne crée jamais silencieusement cette déclaration en retour. À confirmer, car l'alternative (déclaration obligatoire en amont) simplifierait le matching mais durcirait l'entrée en matière.
4. **Lien Transmission → Preuve de capacité (CAP-036).** Proposition : une Transmission `COMPLETED` ne fait **jamais** progresser automatiquement le `status` d'une `CapabilityStatement` (`DECLARED → VERIFIED/ATTESTED`) — cela reste une décision humaine séparée, si et quand CAP-036 existe. À confirmer, car une automatisation même partielle serait une dérive vers la certification automatique interdite par CAP-006 invariant 4.
5. **Clôture conjointe.** Proposition : `IN_PROGRESS → COMPLETED` exige la confirmation des **deux** parties (deux actions distinctes, pas une seule décision unilatérale) — au risque qu'une partie injoignable bloque indéfiniment la clôture. Alternative : une seule confirmation + une fenêtre de non-contestation de l'autre partie. À confirmer.

Tant que ces cinq points ne sont pas tranchés par un humain, aucune implémentation ne doit démarrer sur les parties qu'ils affectent (workflow, permissions, modèle collectif).

## 23. Rôle de l'IA

Comme dans MISSIONS §21 et la doctrine générale : l'IA peut aider à formuler un objectif d'apprentissage, suggérer des jalons raisonnables, expliquer une recommandation de matching, préparer un résumé de clôture proposé (jamais imposé). Elle ne décide jamais à la place des personnes, ne note jamais une transmission, ne choisit jamais qui doit apprendre de qui.

## 24. Audit

Toute transition d'état et toute mutation de participation s'écrit dans `dg_transmission_events` (append-only, jamais modifié ni supprimé depuis l'UI), même patron que `dg_mission_events`. Chaque ligne porte l'acteur réel (jamais un acteur générique, sauf un futur job système explicitement nommé, comme `MissionEvent::SYSTEM_RECURRENCE_ACTOR` pour Missions — TRANSMISSION v1 ne prévoit aucun acteur système : tout événement a un auteur humain).

## 25. États UX obligatoires

- état vide honnête sur `/transmissions` si aucune proposition/transmission n'existe encore, avec l'action qui peut débloquer (proposer, ou consulter les recommandations) ;
- jamais de donnée fictive pour remplir l'écran ;
- badge de statut cohérent avec le vocabulaire déjà établi (`x-dg.badge`, tons réutilisés du système Missions : `decision` pour PROPOSED, `action` pour ACCEPTED/IN_PROGRESS, `project` pour COMPLETED, `neutral` pour DECLINED/WITHDRAWN/INTERRUPTED) ;
- aucune UI n'affiche un formulaire d'action que le backend refuserait (CAP-072), donc la fiche Transmission doit calculer et transmettre au gabarit les mêmes indicateurs de permission que Missions (`canAccept`, `canDecline`, `canStart`, `canComplete`, `canInterrupt`) plutôt que de dupliquer la logique d'autorité côté vue.

## 26. Tests d'acceptation obligatoires (catégories minimales)

Conversion en tests réels attendue, à l'image des ~40 tests Missions. Catégories minimales, non exhaustives :

- proposition transmetteur→apprenant et apprenant→transmetteur, toutes deux fonctionnelles et symétriques ;
- aucune acceptation silencieuse (le statut ne bascule jamais sans action explicite du bon acteur) ;
- retrait avant réponse par l'auteur seul ;
- refus toujours possible sans justification ;
- démarrage impossible avant double acceptation ;
- clôture jamais unilatérale (selon décision §22.5) ;
- interruption possible à tout moment après acceptation, sans verrouillage ;
- visibilité privée par défaut, jamais `PUBLIC` ;
- visibilité contextuelle revalidée à la lecture (perte d'accès à la ZUMRA ⇒ disparition de la Transmission rattachée) ;
- matching : seules les déclarations `DISCOVERABLE` + `matching_consent=true` apparaissent, jamais d'auto-jumelage ;
- Fil : aucun Fil Transmission séparé n'existe (assertion négative de route), événements repliés dans les filtres existants ;
- Mon espace : sections construites depuis l'empreinte de l'acteur, jamais un balayage global masquant une proposition ancienne ;
- aucune `CapabilityStatement` n'est créée silencieusement par une Transmission (tant que §22.3 n'autorise pas explicitement le contraire) ;
- aucun rôle ZUMRA/Projet créé, aucune finance créée ;
- partage sans octroi d'accès (même test que Missions, adapté) ;
- commentaire revalidant la visibilité à chaque lecture ;
- aucune donnée de démonstration réelle seedée.

## 27. Hors périmètre (v1)

- calendrier de disponibilité structuré (attend CAP-025) ;
- notifications poussées (attend CAP-054) ;
- certification, badge, diplôme, niveau calculé ;
- hébergement de contenu pédagogique (vidéo, quiz, cours) ;
- paiement de la transmission (aucune finance ici, cf. CAP-061/062/063 séparés et hors périmètre) ;
- tout ce qui ressemble à un catalogue public de « formateurs » consultable hors relation (pas de page « trouvez votre formateur » façon marketplace) ;
- transmission collective si le point à trancher §22.2 est refusé.

## 28. Definition of Done (pour cette fiche, pas pour le code)

- [x] statut CONCEPTION explicite ;
- [x] rattachement au référentiel confirmé (CAP-006 racine, pas de nouveau numéro) ;
- [x] doctrine et invariants cités avec leurs sources ;
- [x] acteurs, déclencheurs, workflow, permissions, visibilité, matching, intégrations (Fil, Mon espace, commentaire, partage, messagerie, Missions, Preuve) couverts ;
- [x] hors périmètre explicite pour empêcher la dérive LMS ;
- [x] points à trancher listés et bornés à un nombre gérable ;
- [ ] les 5 points de §22 validés par un humain ;
- [ ] aucune ligne de code, migration, route ou vue écrite avant cette validation.

## 29. Instruction d'arrêt

Ne pas commencer l'implémentation de TRANSMISSION avant validation explicite des points §22. Si un point non listé ici s'avère bloquant pendant l'implémentation, appliquer la même règle d'arrêt que Missions : documenter le conflit, les fichiers concernés, les options, le risque — puis arrêter cette partie pour revue, plutôt que de trancher seul une règle métier.
