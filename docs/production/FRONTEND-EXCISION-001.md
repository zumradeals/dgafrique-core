# FRONTEND-EXCISION-001 — retrait de la carrosserie historique

## Statut

`APPLIQUÉ SUR MAIN — FRONTEND NEUF NON ENCORE CONSTRUIT`

Baseline moteur : `cdedc4c589f037fd4b272e1e3c6bfe36389bc9d1`, certificat
`ENGINE-TRUTH-FINAL-001`.

L'arbre final de l'excision a été publié sur GitHub `main` par le commit de consolidation
`f8d55d2812a1f994e90ff0872aeee7a72049f693`. Son arbre Git
`58e1ef917338e18ff5a97b6c5c4e0e28ed0340e6` est identique à l'état local certifié et excisé.

## Décision

Le frontend historique n'est pas conservé dans une branche comparative. Il est retiré de `main`
après intégration du moteur certifié. `dgafrique.com` n'étant pas encore un produit en production,
le déploiement reste en maintenance jusqu'à la livraison du frontend neuf.

## Suppression

- 152 vues Blade historiques ;
- 25 feuilles CSS et le point d'entrée JavaScript historique ;
- 22 illustrations publiques, l'ancien favicon et la référence de design runtime ;
- `vite.config.js`, `package.json` et `package-lock.json` historiques ;
- 3 suites exclusivement visuelles ;
- 5 méthodes visuelles dans des suites mixtes : les 7 contrats `legacy-frontend` résiduels au
  total, plus un garde de contenu devenu nécessairement vide sans vues.

Le script Composer `setup` ne lance plus npm tant qu'aucun frontend n'existe.

## Protection du moteur

Le commit d'excision ne doit contenir aucune différence sous :

- `app/` ;
- `bootstrap/` ;
- `config/` ;
- `database/` ;
- `routes/`.

Les documents `BRAND-DOCTRINE-001`, les tokens de marque, le logo source, la doctrine produit, le
registre des 84 capacités et les certificats runtime sont conservés. Ils sont la mémoire utile ;
l'ancien code visuel ne l'est plus.

## État volontaire après déploiement

- Laravel, PostgreSQL, Redis, scheduler, readiness et services métier restent installés ;
- le domaine reste sous `php artisan down` ;
- aucune route web n'est présentée comme utilisable avant son nouveau rendu ;
- la suite HTTP complète est temporairement non exécutable parce que ses vues ont volontairement
  disparu ; la preuve moteur reste ancrée au commit certifié, dont les chemins protégés sont
  byte-à-byte identiques.

Le nouveau frontend devra recréer son propre pipeline d'assets et remplacer progressivement les
assertions HTTP liées au rendu. Aucun bouton différé, factice ou inactif ne sera réintroduit.
