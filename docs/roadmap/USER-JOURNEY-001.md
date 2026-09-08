# USER-JOURNEY-001 — Opération Parcours de l'Utilisateur GAMAD

## Statut et autorité

`CANONIQUE — UJ-00 PASS — UJ-01 PASS — UJ-02 IN_PROGRESS`

Ce document est le **registre d’exécution des parcours du frontend GAMAD**. Il est subordonné à
`FRONTEND-REBUILD-001` et aux autorités produit/métier actives. Il ne crée ni seconde roadmap,
ni nouvelle doctrine.

> **Contrat d’identité actif — 8 septembre 2026**
>
> GAMAD est le nom public du réseau social d’action. GAMAD Core reste le moteur invisible de
> confiance, d’identité, de session et de fédération. « DG Afrique » est un ancien nom produit et
> ne doit plus être exposé dans l’expérience utilisateur. Les identifiants techniques hérités
> (`dg-*`, chemins, dépôt `dgafrique-core`, domaine `dgafrique.com`) restent hors de ce lot et ne
> doivent pas être renommés mécaniquement.

Cette migration d’identité **ne change aucun statut UJ à elle seule**. Un lot n’avance que par ses
preuves fonctionnelles prévues ci-dessous.

## Formule directrice

> Montrer la bonne action, au bon moment, à la bonne personne, puis rendre la suite évidente.

Le frontend transforme les états, permissions et workflows du moteur en une expérience humaine
simple. À chaque étape, la personne doit comprendre :

1. où elle se trouve ;
2. ce qui se passe réellement ;
3. ce qu’elle peut faire maintenant ;
4. pourquoi elle peut ou ne peut pas le faire ;
5. qui intervient ensuite ;
6. ce qui a changé après son action ;
7. comment poursuivre.

## Résultat produit recherché

`Personne → Intention → Besoin ou capacité → Action/Projet → Collaboration → Preuve → Progression`

Ce parcours n’est pas un tunnel rigide. Une personne peut entrer par un besoin, une capacité, une
ZUMRA, un projet, une transmission ou une invitation. L’interface doit toujours la raccorder à une
action compréhensible et à une prochaine étape réelle.

Les quatre centres gardent des rôles distincts :

| Centre | Rôle utilisateur |
|---|---|
| **Mon espace** | indique ma priorité et ma prochaine action ; |
| **Projet** | transforme une intention en action suivie ; |
| **ZUMRA** | organise les personnes qui apprennent, travaillent et agissent ensemble ; |
| **Fil** | fait circuler les mouvements, besoins, progrès et preuves du réseau. |

## Principes non négociables

- La Personne précède la structure et le jargon interne.
- Le nom visible du produit est **GAMAD**.
- L’utilisateur ne voit jamais les CAP, états techniques ou frontières de GAMAD Core comme
  condition pour agir.
- `Mon espace` présente une priorité dominante et au maximum deux actions principales.
- Une action visible possède un comportement réel, une autorisation serveur et un retour clair.
- Une action indisponible est absente ou expliquée honnêtement ; elle n’est jamais simulée.
- Aucun contenu, membre, chiffre, projet, paiement ou partenaire fictif n’est présenté comme réel.
- Project Brain propose ; l’humain confirme les décisions engageantes.
- Aucun like, follower, score humain, classement de personnes ou mécanique de dépendance.
- Mobile, faible débit, lisibilité et compréhension par une personne peu technophile sont des
  contraintes de conception.
- Les outils spécialisés et surfaces d’administration restent contextuels ; ils ne transforment
  pas la navigation principale en catalogue de modules.
- La navigation mobile respecte `USER-JOURNEY-001-NAVIGATION-CONTRACT.md` :
  **Fil · Découvrir · Agir · ZUMRA · Espace**, `Agir` au centre, aucun menu « Plus ».

## Contrat obligatoire d’une étape

Avant de coder un écran ou une interaction, documenter :

| Champ | Question obligatoire |
|---|---|
| Personne | Quel rôle ou quelle relation métier agit ? |
| Intention | Qu’essaie-t-elle d’accomplir en langage humain ? |
| État d’entrée | Quel état réel du moteur ouvre cette étape ? |
| Action | Quelle action principale est réellement autorisée ? |
| Autorité | Quel service, policy ou garde serveur tranche ? |
| Résultat | Quelle mutation ou navigation réelle se produit ? |
| Retour | Quels succès, attente, refus et erreur sont expliqués ? |
| Suite | Quelle prochaine étape réelle devient disponible ? |

Si l’un de ces champs est inconnu, le lot reste en analyse. Une maquette ne comble jamais un vide
métier par invention.

## Parcours canoniques

### P0 — Entrer et comprendre

`Accueil GAMAD → Comprendre → Créer/ouvrir un compte → Identité confirmée`

