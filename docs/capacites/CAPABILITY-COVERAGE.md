# Couverture du référentiel — CAP-001 à CAP-084

> Carte d'avancement canonique. Le code de `main` reste la vérité technique ; ce fichier est la seule synthèse documentaire autorisée des statuts CAP.
>
> Audit initial réalisé le 2026-08-18 contre le code de `main`. Régularisation DOC-001 poursuivie le 2026-08-20 : toute divergence découverte doit être corrigée ici, jamais masquée par un tracker parallèle.
>
> **Statuts autorisés :** CLOSED · PARTIAL · NOT_IMPLEMENTED · DOC_ONLY · DEPENDENCY_BLOCKED.
>
> **Important :** les totaux historiques figés sont supprimés. Ils devenaient faux dès qu'un module était livré sans mise à jour atomique de ce fichier. La couverture se lit capacité par capacité et doit être recalculée automatiquement avant de réintroduire un total global.

---

## CAP-001 — Identité personne
Status: CLOSED
Evidence: `app/Domain/Identity/CoreIdentity.php`, `app/Infrastructure/GamadCore/GamadCoreClient.php`, `app/Http/Middleware/RequireCoreMember.php`
Gap: aucun — délégation à GAMAD Core par design
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-002 — Compte DG Afrique
Status: CLOSED
Evidence: `AccountRegistrationController.php`, `MemberSessionController.php`, routes `/connexion` `/creer-un-compte`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-003 — Profil de capacités
Status: CLOSED
Evidence: `dg_person_profiles`, `PersonProfile.php`, `MemberProfileController.php`, `/espace/profil`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-004 — Compétences
Status: CLOSED
Evidence: `dg_capability_statements` (KIND_POSSESSED), `CapabilityStatementSynchronizer.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-005 — Apprentissage
Status: CLOSED
Evidence: `dg_capability_statements` (KIND_LEARNING)
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-006 — Transmission
Status: CLOSED
Evidence: `app/Application/Transmission/*`, `routes/cap006.php`, `docs/capacites/specs/TRANSMISSION.md`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #13
Final SHA: 46318988d9d1dee8ca56bf4861802c3b294b721e

## CAP-007 — Programme ZUMRA
Status: CLOSED
Evidence: `dg_zumra_program_memberships`, `ZumraProgramMembershipController.php`, `ZumraMembershipPaymentController.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-008 — Carte ZUMRA
Status: CLOSED
Evidence: `dg_zumra_cards`, `ZumraCardIssuer.php`, vérification signée
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-009 — Découverte de personnes
Status: CLOSED
Evidence: `PeopleDiscoveryController.php`, `/personnes`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-010 — Recommandation
Status: CLOSED
Evidence: `PersonRecommendationEngine.php`, `dg_recommendation_decisions`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-011 — ZUMRA / Groupe humain
Status: CLOSED
Evidence: `ZumraGroupService.php`, `dg_zumra_groups`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module ; recomposition visuelle #40
Final SHA: e8db741bc0274b48a27b77d2a9a06e83b9eb51b6

## CAP-012 — Capacité collective
Status: CLOSED
Evidence: `CollectiveCapabilityProfile.php`, consentement collectif sur `ZumraGroupMembership`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-013 — Besoin
Status: CLOSED
Evidence: `Need.php`, `NeedService.php`, `/besoins`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module ; extension #38
Final SHA: f150e515b9cb87e57e78235e94f4a84ec1cea320

## CAP-014 — Projet
Status: CLOSED
Evidence: `Project.php`, `ProjectService.php`, `/projets`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module ; extension #38
Final SHA: f150e515b9cb87e57e78235e94f4a84ec1cea320

## CAP-015 — Mise en relation projet ↔ compétences
Status: CLOSED
Evidence: `ProjectMatchingEngine.php`, `dg_project_match_decisions`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-016 — Accompagnement DG Afrique
Status: CLOSED
Evidence: `ProjectAccompanimentService.php`, `routes/cap016.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-017 — Maturité
Status: CLOSED
Evidence: `dg_projects.maturity`, `ProjectMaturityService.php`, `routes/cap017.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-018 — Lanceur de satellites
Status: CLOSED
Evidence: `ProjectSatelliteLauncherService.php`, `dg_project_autonomy_pathways`
Gap: aucun — parcours de préparation ; la bascule réelle reste CAP-047
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-019 — Fil d'activité
Status: CLOSED
Evidence: `ActivityFeedService.php`, `/activite`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module ; extension #38
Final SHA: f150e515b9cb87e57e78235e94f4a84ec1cea320

## CAP-020 — Messagerie
Status: CLOSED
Evidence: `MessagingService.php`, `routes/cap020.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-021 — Commentaire
Status: CLOSED
Evidence: `ContextCommentService.php`, `routes/cap021.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-022 — Partage
Status: CLOSED
Evidence: `ContextShareService.php`, `/partages`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-023 — Le graphe des capacités
Status: PARTIAL
Evidence: `CapabilityStatement`, `CapabilityStatementSynchronizer::matchingLabels()` alimente plusieurs moteurs
Gap: pas de vue graphe navigable ni de traversée exposée
Dependencies: aucune
Decision: construire seulement sur besoin produit réel
Implementation PR: —
Final SHA: —

## CAP-024 — Le profil n'est pas une page, c'est une source de capacités
Status: CLOSED
Evidence: `CapabilityStatementSynchronizer::sync()` régénère les statements après édition du profil
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-025 — Disponibilité
Status: CLOSED
Evidence: champs de disponibilité du profil + fiche personne + signal de recommandation
Gap: intégration Mission/Transmission différée POST-BETA, non bloquante pour CAP-025 v1
Dependencies: aucune
Decision: aucune action requise v1
Implementation PR: #17
Final SHA: 4e7df340881595800f94b1dcf8072ed94d6433ab

## CAP-026 — Intention
Status: CLOSED
Evidence: `PersonProfile.intentions`, édité dans le profil
Gap: non utilisé comme signal de recommandation
Dependencies: aucune
Decision: extension éventuelle, non bloquante
Implementation PR: antérieur
Final SHA: —

## CAP-027 — Le tableau de bord comme « prochaine action »
Status: CLOSED
Evidence: `MemberSpaceController::priority()`, `/espace`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-028 — Home personnalisée
Status: CLOSED
Evidence: `/espace` agrège priorité, recommandations, ZUMRA, activité et partages
Gap: aucun — consolidée avec CAP-027
Dependencies: CAP-027 (CLOSED)
Decision: pas de second écran distinct
Implementation PR: consolidation documentaire
Final SHA: —

## CAP-029 — Découverte
Status: DOC_ONLY
Evidence: listings existants personnes/besoins/projets/ZUMRA/Fil
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-030 — Moteur de correspondance
Status: CLOSED
Evidence: moteurs Personne, Projet, Mission, Transmission
Gap: logique dupliquée par domaine
Dependencies: aucune
Decision: factoriser seulement si besoin réel
Implementation PR: antérieur
Final SHA: —

## CAP-031 — Explicabilité des recommandations
Status: CLOSED
Evidence: moteurs retournent des raisons humaines, jamais un score humain
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-032 — L'objet « Besoin »
Status: DOC_ONLY
Evidence: `Need.php`, déjà objet de première classe
Gap: aucun
Dependencies: CAP-013 (CLOSED)
Decision: CAP-013 porte l'implémentation
Implementation PR: —
Final SHA: —

## CAP-033 — « Offrir une capacité »
Status: CLOSED
Evidence: `MissionAssignmentService::offer()/acceptOffer()`
Gap: aucun
Dependencies: CAP-069 (CLOSED)
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-034 — Apprentissage comme réponse à un besoin
Status: CLOSED
Evidence: `Transmission::CONTEXT_NEED`, `TransmissionContextService`
Gap: aucun
Dependencies: CAP-006, CAP-013
Decision: aucune action requise
Implementation PR: #13
Final SHA: 46318988d9d1dee8ca56bf4861802c3b294b721e

## CAP-035 — Mémoire d'expérience
Status: DOC_ONLY
Evidence: mémoire agrégée des preuves via `ProofController::memory()`
Gap: aucun — volontairement pas d'objet séparé
Dependencies: CAP-036 (CLOSED)
Decision: aucune action requise
Implementation PR: #14
Final SHA: 7571089c6d71309d5e7272bb6faf045b4bb60d60

## CAP-036 — Preuve de capacité
Status: CLOSED
Evidence: `app/Application/Proof/*`, `routes/cap036.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #14
Final SHA: 7571089c6d71309d5e7272bb6faf045b4bb60d60

## CAP-037 — La ZUMRA comme micro-espace de travail
Status: CLOSED
Evidence: fiche groupe agrège besoins/projets avec contrôles d'accès
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #21 ; UI #40
Final SHA: d1152ac45b2489ad52dcbb6cfc039d71606e01df

## CAP-038 — Tableau de bord collectif
Status: CLOSED
Evidence: `ZumraGroupController::collectivePriority()`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #21 ; UI #40
Final SHA: d1152ac45b2489ad52dcbb6cfc039d71606e01df

## CAP-039 — La ZUMRA comme capacité d'émergence
Status: CLOSED
Evidence: `MATURITY_EMERGING` → `OWNER_GROUP` → `ProjectSatelliteLauncherService`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-040 — Projet comme objet indépendant
Status: CLOSED
Evidence: `Project.php`, `/projets`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-041 — Équipe projet
Status: CLOSED
Evidence: `ProjectTeamMember`, `ProjectTeamService`, section équipe projet
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #23
Final SHA: 5699162d908ebad20df9104c6a488a50fc8ceb4d

## CAP-042 — Besoin projet
Status: CLOSED
Evidence: `Need::OWNER_PROJECT`, `NeedService`, besoins projet
Gap: aucun
Dependencies: CAP-013, CAP-014
Decision: aucune action requise
Implementation PR: #25
Final SHA: d7b456c2ba17fb726be7b18400e64a756629f44d

## CAP-043 — Dossier de projet vivant
Status: CLOSED
Evidence: fiche projet agrège jalons, maturité, accompagnement, autonomie
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-044 — Maturité calculée par signes, pas par décret
Status: CLOSED
Evidence: `ProjectSignalsEngine::forProject()`
Gap: aucun
Dependencies: aucune
Decision: signaux consultatifs, aucune transition automatique
Implementation PR: #27
Final SHA: 0e6958ddb167c446c80f9b47776b812d2a6f53ed

## CAP-045 — Accompagnement
Status: CLOSED
Evidence: `ProjectAccompanimentService`, demandes d'accompagnement et administration
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #29
Final SHA: 9f48dc91c4fda8d9737c2dd92a7f0cd9b4372295

## CAP-046 — Dossier d'accompagnement
Status: CLOSED
Evidence: synthèse/filtres depuis les actions d'accompagnement réelles
Gap: aucun
Dependencies: CAP-045
Decision: aucune action requise
Implementation PR: #31
Final SHA: 5106974204115f473283e217aa3c4d5288d15f92

## CAP-047 — Le « satellite » comme changement de nature
Status: NOT_IMPLEMENTED
Evidence: `ProjectAutonomyPathway` prépare l'autonomie ; `dg_satellites` et la fédération existent déjà via CAP-048/049
Gap: aucune bascule métier explicite d'un projet mature vers une déclaration/activation de satellite
Dependencies: CAP-048 (CLOSED), CAP-049 (CLOSED), CAP-050 (CLOSED)
Decision: construire la transition métier sur les primitives satellites déjà livrées ; ne pas recréer registre ou fédération
Implementation PR: —
Final SHA: —

## CAP-048 — Registre des satellites
Status: CLOSED
Evidence: `dg_satellites`, `Satellite`, administration des satellites
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #34
Final SHA: 6f25ef392fd89afb7f1a77511717d2962893e569

## CAP-049 — Relation satellite ↔ Core
Status: CLOSED
Evidence: résolution du satellite par registre, continuité fédérée et menus alimentés par registre
Gap: aucun
Dependencies: CAP-048 (CLOSED)
Decision: aucune action requise
Implementation PR: #36
Final SHA: f89145098fa65b1a912d9f766409a17af6d2610e

## CAP-050 — DG Afrique comme client du Core
Status: CLOSED
Evidence: `GamadCoreClient`, `RequireCoreMember`, `FederatedProductGateway::open`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur
Final SHA: —

## CAP-051 — Portabilité de l'identité
Status: PARTIAL
Evidence: session portail + passerelle fédérée + registre multi-satellites
Gap: la portabilité dépend encore du contrat réellement exposé par chaque satellite
Dependencies: CAP-048, CAP-049, CAP-050 (CLOSED)
Decision: étendre par contrat satellite, sans configuration en dur
Implementation PR: —
Final SHA: —

## CAP-052 — Séparation des contextes
Status: DOC_ONLY
Evidence: infrastructure Core isolée et erreurs sans fuite du nom interne
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-053 — Consentement
Status: PARTIAL
Evidence: consentements orientation, découverte, matching et capacité collective
Gap: pas de centre transversal unifié
Dependencies: aucune
Decision: modèle transversal seulement sur besoin produit
Implementation PR: —
Final SHA: —

## CAP-054 — Notifications
Status: CLOSED
Evidence: `app/Application/Notifications/*`, `routes/cap054.php`
Gap: canal in-app uniquement, POST-BETA documenté
Dependencies: aucune
Decision: aucune action requise v1
Implementation PR: #15
Final SHA: 5030bf50d2d4e258ee8e040ed730f3fae299c98a

## CAP-055 — Fil d'activité intelligent
Status: NOT_IMPLEMENTED
Evidence: priorité globale fixe dans `ActivityFeedService`
Gap: aucune personnalisation par spectateur
Dependencies: aucune
Decision: spécifier sous contrainte anti-score
Implementation PR: —
Final SHA: —

## CAP-056 — Publication
Status: PARTIAL
Evidence: cycles de publication existent par objet
Gap: pas de contrat transversal CAP-056
Dependencies: aucune
Decision: documenter avant abstraction
Implementation PR: —
Final SHA: —

## CAP-057 — Commentaire comme contribution
Status: CLOSED
Evidence: `ContextComment` avec finalités structurées
Gap: aucun
Dependencies: CAP-021 (CLOSED)
Decision: consolidé avec CAP-021
Implementation PR: antérieur
Final SHA: —

## CAP-058 — Messagerie contextuelle
Status: CLOSED
Evidence: `MessageConversation` multi-contexte
Gap: aucun
Dependencies: CAP-020 (CLOSED)
Decision: consolidé avec CAP-020
Implementation PR: antérieur
Final SHA: —

## CAP-059 — Conversation → action
Status: CLOSED
Evidence: `ProjectBrainNeedDraftService.php`, `ProjectBrainConversation`, `ProjectBrainMessage`, `ProjectBrainDraft`, `routes/project_brain.php`. Une conversation projet peut produire un brouillon `NEED_CREATE`; aucune mutation Core n'a lieu avant confirmation humaine explicite, puis `NeedService::create()` matérialise le besoin.
Gap: aucun pour le contrat matérialisé « conversation projet → besoin ». Mission/Transmission sont des extensions futures.
Dependencies: CAP-013 (CLOSED), CAP-014 (CLOSED)
Decision: conversation-first + brouillon + confirmation humaine ; jamais de mutation directe par le fournisseur IA
Implementation PR: antérieur à DOC-001 ; Project Brain présent sur `main`
Final SHA: —

## CAP-060 — Réputation : grande prudence
Status: DOC_ONLY
Evidence: doctrine anti-score, aucun modèle de score humain
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-061 — Contributions financières
Status: NOT_IMPLEMENTED
Evidence: seul `ZumraPayment` d'adhésion existe
Gap: aucune contribution générale
Dependencies: aucune
Decision: spécifier avant construction
Implementation PR: —
Final SHA: —

## CAP-062 — Ledger / traçabilité
Status: NOT_IMPLEMENTED
Evidence: journaux métier, pas de ledger financier
Gap: ledger financier absent
Dependencies: CAP-061
Decision: dépend de CAP-061
Implementation PR: —
Final SHA: —

## CAP-063 — Financement de projet
Status: DEPENDENCY_BLOCKED
Evidence: financement projet structuré absent
Gap: contributions + ledger requis
Dependencies: CAP-061, CAP-062
Decision: construire 061/062 d'abord
Implementation PR: —
Final SHA: —

## CAP-064 — Moteur d'opportunités
Status: NOT_IMPLEMENTED
Evidence: aucun objet Opportunité canonique
Gap: objet absent
Dependencies: aucune
Decision: définir objet avant moteur
Implementation PR: —
Final SHA: —

## CAP-065 — Le partenaire comme fournisseur de capacité
Status: NOT_IMPLEMENTED
Evidence: partenaire seulement représenté par labels d'accompagnement
Gap: entité Partenaire absente
Dependencies: CAP-066, CAP-067
Decision: dépend des organisations
Implementation PR: —
Final SHA: —

## CAP-066 — Organisation
Status: NOT_IMPLEMENTED
Evidence: `ZumraGroup` est un concept distinct
Gap: entité Organisation absente
Dependencies: aucune
Decision: spécifier distinctement de ZUMRA
Implementation PR: —
Final SHA: —

## CAP-067 — Identité organisationnelle
Status: DEPENDENCY_BLOCKED
Evidence: aucun objet Organisation
Gap: identité impossible sans CAP-066
Dependencies: CAP-066
Decision: construire avec CAP-066
Implementation PR: —
Final SHA: —

## CAP-068 — Événement
Status: NOT_IMPLEMENTED
Evidence: `*Event` = audit, pas calendrier
Gap: objet événement/RSVP absent
Dependencies: aucune
Decision: spécifier distinctement de l'audit
Implementation PR: —
Final SHA: —

## CAP-069 — Tâche
Status: CLOSED
Evidence: `app/Application/Missions/*`, `routes/cap069.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #12
Final SHA: —

## CAP-070 — Document
Status: DEPENDENCY_BLOCKED
Evidence: fédération présente, aucune API documentaire locale
Gap: stockage/versionnement documentaire réel absent côté intégration
Dependencies: GamaDrive
Decision: ne pas construire de stockage parallèle
Implementation PR: —
Final SHA: —

## CAP-071 — Architecture de navigation future
Status: DOC_ONLY
Evidence: shell + invariants design
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-072 — La règle UX principale
Status: DOC_ONLY
Evidence: invariants design + priorité Mon espace
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-073 — Progressive disclosure
Status: DOC_ONLY
Evidence: principe appliqué dans les flux progressifs
Gap: aucun module métier attendu
Dependencies: aucune
Decision: invariant UX
Implementation PR: —
Final SHA: —

## CAP-074 — DG Afrique comme orchestrateur
Status: DOC_ONLY
Evidence: séparation Core/infrastructure et Application
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-075 — Le Core ne doit pas connaître toute la philosophie ZUMRA
Status: DOC_ONLY
Evidence: client Core limité aux primitives nécessaires
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-076 — Primitives vs produits
Status: DOC_ONLY
Evidence: séparation infrastructure/application
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-077 — API de capacités
Status: NOT_IMPLEMENTED
Evidence: aucune API publique de capacités déclarée
Gap: surface d'intégration externe absente
Dependencies: aucune
Decision: construire sur besoin explicite
Implementation PR: —
Final SHA: —

## CAP-078 — Le satellite peut devenir fournisseur de capacité
Status: DEPENDENCY_BLOCKED
Evidence: CAP-048/049/050 fournissent registre, relation et client Core ; aucun contrat « capacité fournie par satellite » n'existe
Gap: contrat fournisseur absent
Dependencies: CAP-047 (NOT_IMPLEMENTED), CAP-048 (CLOSED), CAP-049 (CLOSED), CAP-050 (CLOSED)
Decision: bloqué uniquement par la matérialisation métier restante, pas par l'absence de registre
Implementation PR: —
Final SHA: —

## CAP-079 — Boucle d'écosystème
Status: DEPENDENCY_BLOCKED
Evidence: passerelle vers satellites disponible ; aucune remontée de capacités/actions depuis un satellite vers DG Afrique
Gap: boucle retour absente
Dependencies: CAP-047 (NOT_IMPLEMENTED), CAP-048 (CLOSED), CAP-049 (CLOSED), CAP-050 (CLOSED), CAP-078
Decision: bloqué par la boucle fournisseur/retour, pas par le registre
Implementation PR: —
Final SHA: —

## CAP-080 — Ce que devrait mesurer DG Afrique
Status: NOT_IMPLEMENTED
Evidence: exclusions doctrinales sans indicateurs positifs canoniques
Gap: indicateurs non définis
Dependencies: aucune
Decision: doctrine avant analytics
Implementation PR: —
Final SHA: —

## CAP-081 — La grande différence avec un réseau social
Status: DOC_ONLY
Evidence: anti-classement, recommandations explicables sans score humain
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-082 — La grande différence avec LinkedIn
Status: NOT_IMPLEMENTED
Evidence: distinction seulement implicite
Gap: note doctrinale dédiée absente
Dependencies: aucune
Decision: documenter si position contractuelle
Implementation PR: —
Final SHA: —

## CAP-083 — La grande différence avec une plateforme d'incubation
Status: DOC_ONLY
Evidence: accompagnement léger distinct d'un workflow d'incubation lourd
Gap: aucun
Dependencies: aucune
Decision: invariant
Implementation PR: —
Final SHA: —

## CAP-084 — La phrase « lanceur de satellites » devient technique
Status: DEPENDENCY_BLOCKED
Evidence: CAP-018 prépare ; CAP-048/049/050 fournissent déjà registre, relation et primitives Core ; CAP-047 manque pour la bascule métier
Gap: transition projet→satellite incomplète
Dependencies: CAP-047 (NOT_IMPLEMENTED), CAP-048 (CLOSED), CAP-049 (CLOSED), CAP-050 (CLOSED)
Decision: fermer seulement lorsque la transition CAP-047 matérialise la chaîne complète
Implementation PR: —
Final SHA: —
