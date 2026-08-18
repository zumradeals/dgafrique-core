# FICHE D'IMPLÉMENTATION — LA ZUMRA COMME MICRO-ESPACE DE TRAVAIL & TABLEAU DE BORD COLLECTIF

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-037 — LA ZUMRA COMME MICRO-ESPACE DE TRAVAIL, CAP-038 — TABLEAU DE BORD COLLECTIF
**Expression produit :** fiche ZUMRA (`/zumra/groupes/{group}`)
**Nouveau CAP :** non
**Nature :** extension additive de la fiche ZUMRA déjà livrée (CAP-011), aucune nouvelle table
**Base de conception :** `docs/capacites/CAPABILITY-COVERAGE.md` (CAP-037/038, PARTIAL), `ZumraGroupController::show()`, `ZumraGroupService`, `NeedService`/`ProjectService` (`canView`/`canDecide` déjà existants), invariant Mon espace « une seule priorité dominante » (`docs/design/DESIGN-INVARIANTS.md` §7)

Cette fiche part directement en **READY** : le patron d'implémentation (priorité dominante calculée
à partir d'objets réels, réutilisation stricte de `NeedService`/`ProjectService`) est déjà établi
par `MemberSpaceController::priority()` — même logique, appliquée au groupe plutôt qu'à l'individu.

---

## 1. Intention

CAP-037 : une ZUMRA n'affiche aujourd'hui que sa gouvernance, sa capacité collective et sa charte —
aucune vue n'agrège les Projets et Besoins qu'elle porte, alors que ces objets existent déjà
(`owner_type = GROUP`). CAP-038 : les demandes d'adhésion en attente sont déjà listées en intégral,
mais rien ne désigne **une seule priorité dominante** pour un responsable qui revient sur la fiche
de sa ZUMRA — exactement le vide que `MemberSpaceController::priority()` comble déjà côté individu.

> **La fiche ZUMRA devient un micro-espace de travail réel : ce que le groupe porte est visible,
> et un responsable sait en un coup d'œil ce qui mérite sa décision maintenant — jamais un mur de
> statistiques, jamais une seconde autorité.**

## 2. Ce que ce chantier n'est pas

- **pas** un nouveau moteur de décision — `NeedService::canView/canDecide` et
  `ProjectService::canView/canDecide` sont réutilisés tels quels ;
- **pas** un remplacement de Mon espace — la priorité collective vit sur la fiche du groupe, jamais
  injectée dans Mon espace (qui reste strictement personnel) ;
- **pas** une gestion des sièges vacants — aucune action réelle n'existe aujourd'hui pour proposer
  quelqu'un à un rôle vacant (vérifié : aucune route, aucun contrôleur, aucune méthode de service).
  Un « siège vacant » ne peut donc pas devenir une priorité actionnable ici sans fabriquer une
  fausse action — explicitement hors périmètre (§6).

## 3. CAP-037 — Micro-espace de travail

Ajout d'une carte « Projets et besoins de cette ZUMRA » sur `zumra.groups.show`, listant (limite 20
chacun, les plus récents d'abord) :
- les `Need` où `owner_type = GROUP` et `owner_reference = group.id`, filtrés par
  `NeedService::canView()` (déjà réel : membre actif du groupe voit tout, y compris PROPOSED ;
  visiteur externe voit seulement ce que la visibilité autorise) ;
- les `Project` où `owner_type = GROUP` et `owner_reference = group.id`, filtrés par
  `ProjectService::canView()`, même logique.

Chaque ligne : titre/nom, badge de statut (réutilise les tons déjà définis), lien vers la fiche
réelle (`needs.show` / `projects.show`). État vide honnête si rien n'existe.

## 4. CAP-038 — Tableau de bord collectif

Une priorité dominante, visible uniquement des responsables (`isLeader`), calculée dans cet ordre
fixe à partir d'objets réels — même patron que `MemberSpaceController::priority()` :

1. une demande d'adhésion en attente (`pendingRequests` déjà chargé) → lien d'ancrage vers la
   section déjà existante (`#demandes`) ;
2. un `Need` du groupe en statut `PROPOSED` → lien vers sa fiche de décision réelle ;
3. un `Project` du groupe en statut `PROPOSED` → lien vers sa fiche de décision réelle ;
4. sinon `null` — état vide honnête, aucune priorité fabriquée.

Affiché en tête de page, même famille visuelle que le bandeau « Aujourd'hui » de Mon espace, mais
scopé au groupe — jamais mélangé à la priorité personnelle du responsable.

## 5. Permissions

Aucune permission nouvelle. La priorité collective n'est calculée et affichée que si
`isLeader($group, $actor)` — déjà vérifié par le contrôleur pour les autres blocs « Espace
responsable ». Les listes Projets/Besoins respectent `canView()` existant, y compris pour un
visiteur non-membre.

## 6. Hors périmètre v1

- gestion/proposition de sièges vacants (aucune action réelle n'existe encore, §2) ;
- pagination complète des Projets/Besoins du groupe (limite fixe de 20, suffisant à ce stade) ;
- notification lors d'un nouveau Besoin/Projet proposé au groupe (CAP-054 déjà livré : à
  connecter plus tard si un besoin réel apparaît, non construit ici pour rester borné).

## 7. Definition of Done

- carte Projets/Besoins visible, filtrée par les autorités existantes, état vide honnête ;
- priorité collective visible uniquement des responsables, dans l'ordre fixe décrit, `null` si rien ;
- tests : visibilité du bloc priorité (leader vs membre vs non-membre), ordre de priorité, filtrage
  `canView` sur la liste Projets/Besoins (un besoin `PRIVATE` d'un autre groupe n'apparaît jamais) ;
- `php artisan test`, `npm run build`, `git status --short` verts.
