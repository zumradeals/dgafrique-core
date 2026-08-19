# PVB-003 — Contrat Conversation & Mémoire Projet

**Statut :** CONTRAT PRODUIT / DONNÉES — V1 de travail  
**Branche :** `feat/project-brain-v2`  
**Références supérieures :** `ARCHITECTURE-PRODUIT-V2.md`, `PVB-001-CONTRAT-FONCTIONNEL-CERVEAU-PROJET-V2.md`, `PVB-002-ARCHITECTURE-UX-ESPACE-PROJET-V2.md`  
**Objet :** définir comment le Cerveau Projet converse, conserve le contexte, construit une mémoire utile, retrouve l'historique et distingue strictement parole, mémoire, vérité métier, événement et preuve.

---

## 1. Thèse

Un Projet DG Afrique doit pouvoir conserver une continuité intellectuelle sans transformer chaque phrase en vérité.

Le système doit permettre à l'utilisateur de revenir demain, dans trois mois ou après plusieurs conversations et de retrouver un Cerveau qui comprend le contexte utile du Projet.

Mais :

> **Se souvenir n'est pas enregistrer une vérité métier.**

Architecture conceptuelle :

`CONVERSATIONS → EXTRACTION CONTEXTUELLE → MÉMOIRE SÉLECTIVE → CONTEXTE DU CERVEAU`

et séparément :

`ACTIONS CONFIRMÉES → CORE MÉTIER → ÉVÉNEMENTS / PREUVES → CONTEXTE DU CERVEAU`

Les deux flux alimentent le contexte, mais ils n'ont jamais la même autorité.

---

## 2. Un Projet peut avoir plusieurs conversations

Un Projet possède zéro, une ou plusieurs conversations.

Exemples :

- Conversation générale ;
- Financement & apports ;
- Équipement & matériel ;
- Local & aménagement ;
- Préparation d'une mission ;
- Candidature à un appel futur.

Une conversation appartient toujours à **un seul Projet**.

Une conversation ne devient jamais un sous-projet et ne possède pas sa propre vérité métier.

L'utilisateur peut commencer une nouvelle conversation pour changer de sujet sans perdre la mémoire utile du Projet.

---

## 3. Conversation active et historique

À tout moment, l'interface connaît :

- Projet actif ;
- conversation active ;
- autres conversations du même Projet ;
- conversations archivées lorsque cette capacité existe.

Changer de conversation ne change pas de Projet.

Changer de Projet change obligatoirement le périmètre de mémoire et de contexte.

**Invariant : aucune mémoire d'un Projet A ne doit être injectée silencieusement dans un Projet B.**

---

## 4. Message

Un message est une unité de conversation.

Il peut provenir notamment de :

- utilisateur ;
- Cerveau ;
- système, pour certains événements techniques/UX explicitement identifiés.

Un message doit pouvoir conserver au minimum :

- conversation ;
- auteur/type d'auteur ;
- contenu ;
- date/heure ;
- statut technique ;
- références éventuelles vers des objets métier ;
- pièces jointes/références lorsque disponibles ;
- métadonnées nécessaires à la traçabilité.

Le schéma exact est hors périmètre de ce contrat.

---

## 5. Le message utilisateur n'est pas une vérité

Exemple :

> « Nous avons déjà 20 ordinateurs. »

Cette phrase peut être vraie, approximative, ancienne ou erronée.

Le Cerveau peut l'utiliser comme **information conversationnelle** et éventuellement proposer une mémoire candidate :

> Ressource annoncée : 20 ordinateurs.

Mais il ne doit pas créer automatiquement un inventaire, une preuve ou un actif métier confirmé.

---

## 6. Mémoire Projet

La **Mémoire Projet** est une couche contextuelle sélective permettant au Cerveau de conserver les informations utiles qui ne sont pas nécessairement représentées par un objet métier canonique.

Elle évite deux extrêmes :

- oublier tout entre deux conversations ;
- stocker chaque phrase comme une vérité permanente.

La mémoire doit rester petite, explicable, corrigeable et reliée à sa provenance.

---

## 7. Ce qui mérite d'être mémorisé

Une information est candidate à la mémoire si elle peut modifier durablement la compréhension ou les prochaines actions du Projet.

Catégories indicatives :

- objectif / orientation ;
- territoire ou zone d'action ;
- contraintes importantes ;
- décisions humaines ;
- hypothèses structurantes ;
- ressources annoncées ;
- partenaires envisagés ;
- préférences opérationnelles ;
- risques identifiés ;
- engagements ;
- prochaines actions ;
- contexte utile non représenté ailleurs dans le Core.

