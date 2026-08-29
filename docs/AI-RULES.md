# AI RULES — DG Afrique Core

Ce fichier est le premier document à lire par toute IA ou tout contributeur automatisé intervenant sur le dépôt.

## Identité produit — invariant supérieur

**DG Afrique est un réseau social d'action.** Il accompagne le développement humain et l'action collective, notamment à travers ZUMRA, les capacités, besoins, projets, missions, apprentissages, transmissions, preuves, opportunités et outils spécialisés.

DG Afrique **n'est pas** un portail web de type moteur de recherche, un catalogue d'applications, ni un « lanceur de satellites » comme finalité produit. La navigation, les recommandations et l'intelligence du produit doivent servir le passage de la capacité à l'action humaine et collective.

ZUMRA est le moteur humain et collectif de cet écosystème. Les outils spécialisés servent l'action ; ils ne deviennent jamais le centre conceptuel de DG Afrique.

La synthèse produit/UX de couche 04 (modèle mental, parcours utilisateur, architecture UX conceptuelle) est canonisée dans `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md` — à lire avant toute mission d'interface.

La raison d'être humaine dont cet invariant est la traduction produit — identité de GAMAD, provenance doctrinale, principe « la Personne précède la structure », sens de Formation — Travail — Adoration — est canonisée dans `docs/canon/DOCTRINE-GAMAD.md`, couche 00, antérieure à ce document : à lire avant toute mission doctrinale ou touchant à la finalité humaine de ZUMRA.

### Modules spécialisés et satellites

Un outil spécialisé naît par défaut comme **module extractible** dans l'architecture DG Afrique lorsqu'il peut raisonnablement vivre dans le monolithe modulaire. Exemples de direction : G-POS, GamaDrive et futurs outils spécialisés du réseau.

Un module extractible possède des frontières métier explicites et évite les couplages qui rendraient son extraction artificiellement coûteuse. Il peut rester durablement un module.

Un **satellite** est la forme technique autonome d'un outil spécialisé lorsqu'un besoin réel d'indépendance apparaît : déploiement séparé, cycle de vie propre, charge, sécurité, données, équipe, disponibilité ou contraintes d'intégration distinctes. Il reste fédéré à l'écosystème et à GAMAD Core selon les contrats actifs.

Invariant : **« On ne construit pas un satellite parce qu'un outil pourrait devenir énorme. On construit d'abord un module extractible. Il devient satellite lorsqu'il a besoin de vivre indépendamment. »**

Un projet humain ou économique qui atteint sa maturité **ne devient pas automatiquement un satellite logiciel**. Sa structuration ou son autonomie métier/juridique est un concept distinct du cycle de vie technique module → satellite.

Toute occurrence documentaire ou future implémentation qui assimile directement « projet mature → satellite » doit être considérée comme obsolète et régularisée avant développement.

## Hiérarchie de vérité

1. **Code + tests de `main`** — vérité technique sur ce qui existe réellement.
2. **`docs/capacites/CAPABILITY-COVERAGE.md`** — unique synthèse documentaire des statuts CAP.
3. **`docs/capacites/CAPABILITY-INDEX.md`** — référentiel des 84 capacités et routage ; jamais un tracker.
4. **Specs et invariants actifs** — contrats à respecter, mais pas preuves d'implémentation.

Il n'existe aucune autorité documentaire parallèle. Un document ancien, daté, dupliqué, de preuve, de handoff ou de référence qui concurrence une autorité active doit être supprimé ; l'historique Git suffit pour retrouver le passé.

Si deux documents actifs se contredisent, vérifier le code puis régulariser immédiatement la documentation.

## Interdictions documentaires

Ne pas créer un second tracker CAP, une copie `v2/final` concurrente d'une spec active, une preuve datée permanente, une archive opaque découpée dans `docs/`, une maquette promue silencieusement en autorité, une quarantaine documentaire durable, ni un statut `CLOSED` fondé uniquement sur une vieille documentation.

Ne pas conserver un fichier « au cas où ». Si son contenu utile existe déjà dans une autorité supérieure, supprimer le fichier. Si une information utile manque, la fusionner d'abord dans l'autorité active puis supprimer la source concurrente.

## Avant toute modification métier

- lire `docs/AI-HANDOFF.md` ;
- lire `CAPABILITY-INDEX.md` et `CAPABILITY-COVERAGE.md` ;
- inspecter modèles, services, contrôleurs, routes, migrations et tests concernés ;
- lire la spec active concernée si elle existe ;
- pour ZUMRA, lire `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` ;
- pour le frontend neuf, lire `docs/brand/BRAND-DOCTRINE-001.md`,
  `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md`, `docs/roadmap/USER-JOURNEY-001.md` et
  `docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md` ;
