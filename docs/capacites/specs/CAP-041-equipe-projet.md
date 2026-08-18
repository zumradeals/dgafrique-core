# FICHE D'IMPLÉMENTATION — ÉQUIPE PROJET

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-041 — ÉQUIPE PROJET
**Expression produit :** fiche projet (`/projets/{project}`)
**Nouveau CAP :** non
**Nature :** nouvelle table additive, distincte du matching (CAP existant `ProjectMatchDecision`)
**Base de conception :** `docs/capacites/CAPABILITY-COVERAGE.md` (CAP-041, NOT_IMPLEMENTED), `ProjectService::canView/canDecide`,
précédent `ZumraGroupMembership`/`ZumraGroupService` (invitation/demande/acceptation), précédent
`ZumraGroupController::invite()` (résolution par `discovery_reference` + consentement)

---

## 1. Intention

Un Projet n'a aujourd'hui aucune structure d'équipe réelle : `ProjectMatchDecision` ne fait que
masquer une suggestion pour un décideur (aucune relation créée), et rien ne permet à quelqu'un de
rejoindre effectivement un projet, d'y avoir un rôle libre, ou d'en être retiré avec une raison.

> **Un Projet peut désormais avoir une équipe réelle : des personnes qui l'ont rejoint (par
> demande approuvée ou par invitation acceptée), chacune avec un rôle libre en texte, jamais un
> classement ni un score — strictement distinct du matching, qui reste une suggestion, jamais une
> adhésion.**

## 2. Ce que ce chantier n'est pas

- **pas** un remplacement ou une extension de `ProjectMatchDecision` — le matching reste un
  masquage de suggestion par décideur ; l'équipe est une adhésion réelle, table séparée ;
- **pas** un système de rôles fixes façon `ZumraGroupRole` (sièges VACANT/PROPOSED/ACCEPTED) — un
  rôle d'équipe projet est un champ texte libre, non gouvernant, non exclusif ;
- **pas** une notation ou un classement des membres de l'équipe ;
- **pas** une invitation par identité brute : comme `ZumraGroupController::invite()`, seule une
  personne avec `discovery_consent = true` peut être invitée, résolue par `discovery_reference`.

## 3. Modèle de données

Nouvelle table `dg_project_team_members`, conventions identiques à `dg_zumra_group_memberships` :

```php
Schema::create('dg_project_team_members', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_id')->constrained('dg_projects')->cascadeOnDelete();
    $table->string('core_identity_reference', 64)->index();
    $table->string('role', 80)->nullable();
    $table->string('status', 24)->index();
    $table->string('entry_mode', 20);
    $table->string('initiated_by_core_reference', 64);
    $table->text('motivation')->nullable();
    $table->text('decision_reason')->nullable();
    $table->timestampTz('requested_at')->nullable();
    $table->timestampTz('invited_at')->nullable();
    $table->timestampTz('joined_at')->nullable();
    $table->timestampTz('left_at')->nullable();
    $table->timestampsTz();
    $table->unique(['project_id', 'core_identity_reference']);
});
```

`ProjectTeamMember` (statuts : `REQUESTED`, `INVITED`, `ACTIVE`, `LEFT`, `REMOVED` — même vocabulaire
que `ZumraGroupMembership`). `role` est un texte libre optionnel (ex. « Coordination technique »),
jamais un identifiant contrôlé.

## 4. Autorité — réutilisation stricte

Aucune nouvelle autorité : `ProjectTeamService` reçoit `ProjectService` en dépendance et réutilise
`canView()` (qui peut voir l'équipe) et `canDecide()` (qui peut inviter/approuver/retirer — porteur
personne ou responsable du groupe propriétaire, exactement comme pour la transition de statut du
projet).

- `requestToJoin(project, actor, motivation)` : requiert `canView($project, $actor)` ; refuse si une
  ligne `ACTIVE`/`REQUESTED`/`INVITED` existe déjà pour cet acteur.
- `invite(project, actor, subjectProfileReference)` : requiert `canDecide($project, $actor)` ;
  résolution de la cible identique à `ZumraGroupController::invite()` (`PersonProfile` par
  `discovery_reference` + `discovery_consent = true`, sinon 404).
- `acceptInvitation(project, actor)` : la personne invitée accepte elle-même sa propre ligne
  `INVITED` → `ACTIVE`.
- `approveRequest(project, actor, teamMemberId)` : requiert `canDecide()` ; `REQUESTED` → `ACTIVE`.
- `leave(project, actor)` : la personne quitte elle-même (`ACTIVE` → `LEFT`).
- `remove(project, actor, teamMemberId, reason)` : requiert `canDecide()` ; toute ligne active/en
  attente → `REMOVED`, `decision_reason` obligatoire.

Chaque mutation écrit un `ProjectEvent` (table déjà existante, réutilisée — pas de nouvelle table
d'audit) : `TEAM_MEMBER_REQUESTED`, `TEAM_MEMBER_INVITED`, `TEAM_MEMBER_JOINED`,
`TEAM_MEMBER_LEFT`, `TEAM_MEMBER_REMOVED`.

## 5. Interface

Section « Équipe » sur `/projets/{project}` (visible si `canView`) :
- liste des membres `ACTIVE` (nom d'affichage si disponible, rôle libre s'il existe) ;
- si `canDecide` : demandes en attente (`REQUESTED`) avec bouton Approuver, invitations envoyées en
  attente, formulaire d'invitation par référence de découverte ;
- si l'acteur n'est ni membre ni décideur et que `canView` est vrai : bouton « Demander à
  rejoindre » ;
- si l'acteur a une ligne `INVITED` : bouton « Accepter l'invitation » ;
- si l'acteur a une ligne `ACTIVE` : bouton « Quitter l'équipe ».
- état vide honnête si aucune équipe.

## 6. Hors périmètre v1

- rôles fixes/gouvernance (réservé aux structures type ZUMRA) ;
- notification lors d'une demande/invitation (CAP-054 déjà livré, à connecter plus tard si un
  besoin réel apparaît) ;
- suggestion automatique de rôle à partir de `required_capabilities` (CAP-042, hors périmètre ici).

## 7. Definition of Done

- table + modèle + service + contrôleur + routes (`routes/cap041.php`) ;
- section équipe visible sur la fiche projet, filtrée par `canView`, actions filtrées par
  `canDecide` ;
- tests : autorité (seul le porteur/responsable peut inviter/approuver/retirer), consentement
  (invitation refusée sans `discovery_consent`), cycle complet demande→approbation,
  invitation→acceptation, quitter, retirer avec raison, doublon refusé, visibilité respecte
  `canView` du projet (ex. projet `PRIVATE` d'un tiers) ;
- `php artisan test`, `npm run build`, `git status --short` verts.