Ne doivent normalement pas être mémorisés comme faits durables : salutations, bavardage, formulations temporaires, répétitions, détails sans effet sur le Projet ou texte déjà fidèlement représenté dans un objet métier canonique.

---

## 8. États d'une mémoire

Une mémoire possède un état explicite.

### `candidate`

Information extraite ou proposée mais non suffisamment confirmée.

### `confirmed`

Information confirmée par un utilisateur autorisé ou issue d'un processus de confirmation fiable.

### `superseded`

Information historiquement vraie/utile mais remplacée par une version plus récente.

### `rejected`

Information explicitement rejetée ou reconnue incorrecte.

### `expired`

Information dont l'ancienneté ou la nature empêche de la considérer comme actuelle sans nouvelle vérification.

Les noms techniques exacts peuvent évoluer à l'implémentation ; la sémantique doit être conservée.

---

## 9. Provenance obligatoire

Toute mémoire doit pouvoir expliquer :

> **Pourquoi le Cerveau croit-il savoir cela ?**

La provenance peut pointer vers :

- message/conversation ;
- confirmation humaine ;
- objet métier ;
- événement métier ;
- preuve ;
- import/source autorisée future.

Une mémoire sans provenance exploitable ne doit pas devenir un fait structurant confirmé.

---

## 10. Hiérarchie de confiance

Le Cerveau doit privilégier les sources les plus autoritatives.

Ordre conceptuel :

`PREUVE / ÉVÉNEMENT MÉTIER → OBJET MÉTIER CONFIRMÉ → MÉMOIRE CONFIRMÉE → MÉMOIRE CANDIDATE → CONVERSATION / HYPOTHÈSE`

Cette hiérarchie est indicative : les règles du domaine métier concerné restent souveraines.

Une mémoire ne peut pas silencieusement écraser une vérité métier plus forte.

---

## 11. Mémoire et objet métier

Lorsque l'information devient un objet métier réel, le Cerveau doit privilégier cet objet.

Exemple :

1. Conversation : « Nous cherchons deux développeurs. »
2. Mémoire candidate éventuelle : capacité manquante — développement web.
3. Utilisateur confirme la création du Besoin.
4. `Need` devient la vérité métier.
5. La mémoire candidate peut être liée, clôturée, remplacée ou simplement ne plus être utilisée comme source principale.

**Invariant : ne pas maintenir deux vérités concurrentes pour la même réalité.**

---

## 12. Mémoire et décision

Une décision structurante exprimée dans une conversation peut devenir une mémoire confirmée si l'utilisateur compétent la confirme.

Exemple :

> « Nous avons décidé de commencer à Bouaké plutôt qu'à Abidjan. »

Le Cerveau peut demander :

> Voulez-vous retenir Bouaké comme zone de démarrage du Projet ?

Après confirmation, cette décision peut alimenter la mémoire et, si un champ métier canonique existe, proposer sa mise à jour via le workflow approprié.

---

## 13. Correction et oubli

L'utilisateur autorisé doit pouvoir dire :

- « Ce n'est plus vrai. »
- « Nous avons changé d'avis. »
- « Ce chiffre était faux. »
- « Oublie cette hypothèse pour ce Projet. »

Le système ne doit pas nécessairement supprimer l'histoire. Il peut marquer la mémoire `superseded`, `rejected` ou autre état approprié afin de conserver la traçabilité sans continuer à l'utiliser comme actuelle.

Les obligations légales futures de suppression/rétention restent distinctes de ce mécanisme fonctionnel.

---

## 14. Contradictions

Lorsque deux informations importantes se contredisent, le Cerveau ne choisit pas arbitrairement.

Exemple :

Ancienne mémoire confirmée :

> Budget cible : 1 500 000 F.

Nouvelle conversation :

> « Finalement le budget sera 3 millions. »

Réponse attendue :

> J'avais retenu un budget cible de 1 500 000 F. Voulez-vous le remplacer par 3 000 000 F ?

La contradiction devient une occasion de clarification.

---

## 15. Mémoire temporelle

Certaines informations vieillissent naturellement.

Exemples :

- « Nous cherchons encore un local » ;
- « Moussa est disponible cette semaine » ;
- « Le fournisseur propose ce prix » ;
- « Nous comptons démarrer en septembre ».

