> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-050 — DG AFRIQUE COMME CLIENT DU CORE

- **Domaine :** Core Integration
- **Propriétaire d'exécution :** DG
- **Statut :** partiellement implémenté

## Responsabilité

DG Afrique consomme les primitives Core sans réimplémenter leurs règles : identité, sessions, fédération, organisations ou autres contrats gouvernés.

## Règle de conception

Tout nouveau besoin doit d'abord répondre à : « cette donnée appartient-elle au métier DG/ZUMRA, au Core, ou à un satellite ? »
