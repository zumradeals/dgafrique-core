# USER-JOURNEY-001 — Opération Parcours de l'Utilisateur

## Statut et autorité

`CANONIQUE — UJ-00 PASS — UJ-01 READY`

Ce document est le **registre d'exécution des parcours** du frontend neuf. Il est subordonné à
`FRONTEND-REBUILD-001`, dont il détaille la dimension utilisateur. Il ne crée ni seconde roadmap
frontend, ni nouvelle doctrine produit.

Toute IA qui reprend le chantier doit continuer ce document et son tableau d'avancement. Elle ne
doit pas créer une directive concurrente `USER-JOURNEY-v2`, `final`, `new` ou équivalente.

## Formule directrice

> Montrer la bonne action, au bon moment, à la bonne personne, puis rendre la suite évidente.

Le frontend transforme les états, permissions et workflows du moteur en une expérience humaine
simple. À chaque étape, la personne doit comprendre :

1. où elle se trouve ;
2. ce qui se passe réellement ;
3. ce qu'elle peut faire maintenant ;
4. pourquoi elle peut ou ne peut pas le faire ;
5. qui intervient ensuite ;
6. ce qui a changé après son action ;
7. comment poursuivre.

## Résultat produit recherché

Le parcours directeur est :

`Personne → Intention → Besoin ou capacité → Action/Projet → Collaboration → Preuve → Progression`

Il n'est pas imposé comme un tunnel rigide. Une personne peut entrer par un besoin, une capacité,
une ZUMRA, un projet, une transmission ou une invitation. L'interface doit toujours la raccorder à
une action compréhensible et à une prochaine étape réelle.

Les quatre centres gardent des rôles distincts :

| Centre | Rôle utilisateur |
|---|---|
| **Mon espace** | indique ma priorité et ma prochaine action ; |
| **Projet** | transforme une intention collective en action suivie ; |
| **ZUMRA** | organise les personnes qui apprennent, travaillent et agissent ensemble ; |
| **Fil** | fait circuler les mouvements, besoins, progrès et preuves du réseau. |

## Principes non négociables

- La Personne précède la structure et le jargon interne.
- L'utilisateur ne voit jamais les CAP, états techniques ou frontières du Core comme condition
  pour agir.
- `Mon espace` présente une priorité dominante et au maximum deux actions principales.
- Une action visible possède un comportement réel, une autorisation serveur et un retour clair.
- Une action indisponible est absente ou expliquée honnêtement ; elle n'est jamais simulée.
- Aucun contenu, membre, chiffre, projet, paiement ou partenaire fictif n'est affiché.
- Le frontend ne décide pas à la place de l'humain : Project Brain propose, l'humain confirme.
- Aucun like, follower, score humain, classement de personnes ou mécanique de dépendance.
- Mobile, faible débit, lisibilité et compréhension par une personne peu technophile sont des
  contraintes de conception, pas une finition tardive.
- Les outils spécialisés et surfaces d'administration restent contextuels ; ils ne transforment
  pas la navigation principale en catalogue de modules.
- La navigation responsive respecte le contrat verrouillé
  `USER-JOURNEY-001-NAVIGATION-CONTRACT.md` : sur mobile **Fil · Découvrir · Agir · ZUMRA ·
  Espace**, avec `Agir` au centre et aucun menu « Plus ».

## Contrat obligatoire d'une étape

Avant de coder un écran ou une interaction, documenter ces huit champs dans le lot concerné :

| Champ | Question obligatoire |
|---|---|
| Personne | Quel rôle ou quelle relation métier agit ? |
| Intention | Qu'essaie-t-elle d'accomplir en langage humain ? |
| État d'entrée | Quel état réel du moteur ouvre cette étape ? |
| Action | Quelle action principale est réellement autorisée ? |
| Autorité | Quel service, policy ou garde serveur tranche ? |
| Résultat | Quelle mutation ou navigation réelle se produit ? |
| Retour | Quels succès, attente, refus et erreur sont expliqués ? |
| Suite | Quelle prochaine étape réelle devient disponible ? |

Si l'un de ces champs est inconnu, le lot reste en analyse. Une maquette ne doit pas combler le
vide par une invention métier.

## Parcours canoniques

### P0 — Entrer et comprendre

`Gateway → Découvrir si nécessaire → Créer/ouvrir un compte → Identité confirmée`

