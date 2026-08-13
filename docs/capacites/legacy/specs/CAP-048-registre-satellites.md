> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-048 — REGISTRE DES SATELLITES

- **Domaine :** Satellites
- **Propriétaire d'exécution :** HYBRID
- **Statut :** Core dispose déjà d'un registre produit ; interface DG à construire

## Finalité

Rendre les satellites visibles et actionnables depuis DG Afrique sans absorber leur métier dans le portail.

## Vue DG minimale

`reference produit · nom · description · capacités fournies · URL/état d'environnement · fédérable · accès utilisateur · dernière ouverture`

## Premier pilote

GamaDrive — `PRD-GAMAD-002`.

## Critère d'acceptation

Un utilisateur ne voit pas un simple lien externe : DG Afrique sait si le produit est fédérable et peut déclencher son ouverture sous session Core.
