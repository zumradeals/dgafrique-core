# CAP-022 — Partage

## Contrat canonique

**Finalité.** Faire circuler une capacité, un besoin ou une opportunité au bon endroit.

**Capacité.** Le partage peut transmettre un projet, une opportunité, une formation, un besoin ou une recommandation à une personne ou une ZUMRA.

**Points clés.**

- partager sans dupliquer inutilement ;
- conserver le contexte de l’objet partagé.

**Garde-fou.** Le partage sert la circulation des possibilités d’action.

Le marqueur historique `[PLUS TARD]` du référentiel était un repère de séquencement produit. La reconstruction ayant atteint CAP-022, cette capacité est maintenant implémentée dans son périmètre propre.

La Doctrine canonique invariante précise en outre que le réseau social ZUMRA peut proposer le « partage avec contexte » et que la visibilité d’un contenu dépend toujours de son contexte métier. CAP-022 ne crée donc aucun droit de lecture nouveau.

## Traduction fonctionnelle

CAP-022 crée une **trace de circulation** qui pointe vers un objet métier réel. Il ne copie ni le besoin ni le projet dans un second objet social.

Dans la stack Laravel actuelle, les objets partageables réellement disponibles sont :

- **Besoin** (CAP-013) ;
- **Projet** (CAP-014).

Formation, Opportunité et toute autre catégorie du référentiel seront raccordées lorsqu’un objet métier canonique correspondant existera. CAP-022 ne fabrique pas de faux objets pour anticiper ces capacités.

Un partage comporte :

- la référence de l’objet source ;
- l’identité canonique interne de la personne qui partage ;
- une destination : personne ou ZUMRA ;
- un contexte court rédigé par l’émetteur ;
- la date du partage.

Le contexte est obligatoire et borné à 800 caractères afin que le partage explique pourquoi l’objet est utile au destinataire plutôt que de devenir un simple mécanisme de diffusion.

## Destinations

### Personne

Une personne peut être choisie uniquement au moyen de sa présence publique actuellement découvrable dans DG Afrique. L’interface manipule sa référence publique de découverte ; sa référence d’identité Core reste interne.

Le partage est refusé si le destinataire ne peut pas voir l’objet source au moment de l’envoi. Le partage vers soi-même est refusé.

### ZUMRA

L’émetteur peut partager vers une ZUMRA dont il est membre actif. Une ZUMRA suspendue ne reçoit ni n’expose de nouveaux partages.

Le partage ne transforme aucun membre en participant au besoin ou au projet. Chaque membre de la ZUMRA ne voit la trace que s’il dispose, au moment de la lecture, du droit métier de consulter l’objet source.

## Visibilité et confidentialité

**Invariant central : un partage ne confère jamais l’accès à l’objet partagé.**

Les droits de lecture de l’objet source sont recalculés à chaque affichage via les services métier de Besoin ou Projet. Si la visibilité est retirée, si l’objet devient inaccessible ou si la relation du lecteur change, la trace cesse d’être rendue à ce lecteur sans supprimer l’historique interne.

Un partage vers une ZUMRA n’est consultable que par un membre actuellement actif de cette ZUMRA. Une ZUMRA suspendue masque son espace de partages.

La référence Core de l’émetteur n’est jamais affichée. Son nom public est montré uniquement lorsque son profil est actuellement découvrable ; sinon l’interface affiche « Membre DG Afrique ». L’émetteur courant est présenté comme « Vous ».

## Non-duplication et traçabilité

Une combinaison identique source + émetteur + destination n’est persistée qu’une seule fois. Une seconde tentative ne duplique pas la trace et ne modifie pas silencieusement le contexte historique initial.

Les partages sont append-only dans CAP-022 : aucune édition ni suppression silencieuse n’est fournie par ce lot.

Le partage ne modifie jamais :

- le contenu de la source ;
- son auteur ou son porteur ;
- son statut ;
- sa visibilité ;
- ses membres ou participants ;
- le matching ;
- un classement ou un score.

## Expérience

Les fiches Besoin et Projet proposent « Partager utilement ». L’écran de partage montre d’abord l’objet source, puis deux destinations explicites : une personne découvrable ou une ZUMRA active de l’émetteur.

Le membre dispose de « Partages reçus ». Une ZUMRA active dispose d’un espace « Partages utiles » pour ses membres.

Aucun compteur de partage, classement viral, like, score d’engagement, repost autonome ou notification artificielle n’est introduit.

## Frontières

CAP-022 n’implémente pas :

- la messagerie (CAP-020) ;
- les commentaires (CAP-021) ;
- des publications libres ou des reposts ;
- des notifications sociales ;
- un moteur de recommandation supplémentaire ;
- une Formation ou une Opportunité fictive ;
- un élargissement de visibilité ;
- une permission, nomination, adhésion ou participation implicite ;
- un score de popularité ou de valeur humaine.
