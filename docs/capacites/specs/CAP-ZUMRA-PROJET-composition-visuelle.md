# FICHE D'IMPLÉMENTATION — RECOMPOSITION VISUELLE FICHE ZUMRA ET FICHE PROJET

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** extension additive purement visuelle de CAP-011, CAP-037, CAP-038 (fiche
ZUMRA) et CAP-041, CAP-042, CAP-044, CAP-045, CAP-046 (fiche Projet) — tous CLOSED, « Gap: aucun »
**Expression produit :** `/zumra/groupes/{group}`, `/projets/{project}`
**Nouveau CAP :** non — aucune donnée, aucune autorité, aucun comportement métier n'est ajouté ou
modifié ; seule la composition visuelle change, décidée directement en conversation avec le porteur
produit
**Nature :** recomposition éditoriale des deux fiches, alignée sur la maquette validée cette session
(`DG Afrique — ZUMRA et Projet`)

---

## 1. Intention

Le porteur produit a signalé que la fiche ZUMRA et la fiche Projet semblaient improvisées comparées
au Fil, à Mon espace et à la landing page (composées avec rigueur dès l'origine). Une maquette a
validé une direction : accueil `x-dg.deep` pour la fiche ZUMRA (comme les moments de bascule),
mentions doctrinales en divulgation discrète au lieu de bandeaux dominants, gouvernance et contenu
composés en grille plutôt qu'en pile de cartes identiques.

## 2. Ce que ce chantier n'est pas

- **pas** un nouveau champ, une nouvelle table ou une nouvelle règle d'autorité — chaque donnée
  affichée provient d'un contrôleur/service déjà CLOSED, inchangé ;
- **pas** une suppression de fonctionnalité — toutes les actions réelles (inviter, approuver,
  rejoindre, retirer, proposer un besoin, repositionner la maturité…) restent à leur place, avec les
  mêmes autorisations.

## 3. Fiche ZUMRA (`zumra/groups/show.blade.php`)

- En-tête : `x-dg.deep` (nom, domaine, objectif fondateur, mode de participation, état opérationnel,
  effectif) — remplace l'en-tête plat, cohérent avec le reste du produit où `x-dg.deep` marque les
  moments de bascule.
- Divulgation discrète (`<details class="dg-disclosure">`) pour « aucune adhésion automatique,
  aucun rôle attribué en silence » — remplace la mention en petit texte sous la grille des sièges.
- Grille des cinq responsabilités en `grid` (`auto-fit`) au lieu d'un empilement vertical.
- « Ce que porte cette ZUMRA » (Besoins + Projets, CAP-037) et « Charte interne · extrait »
  (résumée à 220 caractères, lecture intégrale au clic) composés côte à côte en grille `1.3fr/1fr`
  au lieu de deux cartes pleine largeur empilées ; pastilles `Besoin`/`Projet` teintées au lieu de
  badges neutres.

## 4. Fiche Projet (`projects/show.blade.php`)

- « Signaux observés » (CAP-044) passe d'une liste verticale à une grille `auto-fit` de phrases
  factuelles, cohérent avec la maquette.
- « Équipe du projet » (CAP-041) et « Besoins du projet » (CAP-042) composés côte à côte en grille
  2 colonnes au lieu de deux cartes pleine largeur séparées par d'autres sections.
- Nouvelle carte de synthèse « Accompagnement DG Afrique » (visible uniquement au porteur/décideur,
  `canDecide`) affichant le statut réel (`ProjectAccompaniment::status`, CAP-045/046) avec un lien
  vers le dossier complet — évite qu'un accompagnement actif reste invisible tant qu'on ne clique
  pas sur « Accompagnement DG Afrique → » dans le pied de fiche.
- Le contrôleur `ProjectController::show()` charge `$project->accompaniment` uniquement quand
  `$canDecide` est vrai (même périmètre de visibilité que la page dédiée
  `ProjectAccompanimentController::show()`, qui reste `abort_unless(canDecide)`).

## 5. Definition of Done

- toutes les actions et autorisations existantes inchangées (tests CAP-011/037/038/041/042/044/045/046
  toujours verts) ;
- aucune information doctrinale supprimée, uniquement recomposée ou déplacée en divulgation ;
- `php artisan test`, `npm run build`, `git status --short` verts.
