# USER-JOURNEY-001 / UJ-00 — matrice des contrats d'écran

## Statut

`PASS STATIQUE — CONTRATS CARTOGRAPHIÉS — AUCUN FRONTEND CRÉÉ`

Cette matrice est la preuve de sortie du lot `UJ-00`. Elle est subordonnée à
`USER-JOURNEY-001.md` et ne constitue pas une roadmap concurrente.

Baseline auditée : GitHub `main @ 09e811b457ef0c60e4ecfe29ce75848bb305ff8f`, arbre
`314b0340df907d6c01709f68d17f80b6bd9bab12`.

## 1. Verdict

Le moteur couvre tous les centres humains retenus pour le frontend neuf : entrée, identité,
espace personnel, Fil, personnes, besoins, projets, ZUMRA, missions, transmissions, preuves,
organisations, événements, contributions, ZAHAB, administration et modération.

La construction peut passer à `UJ-01`, à condition de respecter quatre vérités :

1. une route ou un contrôleur de rendu n'est pas une permission ; l'autorité reste dans le
   middleware et les services métier ;
2. les contrôleurs qui retournent encore une `View` sont des contrats de présentation sans vue
   depuis l'excision volontaire ; `UJ-01+` recrée le rendu, jamais le moteur ;
3. les endpoints JSON doivent recevoir une projection humaine contextuelle, pas un service métier
   concurrent ;
4. les anciennes promesses sans moteur sont enregistrées au §9, mais ne doivent produire aucun
   bouton avant un lot backend explicite.

## 2. Méthode et limites

Audit statique exhaustif de :

- `routes/*.php` et des 346 routes nommées ;
- 92 contrôleurs HTTP ;
- 87 services applicatifs ;
- 82 modèles ;
- middlewares `core.member`, `portal.admin`, signatures et limites de débit ;
- constantes d'état, règles de visibilité, méthodes `canView`/`canDecide`/`assert*` ;
- réponses `View`, `JsonResponse`, redirections et erreurs métier ;
- tests Feature, ExternalContracts et Runtime déjà présents ;
- `FUNCTIONAL-COVERAGE-001`, utilisé comme inventaire archéologique des interactions, jamais comme
  description de l'interface courante supprimée.

L'environnement local ne possède ni PHP ni `vendor`. Aucun test runtime n'a donc été rejoué pour
ce lot documentaire. Cette limite ne réouvre pas `ENGINE-TRUTH-FINAL-001` : aucun fichier sous
`app/`, `bootstrap/`, `config/`, `database/`, `routes/` ou `tests/` n'est modifié par `UJ-00`.

## 3. Couverture exhaustive des routes

Chaque préfixe de route est affecté à au moins un contrat d'écran du §7.

| Préfixe | Nombre | Contrats |
|---|---:|---|
| `administration.*` | 65 | S25, S26, S34, S48, S50 à S53 |
| `projects.*` | 48 | S17 à S26, S35 |
| `missions.*` | 41 | S35 à S37 |
| `zumra.*` | 29 | S27 à S35, S46, S47, S52 |
| `transmissions.*` | 25 | S38 à S39 |
| `proofs.*` | 13 | S40 à S41 |
| `community-events.*` | 13 | S44 |
| `comments.*` | 12 | S13 |
| `messages.*` | 12 | S12 |
| `shares.*` | 12 | S13 |
| `contributions.*` | 10 | S46 |
| `organizations.*` | 8 | S42 |
| `partnerships.*` | 8 | S43 |
| `needs.*` | 7 | S14 à S16, S35 |
| `moderation.*` | 7 | S52 |
| `zahab.*` | 6 | S47 |
| `register.*` | 5 | S04 |
| `member.*` | 4 | S05 à S06 |
| `impact-metrics.*` | 3 | S45 |
| `recommendations.*` | 3 | S10 |
| `ledger.*` | 2 | S48 |
| `login.*` | 2 | S03 |
| `people.*` | 2 | S09 |
| `activity.*` | 1 | S07 |
| `federation.*` | 1 | S49 |
| `gateway` | 1 | S01 |
| `home` | 1 | S01 |
| `landing` | 1 | S02 |
| `logout` | 1 | S03 |
| `notifications.*` | 1 | S08 |
| `opportunities.*` | 1 | S11 |
| `readiness` | 1 | S54 |
| **Total** | **346** | **couverture complète** |

Les 371 déclarations `Route::` incluent les groupes et chargements ; 346 routes nommées forment la
surface fonctionnelle adressable.

## 4. Codes d'autorité

