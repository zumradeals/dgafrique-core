# FICHE D'IMPLÉMENTATION — REGISTRE DES SATELLITES

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-048 — REGISTRE DES SATELLITES
**Expression produit :** administration DG Afrique (`/administration/satellites`)
**Nouveau CAP :** non
**Nature :** nouvelle table + écran d'administration, aucune modification du flux de continuité
fédérée existant (`FederationContinuationController`, laissé intact — sa généralisation est
explicitement réservée à CAP-049)
**Base de conception :** `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` (l.622 : « ZUMRA, GamaDrive,
Wasplex, G-Market et G-POS peuvent être présentés comme satellites de DG Afrique lorsqu'ils sont
officiellement raccordés. Cette présentation ne révèle pas l'autorité mère invisible. »),
`config/federation.php` (seule entrée codée en dur aujourd'hui), `docs/capacites/specs/CAP-018-lanceur-satellites.md`
(« CAP-048 introduira le registre des satellites » — confirme que `dg_project_autonomy_pathways`
n'est ni un registre de satellites ni une table d'organisations)

---

## 1. Intention

Aujourd'hui, un seul satellite (« GamaDrive ») existe, codé en dur dans `config/federation.php` —
aucun registre réel, aucun écran pour qu'un administrateur en ajoute, modifie ou désactive un.

> **Un registre réel (`dg_satellites`) remplace le codage en dur comme source de vérité
> administrable : DG Afrique peut désormais déclarer ses satellites (GamaDrive, et à terme
> Wasplex, G-Market, G-POS…) sans déploiement — jamais un identifiant GAMAD/Core exposé, jamais une
> seconde identité créée.**

## 2. Ce que ce chantier n'est pas

- **pas** une généralisation du flux de continuité fédérée — `FederationContinuationController`
  continue de lire `config/federation.php`, inchangé ; le brancher sur le registre est le travail
  explicite de CAP-049 (« généraliser une fois CAP-048 construit ») ;
- **pas** une table de confiance/secrets — confirmé : aucun champ de `config/federation.php`
  n'est un secret (`product_reference`/`display_name`/`callback_url` sont des données de routage
  publiques ; le secret de confiance (`connect_secret`) reste dans `config/gamad-core.php`, partagé,
  jamais par satellite) ;
- **pas** une seconde identité ou un second système d'authentification — le registre ne stocke que
  des métadonnées de présentation/routage, jamais une session ni un jeton.

## 3. Modèle

```php
Schema::create('dg_satellites', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->string('product_reference', 64)->unique();
    $table->string('display_name', 120);
    $table->text('description')->nullable();
    $table->string('callback_url', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('created_by_core_reference', 64);
    $table->timestampsTz();
});
```

`product_reference` reste un identifiant de routage (ex. `PRD-GAMAD-002`), jamais présenté comme
« GAMAD » dans un libellé de champ admin (label : « Référence produit », comme dans
`config/federation.php` déjà). `display_name` est le seul texte destiné à apparaître à un membre
(« GamaDrive »). Aucun champ chiffré : conforme à l'absence de secret confirmée au §2.

## 4. Permissions

Écran entièrement réservé à `portal.admin` (même paire `['core.member', 'portal.admin']` que tous
les écrans d'administration existants) — aucune nouvelle autorité, aucune exposition membre.

## 5. Interface

`/administration/satellites` : liste (nom, référence produit, statut actif/inactif), formulaire de
création, édition, bascule actif/inactif. Pas de suppression physique (désactivation uniquement,
pour préserver la traçabilité).

## 6. Hors périmètre v1

- branchement du flux de continuité fédérée sur le registre (CAP-049) ;
- ajout automatique de Wasplex/G-Market/G-POS — seule GamaDrive est migrée comme premier
  enregistrement réel, reflétant l'état actuel, jamais une donnée fictive ;
- toute relation entre `ProjectAutonomyPathway` (CAP-018) et le registre — hors périmètre, réservé
  à CAP-049 si un besoin réel apparaît.

## 7. Definition of Done

- table `dg_satellites` + modèle + écran CRUD admin fonctionnel ;
- GamaDrive migré comme premier enregistrement réel (mêmes valeurs que `config/federation.php`) ;
- aucun nom GAMAD/Core exposé dans les libellés d'écran ;
- tests : autorité (accès réservé à un administrateur provisionné), création/édition/bascule,
  unicité de `product_reference`, aucune régression sur le flux de continuité fédérée existant ;
- `php artisan test`, `npm run build`, `git status --short` verts.
