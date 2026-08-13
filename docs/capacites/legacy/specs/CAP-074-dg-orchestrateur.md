> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-074 — DG AFRIQUE COMME ORCHESTRATEUR

- **Domaine :** Architecture produit
- **Propriétaire d'exécution :** DG
- **Statut :** décision structurante active

## Définition

DG Afrique est l'endroit où les capacités deviennent utilisables par une personne. Il n'est pas l'endroit où toutes les données doivent physiquement vivre.

- Core : primitives et gouvernance transversale.
- DG Afrique : expérience, orchestration, métier ZUMRA/projets/découverte.
- Satellites : métier autonome et sessions locales fédérées.

## Règle UX

Un utilisateur ne doit pas sentir la frontière technique entre Core, Supabase et satellites ; il doit toutefois garder une compréhension claire de la finalité et des permissions.
