# AGENTS.md — consignes obligatoires pour DG Afrique

Ce fichier s'applique à tout le dépôt. Toute IA, tout agent de code et tout contributeur doit le
lire avant d'analyser, modifier ou générer du code.

## État incontestable du dépôt

- Le **moteur DG Afrique est présent et certifié**. La preuve canonique est
  `docs/production/ENGINE-TRUTH-FINAL-001.md`.
- L'**ancien frontend a été supprimé volontairement et définitivement**. La preuve canonique est
  `docs/production/FRONTEND-EXCISION-001.md`.
- L'absence de vues Blade, de feuilles de style, de JavaScript applicatif, de `package.json` ou de
  pipeline Vite **n'est pas un oubli, une corruption ou une régression à réparer**.
- Le produit web ne possède actuellement **aucun frontend utilisable** et n'est donc pas prêt pour
  une mise en production publique.
- Le prochain chantier officiel est la construction d'**un seul frontend neuf**, décrite dans
  `docs/roadmap/FRONTEND-REBUILD-001.md`.

Résumé obligatoire : **conserver le moteur certifié, ne jamais restaurer l'ancienne carrosserie,
construire la nouvelle interface sur les contrats métier existants.**

## Interdictions

- Ne jamais restaurer l'ancien frontend depuis l'historique Git, une ancienne branche, un bundle,
  une maquette ou un autre dépôt.
- Ne jamais réintroduire une ancienne vue « provisoirement » pour faire passer une route HTTP.
- Ne jamais réimplémenter dans le frontend une règle déjà portée par un service, une autorité, un
  modèle, une transaction ou une intégration du moteur.
- Ne jamais exposer un bouton, une section ou une promesse sans comportement réel, autorisation,
  retour utilisateur et preuve automatisée.
- Ne jamais présenter de données fictives comme des données du produit.
- Ne pas modifier le moteur pendant un lot purement frontend. Une correction moteur doit être
  isolée, justifiée, testée et faire rejouer la certification concernée.

## Lecture obligatoire avant développement

1. `README.md` ;
2. `docs/AI-HANDOFF.md` et `docs/AI-RULES.md` ;
3. `docs/production/ENGINE-TRUTH-FINAL-001.md` ;
4. `docs/production/FRONTEND-EXCISION-001.md` ;
5. `docs/roadmap/FRONTEND-REBUILD-001.md` ;
6. `docs/roadmap/USER-JOURNEY-001.md` ;
7. `docs/roadmap/USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` ;
8. `docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md` ;
9. `docs/brand/BRAND-DOCTRINE-001.md` ;
10. `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md` ;
11. `docs/production/FUNCTIONAL-COVERAGE-001.md` pour la surface concernée.

Pour une capacité métier, lire également la spec CAP active et
`docs/capacites/CAPABILITY-COVERAGE.md`. Pour ZUMRA, lire
`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`.

`docs/design/DESIGN-INVARIANTS.md` est une archive de l'interface supprimée. Ne pas l'utiliser
comme autorité visuelle ou comme plan de restauration du frontend neuf.

## Règle de construction du nouveau frontend

Le frontend doit agir comme un consommateur du moteur certifié : il présente les états réels,
appelle les contrats existants, respecte leurs permissions et rend leurs erreurs compréhensibles.
Il ne devient jamais une deuxième source de vérité métier.

Chaque lot frontend doit apporter ensemble : rendu desktop et mobile, états vide/chargement/erreur,
accessibilité utile, actions réellement câblées et tests adaptés. Une page jolie mais partiellement
factice n'est pas considérée comme livrée.

L'avancement des parcours est enregistré uniquement dans `docs/roadmap/USER-JOURNEY-001.md`. Ne
pas créer une nouvelle roadmap pour reprendre le chantier ; mettre à jour le lot permanent
concerné et ses preuves.

La navigation est verrouillée par `docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md`. Sur
mobile : **Fil · Découvrir · Agir · ZUMRA · Espace**, dans cet ordre, avec `Agir` au centre et
aucun menu générique « Plus ». Ne pas renommer, réordonner, remplacer ou élargir ces contrôles sans
décision explicite du dépositaire produit et mise à jour atomique du contrat et de ses tests.
