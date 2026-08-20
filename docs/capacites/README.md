# Documentation des capacités DG Afrique

Avant toute intervention, lire `../AI-RULES.md`.

## Documents actifs

- `CAPABILITY-INDEX.md` — les 84 capacités, leurs domaines et leur routage. Ce n'est pas un tracker d'avancement.
- `CAPABILITY-COVERAGE.md` — unique synthèse documentaire du statut réel des CAP, toujours subordonnée au code et aux tests de `main`.
- `OVERRIDES.md` — décisions explicites qui modifient ou précisent le référentiel.
- `specs/` — contrats et invariants encore utiles à l'implémentation.
- `TEMPLATE.md` — aide à la rédaction d'une nouvelle fiche lorsqu'elle est réellement nécessaire.

## Historique

L'historique du dépôt conserve les versions précédentes ; les anciens trackers, doublons et anciennes specs n'ont donc pas à rester comme sources concurrentes dans l'arbre courant.

`proofs/` est un **répertoire legacy en quarantaine** : il contient des snapshots du bootstrap et n'appartient pas à la hiérarchie de vérité. DOC-001 interdit toute nouvelle preuve dans ce répertoire. Sa suppression physique complète pourra être décidée séparément si aucune exigence d'audit ne nécessite sa présence dans l'arbre courant ; en attendant, aucune IA ne doit le lire pour déterminer l'état d'un CAP.

Les preuves courantes de livraison vivent dans les tests, les PR et les journaux CI.
