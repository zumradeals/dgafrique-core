# Couverture du référentiel — CAP-001 à CAP-084

> Carte d’avancement canonique. Le code de `main` reste la vérité technique ; ce fichier est la seule synthèse documentaire autorisée des statuts CAP.
>
> Ce fichier est le **registre de couverture** (statut de chaque CAP). Pour l'**ordre et les priorités** issus du dernier audit global, voir `docs/roadmap/ROADMAP-METIER-CANONIQUE.md` — les deux documents ne se dupliquent pas.
>
> Régularisation DOC-001 / ARCH-006 : la notion de satellite désigne uniquement l’autonomie technique éventuelle d’un **outil spécialisé extractible**. Un projet reste un projet ; sa maturité peut conduire à une autonomie organisationnelle ou économique, jamais à une transformation automatique en satellite logiciel.
>
> Doctrine : **fonction interne → module spécialisé extractible → satellite autonome seulement lorsqu’un besoin réel d’autonomie le justifie**.
>
> Statuts autorisés : `CLOSED` · `PARTIAL` · `NOT_IMPLEMENTED` · `DOC_ONLY` · `DEPENDENCY_BLOCKED`.

## Carte canonique

| CAP | Capacité | Statut |
|---|---|---|
| CAP-001 | Identité personne | CLOSED |
| CAP-002 | Compte DG Afrique | CLOSED |
| CAP-003 | Profil de capacités | CLOSED |
| CAP-004 | Compétences | CLOSED |
| CAP-005 | Apprentissage | CLOSED |
| CAP-006 | Transmission | CLOSED |
| CAP-007 | Programme ZUMRA | CLOSED |
| CAP-008 | Carte ZUMRA | CLOSED |
| CAP-009 | Découverte de personnes | CLOSED |
| CAP-010 | Recommandation | CLOSED |
| CAP-011 | ZUMRA / Groupe humain | CLOSED |
| CAP-012 | Capacité collective | CLOSED |
| CAP-013 | Besoin | CLOSED |
| CAP-014 | Projet | CLOSED |
| CAP-015 | Mise en relation projet ↔ compétences | CLOSED |
| CAP-016 | Accompagnement DG Afrique | CLOSED |
| CAP-017 | Maturité | CLOSED |
| CAP-018 | Parcours d’autonomie du projet | CLOSED |
| CAP-019 | Fil d’activité | CLOSED |
| CAP-020 | Messagerie | CLOSED |
| CAP-021 | Commentaire | CLOSED |
| CAP-022 | Partage | CLOSED |
| CAP-023 | Graphe des capacités | PARTIAL |
| CAP-024 | Profil comme source de capacités | CLOSED |
| CAP-025 | Disponibilité | CLOSED |
| CAP-026 | Intention | CLOSED |
| CAP-027 | Tableau de bord comme prochaine action | CLOSED |
| CAP-028 | Home personnalisée | CLOSED |
| CAP-029 | Découverte | DOC_ONLY |
| CAP-030 | Moteur de correspondance | CLOSED |
| CAP-031 | Explicabilité des recommandations | CLOSED |
| CAP-032 | Objet Besoin | DOC_ONLY |
| CAP-033 | Offrir une capacité | CLOSED |
| CAP-034 | Apprentissage comme réponse à un besoin | CLOSED |
| CAP-035 | Mémoire d’expérience | DOC_ONLY |
| CAP-036 | Preuve de capacité | CLOSED |
| CAP-037 | ZUMRA comme micro-espace de travail | CLOSED |
| CAP-038 | Tableau de bord collectif | CLOSED |
| CAP-039 | ZUMRA comme capacité d’émergence | CLOSED |
| CAP-040 | Projet comme objet indépendant | CLOSED |
| CAP-041 | Équipe projet | CLOSED |
| CAP-042 | Besoin projet | CLOSED |
| CAP-043 | Dossier de projet vivant | CLOSED |
| CAP-044 | Maturité calculée par signes | CLOSED |
| CAP-045 | Accompagnement | CLOSED |
| CAP-046 | Dossier d’accompagnement | CLOSED |
| CAP-047 | Module spécialisé extractible → satellite autonome | PARTIAL |
| CAP-048 | Registre des satellites autonomes | CLOSED |
| CAP-049 | Relation satellite autonome ↔ Core | CLOSED |
| CAP-050 | DG Afrique comme client du Core | CLOSED |
| CAP-051 | Portabilité de l’identité | PARTIAL |
| CAP-052 | Séparation des contextes | DOC_ONLY |
| CAP-053 | Consentement | PARTIAL |
| CAP-054 | Notifications | CLOSED |
| CAP-055 | Fil d’activité intelligent | CLOSED |
| CAP-056 | Publication | PARTIAL |
| CAP-057 | Commentaire comme contribution | CLOSED |
| CAP-058 | Messagerie contextuelle | CLOSED |
| CAP-059 | Conversation → action | CLOSED |
| CAP-060 | Réputation : grande prudence | DOC_ONLY |
| CAP-061 | Contributions financières | NOT_IMPLEMENTED |
| CAP-062 | Ledger / traçabilité | NOT_IMPLEMENTED |
| CAP-063 | Financement de projet | DEPENDENCY_BLOCKED |
| CAP-064 | Moteur d’opportunités | CLOSED |
| CAP-065 | Partenaire comme fournisseur de capacité | CLOSED |
| CAP-066 | Organisation | CLOSED |
| CAP-067 | Identité organisationnelle | DEPENDENCY_BLOCKED |
| CAP-068 | Événement | NOT_IMPLEMENTED |
| CAP-069 | Tâche / Mission | CLOSED |
| CAP-070 | Document | DEPENDENCY_BLOCKED |
| CAP-071 | Architecture de navigation future | DOC_ONLY |
| CAP-072 | Règle UX principale | DOC_ONLY |
| CAP-073 | Progressive disclosure | DOC_ONLY |
| CAP-074 | DG Afrique comme orchestrateur | DOC_ONLY |
| CAP-075 | Le Core ne porte pas toute la philosophie ZUMRA | DOC_ONLY |
| CAP-076 | Primitives vs produits | DOC_ONLY |
| CAP-077 | API de capacités | NOT_IMPLEMENTED |
| CAP-078 | Outil spécialisé autonome comme fournisseur de capacité | NOT_IMPLEMENTED |
| CAP-079 | Boucle d’écosystème | DEPENDENCY_BLOCKED |
| CAP-080 | Ce que devrait mesurer DG Afrique | NOT_IMPLEMENTED |
| CAP-081 | Différence avec un réseau social classique | DOC_ONLY |
| CAP-082 | Différence avec LinkedIn | NOT_IMPLEMENTED |
| CAP-083 | Différence avec une plateforme d’incubation | DOC_ONLY |
| CAP-084 | Invariant technique module-first / extractibilité | DOC_ONLY |

