# ROADMAP MÉTIER CANONIQUE — DG Afrique

> **Périmètre V1 actif :** la classification signée par `ENGINE-TRUTH-FINAL-001` est tenue dans
> `docs/capacites/CAPABILITY-COVERAGE.md`. Les snapshots chiffrés historiques ci-dessous restent
> des journaux d'audit et ne remplacent pas le registre canonique courant.

Statut : ACTIVE

**Snapshot canonique actuel : ROADMAP-003** (voir section dédiée ci-dessous). AUDIT-CAP-002/ROADMAP-001/ROADMAP-002 restent conservés plus bas comme historique explicitement daté — ils ne portent plus la priorité active.

Baseline du snapshot canonique actuel :
main @ 52301a3e8635de2be4adcec25825cacbfb6e5ae8

Baseline d'origine (historique) :
main @ cd01a8e7b4da26cd7e63a1d5fda0fea2737ef682 (AUDIT-CAP-002)

## Règle de gouvernance

Cette roadmap constitue la feuille de route métier active de DG Afrique.

Elle ne remplace jamais la vérité exécutable du dépôt.

Ordre d'autorité :

1. Code + migrations + tests
2. Invariants métier actifs
3. CAPABILITY-COVERAGE.md
4. ROADMAP-METIER-CANONIQUE.md

Toute IA travaillant sur les implémentations métier DG Afrique doit consulter cette roadmap avant de proposer ou commencer une nouvelle CAP.

Une CAP ne doit jamais être sélectionnée uniquement à partir :

- de son numéro ;
- d'un ancien ordre historique ;
- de son statut documentaire ;
- d'une interface existante ;
- d'un ancien plan d'implémentation.

Le code réel et les dépendances réelles priment.

## Roadmap ≠ vérité éternelle

Cette roadmap est un SNAPSHOT CANONIQUE du graphe métier à une baseline donnée.

Elle doit être réévaluée après toute évolution structurelle susceptible de modifier :

- les dépendances ;
- le statut réel d'une CAP ;
- les domaines fondamentaux ;
- les blocages externes ;
- l'ordre des priorités.

Ne jamais conserver artificiellement une priorité devenue fausse.

---

## ROADMAP-003 — Snapshot canonique actuel

**Statut : ACTIF — remplace ROADMAP-001/ROADMAP-002 comme référence de priorité.** Réaudit complet du graphe métier depuis le code réel, déclenché après la livraison de ZUMRA-COMP-001, CAP-061, CAP-062 et CAP-063. Baseline : `main @ 52301a3e8635de2be4adcec25825cacbfb6e5ae8`. Validé par le produit avant canonisation.

### Chiffres canoniques

Avant correction du statut CAP-082 (état constaté à l'ouverture de l'audit) :

| Statut | Nombre |
|---|---|
| TOTAL CAP | 84 |
| CLOSED | 57 |
| PARTIAL | 5 |
| NOT_IMPLEMENTED | 5 |
| DEPENDENCY_BLOCKED | 3 |
| DOC_ONLY | 14 |

**Chiffres canoniques après correction CAP-082 et fermeture CAP-067 (voir « CAP gelées » ci-dessous) — état courant de `CAPABILITY-COVERAGE.md` :**

| Statut | Nombre |
|---|---|
| TOTAL CAP | 84 |
| CLOSED | 58 |
| PARTIAL | 5 |
| NOT_IMPLEMENTED | 4 |
| DEPENDENCY_BLOCKED | 2 |
| DOC_ONLY | 15 |

PARTIAL : CAP-023, CAP-047, CAP-051, CAP-053, CAP-056.
DEPENDENCY_BLOCKED : CAP-070, CAP-079.
NOT_IMPLEMENTED (après correction CAP-082) : CAP-068, CAP-077, CAP-078, CAP-080.

### Correction CAP-082

