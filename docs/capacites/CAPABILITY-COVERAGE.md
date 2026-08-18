# Couverture du référentiel — CAP-001 à CAP-084

> Carte d'avancement canonique. Remplace `CAP-MASTER-TRACKER.md` (périmé depuis le 2026-08-16,
> non utilisé comme preuve dans cet audit). Mis à jour après chaque merge — jamais en avance sur
> l'état réel de `main`.
>
> Audit initial réalisé le 2026-08-18, contre `main` @ `5030bf50d2d4e258ee8e040ed730f3fae299c98a`
> (juste après le merge de CAP-054 Notifications, PR #15), en lisant directement le code
> (migrations, modèles, services, contrôleurs, routes, vues) — jamais le tracker stale.
>
> **Statuts autorisés :** CLOSED · PARTIAL · NOT_IMPLEMENTED · DOC_ONLY · DEPENDENCY_BLOCKED.
>
> **Total : 84/84 auditées — 45 CLOSED · 7 PARTIAL · 13 NOT_IMPLEMENTED · 13 DOC_ONLY · 6 DEPENDENCY_BLOCKED.**
> (mis à jour après CAP-044 — signaux de maturité, PR #27)

---

## CAP-001 — Identité personne
Status: CLOSED
Evidence: `app/Domain/Identity/CoreIdentity.php`, `app/Infrastructure/GamadCore/GamadCoreClient.php`, `app/Http/Middleware/RequireCoreMember.php`
Gap: aucun — délégation à GAMAD Core par design (fiche `CAP-001-identite-personne.md`)
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-002 — Compte DG Afrique
Status: CLOSED
Evidence: `AccountRegistrationController.php`, `MemberSessionController.php`, routes `/connexion` `/creer-un-compte` (`routes/web.php`)
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-003 — Profil de capacités
Status: CLOSED
Evidence: migration `dg_person_profiles`, `app/Models/PersonProfile.php`, `MemberProfileController.php`, `/espace/profil`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-004 — Compétences
Status: CLOSED
Evidence: `dg_capability_statements` (KIND_POSSESSED), `app/Application/Profile/CapabilityStatementSynchronizer.php`
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
Evidence: module dédié complet — `app/Application/Transmission/*`, `routes/cap006.php`, fiche `docs/capacites/specs/TRANSMISSION.md`
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
Evidence: `dg_zumra_cards`, `app/Application/Zumra/ZumraCardIssuer.php`, vérification signée
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
Evidence: `app/Application/Recommendation/PersonRecommendationEngine.php`, `dg_recommendation_decisions`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-011 — ZUMRA / Groupe humain
Status: CLOSED
Evidence: `app/Application/Zumra/ZumraGroupService.php`, `dg_zumra_groups`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

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
Evidence: `app/Models/Need.php`, `app/Application/Needs/NeedService.php`, `/besoins`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-014 — Projet
Status: CLOSED
Evidence: `app/Models/Project.php`, `app/Application/Projects/ProjectService.php`, `/projets`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

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
Gap: aucun — portée volontairement bornée (parcours de préparation, pas un vrai satellite ; réservé CAP-047/048)
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-019 — Fil d'activité
Status: CLOSED
Evidence: `app/Application/Activity/ActivityFeedService.php`, `/activite`
Gap: aucun
Dependencies: aucune
Decision: corriger le libellé « [PLUS TARD] » dans `CAPABILITY-INDEX.md`
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-020 — Messagerie
Status: CLOSED
Evidence: `app/Application/Messaging/MessagingService.php`, `routes/cap020.php`
Gap: aucun
Dependencies: aucune
Decision: idem CAP-019
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-021 — Commentaire
Status: CLOSED
Evidence: `app/Application/Comments/ContextCommentService.php`, `routes/cap021.php`
Gap: aucun
Dependencies: aucune
Decision: idem CAP-019
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-022 — Partage
Status: CLOSED
Evidence: `app/Application/Sharing/ContextShareService.php`, `/partages`
Gap: aucun
Dependencies: aucune
Decision: idem CAP-019
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-023 — Le graphe des capacités
Status: PARTIAL
Evidence: `CapabilityStatement`, `CapabilityStatementSynchronizer::matchingLabels()` alimente plusieurs moteurs
Gap: pas de vue « graphe » navigable — tables appariées par label, pas de traversée exposée
Dependencies: aucune
Decision: documenter comme graphe implicite ; construire une vue de traversée seulement si un besoin produit réel apparaît
Implementation PR: —
Final SHA: —

## CAP-024 — Le profil n'est pas une page, c'est une source de capacités
Status: CLOSED
Evidence: `CapabilityStatementSynchronizer::sync()` régénère des `CapabilityStatement` à chaque édition de profil
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-025 — Disponibilité
Status: CLOSED
Evidence: `dg_person_profiles.availability_status/note/updated_at`, formulaire de profil (section collaboration), fiche personne découvrable, `PersonRecommendationEngine` (raison supplémentaire) — fiche `docs/capacites/specs/CAP-025-disponibilite.md`
Gap: intégration aux flux d'invitation Mission/Transmission non faite — documentée hors périmètre v1 (POST-BETA), non bloquante pour le contrat CAP-025 lui-même
Dependencies: aucune
Decision: aucune action requise pour la v1
Implementation PR: #17
Final SHA: 4e7df340881595800f94b1dcf8072ed94d6433ab

## CAP-026 — Intention
Status: CLOSED
Evidence: `PersonProfile.intentions`, édité dans le profil
Gap: le champ n'est pas encore utilisé comme signal par le moteur de recommandation
Dependencies: aucune
Decision: ajouter comme signal de matching si prioritaire — non bloquant
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-027 — Le tableau de bord comme « prochaine action »
Status: CLOSED
Evidence: `MemberSpaceController::priority()`, `/espace`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-028 — Home personnalisée
Status: CLOSED
Evidence: `/espace` (`MemberSpaceController`) est confirmé comme la destination réelle du retour connecté — `routes/web.php` redirige `/connexion` vers `/espace` (`MemberSessionController::store`, vérifié par tous les tests de connexion, ex. `ZumraMembershipPaymentTest::signIn()` → `assertRedirect('/espace')`). Il agrège déjà recommandations, groupes ZUMRA, aperçu d'activité et partages reçus.
Gap: aucun — décision de consolidation validée : CAP-028 = CAP-027, pas un second écran distinct
Dependencies: CAP-027 (CLOSED)
Decision: consolidé avec CAP-027 dans le référentiel ; aucune construction séparée
Implementation PR: — (consolidation documentaire uniquement)
Final SHA: —

## CAP-029 — Découverte
Status: DOC_ONLY
Evidence: réalisée par les listings existants (`/personnes`, `/besoins`, `/projets`, `/zumra/groupes`, Fil)
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-030 — Moteur de correspondance
Status: CLOSED
Evidence: 4 moteurs dédiés — Personne, Projet, Mission, Transmission
Gap: logique dupliquée par domaine plutôt qu'un moteur générique unique
Dependencies: aucune
Decision: factoriser seulement si un 5e domaine de matching apparaît
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-031 — Explicabilité des recommandations
Status: CLOSED
Evidence: chaque moteur retourne des `reasons` en langage humain, jamais un score
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-032 — L'objet « Besoin »
Status: DOC_ONLY
Evidence: `Need.php`, déjà objet de 1ère classe consommé transversalement
Gap: aucun
Dependencies: aucune
Decision: référencer CAP-013 comme implémentation
Implementation PR: —
Final SHA: —

## CAP-033 — « Offrir une capacité »
Status: CLOSED
Evidence: `MissionAssignmentService::offer()/acceptOffer()`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module (`routes/cap069.php`)
Final SHA: —

## CAP-034 — Apprentissage comme réponse à un besoin
Status: CLOSED
Evidence: `Transmission::CONTEXT_NEED`, résolu par `TransmissionContextService`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #13
Final SHA: 46318988d9d1dee8ca56bf4861802c3b294b721e

## CAP-035 — Mémoire d'expérience
Status: DOC_ONLY
Evidence: vue agrégée des Preuves — `CARNET-DE-PREUVES.md` §3, `ProofController::memory()`
Gap: aucun — volontairement pas d'objet séparé
Dependencies: CAP-036 (CLOSED)
Decision: aucune action requise
Implementation PR: #14
Final SHA: 7571089c6d71309d5e7272bb6faf045b4bb60d60

## CAP-036 — Preuve de capacité
Status: CLOSED
Evidence: module dédié complet — `app/Application/Proof/*`, `routes/cap036.php`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #14
Final SHA: 7571089c6d71309d5e7272bb6faf045b4bb60d60

## CAP-037 — La ZUMRA comme micro-espace de travail
Status: CLOSED
Evidence: `ZumraGroupController::show` agrège les `Need`/`Project` du groupe (owner_type=GROUP), filtrés par `NeedService::canView`/`ProjectService::canView`, affichés dans `zumra/groups/show.blade.php`
Gap: aucun
Dependencies: aucune
Decision: extension additive de la fiche existante, aucune nouvelle table
Implementation PR: #21
Final SHA: d1152ac45b2489ad52dcbb6cfc039d71606e01df

## CAP-038 — Tableau de bord collectif
Status: CLOSED
Evidence: `ZumraGroupController::collectivePriority()` — une seule priorité dominante pour les responsables (demande d'adhésion → besoin PROPOSED → projet PROPOSED → null), même patron que `MemberSpaceController::priority()`
Gap: aucun
Dependencies: aucune
Decision: aucun siège vacant en priorité (aucune action réelle n'existe pour en proposer un)
Implementation PR: #21
Final SHA: d1152ac45b2489ad52dcbb6cfc039d71606e01df

## CAP-039 — La ZUMRA comme capacité d'émergence
Status: CLOSED
Evidence: chaîne `MATURITY_EMERGING` → `OWNER_GROUP` → `ProjectSatelliteLauncherService`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-040 — Projet comme objet indépendant
Status: CLOSED
Evidence: `Project.php`, `/projets` autonome
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-041 — Équipe projet
Status: CLOSED
Evidence: `ProjectTeamMember` (table `dg_project_team_members`) + `ProjectTeamService` (demande/invitation/acceptation/départ/retrait), autorité réutilisée depuis `ProjectService::canView/canDecide`, invitation consentie via `PersonProfile.discovery_reference`/`discovery_consent`, section « Équipe du projet » sur `/projets/{project}`
Gap: aucun
Dependencies: aucune
Decision: table additive distincte de `ProjectMatchDecision` (suggestion masquée ≠ adhésion réelle)
Implementation PR: #23
Final SHA: 5699162d908ebad20df9104c6a488a50fc8ceb4d

## CAP-042 — Besoin projet
Status: CLOSED
Evidence: `Need::OWNER_PROJECT` + `NeedService` étendu (éligibilité : porteur/initiateur/membre d'équipe `ACTIF`, statut `OPEN` si `ProjectAuthority::canDecide` sinon `PROPOSED`), carte « Besoins du projet » sur `/projets/{project}`
Gap: aucun
Dependencies: aucune
Decision: `ProjectAuthority` extraite de `ProjectService::canView/canDecide` (comportement inchangé) pour être réutilisée par `NeedService` sans dépendance circulaire ; `required_capabilities`/`required_resources` restent l'instantané figé de création, inchangés
Implementation PR: #25
Final SHA: d7b456c2ba17fb726be7b18400e64a756629f44d

## CAP-043 — Dossier de projet vivant
Status: CLOSED
Evidence: `/projets/{p}` agrège jalons, maturité, accompagnement, autonomie
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-044 — Maturité calculée par signes, pas par décret
Status: CLOSED
Evidence: `ProjectSignalsEngine::forProject()` (jalons, équipe, besoins, contributions, dernière activité — phrases factuelles, jamais un score/pourcentage), panneau « Signaux observés » sur `/projets/{project}`
Gap: aucun
Dependencies: aucune
Decision: panneau strictement consultatif — `ProjectMaturityService::change()` reste l'unique chemin d'écriture sur `Project.maturity`, aucun signal ne déclenche jamais une transition automatique
Implementation PR: #27
Final SHA: —

## CAP-045 — Accompagnement
Status: PARTIAL
Evidence: `ProjectAccompanimentService` (activation/fin/interventions) ; `CAP-016-accompagnement-dg-afrique.md` réserve la file de demandes à CAP-045
Gap: pas de file de demandes/priorisation, seulement une activation directe
Dependencies: aucune
Decision: ajouter une file de demandes distincte de l'activation directe
Implementation PR: —
Final SHA: —

## CAP-046 — Dossier d'accompagnement
Status: PARTIAL
Evidence: `ProjectAccompanimentAction` = liste chronologique basique
Gap: pas de vue dossier enrichie (synthèse, filtres par partenaire/catégorie)
Dependencies: aucune
Decision: enrichir la vue au-delà de la chronologie brute
Implementation PR: —
Final SHA: —

## CAP-047 — Le « satellite » comme changement de nature
Status: NOT_IMPLEMENTED
Evidence: `ProjectAutonomyPathway` ne fait que signaler une intention ; `CAP-018-lanceur-satellites.md` réserve ce changement de nature à CAP-047
Gap: aucun mécanisme technique de bascule réelle projet→satellite
Dependencies: CAP-048, CAP-049
Decision: bloqué tant que CAP-048/049 n'existent pas
Implementation PR: —
Final SHA: —

## CAP-048 — Registre des satellites
Status: NOT_IMPLEMENTED
Evidence: un seul satellite codé en dur dans `config/federation.php`
Gap: aucun registre réel (table, CRUD, écran d'administration)
Dependencies: aucune
Decision: créer table `dg_satellites` + admin avant toute relation générique
Implementation PR: —
Final SHA: —

## CAP-049 — Relation satellite ↔ Core
Status: PARTIAL
Evidence: `FederatedProductGateway` — continuité SSO fédérée, GamaDrive uniquement
Gap: relation ni généralisée ni pilotée par un registre (CAP-048)
Dependencies: CAP-048
Decision: généraliser une fois CAP-048 construit
Implementation PR: —
Final SHA: —

## CAP-050 — DG Afrique comme client du Core
Status: CLOSED
Evidence: `GamadCoreClient`, `RequireCoreMember`, `FederatedProductGateway::open`
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-051 — Portabilité de l'identité
Status: PARTIAL
Evidence: `PortalMemberSession` + `FederatedProductGateway::open` (portabilité réelle mais limitée à GamaDrive)
Gap: pas de portabilité générique multi-satellites
Dependencies: CAP-048
Decision: étendre via le registre CAP-048
Implementation PR: —
Final SHA: —

## CAP-052 — Séparation des contextes
Status: DOC_ONLY
Evidence: invisibilité GAMAD honorée structurellement — `app/Infrastructure/GamadCore/` isolé, aucun nom Core exposé en erreur
Gap: aucun
Dependencies: aucune
Decision: surveiller que les futures vues ne fuient jamais le nom GAMAD/Core
Implementation PR: —
Final SHA: —

## CAP-053 — Consentement
Status: PARTIAL
Evidence: consentements réels mais dispersés — `orientation_consent`, `discovery_consent`, `matching_consent`, `collective_capability_consent`
Gap: pas de centre de consentement unifié (audit transversal, révocation groupée)
Dependencies: aucune
Decision: introduire un modèle `Consent` transversal additif — non prioritaire
Implementation PR: —
Final SHA: —

## CAP-054 — Notifications
Status: CLOSED
Evidence: `app/Application/Notifications/*`, `routes/cap054.php`, fiche `docs/capacites/specs/NOTIFICATIONS.md`
Gap: canal in-app uniquement — documenté comme POST-BETA dès la fiche (§14)
Dependencies: aucune
Decision: aucune action requise pour la v1
Implementation PR: #15
Final SHA: 5030bf50d2d4e258ee8e040ed730f3fae299c98a

## CAP-055 — Fil d'activité intelligent
Status: NOT_IMPLEMENTED
Evidence: `ActivityFeedService` = priorité globale fixe, identique pour tout spectateur ; doctrine anti-scoring explicite
Gap: aucune personnalisation/pondération par spectateur
Dependencies: aucune
Decision: spécifier ce que « intelligent » veut dire sous contrainte anti-score avant de construire
Implementation PR: —
Final SHA: —

## CAP-056 — Publication
Status: PARTIAL
Evidence: cycle brouillon→publication existe par objet (`Need`, `Mission`)
Gap: patron dupliqué ad hoc par domaine, aucun contrat CAP-056 documenté
Dependencies: aucune
Decision: documenter le patron existant comme contrat canonique
Implementation PR: —
Final SHA: —

## CAP-057 — Commentaire comme contribution
Status: CLOSED
Evidence: `ContextComment` (purposes QUESTION/PRECISION/ADVICE/RESOURCE/COORDINATION)
Gap: aucun
Dependencies: CAP-021 (CLOSED)
Decision: consolider dans le référentiel comme = CAP-021
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-058 — Messagerie contextuelle
Status: CLOSED
Evidence: `MessageConversation` multi-contexte (Besoin/ZUMRA/Projet/Mission/Transmission)
Gap: aucun
Dependencies: CAP-020 (CLOSED)
Decision: consolider avec CAP-020
Implementation PR: antérieur au workflow PR-par-module
Final SHA: —

## CAP-059 — Conversation → action
Status: NOT_IMPLEMENTED
Evidence: `MessageEntry` = texte brut seulement
Gap: aucun mécanisme de promotion d'un message en Mission/Besoin
Dependencies: aucune
Decision: spécifier quels contextes de conversation peuvent engendrer une action
Implementation PR: —
Final SHA: —

## CAP-060 — Réputation : grande prudence
Status: DOC_ONLY
Evidence: doctrine anti-score répétée dans presque chaque fiche, aucun modèle de score nulle part
Gap: aucun
Dependencies: aucune
Decision: garder comme point de vigilance à chaque nouvelle fonctionnalité
Implementation PR: —
Final SHA: —

## CAP-061 — Contributions financières
Status: NOT_IMPLEMENTED
Evidence: seul `ZumraPayment` (`PURPOSE_MEMBERSHIP` uniquement) existe
Gap: aucune contribution financière à portée générale
Dependencies: aucune
Decision: spécifier avant de construire ; ne pas réutiliser `ZumraPayment` ; ARRÊT si ambiguïté doctrinale (règle finance §40)
Implementation PR: —
Final SHA: —

## CAP-062 — Ledger / traçabilité
Status: NOT_IMPLEMENTED
Evidence: seuls des journaux d'événements métier (`*Event`) existent, pas un ledger financier
Gap: aucun objet de traçabilité financière
Dependencies: CAP-061
Decision: dépend de CAP-061
Implementation PR: —
Final SHA: —

## CAP-063 — Financement de projet
Status: DEPENDENCY_BLOCKED
Evidence: `Project`/`ProjectAccompaniment*` sans aucun champ de financement
Gap: ne peut exister sans CAP-061 (contributions) et CAP-062 (ledger)
Dependencies: CAP-061, CAP-062
Decision: bloqué — construire 061/062 d'abord
Implementation PR: —
Final SHA: —

## CAP-064 — Moteur d'opportunités
Status: NOT_IMPLEMENTED
Evidence: CAP-021/022 déclarent explicitement l'absence d'objet « Opportunité » canonique
Gap: aucun objet métier Opportunité n'existe
Dependencies: aucune
Decision: définir l'objet Opportunité avant de construire le moteur
Implementation PR: —
Final SHA: —

## CAP-065 — Le partenaire comme fournisseur de capacité
Status: NOT_IMPLEMENTED
Evidence: seul un label texte `SOURCE_PARTNER` existe sur `ProjectAccompanimentAction`
Gap: pas d'entité Partenaire structurée (identité, capacités, cycle de vie)
Dependencies: CAP-066, CAP-067
Decision: dépend de CAP-066/067
Implementation PR: —
Final SHA: —

## CAP-066 — Organisation
Status: NOT_IMPLEMENTED
Evidence: `ZumraGroup` = groupe humain informel, concept distinct
Gap: aucune entité Organisation (identité légale, hiérarchie)
Dependencies: aucune
Decision: spécifier en distinguant explicitement de ZumraGroup
Implementation PR: —
Final SHA: —

## CAP-067 — Identité organisationnelle
Status: DEPENDENCY_BLOCKED
Evidence: aucun objet Organisation auquel rattacher une identité
Gap: ne peut exister avant CAP-066
Dependencies: CAP-066
Decision: construire ensemble avec CAP-066
Implementation PR: —
Final SHA: —

## CAP-068 — Événement
Status: NOT_IMPLEMENTED
Evidence: seuls des journaux d'audit `*Event` existent (concept différent, à ne pas confondre)
Gap: aucun objet calendrier/réunion (date, lieu, invités, RSVP)
Dependencies: aucune
Decision: spécifier un objet de planification distinct du patron d'audit
Implementation PR: —
Final SHA: —

## CAP-069 — Tâche
Status: CLOSED
Evidence: module Missions complet — `app/Application/Missions/*`, `routes/cap069.php` (produit « Missions » = CAP-069 TÂCHE)
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: #12
Final SHA: —

## CAP-070 — Document
Status: DEPENDENCY_BLOCKED
Evidence: seule une continuité SSO existe (`routes/federation.php`), aucune API de stockage documentaire
Gap: aucun stockage/versionnement documentaire réel
Dependencies: GamaDrive (API de stockage réelle, externe)
Decision: bloqué tant que GamaDrive ne livre pas une vraie API documentaire ; ne pas construire de stockage parallèle
Implementation PR: —
Final SHA: —

## CAP-071 — Architecture de navigation future
Status: DOC_ONLY
Evidence: `DESIGN-INVARIANTS.md` §4 — espace réservé aux futurs satellites, honoré dans le shell
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-072 — La règle UX principale
Status: DOC_ONLY
Evidence: `DESIGN-INVARIANTS.md` §7/§8 ; algorithme de priorité réel dans Mon espace
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-073 — Progressive disclosure
Status: DOC_ONLY
Evidence: pratiqué (profil par étapes) sans être nommé explicitement
Gap: le principe n'est jamais nommé ainsi dans la doc
Dependencies: aucune
Decision: nommer le principe explicitement si besoin de traçabilité — non prioritaire
Implementation PR: —
Final SHA: —

## CAP-074 — DG Afrique comme orchestrateur
Status: DOC_ONLY
Evidence: séparation stricte `GamadCore` (primitives) / `Application` (orchestration métier)
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-075 — Le Core ne doit pas connaître toute la philosophie ZUMRA
Status: DOC_ONLY
Evidence: `GamadCoreClient` n'expose que identité/session, aucun concept ZUMRA n'y transite
Gap: aucun
Dependencies: aucune
Decision: surveiller les futurs appels Core
Implementation PR: —
Final SHA: —

## CAP-076 — Primitives vs produits
Status: DOC_ONLY
Evidence: séparation `GamadCore`/`Application` honorée dans toute l'arborescence
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-077 — API de capacités
Status: NOT_IMPLEMENTED
Evidence: aucun `routes/api.php`, aucun groupe `api:` dans `bootstrap/app.php`
Gap: aucune surface API technique n'existe
Dependencies: aucune
Decision: spécifier et construire si un besoin d'intégration externe apparaît — non prioritaire
Implementation PR: —
Final SHA: —

## CAP-078 — Le satellite peut devenir fournisseur de capacité
Status: DEPENDENCY_BLOCKED
Evidence: un seul satellite câblé en dur, aucun registre (CAP-048)
Gap: rien qu'un satellite puisse « fournir » sans registre
Dependencies: CAP-047, CAP-048, CAP-049, CAP-050
Decision: bloqué
Implementation PR: —
Final SHA: —

## CAP-079 — Boucle d'écosystème
Status: DEPENDENCY_BLOCKED
Evidence: passerelle simple vers GamaDrive, aucune remontée de capacités
Gap: aucune boucle possible avec un satellite unique
Dependencies: CAP-047, CAP-048, CAP-049, CAP-050
Decision: bloqué
Implementation PR: —
Final SHA: —

## CAP-080 — Ce que devrait mesurer DG Afrique
Status: NOT_IMPLEMENTED
Evidence: seules des exclusions doctrinales existent (pas de métriques de productivité/sociales)
Gap: aucune définition positive des indicateurs de succès
Dependencies: aucune
Decision: rédiger une fiche doctrinale dédiée avant tout analytics
Implementation PR: —
Final SHA: —

## CAP-081 — La grande différence avec un réseau social
Status: DOC_ONLY
Evidence: OVR-001, doctrine anti-classement ; `PersonRecommendationEngine` produit des raisons, jamais un score
Gap: aucun
Dependencies: aucune
Decision: maintenir le test de non-régression anti-score
Implementation PR: —
Final SHA: —

## CAP-082 — La grande différence avec LinkedIn
Status: NOT_IMPLEMENTED
Evidence: aucune fiche n'articule cette distinction — seulement implicite dans le matching par besoins
Gap: aucun texte doctrinal dédié
Dependencies: aucune
Decision: écrire une note doctrinale si cette position doit rester assumée — non prioritaire
Implementation PR: —
Final SHA: —

## CAP-083 — La grande différence avec une plateforme d'incubation
Status: DOC_ONLY
Evidence: `CAP-016-accompagnement-dg-afrique.md` exclut explicitement un workflow d'incubation lourd
Gap: aucun
Dependencies: aucune
Decision: aucune action requise
Implementation PR: —
Final SHA: —

## CAP-084 — La phrase « lanceur de satellites » devient technique
Status: DEPENDENCY_BLOCKED
Evidence: CAP-018 est un parcours de préparation, explicitement pas encore le satellite réel
Gap: matérialisation complète dépend de CAP-047-050
Dependencies: CAP-047, CAP-048, CAP-049, CAP-050
Decision: considérer comme jalon final de CAP-047-050, pas une capacité autonome
Implementation PR: —
Final SHA: —