- expliquer DG Afrique comme réseau social d'action ;
- distinguer compte DG Afrique et adhésion ZUMRA ;
- permettre d'entrer sans apprendre l'architecture GAMAD ;
- ne montrer aucune statistique ou activité fictive.

**Sortie :** la personne possède une session valide et sait pourquoi elle entre.

### P1 — Exprimer une première intention

`Mon espace vide → Je peux apporter / J'ai un besoin / Je veux découvrir / Je veux participer`

- commencer par une phrase ou un choix humain simple ;
- réutiliser les capacités, besoins et routes existants ;
- ne pas imposer un profil complet avant la première action utile ;
- faire apparaître ensuite la prochaine étape correspondante.

**Sortie :** une intention réelle est enregistrée ou conduit vers une action réelle.

### P2 — Revenir et savoir quoi faire

`Connexion → Mon espace → Priorité dominante → Action → Confirmation → Nouvelle priorité`

- `Mon espace` orchestre sans devenir une liste infinie ;
- les Notifications regroupent ce qui demande information ou action ;
- les Opportunités expliquent pourquoi une possibilité est pertinente ;
- le Fil fait circuler le réseau sans se substituer à la priorité personnelle.

**Sortie :** la personne accomplit ou reporte consciemment une action qui la concerne.

### P3 — Passer d'une capacité ou d'un besoin à une collaboration

`Capacité/Besoin → Découverte explicable → Personne ou collectif pertinent → Mise en relation`

- rendre les profils et disponibilités compréhensibles ;
- expliquer les recommandations sans afficher de score humain ;
- conserver la visibilité et les permissions du domaine source ;
- offrir une transition réelle vers réponse, contact, mission, transmission ou projet.

**Sortie :** une relation ou une action métier réelle existe, pas seulement une consultation.

### P4 — Transformer l'intention en projet suivi

`Intention/Brouillon → Confirmation humaine → Projet → Équipe/Besoins → Mission → Preuve`

- guider la naissance progressive du projet sans formulaire administratif massif ;
- laisser Project Brain proposer sans mutation silencieuse ;
- exposer autorité, maturité, jalons, blocages et besoins avec des mots humains ;
- relier chaque contribution à une preuve ou à un résultat observable.

**Sortie :** le projet possède une prochaine action attribuable et un historique compréhensible.

### P5 — Faire naître et vivre une ZUMRA

`Découvrir/Proposer → Demande ou invitation → Décision → Rôle → Charte → Action collective`

- distinguer clairement ZUMRA, Projet et Organisation ;
- expliquer les états de demande, invitation, responsabilité et cycle de vie ;
- ne proposer que les transitions réellement portées par le moteur ;
- faire de la ZUMRA un espace de Formation — Travail — Adoration orienté vers l'action.

**Sortie :** chaque membre comprend son appartenance, sa responsabilité et la prochaine action du
collectif.

### P6 — Transmettre, réaliser et prouver

`Mission/Transmission → Participation → Réalisation ou blocage → Validation → Preuve → Fil`

- harmoniser les machines d'état proches sans fusionner leurs domaines ;
- rendre explicites responsable, échéance, dépendance, blocage et validation ;
- retourner le résultat réel dans le contexte d'origine et, si autorisé, dans le Fil.

**Sortie :** l'action collective produit une trace utile et une progression vérifiable.

### P7 — Contribuer ou payer sans ambiguïté

`Finalité/Montant → Confirmation → Fournisseur ou ZAHAB → Attente/Retour → Reçu/Réconciliation`

- distinguer adhésion, contribution, acquisition ZAHAB et financement de projet ;
- afficher la finalité et les conséquences avant confirmation ;
- empêcher double soumission et faux succès ;
- expliquer pending, succès, échec, reprise et reçu ;
- ne jamais contourner le ledger ni proposer crédit/débit manuel.

**Sortie :** la personne connaît l'état réel de son opération et dispose de sa trace.

### P8 — Administrer et modérer dans le contexte

`Signal ou demande → File autorisée → Décision motivée → Effet visible → Journal`

- réserver les surfaces aux autorités prévues ;
- afficher des métriques réelles et anti-classement ;
- exiger une décision explicite pour les mutations sensibles ;
- rendre l'effet et la traçabilité vérifiables.

**Sortie :** aucune décision administrative n'est silencieuse ou détachée du domaine concerné.

## Ordre d'exécution

Les identifiants ci-dessous sont permanents. Leur statut est la seule indication autorisée de la
prochaine étape.