## Capacités concernées par ARCH-006

### CAP-018 — Parcours d’autonomie du projet

**Status : CLOSED**

**Evidence :** `ProjectAutonomyPathway`, `dg_project_autonomy_pathways`, service canonique `ProjectAutonomyPathwayService`.

**Lecture correcte :** le code existant prépare et suit l’autonomie organisationnelle/économique d’un projet. L’ancien nom `ProjectSatelliteLauncherService` était une dette historique de nommage ; il ne constituait pas une doctrine et ne créait pas de satellite logiciel.

**Décision :** renommage exécuté par REF-001B (2026-08-20) — `ProjectAutonomyController`, `Administration\ProjectAutonomyPathwayController` et l’ensemble des routes/vues/tests référencent désormais exclusivement `ProjectAutonomyPathwayService`. Aucun changement de comportement n’a été requis, conformément à ARCH-006.

### CAP-039 — ZUMRA comme capacité d’émergence

**Status : CLOSED**

**Evidence :** ZUMRA peut porter l’émergence de besoins, capacités et projets collectifs.

**Lecture correcte :** l’émergence d’un projet depuis une ZUMRA reste dans le domaine Projet. Elle n’implique aucune extraction logicielle.

### CAP-047 — Module spécialisé extractible → satellite autonome

**Status : PARTIAL**

**Evidence :** `dg_satellites`, modèle `Satellite`, registre, passerelle fédérée et primitives de continuité existent déjà.

**Gap :** l’architecture doit encore matérialiser, outil par outil, des frontières suffisamment propres pour permettre une extraction réelle sans réécriture du cœur métier.

**Décision :** aucun outil n’est extrait par anticipation. G-POS, GamaDrive et les futurs outils spécialisés commencent comme modules isolés et extractibles ; un ADR spécifique justifie toute autonomie physique ultérieure.

### CAP-048 — Registre des satellites autonomes

**Status : CLOSED**

**Evidence :** `dg_satellites`, `Satellite`, administration du registre.

**Lecture correcte :** le registre décrit des outils/services devenus techniquement autonomes. Il ne représente pas un registre de projets matures.

### CAP-049 — Relation satellite autonome ↔ Core

**Status : CLOSED**

**Evidence :** résolution par registre, continuité fédérée, menus alimentés par registre.

**Lecture correcte :** cette relation concerne l’intégration technique d’un outil autonome avec les primitives communes de l’écosystème.

### CAP-078 — Outil spécialisé autonome comme fournisseur de capacité

**Status : NOT_IMPLEMENTED**

**Evidence :** registre et fédération existent, mais aucun contrat canonique de capacité fournie par un outil autonome n’est matérialisé.

**Gap :** contrat fournisseur de capacité absent.

**Dependencies :** CAP-077 pour une surface d’intégration explicite lorsque le besoin devient réel ; CAP-048/049/050 sont déjà disponibles comme primitives.

### CAP-079 — Boucle d’écosystème

**Status : DEPENDENCY_BLOCKED**

**Evidence :** passerelle vers les outils autonomes présente ; aucune remontée canonique de capacités/actions vers DG Afrique.

**Dependencies :** CAP-078.

### CAP-084 — Invariant technique module-first / extractibilité

**Status : DOC_ONLY**

**Invariant :** **fonction interne → module spécialisé extractible → satellite autonome seulement si un besoin réel d’autonomie le justifie**.

Un satellite est une forme technique possible d’un outil spécialisé. Ce n’est ni une destination produit, ni un niveau de maturité d’un projet, ni une récompense accordée à un projet réussi.

## Règles de maintenance

1. Le code de `main` est la vérité technique.
2. Cette carte est mise à jour atomiquement avec toute PR qui change réellement le statut d’une CAP.
3. Aucun tracker parallèle de statuts CAP n’est autorisé.
4. Une dette de nommage historique dans le code ne doit jamais être transformée en doctrine produit.
5. Toute extraction d’un module spécialisé vers un satellite autonome exige une justification d’architecture explicite et un plan de migration.
6. L’autonomie d’un projet et l’autonomie technique d’un outil spécialisé sont deux concepts distincts et ne doivent plus être reliés par implication documentaire.