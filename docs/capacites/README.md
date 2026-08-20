# Documentation des capacités DG Afrique

Avant toute intervention, lire `../AI-RULES.md`.

## Documents actifs

- `CAPABILITY-INDEX.md` — les 84 capacités, leurs domaines et leur routage. Ce n'est pas un tracker d'avancement.
- `CAPABILITY-COVERAGE.md` — unique synthèse documentaire du statut réel des CAP, toujours subordonnée au code et aux tests de `main`.
- `OVERRIDES.md` — décisions explicites qui modifient ou précisent le référentiel.
- `specs/` — contrats et invariants encore utiles à l'implémentation.
- `TEMPLATE.md` — aide à la rédaction d'une nouvelle fiche lorsqu'elle est réellement nécessaire.

## Historique

L'historique Git conserve les versions précédentes. Les anciens trackers, snapshots, doublons, quarantaines et anciennes specs ne doivent pas rester dans l'arbre courant comme sources concurrentes.

Les preuves courantes de livraison vivent dans le code, les tests, les PR et les journaux CI.

## Règle satellite

La maturité d'un Projet ne produit jamais un satellite logiciel. Les satellites éventuels proviennent uniquement de l'extraction justifiée d'un outil spécialisé conçu d'abord comme module extractible. Voir `../architecture/ADR-005-extractable-specialized-tools.md`.
