# CLEANUP-ZUMRA-001 — audit des surfaces historiques

Baseline auditée : `origin/main` à `18b8713dff88ec20c3eceb594c637e564f3a5b11`.

## Classement

| Classe | Surface | Décision |
| --- | --- | --- |
| A | `GET /zumra` (`zumra.index`) | Monde ZUMRA canonique ; conserve découverte, recherche, filtres et vues personnelles. |
| A | `GET /zumra/groupes/proposer` et `POST /zumra/groupes` | Parcours canonique « Faire naître une ZUMRA » ; URL technique historique conservée. |
| A | `GET /zumra/groupes/{group}` (`zumra.groups.show`) | Espace ZUMRA canonique ; URL technique historique conservée. |
| B | Adhésion, paiement, carte, demandes, invitations, responsabilités, charte, activités et contributions | Capacités métier nécessaires ; aucune suppression ni modification de gouvernance. |
| B | Missions, événements, mesure, modération, messagerie, partages et commentaires contextualisés par une ZUMRA | Routes internes spécialisées nécessaires à l’Espace ZUMRA. |
| C | `GET /zumra/groupes` (`zumra.groups.index`) | Porte de compatibilité : redirection vers `/zumra`, avec conservation des paramètres sûrs `q`, `mode`, `location` et `view`. |
| D | `resources/views/zumra/groups/index.blade.php` | Ancien annuaire autonome supprimé. Sa recherche a été raccordée au Monde ZUMRA. |
| D | Titre et introduction administrables de l’ancien annuaire | Contrat et champs retirés ; ils ne pilotaient plus aucune surface canonique. |
| E | Routes techniques contenant encore `group`, `groups` ou `/zumra/groupes/{group}` | Nomenclature interne volontairement inchangée : ce chantier ne migre ni modèles, ni tables, ni contrats métier. |
| E | Écrans « nouvelle ZUMRA » imbriqués dans les parcours Projet | Non touchés : parcours contextuels distincts à arbitrer séparément s’ils concurrencent un jour le parcours canonique. |

## Raccordement

- Tous les liens naturels identifiés depuis Monde ZUMRA, Espace ZUMRA, Mon espace, Fil et administration ciblent désormais `zumra.index`.
- Les cartes de découverte du Monde ZUMRA ciblent directement `zumra.groups.show`, donc l’Espace ZUMRA correspondant.
- Les filtres historiques par nom/activité/objectif, mode, lieu et statut personnel sont conservés dans `ZumraSpaceController`.
- Le nom de route `zumra.groups.index` reste disponible uniquement pour la compatibilité ; aucune vue produit ne l’utilise.

## Éléments conservés

- `ZumraGroupController` et `ZumraGroupConfiguration` : création, espace, adhésion, rôles, charte et seuils opérationnels restent actifs.
- Tous les seeds, SVG, images, composants et styles des surfaces canoniques : aucun n’était exclusivement rattaché à l’ancien annuaire.
- Les documents de capacités historiques qui décrivent les URL techniques : ils restent exacts et légitimes.

## Audit GAMAD limité

Aucune chaîne institutionnelle GAMAD n’est exposée dans les fichiers applicatifs et tests touchés par ce nettoyage.

L'audit archéologique élargi demandé après le lancement est consigné dans
[`CLEANUP-ZUMRA-001-ARCHAEOLOGIE.md`](CLEANUP-ZUMRA-001-ARCHAEOLOGIE.md). Aucun code financier,
Core, paiement, contribution, ledger, GeniusPay ou Cerveau n'a été supprimé.
