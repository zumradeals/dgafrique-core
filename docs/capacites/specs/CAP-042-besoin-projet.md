# FICHE D'IMPLÉMENTATION — BESOIN PROJET

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-042 — BESOIN PROJET
**Expression produit :** formulaire besoin (`/besoins/proposer`), fiche projet (`/projets/{project}`)
**Nouveau CAP :** non
**Nature :** extension additive de `Need` (nouveau `owner_type`), aucune migration de schéma
**Base de conception :** `docs/capacites/CAPABILITY-COVERAGE.md` (CAP-042, PARTIAL), `NeedService`
(précédent `OWNER_GROUP`), `ProjectService::canView/canDecide`, précédent CAP-041 (`ProjectTeamMember`)

---

## 1. Intention

Un Projet ne peut aujourd'hui exprimer ses besoins que par un instantané JSON figé à la création
(`required_capabilities`/`required_resources`) : aucun cycle de vie propre, aucune publication,
aucune décision. `Need` sait déjà porter ce cycle de vie complet pour une personne ou une ZUMRA
(`owner_type = PERSON | GROUP`) — il manque `owner_type = PROJECT`.

> **Un Projet peut désormais porter des `Need` réels et vivants (proposition → publication →
> décision → résolution), en plus de son instantané figé initial — même moteur `NeedService`,
> aucune nouvelle autorité fabriquée.**

## 2. Ce que ce chantier n'est pas

- **pas** un remplacement de `required_capabilities`/`required_resources` (instantané de création,
  laissé tel quel) — les deux coexistent, l'un est figé, l'autre vit ;
- **pas** une nouvelle autorité — `NeedService` réutilise `ProjectAuthority` (extraite de
  `ProjectService::canView/canDecide`, voir §4) exactement comme il réutilise déjà `ZumraGroupService`
  pour les besoins de groupe ;
- **pas** une migration de schéma — `dg_needs.owner_type`/`owner_reference` sont déjà des colonnes
  texte libres, sans contrainte `CHECK` ni enum (confirmé dans la migration existante).

## 3. Modèle

`Need::OWNER_PROJECT = 'PROJECT'` — `owner_reference` pointe alors vers `Project.id`.

Éligibilité à proposer un besoin pour un projet (mirroir de `assertActiveGroupMember`) : le porteur
personne, l'initiateur, ou un membre `ProjectTeamMember` `ACTIVE` (CAP-041). Statut à la création :
`OPEN` si l'acteur `canDecide` le projet (porteur personne, ou responsable de la ZUMRA propriétaire),
sinon `PROPOSED` — exactement le patron déjà utilisé pour `OWNER_GROUP`.

## 4. Refactor minimal — `ProjectAuthority`

`ProjectService` dépend déjà de `NeedService` (pour résoudre `source_need_reference`). Si
`NeedService` dépendait à son tour de `ProjectService`, ce serait une dépendance circulaire. La
logique `canView`/`canDecide` de `ProjectService` est donc extraite telle quelle dans une nouvelle
classe `App\Application\Projects\ProjectAuthority` (aucun changement de comportement — mêmes
signatures, mêmes corps) :
- `ProjectService` délègue désormais `canView`/`canDecide` à `ProjectAuthority` ;
- `NeedService` reçoit `ProjectAuthority` en dépendance (pas `ProjectService`) — aucun cycle.

## 5. Permissions — `NeedService` étendu

- `create()` : branche `OWNER_PROJECT` résolvant le `Project` par `public_reference`, exigeant
  l'éligibilité décrite au §3, statut `OPEN`/`PROPOSED` selon `ProjectAuthority::canDecide`. La
  limite de besoins actifs simultanés réutilise le plafond déjà défini pour les groupes (pas de
  nouvelle clé de configuration). La visibilité `GROUP` (portée ZUMRA) est refusée pour un besoin de
  projet exactement comme pour un besoin personnel — repliée sur `PRIVATE`.
- `canView()` : vrai si porteur/initiateur du projet ou membre d'équipe actif ; sinon retombe sur la
  visibilité du besoin lui-même (identique au patron `OWNER_GROUP`).
- `canDecide()` : délègue à `ProjectAuthority::canDecide($project, $actor)`.

## 6. Interface

- `/besoins/proposer` : un troisième choix « Un projet que je porte ou dans l'équipe duquel je suis
  actif », avec sélecteur de projet (uniquement ceux éligibles) — même patron que le sélecteur ZUMRA
  existant.
- `/besoins`, `/besoins/{need}` : affichage du nom du projet porteur, comme pour une ZUMRA.
- `/projets/{project}` : nouvelle carte « Besoins du projet » (après l'équipe, avant le bandeau de
  financement), listant les `Need` du projet filtrés par `canView`, lien « Exprimer un besoin pour ce
  projet » pré-rempli pour un membre éligible.

## 7. Hors périmètre v1

- fusion ou synchronisation automatique avec `required_capabilities`/`required_resources` ;
- notification de nouveau besoin de projet (CAP-054 déjà livré, à connecter plus tard) ;
- nouvelle clé de configuration dédiée au plafond de besoins de projet (réutilise le plafond groupe).

## 8. Definition of Done

- `Need::OWNER_PROJECT` + `ProjectAuthority` + `NeedService` étendu ;
- formulaire, index, fiche besoin et fiche projet à jour ;
- tests : éligibilité à proposer (porteur/initiateur/membre actif vs extérieur refusé), statut
  `OPEN` si décideur sinon `PROPOSED`, `canView`/`canDecide` corrects, visibilité `GROUP` repliée sur
  `PRIVATE`, aucune régression sur `ProjectService::canView/canDecide` (comportement inchangé après
  extraction) ;
- `php artisan test`, `npm run build`, `git status --short` verts.
