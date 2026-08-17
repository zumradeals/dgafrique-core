# FICHE D'IMPLÉMENTATION TRANSVERSALE — TRANSMISSION

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-006 — TRANSMISSION
**Expression produit :** TRANSMISSION
**Nouveau CAP :** non
**Nature :** module transversal natif de DG Afrique, construit sur la déclaration existante CAP-005/CAP-006
**Base de conception :** référentiel des 84 capacités, doctrine canonique ZUMRA (`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`), invariants de design (`docs/design/DESIGN-INVARIANTS.md`), fiches courtes existantes `CAP-005-apprentissage.md` / `CAP-006-transmission.md`, le précédent architectural MISSIONS (`docs/capacites/specs/MISSIONS.md`), et les 5 décisions métier validées le 2026-08-17.

Ce document est un **contrat d'implémentation**. Les 5 points laissés ouverts en version CONCEPTION (0.1) sont tranchés ci-dessous. L'implémentation peut démarrer.

---

## 1. Intention

CAP-005 et CAP-006 existent déjà et sont validés en préproduction, mais **seulement comme couche de déclaration** : une intention d'apprentissage (`LEARNING`) ou une offre de connaissance (`TRANSMISSION`) est une simple ligne `dg_capability_statements`, consommée aujourd'hui uniquement comme raison de recommandation dans `PersonRecommendationEngine`. Aucune rencontre, aucun accord, aucune session, aucune trace de ce qui s'est réellement passé n'existe.

TRANSMISSION est le module qui transforme cette paire de déclarations en **rencontre humaine organisée** :

> **Une Transmission est une relation humaine volontaire par laquelle une capacité, un savoir ou une pratique est transmis dans un contexte réel. DG Afrique organise la rencontre, l'engagement et la trace ; il ne transforme ni l'apprentissage en score, ni la transmission en autorité, ni la participation en certification automatique.**

Chaîne produit à préserver de bout en bout dans l'implémentation :

```text
Je veux apprendre → rencontre → accord mutuel → Transmission → pratique réelle → trace/preuve → éventuellement capacité enrichie
```

Une Transmission répond au minimum à quatre questions :

1. Qui transmet, qui apprend, sur quoi ?
2. Dans quel contexte cette transmission a-t-elle un sens (une capacité isolée, un besoin, une Mission, un Projet, une ZUMRA) ?
3. Les participants ont-ils réellement, individuellement, consenti à cette rencontre précise ?
4. Que s'est-il passé, et qui en a gardé une trace ?

Distinctions invariantes (même famille que MISSIONS §1) :

> **Savoir faire n'est pas accepter de transmettre. Proposer d'apprendre n'est pas être accepté. Accepter une rencontre n'est pas la réaliser. Réaliser une transmission n'est pas la certifier. Officialiser une Transmission n'est pas accepter à la place des participants. Déclarer terminé n'est pas valider la Transmission.**

## 2. Ce que TRANSMISSION n'est pas

- **pas** une plateforme de cours en ligne, **pas** un LMS, **pas** un Udemy/Coursera/Moodle DG Afrique ;
- **pas** un catalogue de formations déconnecté des personnes ;
- **pas** un système de badges, diplômes ou certifications automatiques ;
- **pas** un classement de « meilleurs formateurs » ni un score de qualité pédagogique ;
- même en mode collectif (§9), **pas** une classe virtuelle générique : le moteur reste centré sur une relation humaine réelle, jamais sur une salle de cours anonyme.

Il n'y a pas de « cours », pas de vidéo hébergée par DG Afrique, pas de quiz noté. Un contenu pédagogique éventuel vit ailleurs (GamaDrive, un lien externe) et TRANSMISSION s'y réfère par preuve/document, jamais ne le produit ni ne l'héberge.

## 3. Position dans le référentiel

Rattachements principaux, inchangés depuis la version CONCEPTION :