| Code | Autorité réelle |
|---|---|
| `A-PUBLIC` | visiteur ou membre ; aucune identité membre exigée |
| `A-MEMBER` | middleware `core.member`, session vérifiée à chaque requête auprès de GAMAD Core |
| `A-ADMIN` | `core.member` + `portal.admin` + présence dans `PortalAdministrator` |
| `A-SIGNED` | URL signée et limitée ; généralement retour paiement ou vérification de carte |
| `A-OWNER` | identité propriétaire de l'objet ou sujet exact |
| `A-DECIDER` | `ProjectService::canDecide`, `NeedService::canDecide` ou autorité équivalente |
| `A-CONTEXT` | autorité du Projet, Besoin, ZUMRA ou Organisation porteur du contexte |
| `A-LEADER` | responsable ZUMRA reconnu par `ZumraGroupService` |
| `A-ORG-MANAGER` | owner/admin actif reconnu par `OrganizationService::isManager` |
| `A-PARTICIPANT` | membre d'équipe, assignation ou participation acceptée selon le domaine |
| `A-PROVIDER` | fournisseur du partenariat + autorité du contexte pour certaines transitions |
| `A-SUBJECT` | personne visée par invitation, décision, reçu ou opération personnelle |

Toutes les mutations restent sous middleware `web`/CSRF et limite de débit dédiée. La présence
d'un bouton ne remplace jamais ces autorités serveur.

## 5. Codes d'erreur et états transversaux

| Code | Traduction frontend obligatoire |
|---|---|
| `E-SESSION` | absence de session → connexion avec retour sûr ; expiration/mauvaise identité → session effacée et message clair |
| `E-CORE` | GAMAD Core indisponible/protocole invalide → page 503, aucune fausse session locale |
| `E-403` | action interdite → ne pas muter ; expliquer sans dévoiler d'information sensible |
| `E-404` | objet absent ou volontairement dissimulé par une règle de visibilité |
| `E-409` | transition impossible, doublon, état déjà consommé ou conflit concurrent |
| `E-422` | données invalides, précondition métier incomplète ou montant non admissible |
| `E-429` | limite de débit → attente et réessai, jamais répétition automatique agressive |
| `E-DEPENDENCY` | GAMAD Core, GeniusPay, DeepSeek ou satellite indisponible → état conservé, reprise explicite |
| `E-PAYMENT` | pending/processing/completed/failed/cancelled/refunded affichés depuis la vérité persistée |
| `E-EMPTY` | aucune donnée réelle → état vide honnête avec action permise, jamais fixture |
| `E-NETWORK` | chargement/perte réseau/reprise traités par le futur composant transversal |

Chaque surface doit prévoir `chargement`, `vide`, `contenu`, `interdit`, `introuvable`, `conflit`,
`validation`, `indisponible` et `succès` lorsque ces états sont possibles.

## 6. Registre des états métier

| Code | Modèle et états à rendre |
|---|---|
| `ST-CAPABILITY` | `CapabilityStatement` : POSSESSED/LEARNING/TRANSMISSION ; DECLARED/VERIFIED/ATTESTED ; PRIVATE/DISCOVERABLE |
| `ST-NEED` | `Need` : PROPOSED → OPEN → IN_PROGRESS → RESOLVED → ARCHIVED ; visibilité PRIVATE/GROUP/PROGRAM/PUBLIC |
| `ST-PROJECT-DRAFT` | `ProjectDraft` : DRAFT/CONFIRMED/ABANDONED |
| `ST-PROJECT` | `Project` : PROPOSED/ADOPTED/IN_PROGRESS/COMPLETED/ARCHIVED ; visibilité PRIVATE/GROUP/PROGRAM/PUBLIC |
| `ST-PROJECT-TEAM` | `ProjectTeamMember` : REQUESTED/INVITED/ACTIVE/LEFT/REMOVED |
| `ST-FUNDING` | `ProjectFunding` : OPEN/FUNDED/CLOSED/CANCELLED |
| `ST-ACCOMPANIMENT` | accompagnement ACTIVE/ENDED ; demandes PENDING/ACKNOWLEDGED/CLOSED |
| `ST-AUTONOMY` | parcours d'autonomie ACTIVE/CLOSED, seulement après maturité admissible |
| `ST-ZUMRA` | `ZumraGroup` : CONSTITUTING/READY/VALIDATED/ACTIVE/WARNED/SUSPENDED/REHABILITATING |
| `ST-ZUMRA-MEMBER` | REQUESTED/INVITED/ACTIVE/LEFT/EXCLUDED/SUSPENDED |
| `ST-ZUMRA-ROLE` | VACANT/PROPOSED/ACCEPTED |
| `ST-ZUMRA-PROGRAM` | adhésion PENDING_PAYMENT/ACTIVE/SUSPENDED/CLOSED ; carte ACTIVE/REVOKED |
| `ST-MISSION` | DRAFT/PROPOSED/CHANGES_REQUESTED/REJECTED/OPEN/IN_PROGRESS/BLOCKED/SUBMITTED/COMPLETED/CANCELLED |
| `ST-MISSION-ASSIGNMENT` | OFFERED/INVITED/ACCEPTED/DECLINED/WITHDRAWN/RELEASED/REMOVED |
| `ST-RECURRENCE` | ACTIVE/PAUSED/STOPPED |
| `ST-TRANSMISSION` | PROPOSED/ACCEPTED/IN_PROGRESS/COMPLETED_CONFIRMED/COMPLETED_BY_CONTEXT/ENDED/CANCELLED |
| `ST-TRANSMISSION-PARTICIPANT` | INVITED/OFFERED/ACCEPTED/DECLINED/WITHDRAWN/REMOVED |
| `ST-PROOF` | SUBMITTED/WITNESSED/ACKNOWLEDGED/DISPUTED ; témoin INVITED/CONFIRMED/DECLINED |
| `ST-ORGANIZATION` | ACTIVE/ARCHIVED ; membre REQUESTED/INVITED/ACTIVE/LEFT/REMOVED |
| `ST-PARTNERSHIP` | PROPOSED/ACTIVE/PAUSED/ENDED ; visibilité PRIVATE/PUBLIC |
| `ST-EVENT` | SCHEDULED/COMPLETED/CANCELLED ; participant REGISTERED/WITHDRAWN |
| `ST-CONTRIBUTION` | PROPOSED/ACTIVE/PAUSED/STOPPED ; finalité ACTIVE/RETIRED |
| `ST-PAYMENT` | paiement/acquisition PENDING/PROCESSING/COMPLETED/FAILED/CANCELLED/REFUNDED |
| `ST-MODERATION` | rapport PENDING/DECIDED/DISMISSED ; décision ACTIVE/LIFTED/EXPIRED/MODIFIED |