- l’accueil `/` est l’unique entrée éditoriale publique et explique **GAMAD comme réseau social d’action** ;
- l’ancienne route `/decouvrir` n’est plus une page marketing autonome et redirige vers l’accueil ;
- le regroupement **Découvrir** reste réservé au réseau membre conformément au contrat de navigation ;
- distinguer **compte GAMAD** et adhésion au Programme ZUMRA ;
- permettre d’entrer sans apprendre l’architecture de GAMAD Core ;
- ne montrer aucune statistique ou activité fictive ;
- ne jamais exposer l’ancien nom produit « DG Afrique » dans une surface utilisateur.

**Sortie :** la personne possède une session valide et comprend pourquoi elle entre.

### P1 — Exprimer une première intention

`Mon espace vide → Je peux apporter / J’ai un besoin / Je veux découvrir / Je veux participer`

- commencer par une phrase ou un choix humain simple ;
- réutiliser les capacités, besoins et routes existants ;
- ne pas imposer un profil complet avant la première action utile ;
- faire apparaître ensuite une prochaine étape réelle.

**Sortie :** une intention réelle est enregistrée ou conduit vers une action réelle.

### P2 — Revenir et savoir quoi faire

`Connexion → Mon espace → Priorité dominante → Action → Confirmation → Nouvelle priorité`

- `Mon espace` orchestre sans devenir une liste infinie ;
- les Notifications regroupent ce qui demande information ou action ;
- les Opportunités expliquent pourquoi une possibilité est pertinente ;
- le Fil fait circuler le réseau sans se substituer à la priorité personnelle.

**Sortie :** la personne accomplit ou reporte consciemment une action qui la concerne.

### P3 — Passer d’une capacité ou d’un besoin à une collaboration

`Capacité/Besoin → Découverte explicable → Personne ou collectif pertinent → Mise en relation`

- rendre profils et disponibilités compréhensibles ;
- expliquer les recommandations sans score humain ;
- conserver visibilité et permissions du domaine source ;
- offrir une transition réelle vers réponse, contact, mission, transmission ou projet.

**Sortie :** une relation ou une action métier réelle existe.

### P4 — Transformer l’intention en projet suivi

`Intention/Brouillon → Confirmation humaine → Projet → Équipe/Besoins → Mission → Preuve`

- guider la naissance progressive du projet sans formulaire administratif massif ;
- laisser Project Brain proposer sans mutation silencieuse ;
- exposer autorité, maturité, jalons, blocages et besoins avec des mots humains ;
- relier chaque contribution à un résultat observable.

**Sortie :** le projet possède une prochaine action attribuable et un historique compréhensible.

### P5 — Faire naître et vivre une ZUMRA

`Découvrir/Proposer → Demande ou invitation → Décision → Rôle → Structuration → Action collective`

- distinguer ZUMRA, Projet et Organisation ;
- expliquer demande, invitation, responsabilité et cycle de vie ;
- ne proposer que les transitions portées par le moteur ;
- faire de la ZUMRA un espace Formation — Travail — Adoration orienté vers l’action.

**Sortie :** chaque membre comprend appartenance, responsabilité et prochaine action du collectif.

### P6 — Transmettre, réaliser et prouver

`Mission/Transmission → Participation → Réalisation ou blocage → Validation → Preuve → Fil`

- harmoniser la présentation des machines d’état proches sans fusionner leurs domaines ;
- rendre explicites responsable, échéance, dépendance, blocage et validation ;
- retourner le résultat réel dans son contexte d’origine et, si autorisé, dans le Fil.

**Sortie :** l’action collective produit une trace utile et vérifiable.

### P7 — Contribuer ou payer sans ambiguïté

`Finalité/Montant → Confirmation → Fournisseur ou ZAHAB → Attente/Retour → Reçu/Réconciliation`

- distinguer adhésion, contribution, acquisition ZAHAB et financement de projet ;
- afficher finalité et conséquences avant confirmation ;
- empêcher double soumission et faux succès ;
- expliquer attente, succès, échec, reprise et reçu ;
- ne jamais contourner le ledger.

**Sortie :** la personne connaît l’état réel de son opération et dispose de sa trace.

### P8 — Administrer et modérer dans le contexte

`Signal ou demande → File autorisée → Décision motivée → Effet visible → Journal`

- réserver les surfaces aux autorités prévues ;
- afficher des métriques réelles et anti-classement ;
- exiger une décision explicite pour les mutations sensibles ;
- rendre effet et traçabilité vérifiables.

**Sortie :** aucune décision administrative n’est silencieuse ou détachée de son domaine.

## Ordre d’exécution

Les identifiants sont permanents. Leur statut est la seule indication autorisée de la prochaine
étape.