- **CAP-004 — Compétences** : `dg_capability_statements` / `dg_capability_catalog` restent l'unique source de vérité du **quoi**.
- **CAP-005/CAP-006** : la déclaration reste l'expression d'une intention durable et privée par défaut. Une Transmission (le module) est un **engagement daté entre des personnes précises**, distinct de la déclaration (voir §8).
- **CAP-009/CAP-010** : `PersonRecommendationEngine` reste la source du mécanisme d'explicabilité `LEARNING`↔`TRANSMISSION` (voir §14).
- **CAP-019/020/021/022** : mêmes extensions que MISSIONS, mêmes invariants.
- **CAP-025 — Disponibilité** : contrat minimal (`availability_note`), pas d'implémentation complète.
- **CAP-027** : branchement manuel dans `MemberSpaceController::priority()`, comme Missions.
- **CAP-030/CAP-031** : quatrième moteur bespoke, explicable, sans facteur de score humain.
- **CAP-035/CAP-036** : point d'intégration seulement (voir §13).
- **CAP-069 — Missions** : honore la promesse `MISSIONS.md §12` (voir §12).

TRANSMISSION ne remplace ni Besoin, ni Projet, ni Mission, ni Preuve, ni Messagerie, ni Commentaire, ni Partage. Il s'y intègre. Aucun nouveau numéro CAP n'est créé.

## 4. Doctrine à ne jamais casser

1. L'humain transmet. DG Afrique organise la rencontre et le contexte. L'IA aide à structurer, expliquer, préparer — jamais à remplacer la relation humaine (§21).
2. Savoir faire ne signifie pas accepter d'enseigner — aucune obligation de transmettre déduite d'une capacité possédée.
3. Une intention d'apprentissage ne signifie ni incapacité ni infériorité.
4. **Aucune acceptation silencieuse, jamais.** Proposer n'est pas être accepté. Officialiser un rattachement contextuel n'est pas accepter à la place d'un participant. Déclarer sa part terminée n'est pas valider la Transmission.
5. Aucun score humain, aucun classement de transmetteurs, aucune note pédagogique.
6. Aucun paiement, rang ou badge ne transforme une déclaration ou une transmission réalisée en certification.
7. Privé par défaut ; le matching et la découverte n'utilisent que ce qui est explicitement consenti.
8. Aucune donnée de démonstration réelle.
9. Il n'existe qu'un seul Fil DG Afrique.
10. Aucun rôle ZUMRA/Projet n'est créé par TRANSMISSION. Aucune finance n'est créée par TRANSMISSION. Aucune `CapabilityStatement` n'est créée ou modifiée silencieusement (§8, §13).

## 5. Décisions métier validées (2026-08-17)

### A. Autorité contextuelle

Une Transmission peut être purement personne ↔ personne, sans autorité extérieure.

Si elle est rattachée à un Projet, une ZUMRA ou une Mission :

- l'autorité du contexte (réutilisée telle quelle : autorité de décision du Projet, responsable de la ZUMRA, officialisateur de la Mission — jamais une nouvelle autorité créée) peut **officialiser le rattachement au contexte** ;
- elle peut gérer la **visibilité** de la Transmission dans ce contexte et sa **reconnaissance comme activité** du Projet/ZUMRA/Mission ;
- elle ne peut **jamais** accepter une Transmission à la place d'un transmetteur ou d'un apprenant.

Invariant : **officialiser la Transmission ≠ accepter à la place des participants.** Chaque participant accepte explicitement sa propre participation, quoi que décide l'autorité contextuelle.

### B. Transmission collective

Le module implémenté est complet, pas un sous-ensemble :

- 1 transmetteur ↔ 1 apprenant ;
- 1 transmetteur ↔ plusieurs apprenants ;
- plusieurs transmetteurs ↔ plusieurs apprenants.

Tous les participants (transmetteurs et apprenants) passent par la même mécanique de participation individuelle et de consentement explicite (§9). Le moteur reste centré sur une relation humaine réelle — voir garde-fous anti-LMS §2.

### C. Déclaration CAP-005/CAP-006 préalable

**Non obligatoire.** Une Transmission peut naître directement depuis une Mission, un Projet, un Besoin, une ZUMRA, une interaction humaine, une recommandation, ou une intention ponctuelle, même si aucune déclaration `LEARNING`/`TRANSMISSION` n'existe déjà dans le profil des participants.