## 7. Matrice canonique des écrans

### 7.1 Entrée, identité et cockpit

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S01 | Gateway et alias accueil : `gateway`, `home` | visiteur/membre ; redirection membre | `GatewayController`, `PortalMemberSession` | A-PUBLIC | découvrir, se connecter, rejoindre ; rediriger un membre vers son espace | E-SESSION, E-EMPTY |
| S02 | Découvrir : `landing` | besoins/projets PUBLIC réels ou vide | `LandingController`, `NeedService::canView`, `ProjectService::canView` | A-PUBLIC | comprendre puis entrer dans le compte ; aucune mutation | E-EMPTY, E-NETWORK |
| S03 | Connexion/déconnexion : `login*`, `logout` | anonyme, authentifié, session expirée | `MemberSessionController`, `GamadCoreClient`, `PortalMemberSession`, `SafeLocalDestination` | A-PUBLIC/A-MEMBER | ouvrir/fermer session ; destination de retour locale sûre | E-SESSION, E-CORE, E-422, E-429 |
| S04 | Compte et vérification : `register*` | saisie, vérification pending, expirée, livrée/non livrée, vérifiée | `AccountRegistrationController`, `PendingAccountSession`, `GamadCoreClient` | A-PUBLIC | créer, vérifier, renvoyer le code | E-409, E-422, E-429, E-DEPENDENCY |
| S05 | Mon espace : `member.space` | nouvelle personne/active ; priorité unique ; attention, notification, opportunité, mission, transmission, preuve | `MemberSpaceController`, `NotificationService`, `OpportunityEngine`, `MissionService`, `TransmissionService`, `ProofService`, `ZumraAttentionSource` | A-MEMBER/A-SUBJECT | choisir première intention ; ouvrir l'unique priorité et au plus deux actions | E-SESSION, E-CORE, E-EMPTY |
| S06 | Mon profil/capacité rapide : `member.profile.*`, `member.capability.quick` | ST-CAPABILITY ; profil incomplet/complet, consentements | `MemberProfileController`, `QuickCapabilityController`, `ProfileList`, `CapabilityStatementSynchronizer`, `ProfileConfiguration` | A-MEMBER/A-OWNER | déclarer/mettre à jour profil, capacité, disponibilité et consentements | E-403, E-409, E-422, E-429 |
| S07 | Fil : `activity.index` | événements autorisés, pertinence expliquée, filtres/pagination, vide | `ActivityFeedController`, `ActivityFeedService`, autorités des domaines sources | A-MEMBER | filtrer, ouvrir le contexte ; aucune mutation sociale artificielle | E-404, E-EMPTY, E-NETWORK |
| S08 | Notifications : `notifications.index` | actionnable/FYI/lu, sans compteur de pression | `NotificationController`, `NotificationService`, `NotificationSourceRegistry` | A-MEMBER/A-SUBJECT | ouvrir la source réelle ; marquage FYI selon service | E-404, E-EMPTY |
| S09 | Personnes : `people.index/show` | profil découvrable, consentements, capacités et disponibilité réelles | `PeopleDiscoveryController`, `PeopleDiscoveryConfiguration`, `CapabilityStatementSynchronizer` | A-MEMBER ; consentement du profil | rechercher/filtrer localement, ouvrir, démarrer message si autorisé | E-404, E-EMPTY, E-429 |
| S10 | Recommandations : `recommendations.*` | recommandation visible/masquée, raisons explicables | `RecommendationController`, `PersonRecommendationEngine`, `RecommendationDecision` | A-MEMBER/A-SUBJECT | masquer/restaurer ; ouvrir la personne | E-404, E-409, E-429, E-EMPTY |
| S11 | Opportunités : `opportunities.index` | possibilités réelles et raisons, lecture seule | `OpportunityController`, `OpportunityEngine` | A-MEMBER | ouvrir le Besoin/Projet/ZUMRA/Mission pertinent | E-404, E-EMPTY, E-429 |