Le système doit pouvoir distinguer les faits durables des informations temporelles et éviter de présenter une donnée ancienne comme actuelle.

Une politique d'expiration pourra être définie à l'implémentation selon la catégorie de mémoire.

---

## 16. Résumé de conversation

Une longue conversation peut produire un résumé contextuel compact afin de limiter le volume injecté au Cerveau.

Ce résumé :

- n'est pas une nouvelle vérité métier ;
- doit rester relié à la conversation source ;
- peut être régénéré ;
- doit distinguer décisions, hypothèses, questions ouvertes et actions proposées ;
- ne doit pas remplacer les messages originaux pour l'audit lorsque ceux-ci sont conservés.

Le résumé sert à **retrouver le sens**, pas à réécrire l'histoire.

---

## 17. Contexte de réponse du Cerveau

Pour répondre, le Cerveau ne doit pas recevoir aveuglément l'intégralité du Projet et de toutes les conversations.

Le contexte doit être assemblé à partir de couches pertinentes :

```text
QUESTION ACTUELLE
      │
      ├── conversation récente pertinente
      ├── résumé de conversation
      ├── mémoires Projet pertinentes
      ├── état métier actuel autorisé
      ├── événements récents utiles
      ├── preuves/références nécessaires
      └── recommandations/outils autorisés selon la demande
              ↓
         CONTEXTE CERVEAU
```

Le mécanisme technique exact de sélection est hors périmètre PVB-003.

---

## 18. Principe de contexte minimal utile

Le Cerveau doit recevoir **le minimum de contexte nécessaire pour répondre correctement**, pas le maximum disponible.

Bénéfices :

- meilleure pertinence ;
- réduction des contradictions ;
- respect de la confidentialité ;
- coûts techniques maîtrisés ;
- moindre risque de faire remonter une information ancienne ou sans rapport.

---

## 19. Confidentialité et permissions

La mémoire ne contourne jamais les permissions du Core.

Si une mémoire provient d'une information que l'utilisateur courant n'a plus le droit de voir, elle ne doit pas être révélée par le Cerveau comme voie détournée.

Le système doit prévoir une politique de visibilité des conversations et mémoires adaptée aux rôles Projet.

Question à formaliser ultérieurement : conversation personnelle au sein d'un Projet, conversation partagée avec l'équipe, ou les deux.

Tant que cette politique n'est pas explicitement définie, aucune hypothèse de partage universel ne doit être codée.

---

## 20. Conversations personnelles et collectives — principe préparatoire

Le modèle doit rester compatible avec deux usages futurs :

### Conversation personnelle

Échange entre un membre autorisé et le Cerveau dans le contexte du Projet.

### Conversation partagée

Espace de travail visible à plusieurs membres du Projet selon leurs droits.

Une mémoire issue d'une conversation personnelle ne doit pas devenir automatiquement visible à toute l'équipe.

Toute promotion d'une information privée vers une mémoire collective ou un objet métier partagé doit respecter consentement, permissions et confirmation appropriée.

---

## 21. Mémoire collective du Projet

La mémoire collective représente uniquement le contexte que le Projet peut légitimement partager entre ses acteurs autorisés.

Elle ne doit pas être une fusion brute des conversations privées des membres.

Elle peut être alimentée par :

- objets métier ;
- événements ;
- décisions collectives confirmées ;
- informations explicitement partagées ;
- mémoires promues selon un workflow autorisé.

---

## 22. Pièces jointes et documents

Un document envoyé dans une conversation n'est pas automatiquement une preuve ni une donnée canonique.

Le Cerveau peut :

- l'utiliser comme contexte si autorisé ;
- en extraire des informations candidates ;
- proposer de l'attacher à un objet métier ;
- proposer de l'enregistrer comme preuve lorsque le domaine Proof l'autorise.

L'utilisateur doit pouvoir comprendre la différence entre :

> « document partagé dans la conversation »

et

> « preuve enregistrée dans le Projet ».

---

## 23. Voix

La voix est un mode d'entrée de la conversation, pas une nouvelle couche métier.

Conceptuellement :

`voix → transcription → message → compréhension → mémoire/action candidate`

Le texte transcrit doit pouvoir être corrigé avant une action sensible lorsque la transcription est incertaine.

Une erreur de transcription ne doit jamais produire silencieusement une mutation N3/N4.

---

## 24. Actions issues d'une conversation

Une conversation peut produire plusieurs types de sorties :