Après la clôture d'une Transmission, DG Afrique **peut proposer** — jamais imposer ni exécuter silencieusement — de rendre l'intention durable dans le profil (« Voulez-vous rendre cette intention durable dans votre profil ? »). Cette proposition est une **suggestion d'interface**, jamais une écriture automatique : elle redirige vers le formulaire de profil existant (CAP-003/005/006), qui reste l'unique point d'écriture de `dg_capability_statements`.

### D. Preuve / progression de capacité

Une Transmission terminée :

- peut créer une trace d'expérience (`TransmissionContribution`, §15) ;
- peut référencer des preuves ou livrables par lien/document (`evidence_context`) ;
- peut produire une confirmation contextualisée (`COMPLETED_BY_CONTEXT`, §10) ;
- peut **proposer** une évolution de `CapabilityStatement` (même logique de suggestion qu'au point C : lien vers le profil, jamais une écriture directe).

Elle ne doit **jamais** : certifier automatiquement une compétence, transformer automatiquement un apprenant en compétent, modifier automatiquement le niveau ou le statut d'une `CapabilityStatement`. La progression reste humaine, explicite et contextualisée.

### E. Clôture

Pas de clôture automatique par silence. Quatre issues finales distinctes :

- **`COMPLETED_CONFIRMED`** — résultat confirmé selon le workflow humain défini (§10) : au moins un transmetteur et un apprenant ont individuellement déclaré leur part terminée, puis un participant accepté déclenche explicitement la confirmation de clôture. Deux actions distinctes, jamais fusionnées.
- **`COMPLETED_BY_CONTEXT`** — dans un Projet/ZUMRA/Mission, l'autorité contextuelle valide la réalisation après soumission d'une trace, une fois la Transmission `IN_PROGRESS`.
- **`ENDED`** — la Transmission est arrêtée sans prétendre que l'objectif a été atteint.
- **`CANCELLED`** — annulée avant réalisation (avant tout passage en `IN_PROGRESS`).

Invariant (même famille que Missions) : **déclarer sa part terminée ≠ valider la Transmission.** Une personne peut déclarer sa part terminée sans que cela rende automatiquement la Transmission « réussie ».

## 6. Acteurs

- **Transmetteur** (rôle `TRANSMITTER`) : personne qui accepte de transmettre une capacité précise.
- **Apprenant** (rôle `LEARNER`) : personne qui souhaite recevoir cette transmission.
- **Autorité contextuelle** (optionnelle, §5.A) : autorité déjà existante du Projet/ZUMRA/Mission rattaché — jamais une nouvelle autorité.
- **DG Afrique (le produit)** : jamais un acteur pédagogique. Il propose des rencontres explicables, structure un espace d'échange, conserve la trace. Il ne note rien, ne certifie rien seul.

Un même compte peut être `TRANSMITTER` sur une Transmission et `LEARNER` sur une autre, y compris simultanément. Sur une même Transmission, une personne tient un seul rôle.

## 7. Déclencheurs (points d'entrée)

Une Transmission peut être initiée depuis, au minimum :

1. **« Je veux apprendre »** sur une capacité — l'apprenant initie.
2. **Une proposition volontaire de transmettre** — le transmetteur initie.
3. **Un besoin de compétence** (Besoin CAP-013) — réponse possible, jamais automatique (doctrine CAP-034).
4. **Une Mission** — un rôle `LEARNER` accepté sur une Mission peut proposer/recevoir une Transmission rattachée à cette Mission (§12).
5. **Un Projet** — capacité manquante identifiée dans l'équipe.
6. **Une ZUMRA** — transmission collective organisée entre membres (§9).
7. **Une recommandation** (`PersonRecommendationEngine`) — la raison `learning_transmission`/`transmission_learning` porte un bouton d'action direct.
8. **Une interaction humaine ponctuelle** — aucune source structurée préalable (`origin_type = INTERACTION`).

L'origine (`origin_type`/`origin_reference`) est conservée pour l'explicabilité et l'audit, mais ne conditionne jamais l'autorité de base (consentement individuel de chaque participant).

## 8. Lien avec la déclaration CAP-005/CAP-006

Non obligatoire (§5.C). La Transmission porte son propre `capability_label`/`normalized_label`, avec une référence optionnelle vers `dg_capability_catalog.id` quand elle existe. Aucune création ou modification automatique de `CapabilityStatement` — la seule action possible est une suggestion d'interface pointant vers le formulaire de profil existant.

## 9. Participation, contexte, disponibilité

### Participation individuelle et collective

Tout participant (`TRANSMITTER` ou `LEARNER`) suit le même cycle de consentement explicite :

```text
INVITED  --(accepte)--> ACCEPTED
INVITED  --(refuse)--> DECLINED
OFFERED  --(accepte)--> ACCEPTED       -- une personne se propose elle-même
OFFERED  --(retire)--> WITHDRAWN
ACCEPTED --(se retire)--> WITHDRAWN
ACCEPTED --(retiré par l'organisateur)--> REMOVED
```

`INVITED` = désigné par un autre participant/l'organisateur, doit répondre. `OFFERED` = se propose spontanément, doit être accepté par au moins un `TRANSMITTER` déjà `ACCEPTED` (ou par l'initiateur si aucun `TRANSMITTER` n'est encore accepté). Aucun statut ne bascule vers `ACCEPTED` sans action de la personne concernée elle-même (jamais un tiers qui accepte « pour » quelqu'un — même en mode collectif).