- traiter `docs/design/DESIGN-INVARIANTS.md` uniquement comme archive de l'interface supprimée.

## Roadmap métier

Avant toute nouvelle implémentation CAP ou proposition de prochaine CAP, consulter `docs/roadmap/ROADMAP-METIER-CANONIQUE.md`.

Ne jamais sélectionner une CAP à partir du seul numéro ou du seul statut de `CAPABILITY-COVERAGE.md`. Le code réel reste la vérité exécutable. Si le code et la roadmap divergent, auditer avant d'implémenter.

## Roadmap frontend et parcours

Le frontend neuf suit `docs/roadmap/FRONTEND-REBUILD-001.md`. Son unique registre d'exécution est
`docs/roadmap/USER-JOURNEY-001.md` : reprendre le premier lot `IN_PROGRESS`, sinon le premier lot
`READY`, et mettre à jour ses preuves dans le même changement. Ne jamais créer une roadmap
frontend concurrente pour poursuivre le chantier.

Le contrat de navigation est non négociable pendant l'implémentation : mobile affiche dans cet
ordre **Fil · Découvrir · Agir · ZUMRA · Espace**, `Agir` reste au centre, `Découvrir` regroupe
Personnes/Besoins/Projets et aucun menu générique « Plus » n'est ajouté. Une IA ne peut modifier ce
contrat sans décision produit humaine explicite.

## Après une modification qui change la couverture

Mettre à jour `CAPABILITY-COVERAGE.md` dans la même PR. Ne jamais encoder un statut dans le titre d'une capacité de l'index.

## Garde-fou mécanique DOC-001

Avant merge d'une PR documentaire, exécuter ce contrôle local :

```bash
test "$(find docs/capacites -maxdepth 1 -type f -iname '*tracker*' | wc -l)" -eq 0
test "$(find docs -type f -name '*.b64.part*' | wc -l)" -eq 0
test ! -d docs/capacites/proofs
test ! -d docs/capacites/legacy
test ! -d docs/design/reference
test ! -d docs/design/handoffs
test "$(grep '^| CAP-' docs/capacites/CAPABILITY-INDEX.md | wc -l)" -eq 84
test "$(grep '^| CAP-' docs/capacites/CAPABILITY-COVERAGE.md | wc -l)" -eq 84
awk '/^\| CAP-/ && ($0 ~ /\[PLUS TARD\]/ || $0 ~ /\[FAIT\]/ || $0 ~ /\[CLOSED\]/ || $0 ~ /\[PARTIAL\]/ || $0 ~ /\[NOT_IMPLEMENTED\]/ || $0 ~ /\[DOC_ONLY\]/ || $0 ~ /\[DEPENDENCY_BLOCKED\]/) { bad=1 } END { exit bad }' docs/capacites/CAPABILITY-INDEX.md
awk -F'|' '/^\| CAP-/ { status=$4; gsub(/^[[:space:]]+|[[:space:]]+$/, "", status); if (status != "CLOSED" && status != "PARTIAL" && status != "NOT_IMPLEMENTED" && status != "DOC_ONLY" && status != "DEPENDENCY_BLOCKED") bad=1 } END { exit bad }' docs/capacites/CAPABILITY-COVERAGE.md
awk -F'|' '/^\| CAP-/ { id=$2; gsub(/^[[:space:]]+|[[:space:]]+$/, "", id); count++; if (id != sprintf("CAP-%03d", count) || seen[id]++) bad=1 } END { if (count != 84) bad=1; exit bad }' docs/capacites/CAPABILITY-COVERAGE.md
```

En revue, vérifier également que les identifiants vont exactement de `CAP-001` à `CAP-084`, une
seule fois chacun, dans l'index et la couverture. Le registre de couverture est une table ; les
sections détaillées placées après cette table sont des preuves complémentaires et ne sont pas
requises une fois par capacité.

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

Une régularisation documentaire n'est terminée que si le code n'a pas été modifié pour correspondre artificiellement à une vieille documentation, chaque statut modifié est soutenu par du code courant inspecté, les documents concurrents ou douteux ont été supprimés, les points d'entrée ne renvoient plus vers des fichiers supprimés et la PR peut être relue sans mémoire de conversation externe.

## IA et mutations métier

Une IA peut analyser, proposer et préparer un brouillon. Elle ne doit pas contourner les confirmations humaines prévues par le domaine. Project Brain suit déjà : conversation → proposition structurée → brouillon → confirmation humaine → service métier.

## Règle anti-dérive

Toute PR documentaire doit pouvoir répondre à trois questions : ce document est-il actif ? quelle source gagne en cas de conflit ? apporte-t-il une information absente d'une autorité supérieure ? Si la dernière réponse est non, supprimer ou fusionner le fichier au lieu de le conserver par inertie.
