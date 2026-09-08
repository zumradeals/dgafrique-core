# Expérience produit canonique — GAMAD

> **Règle de vérité active.** GAMAD est le nom canonique du réseau social d’action visible.
> GAMAD Core reste le moteur invisible de confiance, d’identité, de session et de fédération.
> Toute assertion historique présentant « DG Afrique » comme marque d’expérience active ou GAMAD
> comme un nom à cacher de l’utilisateur est supersédée par la décision d’identité du 8 septembre 2026.

## Statut et autorité

**CANONIQUE — Couche 04 (PRODUIT / UX).**

Ce document synthétise les invariants produit nécessaires pour concevoir l’expérience humaine.
Il est subordonné aux doctrines métier et humaines, et coordonné avec :

- `docs/canon/DOCTRINE-GAMAD.md` pour la raison d’être ;
- `docs/foundation/DG-AFRIQUE-DOCTRINE.md`, dont le chemin reste historique pour compatibilité mais
  dont le contenu canonique est désormais GAMAD ;
- `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` pour les règles propres à ZUMRA ;
- `docs/capacites/OVERRIDES.md` et les specs actives pour les contrats métier ;
- `docs/brand/BRAND-DOCTRINE-001.md` pour l’identité visuelle et de marque ;
- `docs/AI-RULES.md` pour les règles de contribution automatisée ;
- `docs/roadmap/USER-JOURNEY-001.md` pour l’exécution du frontend.

En matière de **nom public et d’architecture de marque**, `BRAND-DOCTRINE-001` et `AI-RULES`
font foi. Les formulations de marque historiques présentes dans d’anciens documents ZUMRA,
design, architecture ou dans l’historique Git ne peuvent pas réintroduire « DG Afrique » comme
produit visible.

Les identifiants techniques hérités (`dg-*`, chemins de fichiers, noms de tables, dépôt
`dgafrique-core`, domaine `dgafrique.com`) ne constituent pas une identité produit. Leur éventuel
renommage relève d’une migration technique séparée avec inventaire des dépendances et rollback.

---

## 1. Modèle mental

**GAMAD est un réseau social d’action.** Il accompagne le développement humain et l’action
collective en reliant personnes, capacités, besoins, projets, ZUMRA, missions, apprentissages,
transmissions, preuves, opportunités et outils spécialisés.

GAMAD n’est pas :

- un moteur de recherche comme finalité produit ;
- un catalogue d’applications ;
- un lanceur de satellites ;
- un réseau fondé sur la popularité ou la captation de l’attention ;
- la console technique de GAMAD Core.

Formule directrice :

> La navigation, les recommandations et l’intelligence du produit servent le passage de la
> capacité à l’action humaine et collective.

---

## 2. Architecture visible et invisible

| Couche | Rôle |
|---|---|
| **GAMAD** | Marque publique et réseau social d’action visible. Porte la promesse, l’expérience humaine et l’orchestration produit. |
| **GAMAD Core** | Moteur invisible de confiance : identités canoniques, sessions, autorisations transversales, attestations, références, audit et fédération. |
| **ZUMRA** | Programme et force collective organisée au sein du réseau. |
| **Satellites / outils spécialisés** | Outils métier contextuels, d’abord extractibles ; autonomes seulement lorsqu’un besoin réel d’indépendance le justifie. |

Invariant : **GAMAD Core structure la confiance ; GAMAD traduit cette structure en expérience
humaine compréhensible.** Les utilisateurs n’ont pas à apprendre l’architecture du Core pour agir.

---

## 3. ZUMRA, Projet et Fil restent distincts

- **ZUMRA** = centre d’organisation collective.
- **Projet** = centre d’action structurée.
- **Fil** = centre de circulation, découverte et mise en mouvement.

Une ZUMRA n’est ni une Organisation juridique, ni un Projet, ni un simple groupe de discussion.
Un Projet peut être gouverné par une personne ou collectivement, mais l’invariant métier actif
sur son ancrage ZUMRA reste régi par les contrats et specs correspondants.

La devise ZUMRA demeure : **Formation — Travail — Adoration**.

---

## 4. Boucles fondatrices

Les interfaces doivent servir les boucles suivantes sans les transformer en tunnel obligatoire :

`FIL → PROJET → ACTION → PREUVE → FIL`

`INTENTION → ACTION → PROJET → PREUVE → CAPACITÉ → CONFIANCE → MISE EN RELATION → NOUVELLE ACTION`

