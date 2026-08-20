# Référence design historique — 16 août 2026

> **Statut : HISTORIQUE / CONTEXTE.** Ce dossier n'est pas une seconde source de vérité UI.

Cette référence documente le handoff visuel ayant participé à la direction adoptée le 16 août 2026. Elle reste utile pour comprendre l'origine de certaines décisions, mais les règles actives sont dans `../../DESIGN-INVARIANTS.md` et l'état réellement livré est celui du code de `main`.

## Ce qui reste valable comme contexte

- DG Afrique privilégie l'action, les capacités, les besoins, les projets et la coordination plutôt que les métriques sociales ;
- ZUMRA porte la dimension humaine et collective ;
- les satellites sont des outils spécialisés secondaires ;
- les écrans larges ne doivent pas être artificiellement enfermés dans une colonne étroite ;
- mobile, états vides, permissions et lisibilité font partie du produit ;
- aucune donnée de démonstration ne doit être présentée comme donnée réelle.

## Autorité

En cas de divergence :

1. code/tests de `main` pour ce qui est effectivement livré ;
2. `docs/design/DESIGN-INVARIANTS.md` pour les règles design actives ;
3. décisions explicitement versionnées plus récentes ;
4. ce dossier uniquement comme provenance historique.

Le ZIP source opaque qui était embarqué sous forme de fragments Base64 a été retiré de l'arbre courant pendant DOC-001. Son empreinte historique reste consignée dans `SOURCE-MANIFEST.md` et son contenu reste récupérable via l'historique Git.