`CAP-082` (« Différence avec LinkedIn ») était documentée `NOT_IMPLEMENTED` alors que ses sœurs doctrinales strictement identiques par formulation (`CAP-081` « Différence avec un réseau social classique », `CAP-083` « Différence avec une plateforme d'incubation ») sont `DOC_ONLY`. Incohérence déjà signalée par AUDIT-CAP-002/ROADMAP-001, jamais corrigée. ROADMAP-003 confirme après nouvelle inspection : contenu purement positionnel/doctrinal, rien de codable. **Corrigée dans `docs/capacites/CAPABILITY-COVERAGE.md` : `NOT_IMPLEMENTED` → `DOC_ONLY`.** Les chiffres canoniques ci-dessus intègrent cette correction.

### Réaudit des CAP restantes non financières

- **CAP-053 (Consentement)** — reste `PARTIAL`. Audit approfondi : les 4 champs (`orientation_consent`, `discovery_consent`, `matching_consent`, `collective_capability_consent`) sont chacun lus de façon cohérente dans 8+ domaines, individuellement révocables et horodatés (`_consented_at` effacé à la révocation, vérifié dans `MemberProfileController`). **L'absence de modèle unifié n'est pas un bug constaté — les consentements existants sont des faits de granularités légitimement différentes** (profil, capacité, appartenance ZUMRA). Aucun `ConsentService` générique recommandé sans problème réel démontré.
- **CAP-068 (Événement)** — reste `NOT_IMPLEMENTED`, marquée **DOCTRINE-À-CLARIFIER**. Les 10 modèles `*Event` du dépôt sont des journaux d'audit techniques, jamais l'événement humain planifiable (atelier/rencontre) que le nom suggère. Aucune occurrence de « événement » dans `ZUMRA-DOCTRINE-INVARIANTE.md` en dehors du seul titre d'index — aucun contrat doctrinal à auditer. Ne pas coder tant que ce contrat n'est pas défini.
- **CAP-023 (Graphe des capacités)** — reste `PARTIAL` par design. 5 moteurs de correspondance (`PersonRecommendationEngine`, `OpportunityEngine`, `MissionMatchingEngine`, `ProjectMatchingEngine`, `TransmissionMatchingEngine`) fonctionnent et sont fermés sous leurs propres CAP. Aucune preuve qu'une structure de graphe généralisée résout un problème réel — ne pas créer de `GraphEngine` par principe architectural.
- **CAP-056 (Publication)** — reste `PARTIAL`. `published_at` n'existe réellement que sur `Need`/`ZumraCharter` ; `Project`/`Proof`/`Transmission` gèrent leur propre cycle de révélation sous d'autres noms (statut, visibilité) avec un effet équivalent. Probablement couvert par les cycles propres de chaque domaine — aucune implémentation recommandée actuellement.
- **CAP-047 (Satellite extractible)** — reste `PARTIAL` par design. GamaDrive demeure le seul satellite réel, déjà fédéré (CAP-048/049/050, `CLOSED`). Aucun nouveau candidat réel à l'extraction découvert — aucune abstraction supplémentaire recommandée.
- **CAP-051 (Portabilité de l'identité)** — reste `PARTIAL`. `FederationServiceProvider` ne fournit que la continuité SSO (`FederatedProductGateway`) ; aucune capacité d'export de données découverte nulle part dans le dépôt. SSO ≠ export. Aucun contrat GAMAD Core nouveau ne comble ce manque — reste dépendant d'un contrat produit/Core absent, non contourné localement.
- **CAP-077 → CAP-078 → CAP-079** — restent non prioritaires. Aucune infrastructure de consommateur externe (aucun système de clé API, aucune route API publique de capacités) découverte. Ne pas construire une API abstraite pour elle-même.
- **CAP-080 (Ce que devrait mesurer DG Afrique)** — reste `NOT_IMPLEMENTED`, marquée **DOCTRINE-À-CLARIFIER**. Aucune infrastructure de métriques/analytics nulle part dans le dépôt ; aucune doctrine ne définit quoi mesurer. Coder des métriques maintenant fabriquerait exactement les vanity metrics que la doctrine « réseau d'action » proscrit (DG Afrique doit mesurer l'action réelle — résultats/capacités/coordination —, jamais likes/temps passé/vues).
- **CAP-067 — dégelée et fermée (22 août 2026).** `GamadCoreClient` porte désormais `provisionOrganizationIdentity()`/`createOrganization()`/`resolveOrganizationByIdentity()`, consommant la délégation CORE-ORG-DELEGATION-001 (`gamad-core`). Voir `docs/capacites/specs/CAP-067-identite-organisationnelle.md`.
- **CAP-070** — reste gelée. `config/federation.php` ne porte que le callback SSO GamaDrive, aucune API de stockage documentaire. Aucun contournement local.

### Fissure fondationnelle découverte — Modération, discipline et recours (art. 19)

**Constat central de ROADMAP-003.** L'art. 19 de `ZUMRA-DOCTRINE-INVARIANTE.md` (« Modération, discipline et recours ») définit une capacité métier réelle et détaillée :

- trois niveaux d'autorité : (1) l'auteur, pour son contenu ordinaire ; (2) les responsables, pour les espaces internes de la ZUMRA ; (3) DG Afrique ou GAMAD, pour la Charte générale et les risques transversaux ;
- une réponse proportionnée : masquage préventif, explication, avertissement, limitation, suspension, révocation ;
- une exigence de traçabilité de toute décision disciplinaire (motif, autorité, date, éléments concernés, durée éventuelle, voie de recours) ;
- un droit qu'une ZUMRA ne peut jamais empêcher : signaler directement un abus à GAMAD.

Elle constitue également un **invariant absolu** via l'art. 23.1 : « le droit au signalement, à l'explication et au recours ».

**Vérifié depuis le code : cette capacité n'est aujourd'hui construite nulle part.** `ContextCommentService` ne porte aucune méthode de masquage/retrait ; aucun contrôleur d'administration ne permet à `PortalAdministrator` de modérer un commentaire ou un message ; aucune trace de signalement structuré. Un seul palliatif générique existe (`MessagingService::openSupport()`, une conversation support DG Afrique), qui satisfait partiellement et non-structurellement le seul droit au signalement — rien ne couvre le masquage préventif, la sanction individuelle distincte de la suspension du groupe entier (déjà réelle via ZUMRA-COMP-001), ni la traçabilité formelle exigée par l'art. 19.

**Recherche exhaustive dans `docs/capacites/CAPABILITY-INDEX.md` : aucun numéro CAP ne porte ce sujet.** Ni CAP-052 (Séparation des contextes), ni CAP-053 (Consentement), ni CAP-060 (Réputation) ne couvrent la modération de contenu ou la discipline individuelle — c'est un trou dans le référentiel lui-même, distinct de toute divergence documentaire déjà connue.

### Pourquoi aucun numéro CAP n'est créé ici

`docs/AI-RULES.md:82` fixe explicitement la règle : « vérifier également que les identifiants vont exactement de `CAP-001` à `CAP-084`, une seule fois chacun » — et le garde-fou mécanique DOC-001 (`docs/AI-RULES.md:74-76`) fait échouer toute PR où `CAPABILITY-INDEX.md`/`CAPABILITY-COVERAGE.md` ne comptent pas exactement 84 entrées. **Aucune règle canonique n'autorise l'ajout d'un CAP-085.** `docs/capacites/OVERRIDES.md` permet des décisions explicites qui précisent le référentiel (OVR-001 à OVR-006), mais aucune de ces décisions n'a jamais étendu le compte de 84 CAP — le mécanisme sert à clarifier, pas à numéroter. En conséquence, cette capacité est documentée sous un identifiant de gap non-CAP, à l'identique du précédent `ZUMRA-COMP-001` :

**MODERATION-COMP-001 — Modération, discipline et recours (art. 19)**

Ce n'est pas une CAP officielle. Toute décision d'attribuer un jour un numéro CAP officiel (par exemple via un futur OVERRIDES.md ou une extension explicitement décidée du référentiel à plus de 84 entrées) reste hors du périmètre de ce chantier documentaire et devra être tranchée explicitement, séparément.

### Graphe canonique (ROADMAP-003)

```
MODERATION-COMP-001 (art. 19, PAS DE CAP) ──HARD──▶ aucun blocage technique
   (Messaging, ContextComment, ZumraGroupMembership, PortalAdministrator existent déjà —
   seule l'implémentation manque ; orthogonale au reste du graphe, ne débloque aucune autre CAP,
   mais ferme un invariant absolu actif)

CAP-053 / CAP-023 / CAP-056 / CAP-047 / CAP-051 ──NONE/PRODUCT──▶ aucune dépendance,
   aucun levier, confirmées non-problèmes fonctionnels ou bloquées par un manque de contrat externe

CAP-077 → CAP-078 → CAP-079 ──PRODUCT──▶ bloquée par absence de consommateur externe, inchangé

CAP-067 / CAP-070 ──EXTERNAL──▶ gelées, inchangé

CAP-082 ──NONE──▶ correction documentaire appliquée (NOT_IMPLEMENTED → DOC_ONLY)
```

**Constat clé, confirmé : aucun goulot d'étranglement CAP majeur. Backlog essentiellement plat.** La fissure de modération est orthogonale — elle ne débloque aucune autre CAP — mais ferme un invariant doctrinal absolu resté silencieusement inerte depuis l'origine du référentiel, exactement le type de dette que ce chantier traite en priorité par principe de gouvernance plutôt que par dépendance technique (précédent direct : ZUMRA-COMP-001).

### Priorité canonique #1 — ROADMAP-003

**Audit Phase A de MODERATION-COMP-001 (Modération, discipline et recours, art. 19).** Pas encore son implémentation.

Cette priorité passe devant CAP-053, CAP-068, CAP-023, CAP-051, CAP-056, CAP-047, CAP-077/078/079 et CAP-080, parce que :

- c'est un invariant doctrinal absolu (art. 23.1) actuellement non honoré ;
- valeur utilisateur réelle et directe : sécurité, confiance, recours ;
- aucune dépendance externe ni blocage produit non résolu ;
- l'infrastructure porteuse existe déjà entièrement (`ContextComment`, `MessagingService`, `ZumraGroupService`, `PortalAdministrator`) ;
- la doctrine (art. 19) est suffisamment précise pour être auditée immédiatement — contrairement à CAP-068/CAP-080, le produit n'a pas besoin d'être inventé.

### Dettes non-CAP réauditées

Préservées à l'identique (voir section dédiée plus bas), avec une précision : `MissionReviewFixesTest::test_invitation_never_fabricates_context_access` était un vrai bug de robustesse (validation UUID manquante avant requête) — **fermé par REF-MISSION-UUID-001** (voir « Dettes non-CAP » plus bas). Ce n'était pas une CAP incomplète et ne le devient pas rétroactivement : aucun impact sur CAP-069.

### MODERATION-COMP-001 — livrée (Phase A + Phase B, 2026-09-22)

**La priorité canonique #1 de ROADMAP-003 est désormais close.** Phase A (audit) validée puis Phase B (implémentation V1) livrée : architecture HYBRIDE C→B (discipline ZUMRA réutilisant `ZumraGroupMembership`/`ZumraGroupService`/`ZumraGroupEvent`, `ModerationReport` transversal, `ModerationDecision` vivante, masquage local `ContextComment`/`MessageEntry`). Détail complet, invariants rendus exécutables, frontières vérifiées et preuve runtime : voir `docs/roadmap/MODERATION-COMP-001.md`. **Aucun numéro CAP créé** — le référentiel reste figé à CAP-001–CAP-084 ; `CAPABILITY-INDEX.md`/`CAPABILITY-COVERAGE.md` ne sont pas modifiés par ce chantier.

L'invariant doctrinal absolu de l'art. 23.1 (« droit au signalement, à l'explication et au recours ») qui motivait cette priorité est désormais réellement honoré et testé (`tests/Feature/ModerationTest.php`, 57 cas).

---

## Historique — AUDIT-CAP-002 / ROADMAP-001 / ROADMAP-002 (superseded by ROADMAP-003)

**Ce qui suit est un historique conservé pour mémoire. Il ne porte plus la priorité active — voir « ROADMAP-003 — Snapshot canonique actuel » ci-dessus pour l'état courant.**

Ce qui suit reproduit fidèlement le rapport AUDIT-CAP-002 validé (recalcul complet du backlog métier depuis le code réel, baseline `cd01a8e`). Aucune réinterprétation n'a été effectuée pendant ROADMAP-001 ; seule la mise en forme documentaire a été adaptée.

### Livrable 1 — État global

| Statut | Nombre |
|---|---|
| TOTAL CAP | 84 |
| CLOSED | 54 |
| PARTIAL | 5 |
| NOT_IMPLEMENTED | 7 |
| DEPENDENCY_BLOCKED | 4 |
| DOC_ONLY (non codable par nature) | 14 |

Cohérence registre ↔ code : globalement bonne. Trois écarts réels détectés (voir « Incohérences » ci-dessous), dont un sérieux (CAP-011 / ZUMRA).

### Livrable 2 — Table des CAP restantes (16 lignes codables non-CLOSED)

| CAP | Nom | Statut réel | Domaine | Ce qui existe | Ce qui manque | Blocage | Impact | Levier | Risque | Taille |
|---|---|---|---|---|---|---|---|---|---|---|
| — | **ZUMRA — cycle de vie & validation** *(gap sous CAP-011, CLOSED)* | **Incohérence à l'origine — ZUMRA-COMP-001 Phase B en revue** | ZUMRA | 7 états déclarés (`ZumraGroup::STATE_*`), création, membres, rôles, messagerie, partage | Constat initial (AUDIT-CAP-002) : aucun code ne faisait jamais transitionner un groupe hors de `CONSTITUTING` ; validation auto (7 critères doctrinaux — *correction post-audit : la roadmap indiquait par erreur « 6 critères », l'art. 10 en liste bien 7*) jamais lue ; `max_simultaneous_founder_roles=3` jamais appliqué ; modération 3 niveaux absente. **Corrigé par ZUMRA-COMP-001 Phase B** (branche `fix/zumra-comp-001-lifecycle-validation`, PR en revue) pour le cycle de vie, la readiness structurelle et la limite de rôles fondateurs — la modération à 3 niveaux reste hors périmètre, non traitée. | Interne (rien d'externe) | CRITIQUE | 0 (isolé mais central) | ÉLEVÉ | M |
| CAP-053 | Consentement | PARTIAL | Trust & Consent | `orientation_consent`, `discovery_consent`, `matching_consent`, `collective_capability_consent` — 4 champs indépendants, par domaine | Aucun modèle unifié de consentement/retrait/audit cross-domaine | Interne | ÉLEVÉ | 3+ (matching, discovery, partenariats) | MOYEN | M |
| CAP-061 | Contributions financières | **CLOSED — livrée par CAP-061 Phase B** *(était NOT_IMPLEMENTED lors de l'audit ROADMAP-001/002)* | Finance | `GeniusPayClient` prouvé en prod (CAP-007B, adhésion ZUMRA) — généralisé (`createContributionPayment()`) sans rien casser de CAP-007B | — | Aucun | MOYEN | 2 (CAP-062, CAP-063 — non déclenchés par ce chantier, périmètre volontairement non ouvert) | MOYEN | M |
| CAP-062 | Ledger / traçabilité | **CLOSED — livrée par CAP-062 Phase B** *(était NOT_IMPLEMENTED lors de l'audit ROADMAP-001/002)* | Finance | `LedgerEntry`/`LedgerService`, journal simple immuable posté depuis CAP-061 et CAP-007B, backfill idempotent | — | Aucun | MOYEN | 1 (CAP-063 — non déclenché par ce chantier, lien resté « soft ») | FAIBLE | M |
| CAP-063 | Financement de projet | **CLOSED — livrée par CAP-063 Phase B, V1 strictement déclarative** *(était documentée `DEPENDENCY_BLOCKED`, aucun blocage technique réel — voir section dédiée ci-dessous)* | Finance/Projects | `ProjectFunding`/`ProjectFundingService`, réutilise `ProjectAuthority` | — | Aucun | MOYEN | 0 | FAIBLE | S |
| CAP-023 | Graphe des capacités | PARTIAL | Capabilities & Intelligence | 5 moteurs de correspondance séparés (`PersonRecommendationEngine`, `OpportunityEngine`, `MissionMatchingEngine`, `ProjectMatchingEngine`, `TransmissionMatchingEngine`) | Aucune structure de graphe unifiée/navigable | Aucun | FAIBLE | 0 | FAIBLE | M |
| CAP-051 | Portabilité de l'identité | PARTIAL | Identity Federation | Continuité SSO fédérée vers satellites (`FederationServiceProvider`) | Export réel des données côté DG Afrique | Produit (contrat Core non défini) | FAIBLE | 0 | FAIBLE | S |
| CAP-056 | Publication | PARTIAL | Social & Coordination | `published_at` sur `Need` et `ZumraCharter` uniquement ; Project/Proof gèrent leur propre statut sans notion de « publication » | Concept unifié — ou clarification que chaque domaine gère légitimement le sien | Aucun (possiblement un problème de nommage, pas de code manquant) | FAIBLE | 0 | FAIBLE | S |
| CAP-047 | Satellite extractible | PARTIAL *(par design)* | Specialized Tools | Registre, passerelle fédérée, primitives de continuité | Frontières propres outil par outil — volontairement pas anticipé | Produit (aucun outil ne justifie encore l'extraction) | FAIBLE | 0 | FAIBLE | — |
| CAP-068 | Événement | NOT_IMPLEMENTED | Operations | Rien — les `*Event` du dépôt (`ProjectEvent`, `NeedEvent`…) sont des journaux d'audit internes, **pas** la capacité métier CAP-068 (événement réel type atelier/rencontre) | Modèle Événement réel organisable par ZUMRA/Organisation | Aucun | FAIBLE | 0 | FAIBLE | M |
| CAP-077 | API de capacités | NOT_IMPLEMENTED | Capabilities API | Rien | Contrat externe de capacités | Produit (aucun consommateur externe réel aujourd'hui) | FAIBLE | 1 (CAP-078) | FAIBLE | L |
| CAP-078 | Satellite fournisseur de capacité | NOT_IMPLEMENTED | Ecosystem | Registre CAP-048/049/050 | Contrat capacité↔outil autonome | **Interne** (CAP-077) | FAIBLE | 1 (CAP-079) | FAIBLE | L |
| CAP-079 | Boucle d'écosystème | DEPENDENCY_BLOCKED | Ecosystem | Passerelle fédérée | Remontée canonique de capacités | **Interne** (CAP-078) | FAIBLE | 0 | FAIBLE | L |
| CAP-080 | Ce que devrait mesurer DG Afrique | NOT_IMPLEMENTED | Ecosystem | Rien | Décision produit sur les métriques | Produit | FAIBLE | 0 | FAIBLE | S |
| CAP-082 | Différence avec LinkedIn | NOT_IMPLEMENTED *(probable erreur de statut — devrait être DOC_ONLY comme 081/083, non corrigée pendant ROADMAP-001 — **corrigée vers `DOC_ONLY` par ROADMAP-003**, voir section dédiée)* | Ecosystem | Contenu doctrinal seulement | Rien de codable | — | — | — | — | — |
| CAP-067 | Identité organisationnelle | DEPENDENCY_BLOCKED | Organizations | `Organization` fonctionne via `founder_core_reference` + membres réels | Une Organisation ne peut ni s'authentifier ni détenir de `CapabilityStatement` propre | **Externe** (GAMAD Core n'émet que `type: "personne"`) | FAIBLE (ne bloque plus rien d'ouvert : CAP-065/066 déjà fermées en le contournant honnêtement) | 0 | FAIBLE | — |
| CAP-070 | Document | DEPENDENCY_BLOCKED | Documents | Proof/Transmission/Mission couvrent déjà preuve/référence via texte libre ou URL | API de stockage GamaDrive | **Externe** (GamaDrive n'a que la continuité SSO, aucune API de stockage) | FAIBLE (besoin déjà substantiellement couvert par doctrine) | 0 | FAIBLE | — |

### Livrable 3 — Graphe réel des dépendances

```
CAP-077 (API capacités, produit)
   │
   ▼
CAP-078 (satellite fournisseur de capacité)
   │
   ▼
CAP-079 (boucle d'écosystème)

CAP-062 (ledger) ──soft──▶ CAP-061 (contributions) ──soft──▶ CAP-063 (financement projet)
   (aucun lien dur : une V1 déclarative de CAP-063 ne requiert ni 061 ni 062)

CAP-067 (identité org., bloqué EXTERNE) ──▶ ne débloque plus rien d'ouvert
   (CAP-065/066 déjà fermées en contournant honnêtement ce manque)

CAP-070 (document, bloqué EXTERNE) ──▶ isolé, besoin déjà couvert par Proof/Transmission

ZUMRA — cycle de vie & validation ──▶ isolé dans le graphe CAP,
   mais transverse en pratique (Messagerie, Partage, Commentaire, Missions ZUMRA
   s'appuient tous sur l'état `state` qui ne progresse jamais)

CAP-023 / CAP-051 / CAP-056 / CAP-053 / CAP-047 ──▶ aucune dépendance, aucun levier
   (raffinements isolés sur un socle déjà fermé)
```

Constat clé : aucune CAP restante ne débloque plus de 1-2 autres CAP. Le backlog restant est majoritairement plat/feuille, pas un goulot d'étranglement.

### Livrable 4 — Top 10 priorités identifiées par l'audit

| # | CAP | Pourquoi maintenant | Débloque | Risque si retardé |
|---|---|---|---|---|
| 1 | **ZUMRA — cycle de vie & validation** | Fondation doctrinale centrale silencieusement inerte ; risque d'intégrité réel (rôles fondateurs illimités, aucune ZUMRA jamais « validée ») | Rend la doctrine ZUMRA enfin réelle | Le cœur social de DG Afrique reste une coquille d'état jamais appliquée — la confiance produit repose sur une promesse non tenue |
| 2 | CAP-061 Contributions financières | Fondation prouvée en prod (GeniusPay), motif économique clair | CAP-063, CAP-062 | L'économie du réseau reste à zéro même quand le produit sera prêt à l'ouvrir |
| 3 | CAP-062 Ledger / traçabilité | Faible, sûr, prérequis logique de toute traçabilité financière | CAP-061/063 plus solides | Toute contribution future restera non auditable |
| 4 | CAP-063 Financement de projet | Socle relationnel déjà en place (Project/Organization/Partnership) ; corriger d'abord le statut documentaire faux | Rien techniquement, mais aligne doc/réalité | Doctrine floue entretenue (« bloqué » alors que c'est un choix produit) |
| 5 | CAP-053 Consentement | Fondation trust transverse, 4 implémentations divergentes déjà en prod à unifier avant qu'une 5e n'apparaisse | Cohérence future de CAP-009/010/030/065 | Divergence croissante à chaque nouveau CAP touchant au consentement |
| 6 | CAP-068 Événement | Capacité ZUMRA/Organisation manquante et autonome, sans dépendance | Coordination collective concrète | Aucun risque fort, mais lacune fonctionnelle visible |
| 7 | CAP-023 Graphe des capacités | Intelligence sur un socle déjà fermé, faible risque | Rien de bloquant | Faible — reste optionnel |
| 8 | CAP-051 Portabilité identité | Petit, borné, clarifie une promesse doctrinale | Rien de bloquant | Faible |
| 9 | CAP-056 Publication | Probable clarification documentaire plus que code | Rien | Faible |
| 10 | CAP-077 API de capacités | Nécessaire seulement si un vrai consommateur externe apparaît | CAP-078→079 | Aucun aujourd'hui — pas de consommateur réel |

### Incohérences détectées (préservées telles quelles)

1. **Statut documentaire faux — CAP-063.** Documentée `DEPENDENCY_BLOCKED`, aucune dépendance technique réelle trouvée dans tout le dépôt. C'est un choix produit assumé (CAP-014 interdit explicitement le paiement dans Projet), pas un blocage. Devrait être `NOT_IMPLEMENTED`. **Non corrigé dans `CAPABILITY-COVERAGE.md` pendant ROADMAP-001** — voir section dédiée ci-dessous.
2. **CAP fermée mais fonctionnalité partielle — CAP-011 (ZUMRA/Groupe humain).** Le cycle de vie complet, la validation automatique et la modération n'existent pas en code, alors que CAP-011 est `CLOSED`. La fonction reste opérationnelle (les portes vérifient uniquement `!== SUSPENDED`) mais la doctrine promise n'est pas tenue. Voir « Fissure fondationnelle ZUMRA » ci-dessous.
3. **Classification suspecte — CAP-082** (« Différence avec LinkedIn ») étiquetée `NOT_IMPLEMENTED` alors que ses sœurs doctrinales identiques (CAP-081, CAP-083) sont `DOC_ONLY`. Contenu purement positionnel, rien de codable trouvé. **Non corrigé pendant ROADMAP-001** — ce chantier est documentaire/gouvernance, pas DOC-002. **Corrigé par ROADMAP-003** : `CAPABILITY-COVERAGE.md` porte désormais `DOC_ONLY`.
4. **Documentation incomplète — CAP-070.** Contrairement à CAP-036/067 qui ont chacune une section justificative dédiée dans `CAPABILITY-COVERAGE.md`, CAP-070 n'en a aucune alors que son blocage (GamaDrive) mériterait la même transparence.
5. **Test révélant une dette technique (pas une CAP incomplète) — `MissionReviewFixesTest::test_invitation_never_fabricates_context_access`.** Bug de robustesse (validation de format UUID manquante avant requête), pas un gap métier. CAP-069 reste réellement CLOSED.
6. **Tests révélant un contenu de démonstration désynchronisé, pas une CAP incomplète — `DesignInvariantsTest`/`ProofHttpSmokeTest`/`TransmissionHttpSmokeTest`.** Dette de contenu/design (DEMO-FIRST), pas un manque métier. Le `DatabaseSeeder` reste intentionnellement vide.

---

## Fissure fondationnelle ZUMRA — ZUMRA-COMP-001

CAP-011 (ZUMRA / Groupe humain) est documentée **CLOSED**, mais sa complétude doctrinale n'est **pas réellement exécutée**.

Le problème concerne notamment :

- le cycle de vie ZUMRA (7 états déclarés dans `ZumraGroup::STATE_*`, doctrine en attend 9) ;
- les transitions depuis `CONSTITUTING` — aucun code ne fait jamais progresser un groupe vers `READY`, `VALIDATED`, `ACTIVE`, `WARNED`, `SUSPENDED` ou `REHABILITATING` ;
- la validation automatique et ses 7 critères doctrinaux (`ZUMRA-DOCTRINE-INVARIANTE.md` art. 10 — *correctif ROADMAP-001 : l'audit initial en comptait 6 par erreur de lecture, l'article en liste bien 7*) — jamais lus, jamais appliqués ;
- le plafond `max_simultaneous_founder_roles = 3` — configuré (`ZumraGroupConfiguration.php`) mais jamais lu ni appliqué nulle part ;
- la modération à 3 niveaux (art. 19) et les états associés (`WARNED`/`SUSPENDED`/`REHABILITATING`) — absents en pratique.

Ceci devient :

**ZUMRA-COMP-001 — Cycle de vie & validation**

Important : **ce n'est pas une nouvelle CAP.** C'est un correctif de complétude de CAP-011, à traiter avec un audit dédié avant tout code (le state `ZumraGroup.state` est lu transversalement par Messagerie, Partage, Commentaire et Missions ZUMRA — toucher au state machine a un rayon d'impact large).

### Trois dimensions distinctes, à ne jamais confondre

- **Adhésion au Programme ZUMRA** (`ZumraProgramMembership.status`, CAP-007) — déjà réelle et fonctionnelle (paiement GeniusPay, PENDING_PAYMENT→ACTIVE→SUSPENDED→CLOSED).
- **Maturité d'une ZUMRA** (`ZumraGroup.maturity`, EMERGING/ESTABLISHED, seuil de 50 membres, art. 9) — déjà réelle et fonctionnelle, recalculée à chaque mouvement de membre. N'a jamais été le problème.
- **Statut opérationnel d'une ZUMRA** (`ZumraGroup.state`, cycle de vie de l'art. 10) — c'est cette seule dimension qui était inerte et que ZUMRA-COMP-001 corrige.

### État d'implémentation

Phase A (audit runtime) et Phase B (implémentation, corrigée en revue) sont terminées : branche `fix/zumra-comp-001-lifecycle-validation`, **mergée dans `main`**. Le cycle `CONSTITUTING → READY → VALIDATED → ACTIVE ⇄ WARNED → SUSPENDED → REHABILITATING → ACTIVE` est implémenté dans `ZumraGroupService`, avec gestion des sièges fondateurs vacants (proposition/acceptation) et application réelle de `max_simultaneous_founder_roles`. `READY` signifie « dossier structurel complet, prêt à être soumis à validation » — jamais « les 7 critères doctrinaux sont tous validés » : `evaluateStructuralReadiness()` ne vérifie que les 6 critères automatisables ; le 7e (contrôles de nom/doublon/risque/usurpation) reste un contrôle de conformité humain, jamais présenté comme satisfait par la seule unicité technique du `slug`, et le contrôle anti-fraude n'est jamais fabriqué. Automatique (`acceptRole` + `auto_validation_enabled=true`) ou manuelle (`markReady()`, autorité DG Afrique/GAMAD, lorsque l'automatisation est désactivée) — le cycle ne devient jamais impossible faute d'automatisation. `VALIDATED` et tout ce qui suit restent des décisions explicites de l'autorité DG Afrique/GAMAD (`PortalAdministrator`) ; `CONSTITUTING → VALIDATED` directement reste toujours impossible. Le réaudit post-merge (ROADMAP-002, audit seul, aucune modification de ce document à l'époque) a confirmé depuis le code que ZUMRA-COMP-001 débloque spécifiquement CAP-061 : avant ce correctif, aucune ZUMRA ne pouvait honnêtement atteindre `VALIDATED`, rendant impossible d'implémenter sans fabrication la condition doctrinale « ZUMRA validée » (art. 6.3) que la contribution collective exige.

---

## CAP-061 — Contributions financières (livrée)

**CLOSED — 2026-09-01.** Branche `feat/cap-061-financial-contributions-v1`, PR draft. Le contrat V1 défini en Phase A (`docs/capacites/specs/CAP-061-contributions-financieres.md`) est intégralement couvert :

- **Contribution individuelle** (art. 6.2) : engagement personnel, 500 XOF/mois par défaut, activation/pause/reprise/arrêt libres, jamais de dette, ni relance automatique.
- **Contribution collective ZUMRA** (art. 6.3) : engagement porté par la ZUMRA (`subject_type=ZUMRA_GROUP`), 2500 XOF/mois par défaut, gouvernance à deux acteurs distincts (`PRIMARY_LEAD` propose, `FINANCE_LEAD` approuve, ou l'inverse — jamais la même identité Core), condition « ZUMRA validée » vérifiée sur les états VALIDATED/ACTIVE/WARNED/REHABILITATING (SUSPENDED bloque uniquement l'initiation d'un nouveau paiement), paiement mensuel initié par tout responsable habilité une fois l'engagement actif.
- **`GeniusPayClient` généralisé** (`createContributionPayment()`) sans aucune régression sur `createMembershipPayment()` (CAP-007B) — les 13 tests `ZumraMembershipPaymentTest` restent verts après le correctif de couplage `payments.membership.enabled` (voir Débogage ci-dessous).
- **Finalités** (art. 6.5) : table réelle versionnée/auditée `dg_contribution_purposes`, 8 codes canoniques seedés (`ECOSYSTEM_SUSTAINABILITY`, `TRAINING`, `NEW_ZUMRA`, `VALIDATED_PROJECTS`, `INFRASTRUCTURE`, `SOLIDARITY`, `EMERGENCY`, `AUTHORIZED_FEES`), retrait sans jamais altérer un paiement déjà réalisé.
- **Réconciliation serveur-à-serveur** identique au motif CAP-007B (le retour navigateur n'est jamais une preuve), reçus immuables et idempotents.
- **Aucun** score/rang/dette/wallet/solde/priorité issu du montant, de la fréquence ou de l'absence de contribution. `ZumraGroup.state`/`.maturity` ne sont jamais modifiés par ce domaine financier.
- **Frontière CAP-062** (ledger) : chaque paiement confirmé porte déjà référence/montant/devise/finalité/période/statut/reçu/payeur/sujet — prêt pour un futur ledger additif, mais aucun ledger n'existe dans cette PR.
- **Frontière CAP-063** (financement de projet) : `VALIDATED_PROJECTS` reste un simple code de destination doctrinal ; aucun Projet n'est jamais financé par ce domaine.

Preuve : `tests/Feature/ContributionTest.php` (45 cas). Voir la spec pour le détail intégral.

---

## CAP-062 — Ledger / traçabilité (livrée)

**CLOSED — 2026-09-08.** Branche `feat/cap-062-financial-ledger-v1`, PR draft. Le contrat V1 défini en Phase A (`docs/capacites/specs/CAP-062-ledger-tracabilite.md`) est intégralement couvert :

- **Journal financier simple, immuable, additif** — une écriture (`LedgerEntry`) par paiement réellement CONFIRMÉ, jamais un wallet, un solde stocké ni un moteur double-entry (aucune sortie d'argent réelle n'existe dans le dépôt, confirmé en Phase A).
- **Deux sources V1** : `CONTRIBUTION_PAYMENT` (CAP-061) et `MEMBERSHIP_PAYMENT` (CAP-007B, adhésion) — posté depuis `ContributionService::reconcile()` et `MembershipPaymentService::reconcile()`, uniquement dans la branche `COMPLETED`, jamais sur `PENDING`/`PROCESSING`/`FAILED`/`CANCELLED` ni sur le seul retour navigateur.
- **`LedgerEntry` est une projection**, jamais une source de vérité : ne modifie jamais un paiement source, une `Contribution`, une `ZumraGroup` ni une `ZumraProgramMembership`.
- **Idempotence absolue** : `UNIQUE(source_type, source_id)` en base, doublée d'une recherche applicative préalable et d'un filet de sécurité sur violation de contrainte — un paiement source ne produit jamais plus d'une écriture, y compris en cas de `reconcile()` concurrent ou de backfill rejoué.
- **Backfill** : commande `ledger:backfill`, déterministe et idempotente, réutilisant exactement le même `LedgerService` que le runtime — rattrape l'historique CAP-061/CAP-007B antérieur au déploiement sans jamais dupliquer une écriture déjà postée par `reconcile()`.
- **Autorisations réutilisées** : personne = ses propres écritures, responsable ZUMRA habilité (`isLeader()`, déjà l'autorité de CAP-061) = écritures de sa ZUMRA, `PortalAdministrator` = ledger global. Aucune nouvelle matrice de permissions.
- **Frontière CAP-063** : aucun financement de Projet, aucun wallet/escrow/compte Projet — CAP-063 reste entièrement séparée, non implémentée.
- **Frontière GAMAD Core** (CAP-067) : la doctrine (art. 3.2) évoque des écritures financières portées/attestées par GAMAD Core, mais aucun runtime correspondant n'existe (`GamadCoreClient` ne porte aucune méthode financière) — CAP-062 V1 reste entièrement locale à DG Afrique, documenté sans être comblé par un hack.

Preuve : `tests/Feature/LedgerTest.php` (30 cas). Voir la spec pour le détail intégral.

---

## CAP-063 — Financement de projet (livrée)

**CLOSED — 2026-09-15.** Branche `feat/cap-063-project-funding-declaration-v1`, PR draft. Décision produit : **V1 strictement déclarative** — le contrat ainsi redéfini (`docs/capacites/specs/CAP-063-financement-projet.md`) est intégralement couvert :

- **Aucun mouvement d'argent.** `ProjectFunding` décrit un besoin financier déclaré (montant cible, devise, justification, usage prévu) — jamais un paiement, une collecte, un décaissement, un wallet ou un escrow. Justifié par l'art. 15.3 : un paiement réel exige budget/gouvernance-financière/règles-de-décaissement, trois préconditions absentes du runtime (confirmé en Phase A — aucune capacité de décaissement n'existe nulle part dans le dépôt).
- **`Need` non étendu, nouvel objet `ProjectFunding`** : `Need` n'a aucun champ numérique/devise et sa sémantique qualitative aurait été polluée ; `CAP-013-besoin.md` exclut déjà explicitement le financement de son périmètre.
- **Cycle réduit** `OPEN → CLOSED`/`CANCELLED` (pas de `DRAFT`/`FUNDED`/`PAID`/`COLLECTED`/`DISBURSED`) : la création exige déjà `ProjectAuthority::canDecide`, aucun palier de proposition n'a de justification.
- **Éligibilité** : seuls les Projets `ADOPTED`/`IN_PROGRESS` peuvent déclarer (précondition doctrinale « adoption ») ; `PROPOSED`/`ARCHIVED`/`COMPLETED` refusés.
- **Autorité réutilisée intégralement** : `ProjectAuthority::canDecide`/`canView`, y compris `isLeader()` pour un Projet ZUMRA — **la double approbation `PRIMARY_LEAD`+`FINANCE_LEAD` de CAP-061 n'est pas copiée** (aucun mouvement d'argent ne la justifie ici).
- **Frontières CAP-061/CAP-062/GeniusPay strictement respectées** : aucune modification de `Contribution`/`ContributionPayment`, aucune `LedgerEntry`, aucun appel `GeniusPayClient` (vérifié par test, `Http::assertNothingSent()`).
- **Aucune mutation de propriété** : `Project.owner_*`, équipe, `Organization`, `Partnership`, rôles ZUMRA jamais modifiés par une déclaration financière.

Preuve : `tests/Feature/ProjectFundingTest.php` (31 cas). Voir la spec pour le détail intégral.

---

## CAP-068 — Événement (livrée)

**CLOSED — 2026-09-29.** Branche `feat/cap-068-community-event`, PR draft. Était `NOT_IMPLEMENTED`/`DOCTRINE-À-CLARIFIER` (ROADMAP-003) : le corpus ne définissait « Événement » qu'au titre d'index. Clarification doctrinale validée : `docs/capacites/specs/MISSIONS.md:37` cite « Événement » comme objet métier autonome pair de Besoin/Projet/Transmission/Preuve/Document, jamais synonyme des journaux techniques `*Event` — hypothèse inverse falsifiée par cette même citation.

Définition canonique validée : Mission = action à accomplir avec responsabilité ; Événement = rencontre située dans le temps à laquelle on participe. Décision produit V1 : organisateur `ZUMRA_GROUP`/`ORGANIZATION` uniquement, participation = inscription légère (`CommunityEventParticipant`, jamais `MissionAssignment`), visibilité `INTERNAL`/`PUBLIC`, aucune récurrence, aucun calendrier complexe, aucun matching, aucune finance, aucun score, aucune émargement de présence. Nommé `CommunityEvent` (jamais `Event`) pour éviter toute collision avec les 10 journaux append-only `*Event` existants. Autorité intégralement réutilisée : `ZumraGroupService::isLeader()` / `OrganizationService::isManager()`/`canView()` — aucune matrice nouvelle.

Preuve : `tests/Feature/CommunityEventTest.php` (22 cas). Détail complet : `docs/capacites/specs/CAP-068-evenement.md`.

---

## CAP-080 — Ce que devrait mesurer DG Afrique (livrée)

**CLOSED — 2026-10-01.** Branche `feat/cap-080-impact-metrics`, PR draft. Était `NOT_IMPLEMENTED`/`DOCTRINE-À-CLARIFIER` (ROADMAP-003) : aucune doctrine positive ne définissait quoi mesurer — seule occurrence du corpus (art. 6.5), une interdiction : « Aucune contribution financière ne mesure la dignité, la moralité ou la valeur humaine d'une personne. » Clarification validée par l'invariant produit supérieur (`AI-RULES.md` : « le passage de la capacité à l'action humaine et collective »), cohérente avec l'unique occurrence doctrinale existante.

Décision produit V1 : mesurer la capacité collective à transformer des capacités disponibles en actions et résultats réels — des faits collectifs et des flux métier, jamais la valeur des personnes. Aucun score humain, classement, réputation, gamification ni KPI d'engagement. `ImpactMetricsService` — projection de lecture pure dérivée des domaines existants (Besoin, Projet, Mission, Transmission, Preuve, ZUMRA, Organisation, Partenariat, Événement, `LedgerEntry` pour un compte de contributions confirmées, jamais un montant) — aucune nouvelle table, aucun snapshot, aucun ETL, aucun cron. Granularité portail/ZUMRA/Organisation, jamais individuelle/comparative. Autorité intégralement réutilisée : `ZumraGroupService::isLeader()`+adhésion active, `OrganizationService::canView()`.

Preuve : `tests/Feature/ImpactMetricsTest.php` (11 cas). Détail complet : `docs/capacites/specs/CAP-080-mesure-collective.md`.

---

## Priorité canonique actuelle

**Cette section est désormais portée par ROADMAP-003 (voir section dédiée en haut de ce document).** Résumé : **ZUMRA-COMP-001**, **CAP-061**, **CAP-062**, **CAP-063**, **MODERATION-COMP-001**, **CAP-068** et **CAP-080** sont closes. La priorité canonique #1 déterminée par ROADMAP-003 (audit puis implémentation de MODERATION-COMP-001, art. 19) est livrée — voir `docs/roadmap/MODERATION-COMP-001.md`. CAP-068 et CAP-080, qui restaient `DOCTRINE-À-CLARIFIER`, ont chacune été clarifiées puis closes séparément (voir sections dédiées ci-dessus). **Plus aucune CAP `DOCTRINE-À-CLARIFIER` ne subsiste.** **Aucune nouvelle priorité canonique n'est fixée par ce chantier** : conformément à la règle de gouvernance de cette roadmap (« ne jamais conserver artificiellement une priorité devenue fausse »), déterminer la prochaine priorité exige un nouveau réaudit explicite du graphe métier (prochain chantier ROADMAP), pas une décision silencieuse ici. CAP-053, CAP-023, CAP-051, CAP-056, CAP-047 et CAP-077/078/079 restent dans l'état constaté par ROADMAP-003 (`PARTIAL` par conception ou bloquées par absence de dépendance/consommateur externe).

---

## CAP-063 — statut documentaire (historique, résolu)

Constat AUDIT-CAP-002 préservé tel quel pour mémoire : **CAP-063 était documentée `DEPENDENCY_BLOCKED` alors que l'audit n'avait trouvé aucun blocage technique réel.** L'audit CAP-063 Phase A (2026-09-15) a confirmé et précisé ce constat : ce n'était ni un blocage externe (type CAP-067/070) ni une interdiction produit absolue, mais une porte conditionnelle nommée par la doctrine elle-même (art. 15.3), dont le périmètre V1 légitime est le déclaratif. **Statut corrigé vers `CLOSED` dans `CAPABILITY-COVERAGE.md` lors de la livraison CAP-063 Phase B.**

**Le statut de CAP-063 dans `CAPABILITY-COVERAGE.md` n'a pas été modifié pendant ROADMAP-001.** Cette régularisation documentaire sera traitée dans un chantier explicitement décidé.

---

## CAP gelées — dépendances externes

**CAP-067 — Identité organisationnelle — DÉGELÉE ET FERMÉE (22 août 2026).** Le blocage externe (GAMAD Core n'émettait aucune identité organisationnelle) a été levé par CORE-ORG-DELEGATION-001 (`gamad-core`, PR #85), puis consommé côté DG Afrique par le chantier CAP-067. Toute nouvelle Organisation est désormais raccordée à une identité et une fiche organisationnelles canoniques réelles ; elle peut aussi déclarer explicitement ses propres capacités (`CapabilityStatement`, porteur `ORGANIZATION`). Voir `docs/capacites/specs/CAP-067-identite-organisationnelle.md`. **Statut corrigé vers `CLOSED` dans `CAPABILITY-COVERAGE.md`** — reste marquée `DEPENDENCY_BLOCKED` dans le tableau ROADMAP-002 ci-dessous par fidélité historique à l'audit qui l'a produit, comme pour CAP-063.

**CAP-070 — Document**
Blocage : GamaDrive

La continuité SSO existe, mais aucune API de stockage documentaire canonique n'est disponible.

**Décision : ne pas implémenter ces CAP par contournement local.**

---

## Satellites

Invariant conservé : **PROJET ≠ SATELLITE.**

Les satellites sont des outils logiciels spécialisés, éventuellement extractibles. Ils ne représentent jamais le stade final d'un Projet.

La chaîne **CAP-077 → CAP-078 → CAP-079** reste **non prioritaire** tant qu'aucun besoin réel d'intégration/extraction d'outil spécialisé ne la justifie.

---

## Dettes non-CAP (ne modifient pas cette roadmap)

- **CAP-016 ↔ CAP-065 non réconciliées.** `ProjectAccompanimentAction::provider_label` (texte libre, CAP-016) coexiste avec `Partnership` (relation gouvernable, CAP-065) sans lien structurel. Réconciliation à envisager ultérieurement (ex. `provider_label` référençant un `Partnership` réel), **non bloquante**, non traitée maintenant.
- **`MissionReviewFixesTest::test_invitation_never_fabricates_context_access`** — **fermée (REF-MISSION-UUID-001, 2026-09-22).** Était une dette de robustesse (validation de format UUID manquante avant une requête PostgreSQL typée `uuid`), jamais un gap métier ; `MissionAssignmentService::resolveInvitableSubject()` rejette désormais tout identifiant non conforme au format UUID avant toute requête (même comportement 422 qu'un identifiant inconnu, pour ne rien distinguer de l'extérieur). Aucune autorisation, aucune règle de contexte, aucun modèle métier modifié.
- **`DesignInvariantsTest` / `ProofHttpSmokeTest` / `TransmissionHttpSmokeTest`** → contenu de démonstration/design désynchronisé, pas un manque métier. **Non traitées par REF-MISSION-UUID-001** (hors périmètre explicite de ce chantier).

Ces dettes sont documentées ici pour mémoire ; elles ne modifient ni l'ordre ni les priorités de cette roadmap.

---

## Prochaine action recommandée

**ROADMAP-003 est le snapshot canonique actuel (voir section dédiée en haut de ce document).** Sa priorité #1 — MODERATION-COMP-001 (Modération, discipline et recours, art. 19), Phase A puis Phase B — est livrée (voir `docs/roadmap/MODERATION-COMP-001.md`). Aucun numéro CAP officiel n'a été créé pour ce chantier (voir « Pourquoi aucun numéro CAP n'est créé ici » dans la section ROADMAP-003). **Prochaine action : un nouveau réaudit explicite du graphe métier (ROADMAP-004)** pour déterminer la priorité canonique suivante — cette roadmap ne préjuge pas de son résultat.