Une Transmission passe de `PROPOSED` à `ACCEPTED` (niveau Transmission, §10) dès qu'elle compte **au moins un `TRANSMITTER` et un `LEARNER`** à l'état `ACCEPTED`. Au-delà de ce quorum minimal, la liste de participants reste ouverte (nouvelles invitations/offres possibles) tant que la Transmission n'est pas `IN_PROGRESS`.

### Contexte (rattachement optionnel)

Une Transmission est **toujours valide de manière autonome** et **peut en plus** être rattachée à un contexte porteur : `NONE` (autonome), `NEED`, `MISSION`, `PROJECT`, `ZUMRA`. Ce rattachement n'est **pas** un registre fail-closed d'autorité obligatoire comme pour Missions — il sert l'explicabilité, la visibilité contextuelle et l'intégration au Fil/Mon espace du contexte porteur. L'officialisation par l'autorité contextuelle (§5.A) n'est disponible et pertinente que pour `PROJECT`, `ZUMRA` et `MISSION`, qui portent chacun une autorité déjà implémentée ; un rattachement `NEED` reste une référence de visibilité sans étape d'officialisation formelle (le Besoin n'a pas d'autorité contextuelle dédiée dans le référentiel actuel).

### Disponibilité (CAP-025 — contrat minimal)

Champ libre `availability_note` (texte court) porté par la Transmission. Pas de moteur de créneaux — CAP-025 transversal reste un chantier séparé.

## 10. Objectifs, étapes, progression — sans score humain

- **Objectif d'apprentissage** : texte court obligatoire à la création (`learning_objective`).
- **Étapes/séances** : optionnelles (`TransmissionMilestone`, même forme que `MissionChecklistItem` : libellé, position, complété/non complété). Compléter 100 % des jalons ne clôture jamais automatiquement la Transmission.
- **Progression** : représentée par le statut de la Transmission et, optionnellement, les jalons complétés — jamais un pourcentage de maîtrise, une note ou un badge. Un niveau de maîtrise éventuel reste le champ `proficiency` de `CapabilityStatement`, déclaré par la personne elle-même (§5.D), jamais calculé par DG Afrique.
- **Workflow humain de clôture confirmée** (détail de `COMPLETED_CONFIRMED`, §5.E) : chaque participant `ACCEPTED` peut `declareDone()` individuellement (horodaté, avec note optionnelle). Dès qu'au moins un `TRANSMITTER` et un `LEARNER` ont déclaré leur part terminée, n'importe quel participant `ACCEPTED` peut appeler `confirmCompletion()`, qui est l'action distincte qui fait réellement basculer la Transmission en `COMPLETED_CONFIRMED` avec un résumé court obligatoire.

## 11. Fin et interruption

