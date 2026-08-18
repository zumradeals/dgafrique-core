# FICHE D'IMPLÉMENTATION — PUBLICATION DIRECTE DANS LE FIL + IMAGE RAPIDE

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** extension additive de CAP-013 (Besoin), CAP-014 (Projet), CAP-019 (Fil
d'activité) — les trois CLOSED, « Gap: aucun »
**Expression produit :** Fil (`/activite`), fiche Besoin, fiche Projet
**Nouveau CAP :** non — extension additive de capacités déjà closes, décidée directement par le
porteur produit en conversation (pas d'ambiguïté doctrinale à trancher)
**Nature :** champ image facultatif sur `Need`/`Project`, composeur de publication directe dans le
Fil pour les Besoins, allègement visuel des mentions doctrinales déjà livrées cette session

---

## 1. Intention

Le Fil est le cœur du produit ; aujourd'hui, publier un Besoin exige de quitter le Fil pour un
formulaire séparé, et rien ne permet d'illustrer un Besoin ou un Projet par une image. Cette fiche
ajoute : (1) un champ image facultatif, à l'ajout rapide, sur `Need` et `Project` ; (2) un
composeur de publication directe pour un Besoin, intégré en tête du Fil ; (3) pour un Projet, un
point d'entrée chaleureux vers le formulaire complet (pas un faux raccourci) ; (4) l'allègement
visuel des avertissements doctrinaux déjà déployés cette session (ZUMRA, Projet, Accompagnement).

## 2. Ce que ce chantier n'est pas

- **pas** une galerie multi-image — un seul visuel par objet, à l'ajout rapide, cohérent avec
  « ajout rapide » ;
- **pas** une réduction des champs réellement requis pour un Projet — `summary`/`problem`/
  `proposed_solution`/`beneficiaries`/`objectives`/`milestones` restent obligatoires (CAP-014) ;
  fabriquer ce contenu à la place du porteur violerait l'interdiction des données fictives. Le
  composeur du Fil ouvre donc le vrai formulaire pour un Projet, il ne le contourne pas ;
- **pas** une suppression des mentions doctrinales — l'information sur les droits (autonomie du
  projet, adhésion jamais automatique, aucun score caché) reste entièrement accessible ; seule sa
  présentation change (mention discrète au lieu d'un bandeau dominant), conformément à
  l'invariant : la confidentialité ne masque jamais aux membres leurs règles, droits ou recours.

## 3. Modèle

`Need`/`Project` gagnent une colonne `image_path` (string, nullable) — disque `public`,
`needs/{public_reference}.*` et `projects/{public_reference}.*`. Validation : `image`, `mimes:jpeg,
jpg,png,webp`, `max:4096` (4 Mo). Aucune transformation/recadrage en v1.

## 4. Composeur du Fil (Besoin)

En tête du Fil, un composeur compact — titre, description (le vrai minimum doctrinal de CAP-013,
40 caractères, pas une case vide), catégorie, image facultative — publie directement via
`NeedService::create()` existant (`owner_type = PERSON`, `visibility = PUBLIC`,
`collaboration_mode = ANY` par défaut, modifiables). Aucune nouvelle autorité : mêmes règles que
`/besoins/proposer`.

## 5. Point d'entrée Projet

Bouton chaleureux à côté du composeur Besoin, menant directement à `/projets/proposer` — pas de
formulaire tronqué qui laisserait croire qu'un projet peut naître d'un seul champ.

## 6. Allègement visuel des mentions doctrinales

Remplacement des blocs `.dg-band` pleine largeur par une mention discrète (icône + une ligne),
détail complet disponible au clic — déjà validé en maquette. Appliqué à `zumra/groups/show`,
`projects/show`, `projects/accompaniment`.

## 7. Definition of Done

- image facultative affichée sur la fiche Besoin/Projet et dans les cartes du Fil quand présente ;
- composeur Besoin fonctionnel depuis le Fil, mêmes règles d'autorité et de validation que le
  formulaire existant ;
- aucune information doctrinale supprimée, uniquement moins proéminente ;
- tests : upload d'image valide/refusée (type, taille), publication depuis le composeur produit un
  Besoin identique à celui du formulaire complet, mention doctrinale toujours présente dans le HTML ;
- `php artisan test`, `npm run build`, `git status --short` verts.
