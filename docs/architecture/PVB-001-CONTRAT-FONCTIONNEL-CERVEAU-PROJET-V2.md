# PVB-001 — Contrat fonctionnel du Cerveau Projet V2

**Statut :** CONTRAT PRODUIT — V1 de travail  
**Branche :** `feat/project-brain-v2`  
**Référence supérieure :** `docs/architecture/ARCHITECTURE-PRODUIT-V2.md`  
**Objet :** définir ce qu'est le Cerveau Projet, ce qu'il voit, ce qu'il peut comprendre, proposer, préparer et exécuter après confirmation, ainsi que ses frontières avec la vérité métier.

---

## 1. Définition

Le **Cerveau Projet** est l'interface intelligente et contextuelle d'un Projet DG Afrique.

Il n'est ni le Projet lui-même, ni une base de données parallèle, ni une autorité de gouvernance, ni un moteur autonome de décision.

Sa fonction est de rendre la complexité du Core compréhensible et actionnable par un humain :

`COMPRENDRE → RETROUVER LE CONTEXTE → EXPLIQUER → PROPOSER → PRÉPARER → FAIRE CONFIRMER → DÉLÉGUER AU CORE → RENDRE COMPTE`

**Invariant principal : le Core possède la vérité métier ; le Cerveau possède le contexte ; l'humain possède la décision.**

---

## 2. Objectif humain

Le Cerveau doit permettre à une personne peu technophile, peu à l'aise avec les formulaires ou l'écrit de piloter progressivement un Projet sans connaître la structure technique de DG Afrique.

L'utilisateur doit pouvoir exprimer une intention naturellement :

- « Je veux lancer une activité de transformation de manioc. »
- « Il nous manque deux développeurs. »
- « Qu'est-ce qui bloque ? »
- « Que devons-nous faire cette semaine ? »
- « Nous avons terminé la formation aujourd'hui. »
- « Trouve-nous quelqu'un à Bouaké. »

Le système traduit cette expression vers les objets métier existants sans obliger l'utilisateur à comprendre `Need`, `Mission`, `Proof`, `ProjectEvent`, matching ou autres concepts internes.

La conversation est un **mode d'accès à l'action structurée**, pas un remplacement de la structure métier.

---

## 3. Le Projet reste la racine

Chaque Cerveau est rattaché à **un Projet précis**.

Il ne doit jamais mélanger silencieusement les informations de plusieurs projets.

Un utilisateur peut avoir plusieurs projets ; chacun possède son propre contexte, son historique métier et ses conversations.

Le Cerveau peut signaler une relation avec un autre Projet ou une autre ZUMRA, mais toute action inter-projets doit être explicite.

---

## 4. Ce que le Cerveau peut voir

Le Cerveau ne bénéficie d'aucun privilège magique. Il voit uniquement ce que l'utilisateur courant et le contexte d'exécution ont le droit de voir.

Selon les droits et la disponibilité des données, son contexte peut inclure :

- identité et état du Projet ;
- initiateur, porteur et gouvernance ;
- ZUMRA porteuse ou liée ;
- membres de l'équipe et rôles ;
- capacités disponibles/recherchées ;
- Besoins liés ;
- Missions et dépendances ;
- jalons ;
- événements du Projet ;
- accompagnements ;
- preuves et références visibles ;
- signaux observables de maturité ;
- recommandations autorisées ;
- transmissions pertinentes ;
- opportunités/appels lorsqu'ils existeront ;
- informations financières autorisées lorsqu'un futur domaine Finance sera disponible ;
- capacités déclarées par des outils spécialisés autorisés.

**Invariant : une donnée invisible au membre reste invisible au Cerveau qui agit pour ce membre.**

---

## 5. Ce que le Cerveau doit savoir faire

### 5.1 Expliquer

Il peut répondre à des questions telles que :

- Où en est le Projet ?
- Qu'avons-nous déjà réalisé ?
- Quels besoins sont encore ouverts ?
- Qui compose l'équipe ?
- Quelles missions sont bloquées ?
- Quelles preuves existent ?
- Quelle a été la dernière activité ?

Il doit privilégier les faits observables plutôt qu'un score global opaque.

Exemple :

> 4 jalons sur 7 sont réalisés, 2 besoins restent ouverts et aucune activité n'a été enregistrée depuis 12 jours.

plutôt que :

> Votre Projet est mature à 63 %.

### 5.2 Comprendre une intention

Il peut interpréter une phrase comme une **intention candidate**, par exemple :

- recherche d'une capacité → Besoin candidat ;
- travail à réaliser → Mission candidate ;
- résultat annoncé → événement/preuve candidate ;
- personne souhaitée dans l'équipe → invitation candidate ;
- demande d'aide → accompagnement ou Besoin candidat ;
- opportunité évoquée → information candidate à vérifier.

