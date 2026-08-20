# Documentation des capacités DG Afrique

Avant toute intervention, lire `../AI-RULES.md`.

## Documents actifs

- `CAPABILITY-INDEX.md` — les 84 capacités, leurs domaines et leur routage. Ce n'est pas un tracker d'avancement.
- `CAPABILITY-COVERAGE.md` — unique synthèse documentaire du statut réel des CAP, toujours subordonnée au code et aux tests de `main`.
- `OVERRIDES.md` — décisions explicites qui modifient ou précisent le référentiel.
- `specs/` — contrats et invariants encore utiles à l'implémentation.
- `TEMPLATE.md` — aide à la rédaction d'une nouvelle fiche lorsqu'elle est réellement nécessaire.

## Historique

Git est l'archive. Les anciens trackers, doublons, anciennes specs et snapshots de preuve de bootstrap ne doivent pas être restaurés dans l'arbre courant pour « garder une trace » : cette trace existe déjà dans l'historique Git.

Les preuves de livraison doivent vivre dans les tests, les PR et les journaux CI. Aucun nouveau fichier daté sous `docs/capacites/proofs/` ne doit être créé comme source permanente d'état.
