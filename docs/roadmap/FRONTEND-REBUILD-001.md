# FRONTEND-REBUILD-001 — feuille de route canonique du frontend neuf

## Statut

`AUTORISÉ — F0 PASS — F1 READY`

Le moteur est certifié. L'ancien frontend est supprimé. Cette feuille de route est l'unique chemin
autorisé pour rendre DG Afrique de nouveau utilisable sur le web.

Son registre d'exécution utilisateur est
`docs/roadmap/USER-JOURNEY-001.md`. Ce document subordonné précise l'ordre des parcours, leur
statut et les preuves de sortie ; il doit être poursuivi au lieu de créer une nouvelle directive
frontend.

## Finalité

Construire une seule interface cohérente qui exprime DG Afrique comme **réseau social d'action** :
des personnes rendent leurs capacités visibles, identifient des besoins, forment des ZUMRA,
conduisent des projets, transmettent des connaissances et utilisent des outils spécialisés
extractibles pour progresser.

Le frontend présente et orchestre le moteur existant ; il ne duplique pas ses règles métier.

## Sources obligatoires

- marque : `docs/brand/BRAND-DOCTRINE-001.md` et `docs/brand/tokens.json` ;
- expérience : `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md` ;
- couverture des interactions : `docs/production/FUNCTIONAL-COVERAGE-001.md` ;
- vérité moteur : `docs/production/ENGINE-TRUTH-FINAL-001.md` ;
- vérité des capacités : `docs/capacites/CAPABILITY-COVERAGE.md` ;
- parcours et reprise du chantier : `docs/roadmap/USER-JOURNEY-001.md`.
- navigation responsive verrouillée :
  `docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md`.

Les anciennes vues supprimées et les maquettes historiques ne sont pas des sources d'implémentation.
`docs/design/DESIGN-INVARIANTS.md` décrit l'interface supprimée et reste une archive historique,
pas une autorité visuelle du frontend neuf.

## Phases et portes de sortie

### F0 — Contrats avant pixels

- cartographier chaque écran vers ses routes, services, permissions, états et erreurs réels ;
- distinguer ce qui est disponible, partiel ou différé dans le moteur ;
- interdire dans l'interface les cinq capacités différées par `ENGINE-TRUTH-FINAL-001` ;
- fixer la matrice de couverture qui accompagnera chaque lot.

**Porte F0 :** aucun écran prévu sans contrat moteur identifié.

**Résultat : PASS.** Preuve :
`docs/roadmap/USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` cartographie les 346 routes nommées en
54 contrats, avec états, services, autorités, actions, erreurs et gaps moteur.

### F1 — Fondations visuelles et techniques

- recréer volontairement le pipeline Blade/Livewire, Tailwind CSS et Alpine.js ;
- traduire la doctrine de marque en tokens, composants et règles responsive ;
- construire les primitives : typographie, couleurs, espacements, boutons, formulaires, cartes,
  dialogues, notifications, états système, barre mobile et feuille d'actions `Agir` ;
- mettre en place les tests de composants et contrôles d'accessibilité de base.

**Porte F1 :** aucune page métier avant validation du socle commun desktop et mobile.

### F2 — Coquille applicative

- accès public, inscription, connexion et récupération de compte ;
- navigation globale conforme au contrat verrouillé, recherche utile, notifications et espace
  personnel ;
- layouts public, membre et administration ;
- états chargement, vide, interdit, introuvable et erreur.

**Porte F2 :** navigation complète au clavier et sans débordement aux largeurs de référence.

### F3 — Parcours d'action prioritaires

Cette phase s'exécute selon les lots permanents de `USER-JOURNEY-001`.

- personnes et profils de capacités ;
- besoins et mises en relation ;
- Monde ZUMRA, naissance d'une ZUMRA et Espace ZUMRA ;
- projets, équipe, jalons, besoins, contributions et preuves ;
- fil d'activité, transmissions, missions et accompagnement.

Chaque parcours est livré verticalement : lecture, création, modification, autorisations, erreurs,
confirmation, traçabilité et tests.

**Porte F3 :** aucun bouton mort et aucune donnée de démonstration dans une surface livrée.

### F4 — Outils spécialisés et flux sensibles

- ZAHAB et contributions financières sans contourner le ledger ;
- paiements d'adhésion et retours GeniusPay ;
- Project Brain avec confirmation humaine obligatoire ;
- fédération vers GAMAD Core et modules extractibles.

**Porte F4 :** tests de permissions, idempotence, échec fournisseur, reprise et double soumission.

### F5 — Fermeture fonctionnelle et UX

- confronter toutes les surfaces à `FUNCTIONAL-COVERAGE-001` ;
- vérifier chaque lien, bouton, menu, formulaire, filtre, pagination et état vide ;
- audit responsive, accessibilité, cohérence visuelle, performance et sécurité ;
- supprimer toute promesse qui n'a pas de moteur opérationnel.

**Porte F5 :** couverture exhaustive signée, zéro interaction morte connue.

### F6 — Préproduction puis production

- déployer sur un environnement de préproduction propre ;
- valider les contrats réels GAMAD Core, GeniusPay et DeepSeek activés ;
- exécuter les parcours bout en bout avec données légitimes ;
- vérifier TLS, secrets, workers, scheduler, readiness, alertes et sauvegardes hors serveur ;
- répéter déploiement atomique et rollback ;
- prononcer un GO/NO-GO explicite.

**Porte F6 :** aucune mise en ligne publique avant GO documenté.

## Définition de « livré » pour une surface

Une surface n'est livrée que si son contenu vient de données réelles ou d'un état vide honnête,
ses actions appellent le moteur réel, ses permissions sont appliquées côté serveur, ses erreurs sont
compréhensibles, son rendu mobile et desktop est validé et ses parcours critiques sont automatisés.

Une maquette, une page statique ou un bouton sans comportement ne compte jamais comme avancement
fonctionnel.

## Discipline de changement

- un lot réduit et vertical à la fois ;
- aucune restauration de l'ancien frontend pour accélérer ;
- aucune modification silencieuse du moteur certifié ;
- documentation et tests mis à jour dans le même changement ;
- arrêt immédiat du lot si une contradiction moteur/contrat est découverte, puis audit ciblé avant
  décision.
