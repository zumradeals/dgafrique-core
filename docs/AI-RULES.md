# AI RULES — DG Afrique Core

Ce fichier est le premier document à lire par toute IA ou tout contributeur automatisé intervenant sur le dépôt.

## Hiérarchie de vérité

1. **Code + tests de `main`** — vérité technique sur ce qui existe réellement.
2. **`docs/capacites/CAPABILITY-COVERAGE.md`** — unique synthèse documentaire des statuts CAP.
3. **`docs/capacites/CAPABILITY-INDEX.md`** — référentiel des 84 capacités et routage ; jamais un tracker.
4. **Specs et invariants actifs** — contrats à respecter, mais pas preuves d'implémentation.
5. **Références historiques** — contexte seulement.

`docs/capacites/proofs/` est legacy/quarantaine : ne jamais le lire pour déterminer le statut courant d'un CAP et ne jamais y ajouter de nouveau snapshot.

Si deux documents se contredisent, vérifier le code puis régulariser la documentation.

## Interdictions documentaires

Ne pas créer :

- un second tracker CAP (`MASTER`, `FINAL`, `STATUS`, `PROGRESS`, etc.) ;
- une copie `v2`, `final`, `final-final` d'une spec active ;
- une preuve datée destinée à devenir une source permanente d'état ;
- une archive ZIP/Base64 découpée dans `docs/` ;
- une nouvelle maquette déclarée autorité sans décision explicite sur les invariants ;
- un statut `CLOSED` uniquement parce qu'une spec ou une ancienne preuve l'affirme.

L'historique du dépôt est l'archive des versions précédentes ; il n'est pas nécessaire de maintenir des copies concurrentes dans l'arbre courant.

## Avant toute modification métier

- lire `docs/AI-HANDOFF.md` ;
- lire `CAPABILITY-INDEX.md` et `CAPABILITY-COVERAGE.md` ;
- inspecter modèles, services, contrôleurs, routes, migrations et tests concernés ;
- lire la spec concernée si elle existe ;
- pour ZUMRA, lire `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` ;
- pour UI/navigation, lire `docs/design/DESIGN-INVARIANTS.md`.

## Après une modification qui change la couverture

Mettre à jour `CAPABILITY-COVERAGE.md` dans la même PR. Ne jamais encoder un statut dans le titre d'une capacité de l'index.

## Garde-fou mécanique DOC-001

Avant merge d'une PR documentaire, exécuter ce contrôle local :

```bash
test ! -e docs/capacites/CAP-MASTER-TRACKER.md
test ! -d docs/capacites/legacy/specs
test -z "$(find docs -type f -name '*.zip.b64.part*' -print -quit)"
test -z "$(grep -E '\[(PLUS TARD|FAIT|CLOSED|PARTIAL|NOT_IMPLEMENTED|DOC_ONLY|DEPENDENCY_BLOCKED)\]' docs/capacites/CAPABILITY-INDEX.md || true)"
awk '/^Status:/ { if ($2 != "CLOSED" && $2 != "PARTIAL" && $2 != "NOT_IMPLEMENTED" && $2 != "DOC_ONLY" && $2 != "DEPENDENCY_BLOCKED") bad=1 } END { exit bad }' docs/capacites/CAPABILITY-COVERAGE.md
```

Le répertoire `docs/capacites/proofs/` est gelé : toute PR qui y ajoute un fichier doit être refusée. Il sera supprimé entièrement lorsqu'une décision explicite confirmera qu'aucune exigence d'audit ne nécessite sa présence dans l'arbre courant.

Ce garde-fou est aujourd'hui une règle de revue reproductible. Son automatisation CI éventuelle doit faire l'objet d'un changement de workflow explicite ; la documentation ne doit pas prétendre qu'un contrôle CI existe tant qu'il n'est pas réellement présent dans le dépôt.

## Checklist de clôture DOC-001

Une régularisation documentaire n'est terminée que si :

- le code n'a pas été modifié pour « faire correspondre » artificiellement une vieille documentation ;
- tout statut modifié dans `CAPABILITY-COVERAGE.md` est soutenu par du code courant inspecté ;
- aucun tracker concurrent, spec legacy active ou archive opaque n'a été réintroduit ;
- les documents historiques restants sont explicitement marqués comme tels ;
- les liens vers des fichiers supprimés ont été retirés des points d'entrée actifs ;
- la PR reste documentaire et son diff peut être relu sans dépendre d'une mémoire de conversation externe.

## IA et mutations métier

Une IA peut analyser, proposer et préparer un brouillon. Elle ne doit pas contourner les confirmations humaines prévues par le domaine. Project Brain suit déjà : conversation → proposition structurée → brouillon → confirmation humaine → service métier.

## Règle anti-dérive

Toute PR documentaire doit pouvoir répondre à trois questions :

- ce document est-il actif ou historique ?
- quelle source gagne en cas de conflit ?
- ce fichier apporte-t-il une information qui n'existe pas déjà dans une autorité supérieure ?

Si la troisième réponse est non, supprimer ou fusionner le fichier au lieu de le conserver par inertie.