Une personne peut entrer par une capacité, un besoin, une ZUMRA, un projet, une invitation,
une mission ou une transmission. Le système doit toujours rendre la prochaine action réelle
compréhensible.

---

## 5. Test doctrinal d’une fonctionnalité

Avant toute fonctionnalité ou refonte majeure :

1. Quelle réalité humaine sert-elle ?
2. Aide-t-elle à passer de l’intention à l’action, à la connaissance ou au développement ?
3. Expose-t-elle une complexité que GAMAD pourrait gérer pour la personne ?
4. Respecte-t-elle la souveraineté humaine ?
5. Utilise-t-elle une information déclarée, observée ou prouvée sans les confondre ?
6. Comment interagit-elle avec Personnes, Capacités, Besoins, Projet, ZUMRA et Fil ?
7. Que devient l’information produite dans le temps ?
8. Une personne peu alphabétisée ou peu familière du numérique peut-elle comprendre l’intention
   principale de l’expérience ?

Si la réponse fondamentale à l’une de ces questions est négative, l’expérience doit être repensée
avant implémentation.

---

## 6. Interfaces fondatrices

| Interface | Rôle |
|---|---|
| **Accueil / entrée publique** | Expliquer GAMAD comme réseau social d’action et permettre d’entrer. |
| **Découvrir** | Montrer uniquement des objets réellement partageables publiquement et des états vides honnêtes. |
| **Mon espace** | Indiquer la priorité personnelle et la prochaine action ; ne jamais devenir un tableau de bord de modules. |
| **Fil** | Faire circuler les mouvements du réseau avec une pertinence explicable, jamais un score humain. |

Ces surfaces ne fusionnent pas : l’entrée explique, Découvrir ouvre le réseau, Mon espace oriente,
le Fil fait circuler.

---

## 7. Navigation

Le contrat d’exécution courant est fixé par
`docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md`.

Sur mobile : **Fil · Découvrir · Agir · ZUMRA · Espace**, avec `Agir` au centre et sans menu
« Plus ». `Découvrir` regroupe Personnes, Besoins et Projets.

Les outils spécialisés, satellites, mesures, modération et administration restent contextuels ;
ils ne deviennent pas un catalogue de premier niveau.

---

## 8. Mécaniques sociales interdites

Aucun :

- like comme mécanisme de valeur ;
- follower/abonné comme mesure de valeur humaine ;
- score d’influence ou classement de personnes ;
- viralité comme moteur principal de classement ;
- réputation achetable ;
- gamification de la valeur humaine ;
- défilement infini conçu pour créer une dépendance.

La pertinence peut être calculée et priorisée, mais elle doit rester explicable en langage humain.
La sponsorisation commerciale, si elle existe un jour, doit être explicitement distinguée de la
pertinence organique.

---

## 9. Le Fil

Le Fil est le système de circulation des mouvements de GAMAD. Il doit répondre à :

- « Que se passe-t-il dans le réseau ? »
- « Qu’est-ce qui peut être utile pour moi et pourquoi ? »

Une publication structurante doit produire ou utiliser un objet métier réel. Une recherche d’aide
crée ou référence un vrai Besoin ; elle ne devient pas un post social parallèle sans contrat.

---

## 10. Vérité des données

`PRODUCTION-TRUTH-002` interdit de présenter une donnée fictive comme réelle.

- aucun faux membre, besoin, projet, ZUMRA, paiement ou partenaire ;
- aucun compteur inventé ;
- aucun seed de démonstration utilisé implicitement comme runtime produit ;
- aucune activité fabriquée pour rendre un écran visuellement riche.

Les anciennes décisions `DEMO-FIRST` et les addenda de démonstration sont historiques lorsqu’elles
contredisent `PRODUCTION-TRUTH-002`.

**Mon espace** reste à tolérance zéro : toute information personnelle affichée doit être réellement
celle de la personne.

---

## 11. États honnêtes

Chargement, vide, refus, erreur, perte réseau, attente et indisponibilité sont des états produit à
part entière. Une action visuellement disponible doit être réellement câblée ; sinon elle est
absente ou accompagnée de sa raison exacte.

---

## 12. Première arrivée

Le nouvel utilisateur ne doit pas entrer dans un logiciel de modules.

Parcours conceptuel :

`GAMAD → Comprendre → Compte → Première intention → Première action utile → Retour`

Le compte GAMAD est distinct de l’adhésion au Programme ZUMRA. La création d’un compte n’impose
jamais l’adhésion ZUMRA.