### 7.2 Communication contextuelle

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S12 | Messagerie : `messages.*` | conversations directes et contextes Need/ZUMRA/Invitation/Project/Mission/Transmission/Support | `MessagingController`, `MessagingService` | A-MEMBER + accès au contexte/participant | ouvrir conversation idempotente, ajouter participant projet autorisé, envoyer texte | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S13 | Commentaires et partages : `comments.*`, `shares.*` | fils/inbox réels pour Need/Project/ZUMRA activity/Mission/Transmission/Proof | `ContextCommentService`, `ContextShareService` + autorités source/cible | A-MEMBER/A-CONTEXT/A-PARTICIPANT | commenter avec finalité ; partager vers personne ou ZUMRA admissible | E-403, E-404, E-409, E-422, E-429, E-EMPTY |

### 7.3 Besoins

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S14 | Annuaire Besoins : `needs.index` | ST-NEED, filtres, visibilité, pagination, vide | `NeedController`, `NeedService`, `NeedConfiguration` | A-MEMBER + `canView` | filtrer, ouvrir, exprimer un besoin | E-404, E-EMPTY |
| S15 | Exprimer un Besoin : `needs.create/store` | propriétaire PERSON/GROUP/PROJECT, visibilité et validation | `NeedController`, `NeedService::create` | A-MEMBER + A-OWNER/A-LEADER/A-DECIDER selon propriétaire | créer directement un besoin réel | E-403, E-409, E-422, E-429 |
| S16 | Fiche et cycle Besoin : `needs.show/transition` | ST-NEED, contexte, historique, partenaires, missions | `NeedController`, `NeedService::canView/canDecide/transition`, `PartnershipService`, `MissionContextRegistry` | A-MEMBER/A-DECIDER | ouvrir/en cours/résoudre/archiver, commenter, partager, message, mission contextuelle | E-403, E-404, E-409, E-422, E-429 |

### 7.4 Projets

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S17 | Annuaire Projets : `projects.index` | ST-PROJECT, filtres, visibilité, pagination, vide | `ProjectController`, `ProjectService`, `ProjectHubPresentation` | A-MEMBER + `canView` | filtrer, ouvrir, commencer un projet | E-404, E-EMPTY |
| S18 | Naissance progressive : `projects.create`, `projects.draft.*` | ST-PROJECT-DRAFT ; étapes sauvegardées ; ZUMRA explicite | `ProjectDraftController`, `ProjectDraftService`, `ProjectAuthority`, `ZumraGroupService` | A-MEMBER/A-OWNER ; membre actif de la ZUMRA choisie | démarrer/reprendre, sauvegarder, créer/sélectionner ZUMRA, abandonner, confirmer | E-404, E-409, E-422, E-429 |
| S19 | Naissance assistée : `projects.brain.start*` | intention, conversation, contenu prêt/non prêt, ZUMRA manquante, CREATED | `ProjectBrainStartController`, `ProjectBrainAiProvider`, `ProjectBrainProjectBirthService`, `ProjectAuthority` | A-MEMBER/A-OWNER ; confirmation humaine obligatoire | dialoguer, sélectionner/créer ZUMRA, confirmer le projet | E-404, E-409, E-422, E-429, E-DEPENDENCY avec brouillon conservé |
| S20 | Hub Projet : `projects.show/overview` | ST-PROJECT + signaux, jalons, besoins, équipe, missions, financement, accompagnement | `ProjectController`, `ProjectHubPresentation`, `ProjectSignalsEngine`, services contextuels | A-MEMBER + `ProjectService::canView` | ouvrir les sous-parcours autorisés ; aucune décision fictive | E-403, E-404, E-EMPTY |
| S21 | Cerveau d'un Projet : `projects.brain.show/needs.* /drafts.cancel` | conversation, brouillon Need PENDING/CONFIRMED/CANCELLED | `ProjectBrainController`, `ProjectBrainNeedDraftService`, `ProjectService` | A-MEMBER + accès Projet + propriétaire du brouillon | converser, préparer, confirmer ou annuler un Besoin | E-403, E-404, E-409, E-422, E-DEPENDENCY |
| S22 | Équipe et matching : `projects.team.*`, `projects.matching*` | ST-PROJECT-TEAM ; suggestions visibles/masquées avec raisons | `ProjectTeamService`, `ProjectMatchingEngine`, `ProjectService` | A-MEMBER/A-DECIDER/A-SUBJECT | demander, inviter, accepter, approuver, quitter, retirer ; masquer suggestion | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S23 | Cycle, maturité et jalons : `projects.transition`, `projects.maturity.update`, `projects.milestones.complete` | ST-PROJECT ; jalon PLANNED/COMPLETED ; signaux consultatifs | `ProjectService`, `ProjectMaturityService`, `ProjectSignalsEngine` | A-DECIDER | adopter, démarrer, terminer, archiver selon transition ; compléter jalon ; mettre à jour maturité | E-403, E-404, E-409, E-422, E-429 |
| S24 | Financement Projet : `projects.funding.*` | ST-FUNDING, cible/collecté/reste, historique ZAHAB | `ProjectFundingService`, `ProjectFundingContributionService`, `ZahabWalletService` | lecture `canView` ; gestion A-DECIDER ; contribution A-MEMBER | déclarer, modifier, clôturer, annuler, contribuer avec jeton idempotent | E-403, E-404, E-409, E-422, E-429 |
| S25 | Accompagnement : `projects.accompaniment.*`, routes admin liées | ST-ACCOMPANIMENT | `ProjectAccompanimentService`, configuration | A-DECIDER pour activer/demander/terminer ; A-ADMIN pour traiter/intervenir | activer, demander, journaliser action, accuser réception, clore | E-403, E-404, E-409, E-422, E-429 |
| S26 | Autonomie : `projects.autonomy.*`, vue admin agrégée | ST-AUTONOMY, maturité éligible/non éligible | `ProjectAutonomyPathwayService`, `ProjectService` | A-DECIDER ; admin en lecture agrégée | ouvrir/fermer exploration d'une forme autonome, sans transformer le projet en satellite | E-403, E-404, E-409, E-422, E-429 |

