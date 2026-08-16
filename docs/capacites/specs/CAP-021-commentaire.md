# CAP-021 — Commentaire

## Contrat canonique

**Finalité.** Permettre des contributions courtes autour d’un contexte précis.

**Capacité.** Un commentaire peut servir à poser une question, apporter une précision, conseiller, signaler une ressource ou coordonner une action.

**Point clé.** Le commentaire est lié à une activité, un besoin, un projet, une formation ou une opportunité.

**Garde-fou.** Le commentaire gagne de la valeur lorsqu’il reste contextuel et actionnable.

Le marqueur historique `[PLUS TARD]` du référentiel était un repère de séquencement produit. La reconstruction ayant atteint CAP-021, cette capacité est maintenant implémentée dans son périmètre propre.

## Traduction fonctionnelle

CAP-021 n’introduit pas de publication sociale autonome. Un commentaire n’existe que sous un contexte métier réel et conserve ce contexte pendant toute sa vie.

Les intentions explicitement proposées sont :

- Question ;
- Précision ;
- Conseil ;
- Ressource ;
- Coordination.

Les commentaires sont courts (1 200 caractères maximum), plats et chronologiques. Il n’existe pas de réponse imbriquée, réaction, like, follower, score d’engagement ou classement par popularité.

## Contextes disponibles dans la stack actuelle

- **Besoin** : même règle de visibilité que le besoin ; un besoin archivé est en lecture seule pour les identités qui peuvent encore le voir.
- **Projet** : même règle de visibilité que le projet ; un projet archivé est en lecture seule pour les identités qui peuvent encore le voir.
- **Activité ZUMRA** : rattachée à la ZUMRA réelle visible dans CAP-019 ; une ZUMRA suspendue ne rend pas le fil accessible.

CAP-019 utilise déjà les besoins, projets et événements ZUMRA comme sources d’activité. Le lien « Contribuer » ouvre le même fil contextuel que l’objet métier : aucun commentaire n’est dupliqué pour fabriquer un second objet social.

Il n’existe pas encore d’objet métier canonique Formation ou Opportunité dans la stack Laravel actuelle. CAP-021 n’en invente donc pas. La table polymorphe bornée permet de les ajouter lorsque leurs capacités canoniques seront réellement implémentées.

## Données

Une seule table CAP-021 : `dg_context_comments`.

Chaque ligne contient :

- une référence publique ;
- le type et la référence du contexte ;
- la référence d’identité canonique de l’auteur, uniquement en donnée interne ;
- l’intention de la contribution ;
- le texte ;
- la date de publication.

Les commentaires sont append-only dans CAP-021 : aucune édition ou suppression silencieuse n’est fournie par ce lot.

## Confidentialité et droits

Les droits sont recalculés à chaque lecture et à chaque écriture à partir du contexte métier. Une ancienne visibilité ne donne aucun accès permanent.

L’interface ne montre jamais la référence d’identité interne d’un auteur. Un nom public n’est affiché que si le profil est actuellement découvrable avec consentement ; sinon l’auteur est présenté comme « Membre DG Afrique ». L’auteur courant voit « Vous ».

## Frontières

CAP-021 n’implémente pas :

- la messagerie privée ou de groupe (CAP-020) ;
- le partage d’un objet à une personne ou une ZUMRA (CAP-022) ;
- des posts libres ;
- des réponses imbriquées ;
- des likes, réactions, followers, badges ou scores ;
- des notifications sociales ;
- une Formation ou une Opportunité fictive ;
- une modification de statut, de propriété, de membres ou de visibilité du contexte commenté.

## Expérience

Les fiches Besoin, Projet et ZUMRA exposent un accès clair aux contributions. Le fil d’activité CAP-019 expose aussi « Contribuer » sans utiliser le nombre de commentaires dans son tri. L’écran commentaire rappelle explicitement qu’il s’agit d’une contribution contextuelle au service de l’action, et non d’un produit d’attention.