Une première intention peut être exprimée avec peu d’informations : apporter quelque chose,
exprimer un besoin, découvrir ou participer. Le profil peut s’enrichir progressivement par
l’action.

---

## 13. Souveraineté humaine et IA

Project Brain et toute intelligence intégrée peuvent observer, rapprocher, suggérer, préparer et
expliquer. Une décision engageante reste soumise à la confirmation humaine et aux autorités
métier existantes.

Toute action métier essentielle qui possède une voie déterministe doit rester utilisable sans
obliger la personne à passer par une conversation IA.

---

## 14. Contexte plutôt que catalogue

Missions, Événements, Partenariats, Organisations, Preuves, Transmissions et outils spécialisés
se découvrent depuis les contextes où ils deviennent utiles. L’existence d’une capacité backend
ne crée pas automatiquement une nouvelle entrée de navigation.

Une relation métier ne doit jamais être inventée pour simplifier l’interface.

---

## 15. Décisions UX durables consolidées

Les décisions suivantes restent actives tant que le code ou une autorité métier supérieure ne les
révise pas explicitement :

- les retours de contexte doivent ramener vers l’objet réel d’origine ;
- une ZUMRA est un espace contextuel réel, pas un menu supplémentaire ;
- une Organisation reste distincte d’une ZUMRA et d’un Projet ;
- un Partnership représente une collaboration concrète, jamais une capacité générale déduite ;
- les capacités d’une Organisation sont des faits déclarés explicitement, pas des inférences à
  partir de ses partenariats ou des capacités personnelles de ses membres ;
- une personne découverte peut recevoir une proposition, jamais être inscrite automatiquement à
  une relation exigeant son consentement ;
- une Transmission terminée peut ouvrir une porte vers une Preuve, sans fabriquer cette preuve ;
- la naissance d’une ZUMRA reste légère ; la structuration et la gouvernance viennent ensuite ;
- les projets et brouillons doivent respecter les autorités métier et ne pas contourner les quotas,
  ancrages ou validations par un état UI parallèle ;
- les actions sensibles ont une confirmation proportionnée à leur impact ;
- une action métier ne doit pas être dupliquée dans Blade si un service/policy existe déjà pour la
  trancher.

Les détails d’implémentation des anciennes missions UIUX restent disponibles dans l’historique Git
et dans les specs actives concernées ; ils ne constituent pas une seconde doctrine produit.

---

## 16. Accessibilité et conditions réelles

- WCAG 2.2 AA comme cible minimale ;
- mobile vérifié dès 360 px ;
- focus clavier visible ;
- navigation utilisable sans souris ;
- structure compatible RTL/bidi ;
- images adaptatives et non bloquantes ;
- interface utilisable sur réseau lent et appareil modeste ;
- aucun contenu essentiel enfermé dans une image ;
- vocabulaire humain avant les enums, CAP ou références Core.

---

## 17. Questions ouvertes

Une question ouverte n’autorise aucune invention silencieuse. Restent à formaliser ou vérifier
selon les autorités actives :

- les sujets explicitement marqués ouverts dans les specs métier ;
- les intégrations satellites non encore contractées ;
- toute évolution de sponsorisation ;
- tout nouveau moteur de proximité ou de recommandation ;
- toute nouvelle gouvernance financière ou juridique non déjà canonisée.

---

## 18. Gouvernance

Toute évolution de ce document doit être consciente, argumentée et accompagnée de la modification
correspondante du code/tests lorsqu’elle change un comportement réel.

Le document ne sert pas de journal d’implémentation. L’historique Git conserve les addenda et
missions précédentes. Les détails d’exécution appartiennent à `USER-JOURNEY-001`, aux specs et aux
tests.

En cas de divergence :

1. vérifier le code et les tests pour la vérité exécutable ;
2. vérifier l’autorité métier supérieure ;
3. corriger la documentation active dans le même changement ;
4. ne jamais réintroduire un ancien nom, un ancien écran ou une ancienne architecture uniquement
   parce qu’ils existent dans l’historique.

## 19. Contrat d’identité du 8 septembre 2026

La décision est définitive pour le produit courant :

> **GAMAD est le réseau social d’action visible.**
>
> **GAMAD Core est son moteur invisible de confiance.**
>
> **« DG Afrique » est un ancien nom produit et ne doit plus apparaître comme marque active dans
> l’interface ou les autorités produit.**

Le renommage des identifiants techniques hérités et du domaine public n’est pas inclus dans ce
contrat et devra être traité comme migration d’infrastructure séparée.