| Lot | Contenu | Dépendance | Statut | Preuve de sortie |
|---|---|---|---|---|
| `UJ-00` | matrice écrans ↔ états ↔ services ↔ permissions ↔ erreurs | moteur certifié | **PASS** | `USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` |
| `UJ-01` | socle visuel, composants d'état, navigation canonique et pipeline frontend | UJ-00 | **READY** | portes F1, marque et contrat de navigation validés |
| `UJ-02` | P0 Entrer/comprendre et identité | UJ-01 | PENDING | parcours public et compte automatisés |
| `UJ-03` | P1 première intention et P2 retour quotidien | UJ-02 | PENDING | cockpit réel, priorité/action prouvées |
| `UJ-04` | P3 personnes, capacités, besoins et mise en relation | UJ-03 | PENDING | boucle découverte→action automatisée |
| `UJ-05` | P4 projet, équipe, mission et preuve | UJ-04 | PENDING | boucle projet verticale automatisée |
| `UJ-06` | P5 naissance et vie ZUMRA | UJ-05 | PENDING | transitions et autorités ZUMRA prouvées |
| `UJ-07` | P6 transmission, réalisation, preuve et Fil | UJ-06 | PENDING | résultat visible sans fuite d'autorité |
| `UJ-08` | P7 contributions, ZAHAB et paiements | UJ-07 | PENDING | succès/échec/reprise/idempotence prouvés |
| `UJ-09` | P8 administration, modération et surfaces contextuelles | UJ-08 | PENDING | décisions et journaux prouvés |
| `UJ-10` | fermeture exhaustive et préproduction | UJ-09 | PENDING | F5/F6 signées, GO/NO-GO documenté |

Valeurs de statut autorisées : `PENDING`, `READY`, `IN_PROGRESS`, `BLOCKED`, `PASS`, `DEFERRED`.
Un seul lot peut être `IN_PROGRESS`. Après un `PASS`, le lot suivant peut devenir `READY` dans le
même changement documentaire.

## Définition de terminé pour un lot

Un lot n'est `PASS` que si :

- ses écrans utilisent exclusivement des données réelles ou des états vides honnêtes ;
- toutes les actions visibles sont câblées et protégées côté serveur ;
- succès, attente, refus, validation et erreur sont rendus ;
- la prochaine étape est compréhensible ;
- desktop et mobile sont vérifiés sans débordement bloquant ;
- clavier, focus, libellés et contraste utiles sont contrôlés ;
- les parcours critiques ont des tests HTTP et navigateur adaptés ;
- la matrice `FUNCTIONAL-COVERAGE-001` est mise à jour si sa vérité change ;
- aucun chemin protégé du moteur n'a changé silencieusement ;
- les preuves et le statut du lot sont inscrits dans ce document.

## Protocole obligatoire de reprise par une IA

1. vérifier `main` et l'absence de modifications locales inconnues ;
2. lire `AGENTS.md`, `FRONTEND-REBUILD-001.md` puis ce document en entier ;
3. vérifier dans le code et les tests que le statut du tableau est toujours vrai ;
4. reprendre le premier lot `IN_PROGRESS`, sinon le premier lot `READY` ;
5. ne jamais démarrer un lot `PENDING` dont la dépendance n'est pas `PASS` ;
6. limiter la branche ou PR à ce lot et à ses preuves ;
7. mettre à jour ici le statut, les décisions et les preuves avant merge ;
8. si le code contredit la roadmap, marquer `BLOCKED` et documenter le conflit au lieu d'inventer
   une nouvelle directive ;
9. ne jamais restaurer une vue ou un asset de l'ancien frontend.

## Journal de progression

| Date | Lot | Décision ou preuve | Commit/PR |
|---|---|---|---|
| 2026-08-29 | Initialisation | Roadmap canonique créée ; prochain lot `UJ-00` | présent changement documentaire |
| 2026-08-29 | `UJ-00` | 346 routes affectées à S01-S54 ; états, autorités, erreurs et gaps G01-G12 cartographiés | `USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` |
| 2026-08-29 | `UJ-01` préparation | Navigation mobile verrouillée : Fil · Découvrir · Agir · ZUMRA · Espace ; desktop conserve les six centres | `USER-JOURNEY-001-NAVIGATION-CONTRACT.md` |

La prochaine action officielle est **UJ-01 — construire le socle visuel, les composants d'état et
le pipeline frontend, sans encore créer de page métier**.