- réponse informative ;
- question de clarification ;
- suggestion ;
- mémoire candidate ;
- brouillon métier ;
- demande de confirmation ;
- action métier confirmée ;
- référence vers un objet existant.

Ces sorties doivent être distinguables dans l'interface et dans la traçabilité.

---

## 25. Conversation et événements métier

Une action réussie déclenchée depuis la conversation doit revenir dans la conversation sous forme de résultat fondé sur le Core.

Exemple :

> ✓ Besoin créé — « 2 développeurs web à Bouaké »

Le Cerveau ne déclare ce succès qu'après retour positif du service métier.

L'événement métier correspondant appartient au journal du Projet ; le message de succès n'est qu'une représentation UX de cette réalité.

---

## 26. Conversation et Fil

Le Fil ne doit pas indexer ou publier les conversations privées.

Seuls les objets métier/événements rendus éligibles selon leurs propres règles peuvent alimenter le Fil.

Exemple :

`conversation privée → brouillon Need → confirmation → Need réel → événement autorisé → Fil éventuel`

Jamais :

`conversation privée → publication automatique dans le Fil`

---

## 27. Conversation et Finance

Les futures conversations relatives à l'argent nécessitent une prudence renforcée.

Une phrase comme :

> « Nous avons reçu 500 000 F. »

reste une déclaration conversationnelle tant qu'elle n'est pas reliée à une écriture, une source ou une preuve financière autorisée.

Le Cerveau ne doit jamais transformer une déclaration financière en solde Wallet, financement reçu ou écriture Ledger.

---

## 28. Conversation et outils spécialisés

Les outils spécialisés peuvent être invoqués depuis une conversation lorsque leur capacité est disponible.

Le Cerveau transmet uniquement les informations nécessaires et autorisées.

Le résultat d'un outil doit conserver son origine et ne devient un fait métier que selon le contrat du domaine concerné.

Le Cerveau ne doit pas faire passer une réponse d'un outil externe pour une preuve intrinsèque.

---

## 29. Recherche dans l'historique

L'utilisateur doit pouvoir retrouver une conversation ancienne sans connaître sa date exacte.

À terme, la recherche peut porter sur :

- titre ;
- contenu ;
- sujet ;
- objets métier référencés ;
- période.

Le Cerveau peut également répondre :

> « Nous en avions parlé dans la conversation “Local & aménagement”. »

si cette information est réellement retrouvée et visible.

---

## 30. Titres des conversations

Le système peut proposer automatiquement un titre court à partir du sujet dominant.

Exemples :

- Local & aménagement ;
- Équipe de lancement ;
- Matériel informatique ;
- Préparation financement.

Le titre automatique est une commodité UX, pas une classification métier irréversible. L'utilisateur peut le renommer.

---

## 31. Continuité « Depuis votre dernière visite »

Lors du retour dans un Projet, le Cerveau peut assembler une reprise à partir des événements survenus depuis la dernière consultation pertinente.

Exemple :

> Depuis votre dernière visite : Fatou a accepté l'invitation, une mission a été terminée et votre besoin de local reste ouvert.

Cette synthèse doit être calculée depuis des faits actuels/événements, pas depuis une hallucination de mémoire.

---

## 32. Mémoire de prochaines actions

Le Cerveau peut retenir une prochaine action annoncée :

> « Demain nous devons visiter deux locaux. »

Cette mémoire peut être temporelle et candidate/confirmée selon le contexte.

Elle ne devient pas automatiquement une Mission ni un rappel planifié.

Le Cerveau peut proposer :

> Voulez-vous en faire une mission ?

---

## 33. Mémoire et tâches non terminées

Les actions réelles ouvertes doivent être retrouvées depuis le Core plutôt que depuis une simple mémoire.

Si une Mission existe, le Cerveau consulte la Mission.

Si seule une intention a été évoquée sans création de Mission, elle peut rester mémoire candidate.

Ainsi, le système sait distinguer :

> « Nous avons parlé de le faire »

et

> « Nous avons réellement créé une action à faire ».

---

## 34. Mémoire et apprentissage du Projet

Avec le temps, la mémoire doit permettre au Cerveau de comprendre le Projet de mieux en mieux sans construire un profil opaque des personnes.

Elle apprend **le Projet**, pas une valeur psychologique ou sociale des individus.

Interdit :

- score caché de fiabilité personnelle dérivé des conversations ;
- profilage émotionnel utilisé pour classer les membres ;
- déduction sensible non nécessaire à l'action ;
- transformation du bavardage en réputation.

