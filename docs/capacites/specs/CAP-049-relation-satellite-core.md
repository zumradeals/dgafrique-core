# FICHE D'IMPLÉMENTATION — RELATION SATELLITE ↔ CORE

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-049 — RELATION SATELLITE ↔ CORE
**Expression produit :** menu « Mes outils » (topbar + Mon espace), continuité fédérée
(`/federation/continue/{satellite}`)
**Nouveau CAP :** non
**Nature :** généralisation du flux de continuité fédérée existant sur le registre CAP-048,
aucune nouvelle autorité, aucun nouveau secret
**Base de conception :** `docs/capacites/CAPABILITY-COVERAGE.md` (CAP-049, PARTIAL — « généraliser
une fois CAP-048 construit »), `FederationContinuationController` (flux GamaDrive existant,
laissé fonctionnellement identique, juste rebranché), `Satellite` (CAP-048)

---

## 1. Intention

CAP-048 a construit le registre ; `FederationContinuationController` continuait pourtant de lire
`config/federation.php`, et le bouton « GamaDrive » restait codé en dur dans le menu « Mes outils »
(topbar et Mon espace). CAP-049 termine la relation : le flux lit désormais le registre, et le menu
« Mes outils » affiche automatiquement tout satellite actif déclaré — sans déploiement.

> **Le menu « Mes outils » et la continuité fédérée lisent tous deux le registre `dg_satellites` :
> déclarer un satellite actif dans l'administration le fait apparaître partout, aucune modification
> de code n'est plus nécessaire.**

## 2. Ce que ce chantier n'est pas

- **pas** un nouveau mécanisme de confiance — `FederatedProductGateway::open()` est inchangé,
  toujours porté par le jeton Core de la personne elle-même, jamais un secret par satellite ;
- **pas** une ouverture publique — un satellite inactif (`is_active = false`) n'apparaît nulle part
  et sa route retourne 404, exactement comme un satellite absent du registre aujourd'hui ;
- **pas** un changement du contrat `federation.handoff`/`federation.error` — mêmes en-têtes de
  sécurité (CSP par origine, `no-store`, `X-Frame-Options: DENY`), seul le satellite ciblé change.

## 3. Modèle

`dg_satellites` gagne un `slug` (identifiant d'URL, ex. `gamadrive`), nullable pour rester sans
`doctrine/dbal` (aucune altération de colonne existante), dérivé automatiquement de `display_name`
si omis à la création, toujours unique. Le satellite GamaDrive existant est rétro-rempli avec
`slug = 'gamadrive'` pour préserver l'URL déjà en production.

## 4. Flux généralisé

`GET /federation/continue/{satellite}` devient `POST /federation/continue/{satellite}` où
`{satellite}` est un `slug` : résolution par `Satellite::where('slug', ...)->where('is_active', true)`.
Absent ou inactif → 404 (même comportement observable qu'avant pour un satellite inconnu).
Trouvé mais mal configuré (URL de rappel invalide) → 503, message généré avec le `display_name`
réel, jamais « GamaDrive » figé en dur.

## 5. Interface

Le menu « Mes outils » (topbar desktop et panneau « Mon espace ») itère désormais sur les
satellites `is_active = true` du registre (un composeur de vue les injecte, aucun contrôleur
existant à modifier). État vide honnête déjà en place (« D'autres outils apparaîtront ici… »)
conservé quand le registre ne compte aucun satellite actif.

## 6. Hors périmètre v1

- relation entre `ProjectAutonomyPathway` (CAP-018/047) et le registre — hors périmètre, doctrine
  déjà claire que CAP-018 ne constitue ni un registre ni une table d'organisations ;
- icônes/couleurs par satellite — réutilise le même style de pastille que GamaDrive aujourd'hui ;
- fédération sortante multi-directionnelle — un seul sens (DG Afrique → satellite), inchangé.

## 7. Definition of Done

- registre = source de vérité unique pour le flux de continuité fédérée ET pour l'affichage du
  menu « Mes outils » (topbar + Mon espace) ;
- GamaDrive continue de fonctionner à l'identique (même URL, même comportement) ;
- un nouveau satellite actif apparaît dans le menu sans déploiement (test dédié) ;
- un satellite désactivé disparaît du menu et sa route redevient 404 ;
- tests : suite existante `FederationContinuationTest` verte sans changement de sens, nouveaux
  tests pour un second satellite déclaré/désactivé, aucun nom GAMAD/Core exposé ;
- `php artisan test`, `npm run build`, `git status --short` verts.