- **`COMPLETED_CONFIRMED`** / **`COMPLETED_BY_CONTEXT`** : voir §5.E, §10.
- **`ENDED`** : depuis `ACCEPTED` ou `IN_PROGRESS`, par n'importe quel participant `ACCEPTED`, raison optionnelle. N'empêche pas une nouvelle Transmission ultérieure entre les mêmes personnes.
- **`CANCELLED`** : depuis `PROPOSED` ou `ACCEPTED`, avant tout passage en `IN_PROGRESS`, par l'initiateur ou un `TRANSMITTER` accepté.
- Aucune suppression destructive depuis l'UI — invariant append-only.

## 12. Intégration Missions (CAP-069)

- Un rôle `LEARNER` accepté sur une Mission peut, depuis la fiche Mission, proposer ou recevoir une Transmission rattachée à cette Mission (`origin_type = MISSION`, `context_type = MISSION` si un rattachement visible est souhaité).
- La Transmission reste un objet séparé de `MissionAssignment` : accepter un rôle `LEARNER` sur une Mission n'accepte pas automatiquement une Transmission, et réciproquement.
- Le retrait d'un `LEARNER` d'une Mission n'interrompt pas automatiquement une Transmission déjà `IN_PROGRESS` liée à cette Mission — décision humaine séparée requise.
- Missions ne gagne aucune nouvelle capacité ; TRANSMISSION référence `mission_id`/`mission_public_reference` en `origin_reference`/`context_reference`, jamais l'inverse. L'officialisateur de Mission (autorité déjà existante) est l'autorité contextuelle réutilisée pour `context_type = MISSION` (§5.A).

## 13. Intégration Carnet de preuves / GamaDrive

Ni le Carnet de preuves (CAP-036) ni une capacité de preuve transversale n'existent en code. `TransmissionContribution.evidence_context` (json libre : notes, références documentaires, liens GamaDrive fédérés) est le point de rattachement futur. TRANSMISSION ne certifie jamais automatiquement une preuve et ne devient pas un stockage documentaire généraliste.

## 14. Matching explicable

Service : `TransmissionMatchingEngine`, même moule que `MissionMatchingEngine`/`ProjectMatchingEngine`. Réutilise directement la logique d'appariement `LEARNING`↔`TRANSMISSION` déjà existante et validée dans `PersonRecommendationEngine` (extraction d'une fonction utilitaire partagée plutôt que duplication).

Sources autorisées : `dg_capability_statements` `DISCOVERABLE` + `matching_consent = true`, `orientation_consent`/`discovery_consent` du profil, contexte porteur le cas échéant.

Sortie : recommandations explicables, jamais un classement de personnes.

```text
- capacité recherchée correspondante
- déclaration de transmission compatible
- disponibilité compatible (note libre)
- même zone consentie
- contexte partagé (même ZUMRA / même Projet / même Mission)
```

Interdits : auto-jumelage, « meilleur transmetteur », score de qualité pédagogique. Une suggestion peut être masquée, scoppée à la personne et à la capacité concernée.

## 15. Modèle de données

```text
dg_transmissions
  id, public_reference,
  capability_label, normalized_label, catalog_item_id (nullable, FK dg_capability_catalog),
  learning_objective (text),
  origin_type (NONE|NEED|MISSION|PROJECT|ZUMRA|RECOMMENDATION|PROFILE|INTERACTION), origin_reference (nullable),
  context_type (nullable: NEED|MISSION|PROJECT|ZUMRA), context_reference (nullable),
  visibility (PRIVATE|CONTEXT|PROGRAM) default PRIVATE,
  status (PROPOSED|ACCEPTED|IN_PROGRESS|COMPLETED_CONFIRMED|COMPLETED_BY_CONTEXT|ENDED|CANCELLED),
  availability_note (nullable text),
  proposed_by_core_reference, proposed_at,
  accepted_at, started_at, completed_at, ended_at, cancelled_at,
  completion_summary (nullable text),
  context_officialized_by_core_reference (nullable), context_officialized_at (nullable),
  context_validated_by_core_reference (nullable), context_validated_at (nullable),
  evidence_context (json, nullable),
  timestamps

dg_transmission_participants
  id, transmission_id, core_identity_reference,
  role (TRANSMITTER|LEARNER),
  status (INVITED|OFFERED|ACCEPTED|DECLINED|WITHDRAWN|REMOVED),
  invited_by_core_reference (nullable),
  responded_at (nullable),
  declared_done_at (nullable), declared_done_note (nullable text),
  timestamps
  -- unique(transmission_id, core_identity_reference)

dg_transmission_milestones
  id, transmission_id, label, position, is_required,
  completed_by_core_reference (nullable), completed_at (nullable),
  timestamps

dg_transmission_contributions
  id, transmission_id, core_identity_reference,
  note (text), evidence_context (json, nullable), occurred_at,
  timestamps

dg_transmission_events              -- append-only
  id, transmission_id, event, actor_core_reference,
  from_state (nullable), to_state (nullable), context (json), occurred_at,
  timestamps
```

