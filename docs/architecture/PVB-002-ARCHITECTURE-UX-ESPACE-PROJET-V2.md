# PVB-002 — Architecture UX de l'Espace Projet V2

**Statut :** CONTRAT UX — V1 de travail  
**Branche :** `feat/project-brain-v2`  
**Références :** `ARCHITECTURE-PRODUIT-V2.md`, `PVB-001-CONTRAT-FONCTIONNEL-CERVEAU-PROJET-V2.md`  
**Objet :** définir l'expérience de création, consultation et pilotage d'un Projet V2 avant maquette visuelle et implémentation.

---

## 1. Thèse UX

L'Espace Projet V2 n'est plus un formulaire enrichi ni un tableau de bord administratif.

Il est un **espace de travail vivant et conversationnel** où une personne peut comprendre son Projet, parler avec son Cerveau, agir sur les objets métier et retrouver son historique sans connaître la structure technique de DG Afrique.

Le modèle mental recherché est proche d'un **bureau de Projet intelligent** :

`PARLER → COMPRENDRE → VOIR → DÉCIDER → AGIR → CONSTATER`

Le Cerveau est l'entrée principale ; les objets métier restent la vérité.

---

## 2. Objectifs UX prioritaires

1. Réduire radicalement la charge cognitive du formulaire Projet actuel.
2. Permettre de commencer un Projet avec très peu d'informations.
3. Donner immédiatement une sensation de continuité : « je reviens dans mon Projet là où je l'avais laissé ».
4. Rendre visibles les faits importants sans exposer toute la complexité métier.
5. Permettre une action naturelle par conversation, voix ou raccourci.
6. Faire comprendre ce qui est réel, proposé, incomplet ou en attente de confirmation.
7. Conserver une expérience complète sur desktop et une expérience concentrée sur mobile.
8. Ne jamais obliger un utilisateur à comprendre le vocabulaire interne du Core.

---

## 3. Principe de simplification progressive

L'interface suit trois niveaux :

### Niveau 1 — Essentiel

Ce que tout utilisateur doit comprendre immédiatement :

- nom / intention du Projet ;
- état actuel ;
- ce qui mérite son attention ;
- conversation avec le Cerveau ;
- prochaine action possible.

### Niveau 2 — Action

Visible lorsqu'il veut travailler :

- équipe ;
- besoins ;
- missions ;
- preuves ;
- activité ;
- recommandations.

### Niveau 3 — Détail

Visible à la demande :

- gouvernance ;
- historique complet ;
- paramètres ;
- données structurées ;
- outils spécialisés ;
- futurs éléments Finance/Appels.

**Invariant : la puissance du système augmente sans augmenter proportionnellement la complexité visible.**

---

## 4. Création d'un Projet V2

### 4.1 Point d'entrée

Le bouton principal reste conceptuellement : **Créer un projet**.

Il n'ouvre plus un grand formulaire.

Il ouvre un espace calme avec une question principale :

> **Qu'est-ce que vous voulez réaliser ?**

Entrées prévues : texte, voix lorsque disponible, exemples courts et éventuellement quelques actions guidées.

### 4.2 Création précoce

Le système doit pouvoir créer un brouillon Projet après une intention minimale exploitable.

Exemple :

> « Je veux créer un atelier de formation informatique à Bouaké. »

Le Cerveau peut identifier des éléments candidats : objectif, domaine, lieu. Il demande ensuite une seule précision utile à la fois.

L'utilisateur ne remplit pas une fiche ; **le Projet se construit avec lui**.

### 4.3 Informations différables

Une donnée ne doit pas être demandée à la création si elle peut être obtenue plus tard sans risque : budget, équipe complète, calendrier détaillé, pièces, gouvernance avancée, outils spécialisés, financement.

Les données juridiquement ou opérationnellement nécessaires à une action ultérieure sont demandées au moment où elles deviennent nécessaires.

---

## 5. Architecture desktop — trois zones

Le desktop utilise l'espace disponible et abandonne la page étroite centrée.

```text
┌──────────────────┬──────────────────────────────────────┬──────────────────────┐
│                  │                                      │                      │
│  PROJETS         │          CERVEAU / TRAVAIL           │   PROJET VIVANT      │
│  & HISTORIQUE    │                                      │                      │
│                  │  conversation                        │  Aujourd'hui         │
│  + Nouveau       │  explications                        │  État réel           │
│                  │  brouillons                          │  Besoins             │
│  Projet A        │  confirmations                       │  Missions            │
│   conversation 1 │  actions                             │  Équipe               │
│   conversation 2 │                                      │  Preuves             │
│                  │                                      │                      │
│  Projet B        │                                      │  Opportunités        │
│                  │                                      │  Outils utiles       │
│                  │                                      │                      │
├──────────────────┴──────────────────────────────────────┴──────────────────────┤
│                         navigation DG Afrique                                  │
└────────────────────────────────────────────────────────────────────────────────┘
```

