# CAP-066 — Organisation

## Statut

**Audit et implémentation V1 — 2026-08-20.** Aucune spec ni aucune ligne de code n'existait avant ce chantier ; le concept n'était présent que comme une ligne `NOT_IMPLEMENTED` dans `CAPABILITY-COVERAGE.md` et `CAPABILITY-INDEX.md` (domaine « Organizations »).

## Ce qu'est une Organisation

Une **structure durable** qui porte des responsabilités, des membres, des rôles et éventuellement des ressources dans la durée — créée volontairement par une personne réelle (`core_identity_reference`), jamais produite automatiquement par un autre agrégat.

## Frontières

- **Organisation ≠ Projet.** Un Projet est une action organisée autour d'un problème ou d'une transformation à réaliser, avec un cycle de vie propre (`PROPOSED → ADOPTED → IN_PROGRESS → COMPLETED`). Une Organisation n'a pas ce cycle : elle est une structure de gouvernance qui peut survivre à ses actions.
- **Organisation ≠ ZUMRA.** Une ZUMRA est une communauté d'action, de solidarité, de formation et de transmission avec sa propre doctrine invariante (`ZUMRA-DOCTRINE-INVARIANTE.md`). ZUMRA-DOCTRINE-INVARIANTE.md §2 énonce explicitement qu'« une ZUMRA peut conduire à une startup, une entreprise, une organisation, une coopérative, une association... » — un débouché possible, jamais une identité. Les deux concepts restent orthogonaux : aucune fusion, aucune ZUMRA n'est renommée en Organisation.
- **Organisation ≠ Compte/Identité organisationnelle (CAP-067).** CAP-066 répond à « qu'est-ce que l'Organisation ? » (structure, membres, rôles) ; CAP-067 répondrait à « comment cette Organisation existe-t-elle comme acteur/identité dans l'écosystème (GAMAD Core) ? ». Aucune identité Core organisationnelle n'existe aujourd'hui — chaque fixture de test authentifiée dans tout le dépôt ne retourne que `"type": "personne"`. CAP-067 dépend d'une capacité externe (GAMAD Core émettant un type d'identité organisationnel) que ce dépôt ne peut pas décider seul ; elle reste `DEPENDENCY_BLOCKED`.
- **Organisation ≠ Satellite / structure juridique.** Aucun appel Core de création d'organisation, aucune extraction logicielle. Une Organisation DG Afrique est un objet métier local (comme une ZUMRA), pas une entité juridique enregistrée.

## Relation avec l'autonomie de Projet (ARCH-006)

`ProjectAutonomyPathwayService::TARGET_FORMS` (`COMPANY`, `ASSOCIATION`, `COOPERATIVE`, `STARTUP`, `PLATFORM`, `OTHER`) déclare déjà l'intention d'un porteur de Projet explorant une forme d'autonomie — sans jamais créer d'entité. `Organization::TYPES` réutilise exactement ce même vocabulaire pour rester cohérent dans tout le dépôt.

Ouvrir un parcours d'autonomie (CAP-018) ne crée **aucune** Organisation automatiquement — vérifié par `ProjectAutonomyPathwayTest` (`assertSame(0, Organization::query()->count())`, corrigé par REF-001B après la fermeture de CAP-066, cf. « Dette découverte » ci-dessous). CAP-066 V1 ne relie donc pas encore `ProjectAutonomyPathway` à `Organization` : la création reste un geste humain volontaire et distinct, jamais une mutation automatique déclenchée par la maturité d'un Projet ou d'une ZUMRA.

## Gouvernance

Rôles explicites, propres à l'Organisation (aucune convention générique n'existait à réutiliser — `ZumraGroupRole` porte des sièges nommés propres à ZUMRA, `ProjectTeamMember.role` est un champ texte libre) :

- `OWNER` — fondateur, ne peut être retiré ni partir sans transmettre la propriété au préalable ;
- `ADMIN` — peut modifier l'Organisation et gérer les membres ;
- `MEMBER` — membre actif sans droit de gestion.

Cycle de vie de l'adhésion (`OrganizationMembership.status`) : `REQUESTED → ACTIVE`, `INVITED → ACTIVE`, `ACTIVE → LEFT`/`REMOVED` — mêmes noms d'état que `ProjectTeamMember`.

## Visibilité

`PRIVATE` (visible uniquement par ses membres actifs) ou `PUBLIC` (visible par tout membre DG Afrique connecté). Une Organisation archivée n'accepte plus de nouveaux membres et disparaît de la liste publique par défaut.

## V1 implémentée

- `Organization`, `OrganizationMembership`, `OrganizationEvent` (`dg_organizations`, `dg_organization_memberships`, `dg_organization_events`) ;
- `OrganizationService` : création, modification, demande/invitation/acceptation d'adhésion, approbation, retrait, départ — même schéma que `ZumraGroupService`/`ProjectTeamService` ;
- exposition minimale : `GET /organisations`, `GET /organisations/creer`, `POST /organisations`, `GET /organisations/{organization}`, `POST /organisations/{organization}/rejoindre` — la gouvernance fine (invitation, approbation, retrait) reste au niveau service pour cette V1, sans back-office ;
- événements audités (`ORGANIZATION_CREATED`, `ORGANIZATION_UPDATED`, `MEMBERSHIP_REQUESTED`, `MEMBER_INVITED`, `INVITATION_ACCEPTED`, `MEMBERSHIP_APPROVED`, `MEMBER_REMOVED`, `MEMBER_LEFT`).

## Hors périmètre v1 (délibérément)

- **aucun lien Projet/Need/Proof → Organisation.** `Project.owner_type`/`Need.owner_type`/`Proof.owner_type` ne comportent que `PERSON`/`GROUP`(/`PROJECT` pour Need) ; ajouter une quatrième valeur `ORGANIZATION` toucherait l'autorité de visibilité de trois agrégats stables et n'est démontré par aucun modèle canonique aujourd'hui. Dépendance documentée pour un futur CAP, jamais fabriquée ;
- **aucune identité Core organisationnelle (CAP-067)** — dépendance externe, hors de portée de ce dépôt ;
- **aucune intégration au Fil (CAP-055) ni aux Opportunités (CAP-064)** — pas de scope creep, CAP-066 doit d'abord être stable seule ;
- **aucun back-office** — pas de CRM, pas de facturation, pas de gestion documentaire.

## Dette découverte (non traitée ici)

**Corrigée par REF-001B (2026-08-20).** L'audit CAP-066 avait confirmé que `ProjectAutonomyController` et `Administration/ProjectSatelliteLauncherController` référençaient encore la classe `App\Application\Projects\ProjectSatelliteLauncherService`, supprimée par REF-001 (renommée `ProjectAutonomyPathwayService`) — cause racine des échecs `ProjectSatelliteLauncherTest`. Cette dette était adjacente à CAP-066 et n'en faisait pas partie ; elle a été fermée séparément par REF-001B, qui a également renommé `Administration/ProjectSatelliteLauncherController` → `Administration/ProjectAutonomyPathwayController` et le test en `ProjectAutonomyPathwayTest`.

## Preuve

`tests/Feature/OrganizationTest.php` — 20 cas : création autorisée/refusée, référence publique unique, fondateur associé en `OWNER`, lecture publique/privée, modification autorisée/refusée, adhésion (demande/invitation/acceptation/approbation/retrait), gouvernance minimale non cassable, rôle invalide rejeté, isolation entre organisations, événements produits, absence de fuite dans la liste, absence d'effet sur Projet/Mission/Besoin/ZUMRA non concernés, multi-organisation, validations de type/statut, absence de mutation en lecture.
