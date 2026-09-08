# AI HANDOFF — GAMAD (`dgafrique-core`)

> **Décision d'identité du 8 septembre 2026.** Le nom public et produit canonique est désormais
> **GAMAD** : « le réseau social d'action GAMAD », « le réseau social GAMAD » ou « le réseau
> GAMAD ». **DG Afrique est un ancien nom produit retiré.** GAMAD Core reste le moteur invisible
> de confiance, d'identité, de session et de fédération ; il ne devient pas une seconde marque
> sociale. Les identifiants techniques hérités (`dgafrique-core`, `dg-*`, URLs `dgafrique.com`)
> peuvent être conservés temporairement pour compatibilité et ne doivent pas être interprétés
> comme une identité produit active.

> **Reprise autorisée — version Astra `8566c4a` (7 septembre 2026).** Le dépositaire
> produit a explicitement demandé de publier cette version distincte de la livraison
> rejetée du 6 septembre. Cette branche de proposition réintroduit les vues, composants,
> assets et pipeline de cette version pour revue, sans reprendre les assets de la PR #151.
> Le moteur reste intact ; maintenance et NO-GO production restent en vigueur.
> Les validations antérieures à cette reprise ne certifient pas cette version.
> Le registre actif et les vérifications restantes figurent dans `docs/roadmap/USER-JOURNEY-001.md`.

> **Décision prioritaire du 6 septembre 2026 — MOTEUR SEUL.** À la demande du
> dépositaire produit, la tentative frontend UJ-01/UJ-02 est retirée intégralement.
> Aucune vue applicative, aucun asset d’interface ni pipeline Vite ne sont livrés.
> Les services, routes, données et autorités métier restent inchangés. Le site doit
> rester en maintenance. La reconstruction est suspendue jusqu’à nouvelle instruction
> explicite ; ne pas restaurer automatiquement cette tentative depuis Git.
> Les preuves et mentions de livraison antérieures ci-dessous sont historiques.

> Point d'entrée pour toute IA ou nouveau contributeur. Lire d'abord `docs/AI-RULES.md`.

## État immédiat à ne pas réinterpréter

Le moteur est certifié par `docs/production/ENGINE-TRUTH-FINAL-001.md`. L'ancien frontend a été
volontairement supprimé de `main` selon `docs/production/FRONTEND-EXCISION-001.md`. Le frontend neuf a également été retiré ; la reconstruction est suspendue. Cette situation ne doit jamais déclencher une restauration depuis l'historique. Le seul
chantier autorisé reste la reconstruction neuve suivant
`docs/roadmap/FRONTEND-REBUILD-001.md`, exécutée et suivie par
`docs/roadmap/USER-JOURNEY-001.md`, et les règles racine de `AGENTS.md`.

Le changement de nom **ne modifie pas** ce statut opérationnel : identité GAMAD ne signifie ni
certification du frontend, ni GO production, ni autorisation de modifier le moteur certifié.

## Hiérarchie de vérité

1. le code et les tests de `main` décrivent ce qui existe réellement ;
2. `docs/capacites/CAPABILITY-COVERAGE.md` est l'unique synthèse des statuts CAP ;
3. `docs/capacites/CAPABILITY-INDEX.md` définit le référentiel et le routage, pas l'avancement ;
4. les specs et invariants actifs décrivent des contrats ; ils ne prouvent jamais qu'une fonctionnalité est livrée.

Les versions précédentes du dépôt vivent dans l'historique Git. Aucun ancien tracker, snapshot de preuve, handoff daté, archive design ou quarantaine documentaire ne doit être maintenu comme seconde vérité dans l'arbre courant.

Pour toute capacité ZUMRA, respecter `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`.

Pour toute construction d'interface, navigation ou design, lire
`docs/brand/BRAND-DOCTRINE-001.md`, `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md` et
`docs/roadmap/USER-JOURNEY-001.md`, puis
`docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md`. Ce dernier verrouille sur mobile
**Fil · Découvrir · Agir · ZUMRA · Espace**, sans menu « Plus ». `docs/design/DESIGN-INVARIANTS.md`
est une archive de l'interface supprimée, pas l'autorité visuelle du frontend neuf.

Si un document actif contient encore une ancienne règle affirmant que « DG Afrique » est la couche
sociale visible ou que le mot « GAMAD » doit être caché de l'expérience, cette règle est
**supersédée** par `docs/AI-RULES.md`, la doctrine fondatrice et `BRAND-DOCTRINE-001.md` régularisés
le 8 septembre 2026. Elle doit être corrigée dans l'autorité concernée, jamais reproduite dans une
nouvelle implémentation.

## Projet canonique

- produit public : **GAMAD**, réseau social d'action ;
- dépôt : `zumradeals/dgafrique-core` — identifiant technique historique conservé temporairement ;
- stack : Laravel, PHP 8.4, PostgreSQL, Blade, Tailwind, Alpine.js, Redis ;
- identité, session et fédération : **GAMAD Core** ;
- **GAMAD porte le métier du réseau, sa couche publique/sociale et son orchestration produit** ;
- les outils spécialisés commencent comme modules isolés et extractibles dans l'écosystème ;
- un module ne devient satellite autonome que lorsqu'un besoin technique réel d'autonomie le justifie ;
- un satellite autonome garde la propriété logique de son métier et de ses données ;
- aucune duplication locale concurrente de l'identité canonique Core.

## Compatibilité technique héritée

Ne pas exécuter un remplacement global de `DG`, `dg`, `DG_AFRIQUE`, `dgafrique` ou `dg-*`.
Un identifiant technique historique peut être référencé par du CSS, des tests, des contrats,
des variables d'environnement, la base, des URLs, du déploiement ou des systèmes externes.

La migration de ces identifiants est un lot distinct qui exige : inventaire des dépendances,
contrat de transition, compatibilité descendante lorsque nécessaire, migration SEO/domaine si elle
est décidée, preuves de non-régression et stratégie de rollback. Jusqu'à ce lot, ils restent des
identifiants techniques et ne doivent jamais redevenir une marque présentée aux utilisateurs.

## Règles de chantier

Avant de coder une capacité :

1. lire `AI-RULES.md`, `CAPABILITY-INDEX.md`, `CAPABILITY-COVERAGE.md` et la spec active concernée si elle existe ;
2. inspecter le code et les tests actuels avant de conclure qu'un CAP manque ;
3. ne jamais se fier à un nom de fichier historique, un ancien tracker, une preuve datée ou une maquette pour déduire l'état courant ;
4. si le code contredit la documentation, corriger la documentation dans la même PR ou signaler explicitement le conflit ;
5. toute mutation métier proposée par IA doit respecter les gates humains et les invariants du domaine ;
6. tester permissions, erreurs, états vides et responsive lorsque la modification les concerne.

## Invariants produit

- GAMAD est un **réseau social d'action** orienté développement humain, capacités, besoins, projets, ZUMRA et coordination ;
- GAMAD est le nom visible ; GAMAD Core reste le moteur invisible de confiance ;
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
