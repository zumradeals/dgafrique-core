# Documentation des capacités DG Afrique

Avant toute intervention, lire `../AI-RULES.md`.

## Documents actifs

- `CAPABILITY-INDEX.md` — les 84 capacités, leurs domaines et leur routage. Ce n'est pas un tracker d'avancement.
- `CAPABILITY-COVERAGE.md` — unique synthèse documentaire du statut réel des CAP, toujours subordonnée au code et aux tests de `main`.
- `OVERRIDES.md` — décisions explicites qui modifient ou précisent le référentiel.
- `specs/` — contrats et invariants encore utiles à l'implémentation.
- `TEMPLATE.md` — aide à la rédaction d'une nouvelle fiche lorsqu'elle est réellement nécessaire.

## Historique

Git est l'archive. Les anciens trackers, doublons et anciennes specs ne doivent pas être restaurés dans l'arbre courant pour « garder une trace » : cette trace existe déjà dans l'historique Git.

Le répertoire `proofs/` contient encore des snapshots de preuve produits pendant le bootstrap historique. **Ils sont dépréciés comme autorité et ne doivent jamais être utilisés pour déduire l'état courant.** Leur suppression physique est traitée séparément afin de ne pas mélanger nettoyage d'autorité et éventuels besoins d'audit externe. Toute nouvelle preuve doit vivre dans la PR, les tests ou les journaux CI, pas comme nouveau snapshot permanent sous `docs/capacites/proofs/`.
