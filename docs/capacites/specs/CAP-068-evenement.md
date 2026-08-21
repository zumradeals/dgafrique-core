# CAP-068 — Événement

## Statut

**Clarification doctrinale (2026-09-29) puis implémentation V1 — CLOSED.** Était `NOT_IMPLEMENTED`, marquée `DOCTRINE-À-CLARIFIER` par ROADMAP-003 : le corpus (`ZUMRA-DOCTRINE-INVARIANTE.md`, `docs/capacites/specs/MISSIONS.md`) ne définissait « Événement » nulle part au-delà du titre d'index, avec deux lectures possibles concurrentes (journal technique `*Event` vs objet métier autonome).

## Clarification doctrinale — verdict

L'hypothèse « Événement = journal technique `*Event` » est **falsifiée** par `docs/capacites/specs/MISSIONS.md:37` : *« MISSIONS ne remplace pas Besoin, Projet, Transmission, Preuve, Messagerie, Commentaire, Partage, Finance, **Événement** ou Document. Il s'y intègre. »* — Événement y est cité comme un objet métier autonome, pair de Besoin/Projet/Transmission/Preuve/Document, jamais comme synonyme du pattern d'audit append-only (que le même document utilise par ailleurs, en minuscule, à sa section 19 « Événements append-only »).

**Définition canonique validée :** Mission = action à accomplir avec responsabilité. Événement = rencontre située dans le temps à laquelle on participe.

## Décision produit V1

- Organisateur : `ZUMRA_GROUP` ou `ORGANIZATION` uniquement.
- Participation = présence/inscription légère (`CommunityEventParticipant`), jamais `MissionAssignment`.
- Visibilité : `INTERNAL` (déléguée au contexte porteur) ou `PUBLIC`.
- Aucune récurrence, aucun calendrier complexe, aucun matching, aucune finance, aucun score.
- Aucun remplacement de Mission ni des journaux `*Event` append-only.
- Aucune émargement de présence effective (pas de champ « présent » — le marquage `COMPLETED` de l'organisateur suffit).

## Nommage — `CommunityEvent`, jamais `Event`

Choisi pour éviter toute collision de vocabulaire avec les 10 journaux append-only `*Event` du dépôt (`ProjectEvent`, `ZumraGroupEvent`, `MissionEvent`, …), de forme et de rôle radicalement différents (journal technique immuable vs objet métier avec cycle de vie propre).

## Modèles

`CommunityEvent` (`dg_community_events`) : `organizer_type`/`organizer_reference` (motif `Need.owner_type`/`owner_reference`), `organizer_core_reference`, `title`, `description`, `location` (nullable), `visibility` (`INTERNAL`/`PUBLIC`), `status` (`SCHEDULED`/`COMPLETED`/`CANCELLED`), `scheduled_at`, `decided_by_core_reference`/`decision_note` (nullable, clôture/annulation), `completed_at`/`cancelled_at`.

`CommunityEventParticipant` (`dg_community_event_participants`) : `community_event_id`, `core_identity_reference`, `status` (`REGISTERED`/`WITHDRAWN`). `UNIQUE(community_event_id, core_identity_reference)` — une seule ligne réutilisée à travers le cycle inscription/désinscription (motif `ZumraGroupMembership`).

## Cycle de vie

**`SCHEDULED → COMPLETED | CANCELLED`**, tous deux terminaux. Modification et inscription impossibles hors `SCHEDULED`.

## Autorisations — aucune matrice nouvelle

`CommunityEventService` réutilise strictement `ZumraGroupService::isLeader()` (organisateur ZUMRA) et `OrganizationService::isManager()`/`canView()` (organisateur Organisation). Pour un événement `INTERNAL` porté par une ZUMRA, la visibilité est l'adhésion active (motif déjà établi par `ModerationReportService::isActiveMember()`, faute d'un `canView()` public sur `ZumraGroupService`). Liste des inscrits réservée à l'organisateur.

## Frontières vérifiées

Aucune mutation `Mission`/`Project`/`Contribution`/`LedgerEntry`/`ProjectFunding` — vérifié par test. Aucune colonne score/réputation/finance sur `dg_community_events` — vérifié par réflexion sur les colonnes réelles.

## HTTP

Routes `routes/cap068.php` : création/liste scopées par organisateur (`/zumra/groupes/{group}/evenements`, `/organisations/{organization}/evenements`), puis `/evenements/{event}` (consultation/modification/annulation/tenue/inscription/désinscription/participants).

## Preuve

`tests/Feature/CommunityEventTest.php` — 22 cas : autorisation de création (leader ZUMRA/manager Organisation autorisés, membre ordinaire refusé), visibilité (`INTERNAL` réservée aux membres actifs, `PUBLIC` ouverte, contexte inaccessible refuse l'inscription), inscription/désinscription (double inscription refusée, index unique partiel testé directement, désinscription sans inscription refusée), cycle de vie (annulation, clôture, non-organisateur refusé, événement terminé non modifiable/non inscriptible), confidentialité des inscrits (organisateur seul), absence d'effet de bord Mission/Projet/Finance, absence de colonne score/finance.