Les largeurs sont adaptatives ; la zone centrale reste dominante.

### Zone A — Projets & historique

Fonctions :

- créer un Projet ;
- retrouver rapidement ses Projets ;
- changer de Projet ;
- retrouver les conversations d'un Projet ;
- identifier le Projet actif ;
- rechercher lorsque le volume devient important.

La navigation doit distinguer visuellement **Projet** et **conversation** : une conversation appartient toujours à un Projet.

### Zone B — Cerveau / travail

C'est la surface principale.

Elle contient :

- historique conversationnel ;
- messages du Cerveau ;
- saisie texte/voix ;
- cartes de brouillon ;
- confirmations ;
- résultats d'actions ;
- explications de recommandations ;
- raccourcis contextuels.

La conversation ne doit pas être interrompue par un grand dashboard permanent.

### Zone C — Projet vivant

Panneau contextuel, repliable, qui répond rapidement à :

> **Où en sommes-nous ?**

Il expose des faits et accès rapides, pas une avalanche de métriques.

---

## 6. « Aujourd'hui » — le premier résumé du Projet

Le panneau Projet commence par une zone **Aujourd'hui**.

Elle contient au maximum quelques signaux utiles :

- changement récent ;
- blocage observable ;
- décision en attente ;
- prochaine action suggérée ;
- opportunité importante.

Exemple :

> **Aujourd'hui**
> - 2 besoins sont encore ouverts.
> - La mission « Trouver le local » attend une décision.
> - Une personne compatible est disponible à Bouaké.

Pas de score arbitraire « Projet 67 % ».

---

## 7. Les objets métier deviennent des vues de travail

Le Projet vivant propose des entrées simples :

**Équipe · Besoins · Missions · Preuves · Activité**

Ces entrées ouvrent une vue contextuelle sans faire perdre la conversation.

### Équipe

Affiche les personnes et rôles utiles, les invitations en attente et les capacités manquantes liées à l'équipe.

### Besoins

Affiche les besoins ouverts/en action/résolus avec une formulation humaine et les actions possibles.

### Missions

Affiche le travail concret, les dépendances et les éléments bloqués.

### Preuves

Affiche ce qui documente réellement l'action ; l'interface doit expliquer qu'une preuve est différente d'une simple déclaration.

### Activité

Chronologie lisible des événements métier importants du Projet.

---

## 8. Le composeur conversationnel

Le champ principal ne doit pas ressembler à un formulaire.

Placeholder possible :

> **Parlez de votre projet…**

Actions discrètes :

- envoyer ;
- parler ;
- joindre/ajouter une pièce lorsque pertinent ;
- ouvrir quelques actions rapides.

Exemples d'actions rapides contextuelles :

- Ajouter un besoin
- Créer une mission
- Ajouter une preuve
- Inviter quelqu'un
- Que faire maintenant ?

Les raccourcis ne remplacent pas le langage naturel.

---

## 9. Cartes de brouillon et confirmation

Lorsqu'une phrase produit une action N2/N3, le Cerveau affiche une carte distincte de la conversation.

Exemple :

```text
Besoin proposé
────────────────────────
Nous cherchons 2 développeurs web
Lieu : Bouaké
Projet : Atelier numérique

[Modifier]   [Créer ce besoin]
```

Le bouton d'action doit dire ce qui va réellement arriver : **Créer ce besoin**, pas simplement **OK**.

Après confirmation, la carte change d'état :

> ✓ Besoin créé

avec accès à l'objet réel.

---

## 10. Différencier les états de vérité

L'UX doit rendre immédiatement perceptible :

- **Message** : conversation ;
- **Suggestion** : proposition du Cerveau ;
- **Brouillon** : action préparée ;
- **À confirmer** : mutation prête ;
- **Enregistré** : objet métier créé/modifié ;
- **Prouvé** : information soutenue par une preuve ;
- **Incertain** : information non confirmée ou contradictoire.

Cette distinction doit utiliser langage, iconographie et structure ; elle ne doit pas dépendre uniquement de la couleur.

---

## 11. Opportunités et recommandations

Le Projet vivant peut afficher une petite zone **Opportunités** lorsque des éléments réels existent :