L'interprétation ne crée pas encore la vérité métier.

### 5.3 Proposer

Il peut proposer :

- prochaine action ;
- création ou clarification d'un Besoin ;
- création d'une Mission ;
- invitation d'une personne ;
- recherche de capacités ;
- ajout d'une preuve ;
- demande d'accompagnement ;
- consultation d'une recommandation ;
- utilisation d'un outil spécialisé disponible ;
- préparation future d'une candidature ou demande de financement lorsque les domaines correspondants existeront.

Toute proposition doit pouvoir répondre à : **Pourquoi me proposes-tu cela ?**

### 5.4 Préparer

Le Cerveau peut pré-remplir ou construire un brouillon à partir de la conversation et des faits existants :

- brouillon de Besoin ;
- brouillon de Mission ;
- invitation ;
- résumé du Projet ;
- plan d'action ;
- demande d'accompagnement ;
- description destinée à une candidature ;
- liste de pièces manquantes.

Un brouillon n'est pas une mutation métier.

### 5.5 Déclencher après confirmation

Après confirmation explicite et vérification de l'autorité, le Cerveau peut demander au service métier canonique d'exécuter l'action.

Exemple :

`conversation → brouillon Need → aperçu → confirmation → NeedService → ProjectEvent → Fil éventuel`

Le Cerveau ne contourne jamais le service métier, ses validations, ses permissions ou ses événements.

---

## 6. Niveaux d'action

Chaque capacité du Cerveau appartient à un niveau de risque.

### N0 — Lecture

Aucune confirmation supplémentaire : lire, résumer, expliquer, comparer des faits déjà visibles.

### N1 — Suggestion

Aucune mutation : proposer une prochaine action, une personne, une Mission, un Besoin ou une amélioration.

### N2 — Brouillon

Le Cerveau structure une action mais ne l'exécute pas. L'utilisateur peut corriger, abandonner ou confirmer.

### N3 — Mutation confirmée

Création/modification d'un objet métier après confirmation explicite et contrôle des permissions.

### N4 — Action sensible

Financement, décaissement, engagement contractuel, changement majeur de gouvernance, suppression irréversible ou autre action à risque : le Cerveau ne décide jamais seul. Les workflows spécialisés, contrôles et confirmations renforcées s'appliquent.

---

## 7. Conversation, mémoire, journal et preuve

Ces quatre couches sont strictement distinctes.

### 7.1 Conversation

Historique des échanges humains avec le Cerveau.

Une phrase peut être hypothétique, erronée, émotionnelle ou exploratoire.

### 7.2 Mémoire Projet

Faits contextuels utiles issus d'une source identifiable et possédant un état, par exemple :

- `candidate` : déduit/proposé mais non confirmé ;
- `confirmed` : confirmé par une autorité compétente ;
- `superseded` : remplacé par une information plus récente ;
- `rejected` : explicitement refusé ;
- `expired` : information devenue trop ancienne pour être utilisée comme actuelle.

Une mémoire doit conserver sa provenance : conversation, objet métier, événement, preuve ou action humaine.

### 7.3 Journal métier

`ProjectEvent` et les événements des domaines concernés décrivent ce qui a réellement eu lieu dans le système.

Le Cerveau lit le journal ; il ne le réécrit pas librement.

### 7.4 Preuve

Une preuve soutient une affirmation ou un résultat selon le domaine Proof.

Dire « la formation est terminée » dans une conversation ne crée pas automatiquement une preuve. Le Cerveau peut proposer : **Ajouter une preuve ?**

---

## 8. Contrat de mémoire

La mémoire du Cerveau doit être **sélective**, pas une copie infinie de chaque phrase.

Elle privilégie les informations susceptibles de changer la compréhension ou l'action du Projet :

- objectif ou orientation ;
- territoire ;
- contraintes ;
- décisions ;
- préférences opérationnelles ;
- partenaires envisagés ;
- ressources annoncées ;
- risques ;
- hypothèses importantes ;
- engagements ;
- prochaines actions.

Le système doit permettre de savoir **d'où vient un souvenir** et, pour les éléments structurants, de le corriger ou le révoquer.

Une mémoire ne doit jamais supplanter silencieusement une donnée canonique plus fiable.

Priorité indicative des sources :

`PREUVE / ÉVÉNEMENT MÉTIER → OBJET MÉTIER CONFIRMÉ → MÉMOIRE CONFIRMÉE → CONVERSATION / HYPOTHÈSE`

Cette hiérarchie ne remplace pas les règles spécifiques de chaque domaine.

---

## 9. Contrat de recommandation

Le Cerveau peut consommer les moteurs de recommandation, mais ne doit pas inventer de compatibilité.