### 7.5 ZUMRA

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S27 | Monde ZUMRA : `zumra.index`, redirection `zumra.groups.index` | mes groupes, invitations, rôles proposés, demandes à décider, découverte, ST-ZUMRA | `ZumraSpaceController`, `ZumraAttentionSource` | A-MEMBER | ouvrir attention, découvrir, faire naître, rejoindre | E-404, E-EMPTY |
| S28 | Adhésion programme : `zumra.membership.*` | ST-ZUMRA-PROGRAM, éligibilité paiement, solde ZAHAB | `ZumraProgramMembershipController`, `ZumraProgramConfiguration`, `ZahabWalletService` | A-MEMBER/A-SUBJECT | demander adhésion puis choisir paiement autorisé | E-409, E-422, E-429, E-DEPENDENCY |
| S29 | Paiement, reçu et carte : `zumra.payment.*`, `zumra.card.*` | ST-PAYMENT, reçu, carte ACTIVE/REVOKED | `MembershipPaymentService`, `ZumraCardIssuer` | A-MEMBER/A-SUBJECT ; retour/carte publique A-SIGNED | payer GeniusPay/ZAHAB, vérifier état persisté, voir reçu/carte | E-404, E-409, E-422, E-429, E-PAYMENT, E-DEPENDENCY |
| S30 | Naissance ZUMRA : `zumra.groups.create/store` | création CONSTITUTING, quotas fondateurs, configuration | `ZumraGroupController`, `ZumraGroupService::create`, `ZumraGroupConfiguration` | A-MEMBER | créer explicitement le groupe et son premier rôle | E-403, E-409, E-422, E-429 |
| S31 | Espace ZUMRA : `zumra.groups.show` | ST-ZUMRA, ST-ZUMRA-MEMBER, ST-ZUMRA-ROLE, activités, charte, projets/besoins/missions/événements | `ZumraGroupController` et services contextuels | A-MEMBER ; visibilité et appartenance selon sous-section | ouvrir les actions contextuelles réellement permises | E-403, E-404, E-409, E-EMPTY |
| S32 | Membres, invitations et rôles : `zumra.groups.request/invite/invitation.accept/requests.approve/leave/roles.*` | ST-ZUMRA-MEMBER, ST-ZUMRA-ROLE | `ZumraGroupService` | A-MEMBER/A-SUBJECT/A-LEADER | demander, inviter, accepter, approuver, quitter, proposer/accepter rôle | E-403, E-404, E-409, E-422, E-429 |
| S33 | Vie collective : `collective-capabilities.consent`, `contribution.propose/approve`, `charter.set`, `activities.add`, missions contextuelles | consentement, contribution ST-CONTRIBUTION, charte publiée, activité réelle | `CollectiveCapabilityProfile`, `ContributionService`, `ZumraGroupService`, `MissionWorkflow` | membre pour consentement ; A-LEADER/financier selon mutation | consentir, proposer/approuver contribution, publier charte, ajouter activité, créer mission | E-403, E-404, E-409, E-422, E-429 |
| S34 | Cycle administratif ZUMRA : `administration.zumra.groups.*`, carte revoke | ST-ZUMRA complet, carte ACTIVE/REVOKED | `ZumraGroupLifecycleController`, `ZumraGroupService`, admin `ZumraCardController` | A-ADMIN | READY/VALIDATED/ACTIVE/WARNED/SUSPENDED/REHABILITATING/reactivate ; révoquer carte | E-403, E-404, E-409, E-422, E-429 |

