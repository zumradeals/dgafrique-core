# FICHE D'IMPLÉMENTATION TRANSVERSALE — NOTIFICATIONS

**Statut :** CONCEPTION
**Version :** 0.1
**Racine référentielle :** CAP-054 — NOTIFICATIONS (domaine dédié « Notifications », exécution DG)
**Expression produit :** NOTIFICATIONS
**Nouveau CAP :** non
**Nature :** module transversal natif de DG Afrique, cité comme dépendance non implémentée par MISSIONS (§20) et TRANSMISSION (§13/§21) mais jamais construit
**Base de conception :** référentiel des 84 capacités (`docs/capacites/CAPABILITY-INDEX.md` L.60), doctrine canonique ZUMRA (`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` L.20, L.675), invariants de design (`docs/design/DESIGN-INVARIANTS.md` §7, L.83-93), et l'inventaire en code des modules déjà livrés (Missions, Transmission, Carnet de preuves, Besoin/Projet/ZUMRA).

Ce document est en version **CONCEPTION**. La section 15 liste les points laissés ouverts. Aucune implémentation ne démarre avant validation humaine de ces points.

---

## 1. Intention

Aucune primitive Notification n'existe en code aujourd'hui — confirmé par grep exhaustif de `app/` (aucun trait `Notifiable`, aucune table `notifications`, aucun usage d'`Illuminate\Notifications`). Trois fiches déjà mergées le citent explicitement comme dépendance manquante :

> TRANSMISSION.md §13, L.333 : *« CAP-054 : aucune primitive Notification n'existe. Pas de second système parallèle ; ce qui exige attention reste visible via Mon espace/Fil. »*

> MISSIONS.md §20 (intention déjà écrite, jamais construite) : *« Notifier uniquement ce qui mérite l'attention : invitation, décision sur proposition, participation acceptée/refusée, blocage pertinent, soumission à valider, correction demandée, validation, annulation, dépendance devenue disponible, échéance significative. »* / *« Pas de notification pour chaque consultation, édition mineure ou item de checklist. »*

NOTIFICATIONS tient cette promesse : il devient le lieu unique où l'ensemble des signaux « ceci vous concerne » déjà calculés séparément par chaque module (invitations, décisions à prendre, transitions significatives) devient consultable comme une liste chronologique traversant tous les modules — sans devenir un second moteur de détection, sans devenir une grille de compteurs, sans expédier de courriel ou de push tant que l'infrastructure ne le justifie pas.

> **Une notification annonce qu'une chose réelle mérite l'attention d'une personne précise. Elle ne fabrique jamais une urgence, ne devient jamais un score d'engagement, et ne duplique jamais la décision déjà prise ailleurs par le module d'origine.**

## 2. Ce que NOTIFICATIONS n'est pas

- **pas** un second moteur de détection : chaque « ceci vous concerne » existe déjà en code (`MissionAssignment` INVITED, `TransmissionParticipant` INVITED, `ProofWitness` INVITED, items décidables Besoin/Projet/ZUMRA) — NOTIFICATIONS **lit** ces sources, ne les recalcule jamais indépendamment ;
- **pas** une grille de compteurs comme langage principal — l'invariant de design (`DESIGN-INVARIANTS.md` L.92) l'interdit explicitement pour Mon espace, et cette fiche l'étend à toute l'interface ;
- **pas** un remplacement de la chaîne `MemberSpaceController::priority()` — la priorité dominante unique reste inchangée, dans le même ordre, avec la même forme (§11) ;
- **pas** un second Fil — le Fil unique (`ActivityFeedService`) reste l'unique flux d'activité partagé/contextuel ; NOTIFICATIONS est personnel et non partagé (§10) ;
- **pas** un système de courriel, push ou webhook en v1 — aucune infrastructure réelle n'existe pour cela (§12), et en construire une dépasse la largeur fonctionnelle visée actuellement ;
- **pas** CAP-055 (Fil d'activité intelligent) — capacité distincte, hors périmètre de cette fiche.

## 3. Position dans le référentiel

- **CAP-054 — Notifications** (`CAPABILITY-INDEX.md` L.60) : domaine propre « Notifications », exécution DG. Aucune fiche préexistante, aucun code.
- **ActivityFeedService** (Fil unique, CAP transversal) : reste l'unique flux partagé/contextuel. `NEED_EVENTS`/`PROJECT_EVENTS`/`ZUMRA_EVENTS`/`MISSION_EVENTS`/`TRANSMISSION_EVENTS`/`PROOF_EVENTS` y sont déjà curés à un sous-ensemble étroit (3 événements par module en moyenne) pour un affichage partagé. NOTIFICATIONS s'appuie sur les mêmes modèles `*Event` mais avec un filtre **personnel**, potentiellement plus large sur les items qui concernent explicitement l'acteur (invitations, décisions), jamais identique au filtre du Fil (§6).
- **`MessageParticipant.last_read_at`** (`app/Models/MessageParticipant.php`) : mécanisme d'« unread » déjà réel, mais strictement scopé à la messagerie, jamais surfacé dans le topbar/shell aujourd'hui (`topbar.blade.php`, `shell.blade.php` : lien texte plein, aucun badge). C'est le seul précédent de lecture/non-lecture en code ; NOTIFICATIONS en reprend l'esprit (marqueur booléen par personne) sans réutiliser directement le modèle (portée différente : sept sources hétérogènes, pas une seule).
- **`MemberSpaceController::priority()`** (lignes 112-208) : chaîne if/elseif à une seule priorité dominante, ordre fixe (besoin proposé → projet personnel → paiement ZUMRA en attente → `nextAction()` Mission → `nextAction()` Transmission → `nextAction()` Preuve → aperçu du Fil → complétion de profil → état vide). NOTIFICATIONS n'ajoute **aucun maillon** à cette chaîne (§11).
- **CAP-055 — Fil d'activité intelligent** : capacité voisine mais distincte dans l'index, hors périmètre.

NOTIFICATIONS ne remplace ni le Fil, ni Mon espace, ni la messagerie. Il leur est référencé, jamais fusionné.

## 4. Doctrine à ne jamais casser

1. **Invisibilité institutionnelle** : *« […] un courriel, une notification, une métadonnée éditoriale ou tout autre contenu destiné aux utilisateurs »* ne doit jamais exposer le nom, le sigle ou l'architecture de l'institution fondatrice invisible (`ZUMRA-DOCTRINE-INVARIANTE.md` L.20). Toute notification reste sous la voix produit DG Afrique uniquement.
2. **Paramètres administrables, jamais un moteur figé en dur** : *« délais, rappels et notifications »* sont listés (L.675, §23.2 du canon) parmi les paramètres que l'administration doit pouvoir ajuster sans déploiement — les seuils/règles de déclenchement doivent rester configurables, pas codés en dur de façon irréversible.
3. **Sélectivité stricte** (MISSIONS.md §20, déjà écrite comme intention pour ce module) : notifier uniquement ce qui mérite l'attention ; jamais chaque consultation, édition mineure ou item de checklist.
4. **Une seule priorité dominante à la fois, pas de grille de compteurs comme langage principal** (`DESIGN-INVARIANTS.md` §7, L.89-92) — la tension centrale que cette fiche doit résoudre explicitement (§11, §15).
5. Aucune donnée de démonstration réelle. Aucune notification fictive pour peupler l'interface.

## 5. Acteurs

Aucun nouvel acteur. La même identité GAMAD Core (`dg_identity`) reçoit des notifications qui la concernent personnellement — jamais une notification de groupe non ciblée, jamais un tiers non concerné.

## 6. Sources d'événements — reprise, jamais duplication

Sept modèles `*Event` existent déjà et couvrent tout le référentiel construit à ce jour : `NeedEvent`, `ProjectEvent`, `ZumraGroupEvent`, `ZumraProgramMembershipEvent` (existant mais jamais branché au Fil aujourd'hui), `MissionEvent`, `TransmissionEvent`, `ProofEvent`. Leurs champs varient (`status` vs `state`, présence ou non de `subject_type`) — pas de subscriber générique unique, un petit adaptateur par source, même patron que `ActivityFeedService`.

En plus des événements, chaque module expose déjà une requête « en attente pour moi », que NOTIFICATIONS doit **envelopper**, jamais réinventer :

| Module | Source | Filtre |
|---|---|---|
| Mission | `MissionAssignment` | `core_identity_reference = moi` et `status = INVITED` |
| Transmission | `TransmissionParticipant` | `core_identity_reference = moi` et `status = INVITED` |
| Preuve | `ProofWitness` | `core_identity_reference = moi` et `status = INVITED` |
| Besoin/Projet/ZUMRA | requêtes d'autorité directes (`MemberSpaceController` L.51-63) | items décidables par propriété/rôle, pas de ligne d'invitation dédiée |

## 7. Modèle de données proposé

```text
dg_notification_reads               -- marqueur personnel de lecture, jamais une seconde détection
  id, core_identity_reference,
  source_type (NEED|PROJECT|ZUMRA|ZUMRA_PROGRAM|MISSION|TRANSMISSION|PROOF),
  source_event_id (FK polymorphe vers le *Event correspondant, nullable si item "à décider" sans event dédié),
  read_at (nullable), dismissed_at (nullable),
  timestamps
  -- unique(core_identity_reference, source_type, source_event_id)
```

Aucune nouvelle table de détection : la liste des notifications d'une personne à un instant T reste **calculée** en agrégeant les sources du §6, jamais stockée en double. `dg_notification_reads` ne porte que l'état « vu/pas vu », en jointure optionnelle sur ce calcul — exactement le même principe que `MessageParticipant.last_read_at`, généralisé à sept sources.

## 8. États

Chaque item calculé est soit **NON_LU** (par défaut), soit **LU** (une fois `read_at` posé). Aucun autre état — pas de « traité », pas de workflow : le traitement réel (accepter une invitation, décider un besoin) reste entièrement porté par le module d'origine, jamais dupliqué ici.

## 9. Permissions et confidentialité

Une notification n'est jamais visible à quiconque d'autre que la personne concernée. Elle hérite strictement de la visibilité déjà tranchée par le module source — si l'acteur perd l'accès au contexte source (ex. quitte une ZUMRA), l'item disparaît du calcul au prochain chargement, sans qu'aucune donnée obsolète ne soit stockée séparément.

## 10. Relation avec le Fil unique

Le Fil (`ActivityFeedService`) reste l'unique flux d'activité **partagé/contextuel**. NOTIFICATIONS est strictement **personnel** : deux surfaces différentes, deux publics différents (le Fil montre ce qui se passe dans un contexte visible ; Notifications montre ce qui concerne précisément moi). Pas de fusion, pas de duplication d'affichage du même événement dans les deux surfaces avec deux libellés différents — un item qui apparaît dans Notifications parce qu'il me concerne personnellement (ex. une invitation) n'a généralement pas vocation à apparaître aussi dans le Fil, et inversement.

## 11. Relation avec Mon espace — la tension centrale

`MemberSpaceController::priority()` reste **inchangé** : même ordre, même chaîne if/elseif, même forme `nextAction()` (`heading`/`body`/`primary`), toujours une seule priorité dominante à la fois. NOTIFICATIONS n'insère **aucun maillon** dans cette chaîne — les `nextAction()` de chaque module **sont déjà** la notification de leur propre domaine pour la priorité du jour.

Le rôle de NOTIFICATIONS est différent : un journal secondaire, consultable à part (ex. `/notifications`), qui rassemble **tout** ce qui est non lu à travers tous les modules — utile pour rattraper ce qui s'est passé pendant une absence, jamais pour concurrencer la priorité dominante de Mon espace. Aucun badge numérique en violation de l'invariant « pas de grille de compteurs comme langage principal » (§15, point à trancher).

## 12. Livraison — ce qui existe vs ce qui n'existe pas

Vérifié en code, même rigueur que l'audit GamaDrive de Carnet de preuves :

- **Système de notifications Laravel intégré** : absent partout (aucun `Notifiable`, aucune table `notifications`, aucun usage d'`Illuminate\Notifications`).
- **Courriel** : `config/mail.php` — mailer par défaut `log`, aucun `Mailable` dans l'app.
- **Files d'attente** : `config/queue.php` — driver `database` présent mais rien n'y est actuellement mis en file pour des notifications.
- **Broadcast/websocket** : `config/broadcasting.php` **n'existe pas** — aucune config Pusher/Reverb.
- **GAMAD Core** : `app/Infrastructure/GamadCore/` — zéro trace de notification/push/webhook/courriel, même verdict que pour GamaDrive (aspirationnel nulle part, absent du code, pas seulement de la documentation).

**Conclusion : toute livraison push/courriel/webhook serait construite entièrement à partir de rien.** Hors proportion avec la priorité de largeur fonctionnelle actuelle (§14, point à trancher).

## 13. Aucune automatisation qui outrepasse les autorités existantes

- NOTIFICATIONS n'accorde jamais un accès qu'un module source refuserait — il ne fait qu'annoncer un item déjà légitimement calculé par ce module.
- Aucune notification n'est générée pour un événement que le module source n'a pas lui-même produit dans son `*Event`.
- Marquer une notification comme lue ne modifie jamais l'état métier sous-jacent (une invitation reste `INVITED` tant qu'elle n'est pas acceptée/déclinée dans son propre module).

## 14. Hors périmètre v1

- courriel, push, webhook, SMS (aucune infrastructure réelle, §12) ;
- badge numérique global de type compteur dans le topbar (tension avec l'invariant de design, §15) ;
- préférences de notification granulaires par type/canal (prématuré tant qu'un seul canal — in-app — existe) ;
- CAP-055 (Fil d'activité intelligent) ;
- tout algorithme de priorisation/scoring des notifications entre elles au-delà d'un tri chronologique simple.

## 15. Points à trancher (CONCEPTION)

1. **Surface d'affichage sans violer l'invariant « pas de grille de compteurs ».** Page `/notifications` dédiée (journal chronologique, comme Mon Carnet de preuves) avec un lien texte simple dans le topbar — même patron que « Messages » aujourd'hui (aucun badge numérique) — ou tolère-t-on une seule pastille discrète (point, pas nombre) indiquant seulement « du non-lu existe » sans jamais afficher de compte ? Recommandation : lien texte plein, aucune pastille ni compteur en v1, cohérent avec le fait que « Messages » n'a lui-même aucun badge aujourd'hui.
2. **Périmètre des événements éligibles.** Le filtre personnel de NOTIFICATIONS doit-il se limiter strictement aux items déjà exposés par les `mySections()`/`myMissionsSections()`/`myTransmissionsSections()` de chaque module (invitations + items décidables), ou inclure aussi un sous-ensemble élargi d'événements `*Event` « FYI » (ex. une Mission que j'ai rejointe passe à `COMPLETED`) au-delà de ce que le Fil projette déjà ? Recommandation : v1 strictement limité aux items actionnables déjà calculés (§6) — pas d'extension FYI tant que la V1 n'est pas validée à l'usage.
3. **Persistance de l'état lu/non lu.** Table dédiée `dg_notification_reads` (§7, pointeur polymorphe vers l'event source) contre extension du patron `last_read_at` par module ? Recommandation : table dédiée unique, pour éviter d'ajouter une colonne d'état de lecture sur sept modèles hétérogènes déjà livrés.
4. **Déclenchement automatique de la disparition d'un item.** Un item « invitation en attente » disparaît-il de Notifications uniquement quand l'invitation change d'état dans son module d'origine (recommandation, cohérent avec §13), ou une personne peut-elle aussi « rejeter »/masquer manuellement une notification sans agir sur l'invitation elle-même (risque de double état à maintenir) ?
5. **Paramètres administrables (doctrine L.675).** Cette v1 doit-elle déjà exposer un écran d'administration pour ajuster quels types d'événements génèrent une notification, ou ce réglage reste-t-il en dur dans le code pour la v1 (avec un écran d'administration explicitement reporté à une version ultérieure, comme le permet la doctrine qui demande seulement que ce soit *administrable*, pas immédiatement livré) ? Recommandation : en dur en v1, avec la liste des événements éligibles documentée dans le code, écran d'administration hors périmètre v1.

## 16. Instruction d'arrêt

Si un point non couvert par cette fiche s'avère bloquant pendant l'implémentation, documenter le conflit et arrêter cette partie pour revue, comme pour Missions, Transmission et Carnet de preuves.
