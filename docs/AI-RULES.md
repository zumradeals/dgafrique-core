# Règles de vérité du dépôt DG Afrique

Ce document est normatif pour toute contribution humaine ou assistée par IA.

## Hiérarchie de vérité

1. **Le code présent sur `main` est la vérité technique absolue.**
2. Pour l’état d’implémentation des capacités CAP-001 à CAP-084, la seule carte documentaire autorisée est `docs/capacites/CAPABILITY-COVERAGE.md`.
3. `docs/capacites/CAPABILITY-INDEX.md` définit le référentiel fonctionnel ; il ne prouve jamais qu’une capacité est implémentée.
4. Une spécification décrit un contrat fonctionnel ; elle ne prouve jamais que le contrat est implémenté.
5. Les doctrines et invariants définissent des contraintes produit/architecture ; ils ne remplacent jamais l’inspection du code.

## Règle de travail obligatoire

Avant de proposer ou coder une fonctionnalité :

- inspecter le code existant ;
- inspecter les migrations ;
- inspecter les routes ;
- inspecter les tests ;
- vérifier `CAPABILITY-COVERAGE.md` ;
- réutiliser les services métier existants au lieu de recréer un moteur parallèle.

En cas de contradiction documentation ↔ code : **le code gagne** et la documentation doit être corrigée dans la même PR.

## Documentation interdite comme source de statut

Un ancien tracker, handoff, preuve datée, capture, maquette, plan de chantier ou document historique ne doit jamais servir à déterminer l’état actuel d’une capacité.

Les preuves historiques Git restent disponibles dans l’historique du dépôt ; elles n’ont pas à rester dans l’arbre courant si elles créent une seconde vérité.

## Discipline documentaire

- Une information normative doit avoir un propriétaire documentaire unique.
- Pas de fichier `*-FINAL`, `*-OLD`, `*-NEW`, `tracker-v2`, `roadmap-final` ou équivalent créant une vérité concurrente.
- Lorsqu’un contrat évolue, mettre à jour le document canonique au lieu d’ajouter un nouveau document concurrent.
- Toute PR qui change matériellement l’état d’une CAP doit mettre à jour `CAPABILITY-COVERAGE.md`.
- Les seeds/projections produit ne constituent jamais une preuve d’implémentation métier.

## Projet vivant V2

La doctrine produit reste celle de `docs/architecture/ARCHITECTURE-PRODUIT-V2.md` tant qu’elle n’est pas explicitement remplacée par une décision canonique. Le Projet est le dossier vivant principal de l’action, sans remplacer le socle Personnes/Capacités/ZUMRA.

## Modules et satellites

Invariant :

> On ne construit pas un satellite parce qu’un outil pourrait devenir énorme. On construit d’abord un module extractible. Il devient satellite lorsqu’il a besoin de vivre indépendamment.
