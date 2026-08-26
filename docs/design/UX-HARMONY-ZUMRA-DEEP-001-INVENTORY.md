# UX-HARMONY-ZUMRA-DEEP-001 — inventaire des surfaces GET

Baseline auditée : `0981ef99a05836e7f5f2795a0cf342466156e015` (`main`, après #148).

L’inventaire combine les routes dont le chemin est ZUMRA et les surfaces génériques réellement
atteignables depuis l’espace d’un groupe. Une route JSON reste volontairement une API : LOT C ne
fabrique pas une page HTML ni une autorité pour la rendre décorative.

| Route GET | Nom | Vue / réponse | Classification | Décision LOT C |
|---|---|---|---|---|
| `/zumra` | `zumra.index` | `zumra.index` | `DEJA_HARMONISEE` | Référence laissée intacte. |
| `/zumra/groupes` | `zumra.groups.index` | redirection vers `zumra.index` | `REDONDANTE` | Conservée comme compatibilité de navigation. |
| `/zumra/groupes/proposer` | `zumra.groups.create` | `zumra.groups.create` | `DEJA_HARMONISEE` | Parcours de naissance déjà harmonisé, intact. |
| `/zumra/groupes/{group}` | `zumra.groups.show` | `zumra.groups.show` | `ACTIVE_ET_UTILE` | Accueil de référence intact ; gouvernance profonde mise à l’échelle. |
| `/zumra/adhesion` | `zumra.membership.show` | `zumra.membership` | `ACTIVE_ET_UTILE` | Présence large et mobile harmonisées, métier inchangé. |
| `/zumra/adhesion/paiement/retour` | `zumra.payment.return` | `zumra.payment-status` | `ACTIVE_ET_UTILE` | État financier réel conservé, surface harmonisée. |
| `/zumra/adhesion/recus/{receipt}` | `zumra.payment.receipt` | `zumra.receipt` | `ACTIVE_ET_UTILE` | Reçu réel et imprimable, surface harmonisée. |
| `/zumra/carte` | `zumra.card.show` | `zumra.card` | `ACTIVE_ET_UTILE` | Attestation harmonisée, aucune fonction de paiement. |
| `/verifier/carte-zumra/{card}` | `zumra.card.verify` | `zumra.card-verification` | `ACTIVE_ET_UTILE` | Vérification publique signée harmonisée. |
| `/zumra/groupes/{group}/missions/creer` | `zumra.groups.missions.create` | `missions.create` | `DEJA_HARMONISEE` | LOT A/#147, laissée intacte. |
| `/zumra/groupes/{group}/evenements` | `community-events.zumra.index` | JSON | `JSON_ONLY` | Aucun écran artificiel créé. |
| `/zumra/groupes/{group}/evenements/creer` | `community-events.zumra.create` | `community-events.create` | `ACTIVE_ET_UTILE` | Formulaire large/tactile, autorité existante inchangée. |
| `/evenements/{event}` depuis une ZUMRA | `community-events.show` | `community-events.show` | `ACTIVE_ET_UTILE` | Fiche partagée harmonisée ; CTA issus de `CommunityEventService`. |
| `/evenements/{event}/participants` | `community-events.participants` | JSON | `JSON_ONLY` | Liste réservée à l’autorité organisatrice, inchangée. |
| `/commentaires/activite/zumra/{group}` | `comments.zumra-activity` | `comments.context` | `ACTIVE_ET_UTILE` | Fil contextuel harmonisé, droits du service inchangés. |
| `/partages/zumra/{group}` | `shares.group` | `shares.index` | `ACTIVE_ET_UTILE` | Ressources/partages harmonisés, visibilité source inchangée. |
| `/zumra/groupes/{group}/mesure` | `impact-metrics.zumra` | JSON | `JSON_ONLY` | Métriques réelles, aucun compteur UX inventé. |
| `/zumra/groupes/{group}/moderation` | `zumra.groups.moderation.index` | JSON | `JSON_ONLY` | Autorité responsable existante, aucune page décorative créée. |
| `/zumra/groupes/{group}/zahab` | `zahab.wallet.zumra-group` | JSON | `JSON_ONLY` | Lecture réelle seulement ; aucune écriture Wallet/Ledger. |
| `/projets/cerveau/preparation/{intent}/zumra/nouvelle` | `projects.brain.start.zumra.create` | parcours Projet | `DEJA_HARMONISEE` | Surface Projet, hors redesign LOT C. |
| `/projets/proposer/{draft}/zumra/nouvelle` | `projects.draft.zumra.create` | parcours Projet | `DEJA_HARMONISEE` | Surface Projet, hors redesign LOT C. |
| `/contributions` et `/contributions/tableau` | `contributions.*` | contributions | `ACTIVE_ET_UTILE` transversal | Pas une sous-page de groupe ; volontairement inchangée. |
| `/administration/communaute/zumra` | `administration.community.zumra` | administration | `ADMIN` | Explicitement exclue. |
| `/administration/programme-zumra` | `administration.zumra.edit` | administration | `ADMIN` | Explicitement exclue. |
| `/administration/groupes-zumra` | `administration.zumra.groups.edit` | administration | `ADMIN` | Cycle de vie admin explicitement exclu. |

## Routes attendues mais absentes

Il n’existe aucune route GET autonome pour membres, candidatures, rôles, responsables, charte,
activités, projets ou besoins d’un groupe. Ces capacités sont des sections réelles de
`zumra.groups.show`, et non des routes orphelines. Il n’existe pas non plus de page membre de
suspension/réhabilitation : les transitions de cycle de vie sont administratives et restent dans
leur périmètre protégé.

## Autorités réutilisées

- `ZumraGroupService` : naissance, adhésion au groupe, invitations, rôles, charte et cycle réel ;
- `NeedService`, `ProjectService`, `MissionService` : visibilité des objets rattachés ;
- `CommunityEventService` : événements, participation et transitions organisateur ;
- `ContextCommentService`, `ContextShareService` : contributions et partages contextuels ;
- `CollectiveCapabilityProfile` et son consentement réel ;
- `MembershipPaymentService` et `ZahabWalletService` : lecture/paiement légitime uniquement.

Aucune route n’est supprimée par LOT C. Aucune surface `PROTOTYPE_LEGACY`, `ORPHELINE` ou
`A_SUPPRIMER` n’est démontrée dans la baseline : la seule redondance est la redirection historique
`/zumra/groupes` vers le Monde ZUMRA moderne.