### 7.6 Missions, transmissions et preuves

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S35 | Missions — annuaire, fiche, création contextuelle : `missions.index/show`, `*.missions.create/store`, `missions.children.*` | ST-MISSION, visibilité, contexte Project/ZUMRA/Need/Mission parent | `MissionController`, `MissionService`, `MissionContextRegistry`, `MissionVisibilityService`, `MissionWorkflow` | A-MEMBER + visibilité ; création A-CONTEXT | filtrer, ouvrir, créer dans contexte, créer sous-mission | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S36 | Workflow Mission : propose/request-changes/resubmit/reject/officialize/start/block/submit/correct/validate/cancel/reopen | ST-MISSION + blocker + submission | `MissionWorkflow`, `MissionBlockerService`, `MissionSubmissionService` | officializer du contexte ou exécuteur accepté selon transition | exécuter uniquement la prochaine transition permise, avec raison/preuve requise | E-403, E-404, E-409, E-422, E-429 |
| S37 | Structure Mission : assignments/checklist/dependencies/requirements/contributions/matching/recurrence | ST-MISSION-ASSIGNMENT, ST-RECURRENCE, dépendances et blocages | services Mission dédiés, matching explicable | A-CONTEXT/A-PARTICIPANT/A-SUBJECT selon action | offrir/inviter/accepter/refuser/retirer ; structurer ; contribuer ; récurrence ; masquer match | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S38 | Transmissions — annuaire/création/fiche : `transmissions.index/create/store/show` | ST-TRANSMISSION, contexte Need/Mission/Project/ZUMRA, visibilité | `TransmissionController`, `TransmissionService`, `TransmissionContextService`, `TransmissionWorkflow` | A-MEMBER + accès contexte | créer, filtrer, ouvrir ; choisir rôle initiateur et visibilité admissible | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S39 | Workflow/participation Transmission : transitions, participants, jalons, contributions, matching | ST-TRANSMISSION, ST-TRANSMISSION-PARTICIPANT | `TransmissionWorkflow`, `TransmissionParticipationService`, `TransmissionService`, `TransmissionMatchingEngine` | A-CONTEXT/A-PARTICIPANT/A-SUBJECT | offrir/inviter/accepter/refuser/quitter ; démarrer/terminer/valider ; jalon/contribution ; masquer match | E-403, E-404, E-409, E-422, E-429 |
| S40 | Preuves — annuaire, mémoire, création, fiche : `proofs.index/create/store/memory*/show` | ST-PROOF, visibilité, références texte/URL ; aucune promesse documentaire tant que CAP-070 est bloquée | `ProofController`, `ProofService`, `ProofContextService`, `ProofVisibilityService`, `ProofWorkflow` | A-MEMBER + accès contexte/propriétaire | soumettre, consulter mémoire et fiche | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S41 | Témoins et cycle Preuve : `proofs.witnesses.*`, acknowledge/dispute/archive/restore | ST-PROOF + témoin INVITED/CONFIRMED/DECLINED | `ProofWorkflowController`, `ProofWorkflow` | A-OWNER/A-SUBJECT/A-CONTEXT selon transition | inviter/confirmer/refuser témoignage ; reconnaître/contester/archiver/restaurer | E-403, E-404, E-409, E-422, E-429 |

### 7.7 Organisations, partenariats, événements et mesure

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S42 | Organisations : `organizations.*` | ST-ORGANIZATION, membres, capacités, événements, partenariats | `OrganizationService`, `OrganizationCapabilityService`, `GamadCoreClient` | A-MEMBER ; A-ORG-MANAGER pour gestion | créer atomiquement avec Core, rejoindre/approuver, ajouter/retirer capacité | E-403, E-404, E-409, E-422, E-429, E-DEPENDENCY |
| S43 | Partenariats : `partnerships.*` | ST-PARTNERSHIP, contextes Project/ZUMRA/Need | `PartnershipService` | A-MEMBER + A-PROVIDER/A-CONTEXT | proposer, activer, pause, reprise, retrait, fin | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S44 | Événements : `community-events.*` | ST-EVENT, contexte ZUMRA/Organisation, visibilité, participants | `CommunityEventService`, autorités ZUMRA/Organisation | A-MEMBER ; organisateur A-LEADER/A-ORG-MANAGER | créer, modifier, annuler, terminer, s'inscrire/se désinscrire, voir participants autorisés | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S45 | Mesure collective : `impact-metrics.*` | projections réelles portail/ZUMRA/Organisation, jamais classement individuel | `ImpactMetricsService` | A-MEMBER + visibilité du contexte | lecture contextuelle seulement | E-403, E-404, E-429, E-EMPTY |

