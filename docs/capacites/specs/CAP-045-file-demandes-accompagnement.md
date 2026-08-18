# FICHE D'IMPLÉMENTATION — ACCOMPAGNEMENT : FILE DE DEMANDES

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-045 — ACCOMPAGNEMENT (FILE DE DEMANDES)
**Expression produit :** fiche accompagnement projet (`/projets/{project}/accompagnement`),
administration DG Afrique (`/administration/accompagnement-projets`)
**Nouveau CAP :** non
**Nature :** extension additive de `ProjectAccompanimentService`, nouvelle table liée à
`ProjectAccompaniment`
**Base de conception :** `docs/capacites/specs/CAP-016-accompagnement-dg-afrique.md` (« CAP-045/046
détailleront plus tard la file de demandes »), `ProjectAccompanimentService::activate/end/recordAction`,
précédent CAP-018 (liste déterministe, « aucun score caché n'est calculé »), précédent CAP-041
(`ProjectTeamMember` — demande en attente puis décision privilégiée)

---

## 1. Intention

L'activation d'un accompagnement reste un geste direct et volontaire du porteur autorisé (CAP-016,
inchangé). Ce qui manque : un moyen, une fois l'accompagnement actif, de **formuler une demande
précise** à DG Afrique (« nous avons besoin de… ») qui entre dans une file que les administrateurs
traitent dans l'ordre — jamais un score, jamais une priorité cachée.

> **Une fois l'accompagnement actif, le porteur peut transmettre une demande précise ; elle entre
> dans une file consultée par DG Afrique dans l'ordre d'arrivée, jamais triée par un score.**

## 2. Ce que ce chantier n'est pas

- **pas** un remplacement de l'activation directe (`activate()`/`end()`, inchangés) — la demande
  n'est possible qu'une fois l'accompagnement déjà actif, elle ne l'ouvre pas ;
- **pas** une notation ou une priorisation calculée — la file est strictement ordonnée par date de
  demande (la plus ancienne d'abord), comme le lanceur de satellites (CAP-018) l'affirme déjà :
  « liste déterministe … aucun score caché n'est calculé » ;
- **pas** le dossier enrichi (CAP-046, distinct) — ici uniquement le cycle de vie de la demande
  elle-même (transmise → prise en charge → close).

## 3. Modèle

Nouvelle table `dg_project_accompaniment_requests`, conventions identiques à
`dg_project_accompaniment_actions` :

```php
Schema::create('dg_project_accompaniment_requests', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('project_accompaniment_id')->constrained('dg_project_accompaniments')->cascadeOnDelete();
    $table->string('requested_by_core_reference', 64);
    $table->string('subject', 180);
    $table->text('description');
    $table->string('status', 20)->index();
    $table->timestampTz('requested_at');
    $table->string('acknowledged_by_core_reference', 64)->nullable();
    $table->timestampTz('acknowledged_at')->nullable();
    $table->string('closed_by_core_reference', 64)->nullable();
    $table->timestampTz('closed_at')->nullable();
    $table->text('resolution_note')->nullable();
    $table->timestampsTz();
});
```

Statuts : `PENDING` → `ACKNOWLEDGED` → `CLOSED` (linéaire, jamais de retour arrière — clore reste
possible directement depuis `PENDING`).

## 4. Permissions — extension de `ProjectAccompanimentService`

- `request()` : requiert `ProjectService::canDecide` (le porteur autorisé, même autorité que
  `activate()`) et un accompagnement `ACTIVE` existant ;
- `acknowledgeRequest()`/`closeRequest()` : requièrent `PortalAdministrator`, exactement comme
  `recordAction()` — DG Afrique reste la seule à traiter la file, jamais le porteur lui-même.

Chaque mutation écrit un `ProjectEvent` (`ACCOMPANIMENT_REQUEST_SUBMITTED`,
`ACCOMPANIMENT_REQUEST_ACKNOWLEDGED`, `ACCOMPANIMENT_REQUEST_CLOSED`).

## 5. Interface

- `/projets/{project}/accompagnement` : formulaire de demande visible pour le porteur autorisé
  quand l'accompagnement est actif, liste de ses propres demandes avec statut ;
- `/administration/accompagnement-projets` : file « Demandes en attente », triée par date de
  demande croissante (la plus ancienne en tête), actions Prendre en charge / Clore avec note de
  résolution facultative.

## 6. Hors périmètre v1

- dossier enrichi/filtres par partenaire ou catégorie (CAP-046, distinct) ;
- notification lors d'une nouvelle demande (CAP-054 déjà livré, à connecter plus tard) ;
- réouverture d'une demande close.

## 7. Definition of Done

- file consultative strictement ordonnée par date, aucun score/priorité calculé ;
- demande possible uniquement si accompagnement actif, uniquement par le porteur autorisé ;
- prise en charge/clôture réservées à un administrateur provisionné ;
- tests : autorité (porteur seul peut demander, admin seul peut traiter), demande refusée sans
  accompagnement actif, ordre de file déterministe, cycle complet PENDING→ACKNOWLEDGED→CLOSED ;
- `php artisan test`, `npm run build`, `git status --short` verts.