## 16. Visibilité et confidentialité

Privée par défaut (visible du/des transmetteur(s), du/des apprenant(s), et de l'autorité contextuelle si rattachement + officialisation). `CONTEXT`/`PROGRAM` uniquement par choix explicite au rattachement, jamais `PUBLIC` en v1. Revalidation systématique à la lecture : accès du contexte porteur (si rattaché) **ET** visibilité propre de la Transmission, jamais l'un sans l'autre.

## 17. Machine d'état

```text
PROPOSED --(quorum ≥1 TRANSMITTER + ≥1 LEARNER acceptés)--> ACCEPTED
PROPOSED --(annule, avant IN_PROGRESS)--> CANCELLED
ACCEPTED --(annule, avant IN_PROGRESS)--> CANCELLED
ACCEPTED --(démarre)--> IN_PROGRESS
ACCEPTED --(arrête)--> ENDED
IN_PROGRESS --(confirmation conjointe explicite)--> COMPLETED_CONFIRMED
IN_PROGRESS --(validation autorité contextuelle après trace)--> COMPLETED_BY_CONTEXT
IN_PROGRESS --(arrête)--> ENDED
```

Aucun raccourci : `PROPOSED → COMPLETED_*` directement est interdit. Chaque transition reste distincte et auditée. Verrouillage de ligne (`lockForUpdate`) sur la Transmission pour toute transition sensible, vérification d'état source, vérification d'autorité, mutation, événement append-only — même primitive que `MissionWorkflow::applyTransition()`.

## 18. Permissions et consentement

- **Proposer** : toute personne membre.
- **Inviter/offrir une participation** : l'initiateur, puis tout `TRANSMITTER` `ACCEPTED` (pour inviter d'autres `TRANSMITTER`/`LEARNER`).
- **Accepter/refuser/se retirer sa propre participation** : exclusivement la personne concernée.
- **Retirer un autre participant (`REMOVED`)** : un `TRANSMITTER` `ACCEPTED`, jamais un `LEARNER` sur un autre `LEARNER`, jamais l'autorité contextuelle.
- **Officialiser le rattachement contextuel** : l'autorité contextuelle réelle du Projet/ZUMRA/Mission — jamais un droit d'accepter à la place d'un participant (§5.A).
- **Démarrer (`ACCEPTED → IN_PROGRESS`)** : tout participant `ACCEPTED`.
- **Déclarer sa part terminée** : tout participant `ACCEPTED`, pour lui-même uniquement.
- **Confirmer la clôture (`COMPLETED_CONFIRMED`)** : tout participant `ACCEPTED`, une fois le quorum de déclarations atteint (§10).
- **Valider par le contexte (`COMPLETED_BY_CONTEXT`)** : l'autorité contextuelle réelle, uniquement si un rattachement `PROJECT`/`ZUMRA`/`MISSION` existe et que la Transmission est `IN_PROGRESS`.
- **Arrêter (`ENDED`)** : tout participant `ACCEPTED`.
- **Annuler (`CANCELLED`)** : l'initiateur ou un `TRANSMITTER` `ACCEPTED`, avant `IN_PROGRESS`.
- **Gérer les jalons, ajouter une contribution** : tout participant `ACCEPTED`.