| Lot | Contenu | Dépendance | Statut | Preuve de sortie |
|---|---|---|---|---|
| `UJ-00` | matrice écrans ↔ états ↔ services ↔ permissions ↔ erreurs | moteur certifié | **PASS** | `USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` |
| `UJ-01` | socle visuel, composants d’état, navigation et pipeline | UJ-00 | **PASS** | frontend Astra réconcilié sur `main`, tests frontend/build et smoke production validés |
| `UJ-02` | P0 Entrer/comprendre et identité | UJ-01 | **IN_PROGRESS** | accueil canonique, création/vérification/connexion et sortie P0 à certifier ensemble |
| `UJ-03` | P1 première intention et P2 retour quotidien | UJ-02 | PENDING | cockpit réel, priorité/action prouvées |
| `UJ-04` | P3 personnes, capacités, besoins et mise en relation | UJ-03 | PENDING | boucle découverte→action automatisée |
| `UJ-05` | P4 projet, équipe, mission et preuve | UJ-04 | PENDING | boucle projet verticale automatisée |
| `UJ-06` | P5 naissance et vie ZUMRA | UJ-05 | PENDING | transitions et autorités ZUMRA prouvées |
| `UJ-07` | P6 transmission, réalisation, preuve et Fil | UJ-06 | PENDING | résultat visible sans fuite d’autorité |
| `UJ-08` | P7 contributions, ZAHAB et paiements | UJ-07 | PENDING | succès/échec/reprise/idempotence prouvés |
| `UJ-09` | P8 administration, modération et surfaces contextuelles | UJ-08 | PENDING | décisions et journaux prouvés |
| `UJ-10` | fermeture exhaustive et préproduction | UJ-09 | PENDING | F5/F6 signées, GO/NO-GO documenté |

Valeurs autorisées : `PENDING`, `READY`, `IN_PROGRESS`, `BLOCKED`, `PASS`, `DEFERRED`.
Un seul lot peut être `IN_PROGRESS`.

## Définition de terminé

Un lot n’est `PASS` que si :

- ses écrans utilisent des données réelles ou des états vides honnêtes ;
- toutes les actions visibles sont câblées et protégées côté serveur ;
- succès, attente, refus, validation et erreur sont rendus ;
- la prochaine étape est compréhensible ;
- desktop et mobile sont vérifiés sans débordement bloquant ;
- clavier, focus, libellés et contraste sont contrôlés ;
- les parcours critiques ont des tests adaptés ;
- la matrice de couverture est mise à jour si sa vérité change ;
- aucun chemin protégé du moteur n’a changé silencieusement ;
- les preuves et le statut sont inscrits ici avant merge.

## Protocole de reprise par une IA

1. vérifier `main` et la branche de travail ;
2. lire `AGENTS.md`, `FRONTEND-REBUILD-001.md`, ce document et le contrat de navigation ;
3. vérifier dans le code/tests que le statut du tableau est toujours vrai ;
4. reprendre le premier lot `IN_PROGRESS`, sinon le premier `READY` ;
5. ne jamais démarrer un lot `PENDING` dont la dépendance n’est pas `PASS` ;
6. limiter la branche/PR à son lot et à ses preuves ;
7. mettre à jour ici statut, décisions et preuves avant merge ;
8. si code et roadmap divergent, documenter le conflit au lieu d’inventer une directive ;
9. ne jamais restaurer une ancienne vue ou un ancien nom produit depuis l’historique Git.

## Journal actif

| Date | Lot | Décision ou preuve | Référence |
|---|---|---|---|
| 2026-08-29 | `UJ-00` | matrice de contrats créée ; parcours, états, autorités et gaps cartographiés | `USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md` |
| 2026-08-29 | `UJ-01` | navigation mobile verrouillée : Fil · Découvrir · Agir · ZUMRA · Espace | `USER-JOURNEY-001-NAVIGATION-CONTRACT.md` |
| 2026-09-07 | Reprise Astra | version frontend Astra réintroduite pour revue sans modifier le moteur ; NO-GO production maintenu | branche de reprise Astra |
| 2026-09-08 | Identité GAMAD | ancien nom produit retiré des surfaces ; GAMAD devient le réseau visible, GAMAD Core reste invisible ; aucune promotion de statut UJ par ce seul changement | PR d’identité GAMAD |
| 2026-09-08 | `UJ-01` | socle Astra réconcilié sur `main`, construit et déployé ; navigation, accueil, connexion et espace membre répondent en production | `main` après PR #157/#158 |
| 2026-09-08 | `UJ-02` | décision produit : `/` devient l’unique landing publique ; `/decouvrir` est retirée comme page autonome et conservée seulement en redirection ; le « Découvrir » membre reste inchangé | branche `ux/uj02-landing-canonical-entry` |

Les journaux détaillés des tentatives précédentes restent dans l’historique Git. Ils ne sont pas
une autorité parallèle et ne doivent pas être restaurés comme instructions actives.

## Règle de clôture

Une migration de nom n’est jamais une autorisation de production. Le **NO-GO production** reste
inchangé jusqu’aux portes prévues par UJ-10 et les autorités de préproduction.