Exemple :

> Trois personnes peuvent être pertinentes pour ce Besoin.
> Pourquoi ? Elles ont déclaré la capacité Développement Laravel, accepté les rapprochements et sont visibles dans ce contexte.

Le Cerveau ne doit pas :

- afficher un score de valeur humaine ;
- garantir qu'une personne acceptera ;
- ajouter automatiquement une personne à l'équipe ;
- utiliser une contribution financière comme avantage de matching ;
- révéler une personne ou donnée hors consentement.

---

## 10. Contrat avec le Fil

Le Cerveau peut produire, via les services métier, des événements qui deviennent éligibles au Fil.

Il ne publie pas directement un faux post parallèle.

Exemple :

`« Nous cherchons un formateur » → Need confirmé → NeedService → événement → Fil → personnes pertinentes → action`

Inversement, un élément du Fil peut ouvrir le Projet et son Cerveau avec le bon contexte :

`Fil → opportunité/recommandation → Projet → Cerveau → explication/action`

---

## 11. Contrat avec les outils spécialisés

Le Cerveau ne doit pas contenir en dur toute la logique de G-POS, Transport/Logistique ou d'autres outils futurs.

Un outil spécialisé peut exposer des capacités utilisables dans certains contextes.

Exemple futur :

`Besoin matériel → capacité G-POS disponible → offres autorisées → choix humain → paiement → livraison → preuve`

ou :

`Besoin logistique → outil Transport disponible → propositions → choix → mission/livraison → preuve`

Si l'outil n'existe pas, le Projet et le Besoin restent parfaitement valides.

---

## 12. Contrat futur avec Finance

Le Cerveau pourra aider à **rendre un Projet finançable**, jamais à s'auto-financer.

Il pourra :

- expliquer les critères d'un programme ;
- détecter les pièces manquantes ;
- préparer une demande ;
- structurer un plan de financement ;
- présenter les apports et besoins ;
- suivre les jalons et preuves liés à un décaissement.

Il ne pourra jamais :

- promettre un financement ;
- décider seul d'une allocation ;
- déplacer de l'argent sans workflow autorisé ;
- inventer une preuve financière ;
- présenter un financement comme acquis avant décision réelle.

---

## 13. Modes d'entrée

Le contrat produit doit être compatible avec plusieurs entrées :

- texte ;
- voix/transcription lorsque disponible ;
- actions rapides ;
- formulaire simplifié de secours ;
- événement provenant du Fil ;
- notification ;
- outil spécialisé.

Le texte n'est donc pas une obligation doctrinale. L'objectif est de réduire la charge cognitive, notamment pour les publics peu à l'aise avec l'écrit.

---

## 14. Expérience de création d'un Projet

La création V2 ne doit plus commencer par un grand formulaire.

Entrée minimale souhaitée :

> **Qu'est-ce que vous voulez réaliser ?**

L'utilisateur répond en quelques mots ou oralement.

Le Cerveau peut ensuite demander **une seule information utile à la fois**, uniquement lorsque nécessaire.

Exemple :

1. « Je veux ouvrir un atelier informatique. »
2. « Où souhaitez-vous commencer ? »
3. « À Bouaké. »
4. « Êtes-vous seul ou avez-vous déjà une équipe ? »

Le Projet peut être créé tôt avec un état incomplet assumé. Il s'enrichit pendant sa vie.

**Invariant UX : ne pas exiger aujourd'hui une information qui peut être apprise demain sans risque métier.**

---

## 15. Espace Projet V2 — zones fonctionnelles

L'interface future doit au minimum rendre accessibles :

1. **Historique / navigation Projet** — projets et conversations ;
2. **Conversation centrale** — Cerveau ;
3. **État réel du Projet** — faits observables ;
4. **Actions** — besoins, missions, équipe, preuves ;
5. **Contexte** — porteur, ZUMRA, territoire, objectif ;
6. **Activité** — événements récents ;
7. **Opportunités** — recommandations pertinentes ;
8. **Outils spécialisés** — uniquement lorsqu'ils sont utiles/disponibles.

Ces zones ne doivent pas toutes être visibles simultanément sur mobile.

---

## 16. Réponse « Aujourd'hui » du Cerveau

Le Cerveau doit pouvoir produire une synthèse courte orientée action :

- ce qui a changé ;
- ce qui bloque ;
- ce qui attend une décision ;
- ce qui pourrait faire avancer ;
- une ou quelques actions prioritaires explicables.

Il ne doit pas fabriquer une urgence artificielle ni transformer le Projet en système de gamification.

---

## 17. Échecs et incertitude

Le Cerveau doit savoir dire :