Identité toujours via GAMAD Core, jamais un garde Laravel local. Toute désignation d'un tiers dans l'UI passe par une `discovery_reference` consentie — jamais une saisie ou un affichage direct de `core_identity_reference`.

## 19. Fil unique

Un seul Fil DG Afrique. Événements Transmission éligibles :

```text
TRANSMISSION_PROPOSED     -- uniquement si rattachée à un contexte visible (ZUMRA/Projet/Mission)
TRANSMISSION_ACCEPTED
TRANSMISSION_COMPLETED    -- COMPLETED_CONFIRMED ou COMPLETED_BY_CONTEXT
```

Pas de Fil Transmission autonome. `ActivityFeedService` gagne une méthode privée `transmissionItems()` suivant le patron `missionItems()`, repliée dans les buckets existants selon le `context_type` porté.

## 20. Mon espace

Route globale : `GET /transmissions`.

Sections : **Propositions reçues** (à répondre), **Mes demandes** (envoyées, en attente), **En cours — je transmets**, **En cours — j'apprends**, **Terminées**.

`MemberSpaceController::priority()` gagne une prochaine action Transmission (proposition/invitation reçue à traiter, part à déclarer terminée, clôture en attente de confirmation), injectée dans la même chaîne if/elseif qu'aujourd'hui, jamais un second système de priorité. Pas de mur de statistiques.

## 21. Notifications, commentaires, partage, messagerie, IA

- **CAP-054** : aucune primitive Notification n'existe. Pas de second système parallèle ; ce qui exige attention reste visible via Mon espace/Fil.
- **CAP-021** : `ContextComment` gagne `CONTEXT_TRANSMISSION`, même patron que `CONTEXT_MISSION`.
- **CAP-022** : `ContextShare` gagne `SOURCE_TRANSMISSION`, même patron que `SOURCE_MISSION`.
- **CAP-020** : `MessageConversation` gagne `CONTEXT_TRANSMISSION`. La conversation n'est jamais la source de vérité du statut.
- **IA** : peut aider à formuler un objectif d'apprentissage, suggérer des jalons raisonnables, expliquer une recommandation de matching, préparer un résumé de clôture proposé (jamais imposé). Elle ne décide jamais à la place des personnes, ne note jamais une transmission, ne choisit jamais qui doit apprendre de qui.

## 22. Audit

Toute transition d'état et toute mutation de participation s'écrit dans `dg_transmission_events` (append-only), même patron que `dg_mission_events`. Chaque ligne porte l'acteur réel — aucun acteur système en v1.

## 23. États UX obligatoires

- état vide honnête sur `/transmissions` si rien n'existe encore ;
- jamais de donnée fictive ;
- badges de statut cohérents avec le système déjà établi (`x-dg.badge` : `decision` pour PROPOSED, `action` pour ACCEPTED/IN_PROGRESS, `project` pour COMPLETED_CONFIRMED/COMPLETED_BY_CONTEXT, `neutral` pour DECLINED/WITHDRAWN/ENDED/CANCELLED) ;
- la vue reçoit les mêmes indicateurs de permission calculés côté service (`canRespond`, `canOfficializeContext`, `canStart`, `canDeclareDone`, `canConfirmCompletion`, `canValidateByContext`, `canEnd`, `canCancel`) plutôt que de dupliquer la logique d'autorité côté gabarit.

## 24. Hors périmètre (v1)

- calendrier de disponibilité structuré (attend CAP-025) ;
- notifications poussées (attend CAP-054) ;
- certification, badge, diplôme, niveau calculé ;
- hébergement de contenu pédagogique ;
- paiement de la transmission ;
- catalogue public de « formateurs » consultable hors relation.

## 25. Definition of Done (fiche)

- [x] statut READY FOR IMPLEMENTATION ;
- [x] 5 décisions métier intégrées explicitement ;
- [x] doctrine produit et chaîne produit citées ;
- [x] modèle de données, machine d'état, permissions, intégrations couverts sans zone grise bloquante ;
- [x] hors périmètre explicite anti-LMS.

Implémentation autorisée sur la base de ce document.
