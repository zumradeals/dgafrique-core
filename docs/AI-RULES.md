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

Ne pas créer un second tracker CAP, une copie `v2/final` concurrente d'une spec active, une preuve datée permanente, une archive opaque découpée dans `docs/`, une maquette promue silencieusement en autorité, ni un statut `CLOSED` fondé uniquement sur une vieille documentation.

L'historique du dépôt conserve les versions précédentes ; il n'est pas nécessaire de maintenir des copies concurrentes dans l'arbre courant.

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
test "$(find docs/capacites -maxdepth 1 -type f -name '*MASTER*TRACKER*' | wc -l)" -eq 0
test ! -d docs/capacites/legacy/specs
test "$(find docs -type f -name '*.b64.part*' | wc -l)" -eq 0
test "$(grep '^| CAP-' docs/capacites/CAPABILITY-INDEX.md | wc -l)" -eq 84
test "$(grep '^## CAP-' docs/capacites/CAPABILITY-COVERAGE.md | wc -l)" -eq 84
test "$(grep '^Status:' docs/capacites/CAPABILITY-COVERAGE.md | wc -l)" -eq 84
awk '/^\| CAP-/ && ($0 ~ /\[PLUS TARD\]/ || $0 ~ /\[FAIT\]/ || $0 ~ /\[CLOSED\]/ || $0 ~ /\[PARTIAL\]/ || $0 ~ /\[NOT_IMPLEMENTED\]/ || $0 ~ /\[DOC_ONLY\]/ || $0 ~ /\[DEPENDENCY_BLOCKED\]/) { bad=1 } END { exit bad }' docs/capacites/CAPABILITY-INDEX.md
awk '/^Status:/ { if ($2 != "CLOSED" && $2 != "PARTIAL" && $2 != "NOT_IMPLEMENTED" && $2 != "DOC_ONLY" && $2 != "DEPENDENCY_BLOCKED") bad=1 } END { exit bad }' docs/capacites/CAPABILITY-COVERAGE.md
awk '/^## CAP-/ { if (seen && statuses != 1) bad=1; seen=1; statuses=0; next } /^Status:/ { statuses++ } END { if (seen && statuses != 1) bad=1; exit bad }' docs/capacites/CAPABILITY-COVERAGE.md
```

En revue, vérifier également que les identifiants vont exactement de `CAP-001` à `CAP-084`, une seule fois chacun, dans l'index et la couverture.

Le répertoire `docs/capacites/proofs/` est gelé : toute PR qui y ajoute un fichier doit être refusée. Il sera supprimé entièrement lorsqu'une décision explicite confirmera qu'aucune exigence d'audit ne nécessite sa présence dans l'arbre courant.

Ce garde-fou est actuellement une règle de revue reproductible, pas un job automatisé. Toute automatisation future doit être ajoutée explicitement au dépôt avant d'être présentée comme active.

## Portée de DOC-001

DOC-001 régularise l'autorité et les contradictions documentaires. Il ne certifie pas à lui seul chaque ligne métier des 84 CAP. Lorsqu'une entrée de couverture n'a pas été réauditée en profondeur pendant DOC-001, elle reste soumise à la règle n°1 : le code et les tests de `main` gagnent.

Corrections de couverture explicitement réauditées pendant DOC-001 :

- CAP-019 à CAP-022 : retrait des anciens marqueurs « plus tard » de l'index, les modules étant déjà présents ;
- CAP-047/048/049/050/051/078/079/084 : dépendances satellites réalignées après présence du registre et de la fédération sur `main` ;
- CAP-059 : statut corrigé après inspection de Project Brain (`ProjectBrainNeedDraftService`, modèles de conversation/brouillon, routes et confirmation humaine) ;
- CAP-077 : preuve reformulée contre le routage actuel pour distinguer les surfaces web internes d'une API de capacités externe.

Toute future divergence découverte doit corriger `CAPABILITY-COVERAGE.md` dans la PR qui la révèle.

## Checklist de clôture DOC-001

Une régularisation documentaire n'est terminée que si le code n'a pas été modifié pour correspondre artificiellement à une vieille documentation, chaque statut modifié est soutenu par du code courant inspecté, les documents historiques restants sont explicitement marqués, les points d'entrée ne renvoient plus vers des fichiers supprimés et la PR peut être relue sans mémoire de conversation externe.

## IA et mutations métier

Une IA peut analyser, proposer et préparer un brouillon. Elle ne doit pas contourner les confirmations humaines prévues par le domaine. Project Brain suit déjà : conversation → proposition structurée → brouillon → confirmation humaine → service métier.

## Règle anti-dérive

Toute PR documentaire doit pouvoir répondre à trois questions : ce document est-il actif ou historique ? quelle source gagne en cas de conflit ? apporte-t-il une information absente d'une autorité supérieure ? Si la dernière réponse est non, supprimer ou fusionner le fichier au lieu de le conserver par inertie.