- personne/capacité pertinente ;
- transmission utile ;
- ZUMRA pertinente ;
- futur appel à projets ;
- futur outil spécialisé répondant à un besoin.

Chaque recommandation importante doit proposer **Pourquoi ?**

La sponsorisation commerciale, lorsqu'elle existera, reste explicitement signalée et distincte d'une recommandation organique.

---

## 12. Outils spécialisés

Les outils ne constituent pas une grande grille permanente.

Ils apparaissent **au moment utile**.

Exemple :

> Votre mission nécessite l'achat de 4 brouettes.
> G-POS peut rechercher les catalogues autorisés à proximité.
> [Voir les offres]

Un futur outil Transport peut apparaître lorsqu'un besoin logistique est identifié.

Si aucun outil n'existe, le workflow Core continue normalement.

---

## 13. Mobile — une seule tâche à la fois

Sur mobile, les trois colonnes desktop deviennent des surfaces successives.

Écran principal : **conversation Cerveau**.

En-tête compact : Projet actif + état court.

Navigation mobile recommandée :

- **Cerveau**
- **Projet**
- **Activité**

La liste des Projets est accessible par le nom du Projet / menu latéral ou feuille dédiée.

La vue **Projet** regroupe Équipe, Besoins, Missions, Preuves et Opportunités sous forme de cartes simples.

**Invariant mobile : ne jamais reproduire les trois colonnes desktop en miniature.**

---

## 14. Public peu à l'aise avec l'écrit

L'expérience doit être compatible avec :

- phrases courtes ;
- vocabulaire courant ;
- gros contrôles tactiles ;
- une décision principale par écran/carte ;
- voix lorsque la capacité technique est disponible ;
- lecture audio future possible ;
- confirmations explicites ;
- pictogrammes accompagnés de texte ;
- aucune dépendance à une terminologie administrative complexe.

Exemple à éviter :

> « Définissez les capacités requises et la gouvernance du projet. »

Préférer :

> « De qui avez-vous besoin pour avancer ? »

---

## 15. Retour dans un Projet

Lorsqu'un utilisateur revient, le Cerveau ne doit pas afficher un écran vide.

Il peut reprendre avec une synthèse courte :

> **Depuis votre dernière visite**
> - Fatou a accepté l'invitation.
> - Une mission a été terminée.
> - Votre besoin de développeur est toujours ouvert.
>
> Voulez-vous voir ce qui mérite votre attention ?

Cette synthèse est dérivée d'événements réels.

---

## 16. Navigation et profondeur

L'utilisateur doit pouvoir passer de :

`Conversation → Objet métier → détail → retour à la conversation`

sans perdre son contexte.

Sur desktop, le détail peut s'ouvrir dans le panneau droit ou une vue de travail centrale temporaire.

Sur mobile, il peut devenir une page dédiée avec retour explicite au Projet.

Le navigateur doit conserver des URLs adressables pour les objets importants ; l'interface conversationnelle ne doit pas transformer tout le produit en état opaque côté client.

---

## 17. États vides

Un Projet neuf ne doit pas afficher cinq grands blocs « Aucun élément ».

Préférer :

> **Votre projet commence ici.**
> Parlez-moi de ce que vous voulez réaliser. Nous le structurerons ensemble.

Puis révéler les sections lorsqu'elles deviennent pertinentes.

Pour une section explicitement ouverte :

> **Aucune mission pour le moment.**
> Une mission est une action concrète à réaliser.
> [Créer une mission]

---

## 18. Notifications et attention

Le Cerveau ne doit pas multiplier les alertes.

Trois niveaux UX suffisent conceptuellement :

- **Information** — changement utile ;
- **Attention** — action ou décision attendue ;
- **Sensible** — action financière, gouvernance, suppression ou autre workflow renforcé.

Pas de faux sentiment d'urgence, streak, compteur anxiogène ou badge destiné uniquement à ramener l'utilisateur.

---

## 19. Accessibilité et résilience

Le design doit prévoir :

- navigation clavier desktop ;
- focus visible ;
- contraste suffisant ;
- tailles de texte lisibles ;
- états non communiqués uniquement par couleur ;
- fonctionnement dégradé lorsque l'IA est indisponible ;
- possibilité d'accéder manuellement aux objets métier ;
- messages d'erreur compréhensibles et récupérables.

**Invariant : une panne du Cerveau ne doit pas rendre le Projet inaccessible.**

---

## 20. Mode sans IA / dégradé

Même sans Cerveau disponible, l'utilisateur doit pouvoir :

