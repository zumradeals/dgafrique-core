# ROADMAP MÉTIER CANONIQUE — DG Afrique

Statut : ACTIVE

Source : AUDIT-CAP-002

Baseline d'audit :
main @ cd01a8e7b4da26cd7e63a1d5fda0fea2737ef682

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

## AUDIT-CAP-002 — constats préservés

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
| CAP-061 | Contributions financières | NOT_IMPLEMENTED | Finance | `GeniusPayClient` prouvé en prod (CAP-007B, adhésion ZUMRA) | Aucun modèle Contribution/Wallet pour un contexte hors adhésion | Aucun | MOYEN | 2 (CAP-062, CAP-063) | MOYEN | M |
| CAP-062 | Ledger / traçabilité | NOT_IMPLEMENTED | Finance | Rien (aucun modèle Ledger) | Registre d'audit financier | Aucun | MOYEN | 1 (CAP-061/063 plus solides avec) | FAIBLE | M |
| CAP-063 | Financement de projet | NOT_IMPLEMENTED *(documenté `DEPENDENCY_BLOCKED` — voir section dédiée ci-dessous)* | Finance/Projects | Project, Organization, Partnership, Need — tout le socle relationnel existe | Modèle de financement lui-même ; CAP-014 impose explicitement « aucun paiement dans Projet » | **Produit** (choix doctrinal assumé, pas dépendance technique) | MOYEN | 0 | MOYEN | M |
| CAP-023 | Graphe des capacités | PARTIAL | Capabilities & Intelligence | 5 moteurs de correspondance séparés (`PersonRecommendationEngine`, `OpportunityEngine`, `MissionMatchingEngine`, `ProjectMatchingEngine`, `TransmissionMatchingEngine`) | Aucune structure de graphe unifiée/navigable | Aucun | FAIBLE | 0 | FAIBLE | M |
| CAP-051 | Portabilité de l'identité | PARTIAL | Identity Federation | Continuité SSO fédérée vers satellites (`FederationServiceProvider`) | Export réel des données côté DG Afrique | Produit (contrat Core non défini) | FAIBLE | 0 | FAIBLE | S |
| CAP-056 | Publication | PARTIAL | Social & Coordination | `published_at` sur `Need` et `ZumraCharter` uniquement ; Project/Proof gèrent leur propre statut sans notion de « publication » | Concept unifié — ou clarification que chaque domaine gère légitimement le sien | Aucun (possiblement un problème de nommage, pas de code manquant) | FAIBLE | 0 | FAIBLE | S |
| CAP-047 | Satellite extractible | PARTIAL *(par design)* | Specialized Tools | Registre, passerelle fédérée, primitives de continuité | Frontières propres outil par outil — volontairement pas anticipé | Produit (aucun outil ne justifie encore l'extraction) | FAIBLE | 0 | FAIBLE | — |
| CAP-068 | Événement | NOT_IMPLEMENTED | Operations | Rien — les `*Event` du dépôt (`ProjectEvent`, `NeedEvent`…) sont des journaux d'audit internes, **pas** la capacité métier CAP-068 (événement réel type atelier/rencontre) | Modèle Événement réel organisable par ZUMRA/Organisation | Aucun | FAIBLE | 0 | FAIBLE | M |
| CAP-077 | API de capacités | NOT_IMPLEMENTED | Capabilities API | Rien | Contrat externe de capacités | Produit (aucun consommateur externe réel aujourd'hui) | FAIBLE | 1 (CAP-078) | FAIBLE | L |
| CAP-078 | Satellite fournisseur de capacité | NOT_IMPLEMENTED | Ecosystem | Registre CAP-048/049/050 | Contrat capacité↔outil autonome | **Interne** (CAP-077) | FAIBLE | 1 (CAP-079) | FAIBLE | L |
| CAP-079 | Boucle d'écosystème | DEPENDENCY_BLOCKED | Ecosystem | Passerelle fédérée | Remontée canonique de capacités | **Interne** (CAP-078) | FAIBLE | 0 | FAIBLE | L |
| CAP-080 | Ce que devrait mesurer DG Afrique | NOT_IMPLEMENTED | Ecosystem | Rien | Décision produit sur les métriques | Produit | FAIBLE | 0 | FAIBLE | S |
| CAP-082 | Différence avec LinkedIn | NOT_IMPLEMENTED *(probable erreur de statut — devrait être DOC_ONLY comme 081/083, non corrigée pendant ROADMAP-001)* | Ecosystem | Contenu doctrinal seulement | Rien de codable | — | — | — | — | — |
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
3. **Classification suspecte — CAP-082** (« Différence avec LinkedIn ») étiquetée `NOT_IMPLEMENTED` alors que ses sœurs doctrinales identiques (CAP-081, CAP-083) sont `DOC_ONLY`. Contenu purement positionnel, rien de codable trouvé. **Non corrigé pendant ROADMAP-001** — ce chantier est documentaire/gouvernance, pas DOC-002.
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

Phase A (audit runtime) et Phase B (implémentation, corrigée en revue) sont terminées et en revue : branche `fix/zumra-comp-001-lifecycle-validation`, PR draft. Le cycle `CONSTITUTING → READY → VALIDATED → ACTIVE ⇄ WARNED → SUSPENDED → REHABILITATING → ACTIVE` est implémenté dans `ZumraGroupService`, avec gestion des sièges fondateurs vacants (proposition/acceptation) et application réelle de `max_simultaneous_founder_roles`. `READY` signifie « dossier structurel complet, prêt à être soumis à validation » — jamais « les 7 critères doctrinaux sont tous validés » : `evaluateStructuralReadiness()` ne vérifie que les 6 critères automatisables ; le 7e (contrôles de nom/doublon/risque/usurpation) reste un contrôle de conformité humain, jamais présenté comme satisfait par la seule unicité technique du `slug`, et le contrôle anti-fraude n'est jamais fabriqué. Automatique (`acceptRole` + `auto_validation_enabled=true`) ou manuelle (`markReady()`, autorité DG Afrique/GAMAD, lorsque l'automatisation est désactivée) — le cycle ne devient jamais impossible faute d'automatisation. `VALIDATED` et tout ce qui suit restent des décisions explicites de l'autorité DG Afrique/GAMAD (`PortalAdministrator`) ; `CONSTITUTING → VALIDATED` directement reste toujours impossible. Cette roadmap sera réévaluée après le merge, conformément à la règle « ne jamais conserver artificiellement une priorité devenue fausse ».

---

## Priorité canonique actuelle

**PRIORITÉ #1**

**ZUMRA-COMP-001 — Cycle de vie & validation** — Phase A et Phase B livrées, PR draft en revue (non mergée à ce jour). Le réaudit du graphe métier est à faire après le merge, pas avant.

Puis, **sous réserve du réaudit après ce correctif** :

1. CAP-061 — Contributions financières
2. CAP-062 — Ledger / traçabilité
3. CAP-063 — Financement de projet

Cette séquence n'est **pas un ordre éternel**. Après ZUMRA-COMP-001, le graphe métier doit être rapidement revalidé — un correctif touchant transversalement Messagerie/Partage/Commentaire/Missions ZUMRA peut faire apparaître de nouvelles dépendances ou refermer des écarts non anticipés ici.

---

## CAP-063 — statut documentaire

Constat AUDIT-CAP-002 préservé tel quel : **CAP-063 est actuellement documentée `DEPENDENCY_BLOCKED` dans `CAPABILITY-COVERAGE.md`, mais l'audit n'a trouvé aucun blocage technique réel.** Son absence relève actuellement d'une décision/doctrine produit (CAP-014 interdit explicitement tout paiement dans Projet), pas d'une dépendance technique manquante.

**Le statut de CAP-063 dans `CAPABILITY-COVERAGE.md` n'a pas été modifié pendant ROADMAP-001.** Cette régularisation documentaire sera traitée dans un chantier explicitement décidé.

---

## CAP gelées — dépendances externes

**CAP-067 — Identité organisationnelle**
Blocage : GAMAD Core

L'Organization locale fonctionne, mais Core ne fournit pas encore une identité organisationnelle permettant à une Organisation de s'authentifier et de porter proprement une `CapabilityStatement`.

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
- **`MissionReviewFixesTest`** → dette de robustesse (validation UUID manquante avant requête DB), pas un gap métier.
- **`DesignInvariantsTest` / `ProofHttpSmokeTest` / `TransmissionHttpSmokeTest`** → contenu de démonstration/design désynchronisé, pas un manque métier.

Ces dettes sont documentées ici pour mémoire ; elles ne modifient ni l'ordre ni les priorités de cette roadmap.

---

## Prochaine action recommandée

**ZUMRA-COMP-001 — audit dédié avant code.** Ne pas commencer d'implémentation directement : le rayon d'impact transversal (Messagerie, Partage, Commentaire, Missions ZUMRA) exige un audit d'implémentation propre, séparé de ce document de canonisation.