---

## 35. Suppression d'une conversation

La suppression/archivage d'une conversation ne doit pas casser silencieusement des objets métier créés depuis celle-ci.

Un `Need`, une `Mission` ou une `Proof` confirmé appartient au Core indépendamment de la conversation qui a aidé à le créer.

La politique exacte de suppression, conservation et audit sera définie avant implémentation.

---

## 36. Modèle logique indicatif

Sans figer les tables :

```text
PROJECT
  │
  ├── CONVERSATIONS
  │      │
  │      ├── MESSAGES
  │      │      └── REFERENCES / ATTACHMENTS
  │      │
  │      └── SUMMARIES
  │
  ├── PROJECT MEMORIES
  │      ├── state
  │      ├── category
  │      ├── value/context
  │      ├── provenance
  │      ├── temporal validity
  │      └── visibility
  │
  ├── CORE OBJECTS
  │      ├── Needs
  │      ├── Missions
  │      ├── Team
  │      └── Proofs
  │
  └── PROJECT EVENTS
```

Les relations précises, contraintes SQL et stratégie de stockage appartiennent à la conception technique ultérieure.

---

## 37. Frontières non négociables PVB-003

1. Une conversation appartient à un seul Projet.
2. Une conversation n'est pas une source de vérité métier.
3. Une mémoire n'est pas automatiquement une preuve.
4. Une mémoire doit avoir une provenance exploitable.
5. Une mémoire candidate reste explicitement incertaine.
6. Une vérité métier plus forte prime sur une mémoire contradictoire.
7. Le Cerveau ne choisit pas arbitrairement entre deux faits contradictoires importants.
8. Les conversations privées ne sont jamais publiées automatiquement dans le Fil.
9. Une pièce jointe conversationnelle n'est pas automatiquement une Proof.
10. Une déclaration financière ne modifie jamais Wallet/Ledger.
11. Une conversation personnelle ne nourrit pas silencieusement une mémoire collective visible à tous.
12. Le contexte injecté au Cerveau respecte les permissions de l'utilisateur courant.
13. Le système privilégie le contexte minimal utile.
14. Une action ouverte existant dans le Core prime sur le souvenir conversationnel de cette action.
15. Supprimer une conversation ne supprime pas automatiquement les objets métier confirmés issus de celle-ci.
16. La mémoire apprend le Projet ; elle ne construit pas un score humain caché.

---

## 38. Critères d'acceptation PVB-003

PVB-003 sera correctement traduit lorsque :

- un Projet peut posséder plusieurs conversations clairement séparées ;
- ouvrir une nouvelle conversation conserve le contexte Projet pertinent ;
- changer de Projet isole complètement le contexte ;
- le Cerveau peut retrouver une décision ou information utile ancienne avec provenance ;
- une information conversationnelle peut rester candidate sans devenir vérité ;
- une contradiction importante provoque une clarification ;
- une mémoire peut être corrigée, remplacée ou rejetée ;
- un objet métier confirmé devient la source principale lorsqu'il existe ;
- le Cerveau peut résumer une longue conversation sans effacer son historique ;
- le retour dans un Projet peut produire « Depuis votre dernière visite » à partir de faits réels ;
- les conversations privées restent privées selon les permissions ;
- le système reste compatible avec conversations personnelles et partagées ;
- documents, voix, Finance et outils spécialisés respectent les mêmes frontières de vérité.

---

## 39. Hors périmètre PVB-003

Ce contrat ne fixe pas encore :

- schéma SQL exact ;
- moteur/vector store/RAG ;
- modèle d'embeddings ;
- fournisseur IA ;
- fenêtre exacte de contexte ;
- quotas de messages ;
- durée légale de conservation ;
- chiffrement applicatif éventuel ;
- politique définitive conversation privée/partagée ;
- design visuel détaillé des mémoires ;
- stratégie de pièces jointes ;
- transcription vocale.

Ces choix doivent respecter le présent contrat.

---

## 40. Suite

Après PVB-003 :

1. **PVB-004 — Contrat Actions/Tools du Cerveau vers le Core** ;
2. définir le catalogue initial des actions réellement exécutables ;
3. cartographier ces actions vers les services/permissions existants ;
4. seulement ensuite figer le modèle technique conversation/mémoire ;
5. implémenter le shell Projet V2 puis le Cerveau par tranches verticales testables.