### 7.8 Contributions, ZAHAB, ledger et fédération

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S46 | Contributions : `contributions.*` et actions ZUMRA associées | ST-CONTRIBUTION, ST-PAYMENT, période/finalité/reçu | `ContributionService`, `ContributionConfiguration`, `ZahabWalletService` | A-SUBJECT ; A-LEADER/responsable financier pour collectif | démarrer/proposer/approuver, pause/reprise/arrêt, payer GeniusPay/ZAHAB, retour/reçu | E-403, E-404, E-409, E-422, E-429, E-PAYMENT, E-DEPENDENCY |
| S47 | Wallet et acquisition : `zahab.*` | solde dérivé du ledger, historique, ST-PAYMENT acquisition | `ZahabWalletService`, `ZahabAcquisitionService`, `LedgerService` | A-MEMBER/A-SUBJECT ; wallets collectifs selon A-LEADER/A-ORG-MANAGER | consulter, acquérir via fournisseur, vérifier retour ; aucun crédit/débit manuel | E-403, E-404, E-409, E-422, E-429, E-PAYMENT, E-DEPENDENCY |
| S48 | Ledger : `ledger.*`, `administration.ledger.index` | écritures immuables autorisées, source/type/référence | `LedgerController`, `LedgerService`, autorités du sujet ; admin vue globale | A-MEMBER/A-SUBJECT/A-CONTEXT ; A-ADMIN global | lecture seulement ; aucune route générique crédit/débit/correction | E-403, E-404, E-429, E-EMPTY |
| S49 | Continuer vers un outil : `federation.continue` | satellite actif/inactif, callback valide/invalide | `FederationContinuationController`, `PortalMemberSession`, `FederatedProductGateway` | A-MEMBER + satellite actif | obtenir une continuation fédérée, jamais transmettre le mot de passe | E-404, E-409, E-422, E-429, E-DEPENDENCY/502/503 |

### 7.9 Administration, modération et exploitation

| ID | Écran futur / routes | États et lecture | Services/contrôleurs | Autorité | Actions réelles | Erreurs |
|---|---|---|---|---|---|---|
| S50 | Tour de contrôle : dashboard, communauté, projets, finance, moteurs | agrégats réels, listes filtrées, aucun compteur fictif | contrôleurs `Administration\Admin*`, `AdminJournalAggregator` | A-ADMIN | superviser et ouvrir le domaine source ; lecture majoritaire | E-403, E-404, E-429, E-EMPTY |
| S51 | Configurations : profil, découverte, recommandations, ZUMRA, contributions, capacités collectives, besoins, projets, matching, notifications, modération, accompagnement | paramètres persistés/defaults, finalités/charte actives ou retirées | contrôleurs `Administration\*Configuration*`, configurations applicatives | A-ADMIN | modifier/publier/retirer/réactiver selon route dédiée | E-403, E-404, E-409, E-422, E-429 |
| S52 | Modération membre/ZUMRA/admin : `moderation.*`, `zumra.groups.moderation.*`, `administration.moderation.*` | ST-MODERATION | `ModerationReportService`, `ModerationDecisionService` | signalant/visé A-SUBJECT ; A-LEADER limité au contexte ; A-ADMIN global | signaler, suivre, escalader, décider, faire recours, trancher recours | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S53 | Satellites et journal admin : `administration.satellites.*`, `administration.journal.index` | satellite actif/inactif ; événements d'audit | `SatelliteController`, `AdminJournalAggregator` | A-ADMIN | enregistrer/modifier/activer satellite ; consulter journal | E-403, E-404, E-409, E-422, E-429, E-EMPTY |
| S54 | Readiness : `readiness` | PostgreSQL/Redis/cache/scheduler ready ou non | `ReadinessController`, `OperationalReadinessService` | endpoint d'exploitation, pas navigation membre | lecture machine 200/503 ; ne pas créer une page produit | E-DEPENDENCY |

## 8. Architecture de navigation déduite des contrats

Les six centres fonctionnels restent limités à :

`Fil · Mon espace · Personnes · Besoins · Projets · ZUMRA`

Leur composition responsive est verrouillée par
`USER-JOURNEY-001-NAVIGATION-CONTRACT.md`. Sur mobile, ils deviennent exactement :

`Fil · Découvrir · Agir · ZUMRA · Espace`

`Découvrir` regroupe uniquement Personnes, Besoins et Projets. `Agir`, au centre, ouvre des
actions réelles et autorisées ; ce n'est ni un septième centre métier, ni une page vide. Aucun
menu générique « Plus » n'est autorisé dans la barre inférieure.

