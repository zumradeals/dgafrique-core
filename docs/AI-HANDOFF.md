# AI HANDOFF — DG Afrique Core

> Point d'entrée pour toute IA ou nouveau contributeur. Lire d'abord `docs/AI-RULES.md`.

## Hiérarchie de vérité

1. le code et les tests de `main` décrivent ce qui existe réellement ;
2. `docs/capacites/CAPABILITY-COVERAGE.md` est l'unique synthèse des statuts CAP ;
3. `docs/capacites/CAPABILITY-INDEX.md` définit le référentiel et le routage, pas l'avancement ;
4. les specs et invariants actifs décrivent des contrats ; ils ne prouvent jamais qu'une fonctionnalité est livrée.

Les versions précédentes du dépôt vivent dans l'historique Git. Aucun ancien tracker, snapshot de preuve, handoff daté, archive design ou quarantaine documentaire ne doit être maintenu comme seconde vérité dans l'arbre courant.

Pour toute capacité ZUMRA, respecter `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`.

Pour toute modification d'interface, navigation ou design, lire `docs/design/DESIGN-INVARIANTS.md`.

## Projet canonique

- dépôt : `zumradeals/dgafrique-core` ;
- stack : Laravel, PHP 8.4, PostgreSQL, Blade, Tailwind, Alpine.js, Redis ;
- identité et fédération : GAMAD Core ;
- DG Afrique possède son métier et son orchestration ;
- les outils spécialisés commencent comme modules isolés et extractibles dans l'écosystème ;
- un module ne devient satellite autonome que lorsqu'un besoin technique réel d'autonomie le justifie ;
- un satellite autonome garde la propriété logique de son métier et de ses données ;
- aucune duplication locale concurrente de l'identité canonique Core.

## Règles de chantier

Avant de coder une capacité :

1. lire `AI-RULES.md`, `CAPABILITY-INDEX.md`, `CAPABILITY-COVERAGE.md` et la spec active concernée si elle existe ;
2. inspecter le code et les tests actuels avant de conclure qu'un CAP manque ;
3. ne jamais se fier à un nom de fichier historique, un ancien tracker, une preuve datée ou une maquette pour déduire l'état courant ;
4. si le code contredit la documentation, corriger la documentation dans la même PR ou signaler explicitement le conflit ;
5. toute mutation métier proposée par IA doit respecter les gates humains et les invariants du domaine ;
6. tester permissions, erreurs, états vides et responsive lorsque la modification les concerne.

## Invariants produit

- DG Afrique est un **réseau social d'action** orienté développement humain, capacités, besoins, projets, ZUMRA et coordination ;
- ZUMRA est le moteur humain et collectif ;
- les outils spécialisés servent le réseau et ne constituent pas sa finalité ;
- **Projet et Satellite sont deux concepts sans relation de maturité** : un projet reste un projet, même lorsqu'il devient autonome économiquement ou organisationnellement ;
- doctrine technique : **fonction interne → module spécialisé extractible → satellite autonome seulement sur besoin réel** ;
- aucune interface ne doit introduire likes, followers, score humain ou popularité comme mesure de valeur ;
- les recommandations doivent rester explicables ;
- les données de démonstration ne doivent jamais être présentées comme réelles ;
- les écrans larges doivent être réellement exploités et le mobile traité comme un état produit à part entière ;
- les états vides doivent être honnêtes ;
- aucun faux membre, montant, paiement, projet ou partenaire ne doit être injecté dans le métier.

## État du chantier

Ne jamais utiliser ce handoff pour savoir quel est « le prochain CAP ». Cette information change avec `main` et appartient exclusivement à `CAPABILITY-COVERAGE.md` après vérification du code.

Le dépôt courant est la base. Les anciennes consignes de bootstrap telles que « premier gate » ou « repartir de zéro » appartiennent à l'historique Git et ne doivent plus piloter une nouvelle session.
