# AI RULES — DG Afrique Core

Ce fichier est le premier document à lire par toute IA ou tout contributeur automatisé intervenant sur le dépôt.

## Hiérarchie de vérité

1. **Code + tests de `main`** — vérité technique sur ce qui existe réellement.
2. **`docs/capacites/CAPABILITY-COVERAGE.md`** — unique synthèse documentaire des statuts CAP.
3. **`docs/capacites/CAPABILITY-INDEX.md`** — référentiel des 84 capacités et routage ; jamais un tracker.
4. **Specs et invariants actifs** — contrats à respecter, mais pas preuves d'implémentation.
5. **Références historiques** — contexte seulement.

Si deux documents se contredisent, ne jamais choisir silencieusement celui qui arrange la tâche. Vérifier le code, puis régulariser la documentation.

## Interdictions documentaires

Ne pas créer :

- un second tracker CAP (`MASTER`, `FINAL`, `STATUS`, `PROGRESS`, etc.) ;
- une copie `v2`, `final`, `final-final` d'une spec active ;
- une preuve datée destinée à devenir une source permanente d'état ;
- une archive ZIP/Base64 découpée dans `docs/` pour conserver de l'historique ;
- une nouvelle maquette déclarée autorité sans décision explicite sur les invariants ;
- un statut « CLOSED » uniquement parce qu'une spec ou une ancienne preuve l'affirme.

Git est l'archive historique. Le dépôt courant doit rester un espace de travail, pas un entrepôt de snapshots.

## Avant toute modification métier

- lire `docs/AI-HANDOFF.md` ;
- lire l'entrée du CAP dans `CAPABILITY-INDEX.md` et `CAPABILITY-COVERAGE.md` ;
- inspecter les modèles, services, contrôleurs, routes, migrations et tests concernés ;
- lire la spec concernée si elle existe ;
- pour ZUMRA, lire `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` ;
- pour UI/navigation, lire `docs/design/DESIGN-INVARIANTS.md`.

## Après une modification qui change la couverture

Mettre à jour `CAPABILITY-COVERAGE.md` dans la même PR. Ne pas modifier le titre canonique d'une capacité dans l'index pour encoder son statut (`[PLUS TARD]`, `[FAIT]`, etc.).

## IA et mutations métier

Une IA peut analyser, proposer et préparer un brouillon. Elle ne doit pas contourner les confirmations humaines prévues par le domaine. Project Brain suit déjà ce patron : conversation → proposition structurée → brouillon → confirmation humaine → service métier.

## Règle anti-dérive

Toute PR documentaire doit pouvoir répondre à trois questions :

- ce document est-il actif ou historique ?
- quelle source gagne en cas de conflit ?
- ce fichier apporte-t-il une information qui n'existe pas déjà dans une autorité supérieure ?

Si la troisième réponse est non, supprimer ou fusionner le fichier au lieu de le conserver par inertie.