Les autres surfaces s'ouvrent depuis le contexte réel :

- Mission depuis Projet, Besoin, ZUMRA ou une Mission parente ;
- Transmission et Preuve depuis l'action concernée ;
- Événement depuis ZUMRA ou Organisation ;
- Partenariat depuis Besoin, Projet ou ZUMRA ;
- financement, accompagnement et autonomie depuis le Projet ;
- contribution et wallet depuis Mon espace, ZUMRA ou Finance ;
- commentaires, partages, messages et modération depuis l'objet source ;
- administration dans une coque réservée séparée.

Cette structure évite de transformer 346 routes en catalogue de modules.

## 9. Registre des écarts moteur à ne pas oublier

Ces capacités étaient promises ou évoquées par l'ancien frontend, mais le moteur certifié ne
porte pas encore leur contrat complet. Elles restent mémorisées ici ; elles ne doivent pas devenir
des boutons morts dans le frontend neuf.

| Gap | Capacité absente ou incomplète | Décision UJ-00 | Impact futur |
|---|---|---|---|
| G01 | récupération de mot de passe | lot backend obligatoire avant PASS de `UJ-02` | bloquant production identité |
| G02 | connexion WhatsApp | ne pas afficher ; fournisseur d'identité à décider/coder | non bloquant si email/identifiant fonctionne |
| G03 | recherche globale fédérée | ne pas afficher comme recherche globale ; garder recherches locales réelles | à contractualiser avant promesse shell |
| G04 | graphe « se connecter / mon réseau », blocage et invitation externe | ne pas afficher ; aucun modèle de relation humaine | futur lot social transverse |
| G05 | suivis/favoris de Besoin, Projet ou Mission | ne pas afficher ; aucun modèle de suivi générique | futur lot de préférence personnelle |
| G06 | brouillon et contribution directe à un Besoin, financement autonome d'un Besoin | garder création directe ; ne pas inventer contribution/finance | décision produit + moteur requis |
| G07 | sondage, priorité/impact propre à une Mission, domaine Task séparé | employer Missions et preuves existantes sans faux indicateur | nouveau contrat métier requis |
| G08 | documents, pièces jointes, médias et saisie vocale | texte/URL seulement ; CAP-070 bloquée par contrat documentaire | dépendance GamaDrive/document |
| G09 | proximité géographique précise | ne pas simuler ; consentement et modèle de précision absents | futur lot confidentialité/localisation |
| G10 | canaux multiples ZUMRA | présenter la conversation unique réelle | moteur de canaux requis avant onglets |
| G11 | portabilité complète d'identité CAP-051 | continuation fédérée seulement | différée par contrat externe |
| G12 | API de capacités CAP-077, fournisseur CAP-078, boucle écosystème CAP-079 | aucune surface promise | différées tant qu'aucun consommateur/contrat réel |

Le fait de ne pas afficher ces actions n'est pas un oubli : ce tableau est leur mémoire officielle.
Lorsqu'une capacité est autorisée, elle reçoit d'abord modèle/service/autorité/tests, puis rejoint
la matrice par un changement explicite.

## 10. Décisions d'implémentation pour UJ-01+

1. Créer d'abord les composants transversaux d'état correspondant au §5.
2. Garder les contrôleurs et services actuels comme sources ; adapter leurs projections au nouveau
   rendu sans dupliquer les règles.
3. Transformer les réponses JSON destinées à l'humain en vues ou composants contextuels seulement
   dans leur lot UJ ; conserver JSON lorsqu'il s'agit d'un contrat machine utile.
4. Chaque formulaire affiche uniquement les transitions permises par le service et accepte qu'une
   concurrence produise encore E-409 côté serveur.
5. Chaque succès doit revenir à un écran qui montre l'état persisté, jamais se fier uniquement à
   un message flash ou au retour navigateur d'un fournisseur.
6. `FUNCTIONAL-COVERAGE-001` reste une archive d'interactions historiques : ses gaps sont soit
   reliés à un contrat ci-dessus, soit conservés au §9.

## 11. Preuves de fermeture UJ-00

| Contrôle | Résultat |
|---|---|
| Préfixes de routes nommées couverts | 32/32 |
| Routes nommées affectées | 346/346 |
| Contrats d'écran ou exploitation | S01 à S54 |
| États métier majeurs enregistrés | 24 familles |
| Autorités normalisées | 12 codes |
| Erreurs/états transversaux normalisés | 11 codes |
| Promesses sans moteur conservées | G01 à G12 |
| Frontend créé/modifié | aucun |
| Moteur créé/modifié | aucun |
| Prochaine étape autorisée | `UJ-01` |

Verdict : **UJ-00 PASS**. Le frontend peut commencer ses fondations, mais aucun écran métier ne
doit précéder les composants transversaux, la doctrine de marque et les contrats de cette matrice.