- « Je ne trouve pas cette information dans le Projet. »
- « Cette information a été évoquée mais pas confirmée. »
- « Je n'ai pas accès à cette donnée. »
- « Deux informations se contredisent ; laquelle est correcte ? »
- « Je peux préparer cette action, mais vous devez la confirmer. »

Il vaut mieux afficher une incertitude que fabriquer une cohérence fausse.

---

## 18. Traçabilité des actions du Cerveau

Pour toute mutation N3/N4 initiée depuis le Cerveau, le système doit pouvoir retracer au minimum :

- utilisateur initiateur ;
- Projet ;
- action proposée ;
- données présentées à confirmation ;
- confirmation ;
- service métier appelé ;
- résultat ou erreur ;
- événement métier produit lorsque applicable.

Le texte généré par le Cerveau n'est jamais, à lui seul, une preuve que l'action a réussi.

---

## 19. Frontières non négociables PVB-001

1. Le Cerveau ne devient jamais une seconde base métier.
2. Le Cerveau ne contourne jamais les permissions.
3. Le Cerveau ne transforme jamais une suggestion en décision sans confirmation lorsque la mutation l'exige.
4. Conversation, mémoire, événement et preuve restent distincts.
5. Pas de score opaque de valeur ou de maturité humaine/projet présenté comme vérité.
6. Pas d'invention de personne, capacité, besoin, opportunité, financement ou preuve.
7. Pas de mélange silencieux entre plusieurs Projets.
8. Pas d'outil spécialisé obligatoire pour faire exister un Projet.
9. Pas de publication sociale parallèle lorsqu'un objet métier réel doit être créé.
10. Le Cerveau doit expliquer les raisons d'une recommandation importante.
11. Une information incertaine reste marquée comme incertaine.
12. Une action sensible reste sous gouvernance humaine et workflow spécialisé.
13. L'expérience conversationnelle doit réduire la complexité sans supprimer la rigueur du Core.

---

## 20. Architecture logique cible

```text
                       HUMAIN
                         │
             texte / voix / action
                         │
                         ↓
                🧠 CERVEAU PROJET
                         │
        ┌────────────────┼────────────────┐
        ↓                ↓                ↓
     CONTEXTE          MÉMOIRE        ORCHESTRATION
        │                │                │
        └────────────────┼────────────────┘
                         ↓
                  PROPOSITION / BROUILLON
                         │
                    CONFIRMATION
                         │
                         ↓
                    CORE MÉTIER
       ┌────────┬────────┼────────┬────────┐
       ↓        ↓        ↓        ↓        ↓
     Need    Mission   Team    Proof   Accompagnement
       │        │        │        │        │
       └────────┴────────┼────────┴────────┘
                         ↓
                   ProjectEvent
                         │
              ┌──────────┴──────────┐
              ↓                     ↓
             FIL              CONTEXTE FUTUR
                              Finance / Appels /
                              Outils spécialisés
```

---

## 21. Critères d'acceptation fonctionnels PVB-001

PVB-001 est considéré correctement traduit dans le produit lorsque :

- un Projet peut être initié sans grand formulaire ;
- le Cerveau peut expliquer l'état réel à partir du Core ;
- une phrase naturelle peut produire un brouillon d'action structurée ;
- aucune mutation importante n'est exécutée silencieusement ;
- une action confirmée passe par le service métier canonique ;
- l'utilisateur peut distinguer ce qui est dit, mémorisé, réellement enregistré et prouvé ;
- les recommandations importantes sont explicables ;
- les permissions du Core sont conservées ;
- l'absence d'un futur outil spécialisé ne bloque jamais le Projet ;
- l'interface peut fonctionner sur desktop et être simplifiée sur mobile ;
- le Cerveau peut reconnaître explicitement l'incertitude et l'absence d'information.

---

## 22. Hors périmètre de PVB-001

Ce contrat ne choisit pas encore :

- fournisseur/modèle d'IA ;
- architecture RAG/vectorielle ;
- modèle exact des tables conversation/mémoire ;
- politique de rétention ;
- transcription vocale ;
- design visuel final ;
- implémentation des modules Appels à projets/Finance ;
- contrat technique final des outils spécialisés ;
- statut monétaire de ZAHAB.

Ces décisions doivent respecter le présent contrat lorsqu'elles seront traitées.

---

## 23. Suite recommandée

Après validation de PVB-001 :

1. **PVB-002 — Architecture UX de l'Espace Projet V2** ;
2. maquette desktop puis mobile ;
3. **PVB-003 — Contrat Conversation & Mémoire Projet** ;
4. **PVB-004 — Contrat Actions/Tools du Cerveau vers le Core** ;
5. plan de migration de l'interface Projet actuelle ;
6. implémentation incrémentale avec tests de permissions et non-régression.