- ouvrir le Projet ;
- consulter l'état réel ;
- voir équipe/besoins/missions/preuves ;
- créer les objets via des formulaires simplifiés ;
- consulter l'activité ;
- poursuivre les workflows autorisés.

Le Cerveau améliore l'expérience ; il n'est pas la seule porte d'accès au Core.

---

## 21. Ce que le desktop ne doit plus être

À proscrire :

- formulaire principal occupant la page ;
- contenu étroit centré avec grands espaces inutiles ;
- grille de cartes égales sans hiérarchie ;
- dashboard rempli de métriques ;
- menu de 15 sous-modules avant que l'utilisateur sache quoi faire ;
- terminologie Core exposée sans traduction humaine ;
- Cerveau relégué dans un petit widget de chat flottant.

Le Cerveau est une **surface de travail de premier rang**.

---

## 22. Hiérarchie visuelle attendue

Sans figer le style graphique, la future maquette doit respecter :

1. conversation / action courante = dominance visuelle ;
2. Projet actif = identité claire mais compacte ;
3. Aujourd'hui / attention = secondaire fort ;
4. objets métier = accessibles, non envahissants ;
5. outils spécialisés/opportunités = contextuels ;
6. paramètres/détails avancés = tertiaires.

Le design doit respirer sur grand écran et éviter l'effet « application mobile agrandie ».

---

## 23. Scénario de référence desktop

Utilisateur ouvre **Atelier informatique Bouaké**.

Colonne gauche : Projet actif et anciennes conversations.

Centre :

> **Cerveau Projet**
> Bonjour. Depuis votre dernière visite, Moussa a rejoint l'équipe et votre besoin de local est toujours ouvert.
>
> Que voulez-vous faire aujourd'hui ?

Utilisateur :

> « Il faut maintenant trouver deux personnes qui savent développer des sites web. »

Cerveau :

> Je peux créer un besoin pour 2 personnes ayant une capacité en développement web à Bouaké.

Carte :

> **Besoin proposé**
> 2 personnes — Développement web — Bouaké
> [Modifier] [Créer ce besoin]

Après confirmation :

> ✓ Besoin créé.
> Je peux maintenant rechercher des personnes compatibles si vous le souhaitez.

Le panneau droit met immédiatement **Besoins : 2 ouverts** à jour à partir du Core.

---

## 24. Scénario de référence mobile

L'utilisateur ouvre le même Projet.

Il voit :

> **Atelier informatique Bouaké**
> 2 éléments méritent votre attention
>
> **Cerveau**
> Que voulez-vous faire aujourd'hui ?

Il parle :

> « La formation d'hier est terminée. »

Le Cerveau répond :

> D'accord. Je peux enregistrer l'activité. Avez-vous une photo, un document ou une autre preuve à ajouter ?

Une seule décision principale est affichée à la fois.

---

## 25. Critères d'acceptation PVB-002

PVB-002 est correctement traduit lorsque :

- la création Projet ne commence plus par un grand formulaire ;
- desktop utilise réellement la largeur disponible ;
- la conversation est la surface dominante ;
- les Projets et leurs conversations restent facilement retrouvables ;
- le Projet réel est visible sans transformer l'écran en dashboard ;
- Équipe/Besoins/Missions/Preuves/Activité sont accessibles sans quitter mentalement le Projet ;
- les brouillons et confirmations sont visuellement distincts des messages ;
- mobile privilégie une tâche à la fois ;
- la voix peut être ajoutée sans changer l'architecture ;
- l'absence d'IA n'empêche pas d'utiliser le Projet ;
- opportunités et outils spécialisés restent contextuels ;
- aucune sponsorisation future ne peut se faire passer pour une recommandation organique ;
- l'interface reste compréhensible pour un public peu technophile.

---

## 26. Maquette à produire après validation

La première maquette doit représenter **desktop large** avec :

- colonne gauche Projets + historique ;
- centre Cerveau avec une conversation réaliste ;
- panneau droit Projet vivant / Aujourd'hui ;
- un brouillon de Besoin en attente de confirmation ;
- une recommandation explicable ;
- aucun futur module Finance artificiellement affiché si le contexte ne l'exige pas.

Une seconde maquette mobile doit ensuite montrer la même situation en mode une tâche à la fois.

---

## 27. Suite

Après validation visuelle de cette architecture :

1. maquette desktop ;
2. ajustements UX ;
3. maquette mobile ;
4. **PVB-003 — Contrat Conversation & Mémoire Projet** ;
5. **PVB-004 — Contrat Actions/Tools du Cerveau vers le Core** ;
6. plan d'implémentation incrémental et migration de l'interface Projet existante.
