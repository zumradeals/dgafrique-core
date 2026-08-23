# CAP-011 — ZUMRA / Groupe humain

- **Domaine :** ZUMRA & Capabilities
- **Propriétaire d’exécution :** DG Afrique
- **Statut :** implémenté — preuve VPS requise
- **Sources :** doctrine ZUMRA v1.1, CAP-007 à CAP-010

## Finalité

Une ZUMRA est un centre d’incubation humain spécialisé autour d’une activité (ZUMRA-HUMAN-BIRTH-001) : pas d’activité, pas de ZUMRA. Elle permet à des adhérents actifs de constituer une unité humaine organisée autour de cette activité, avec une gouvernance réelle qui se construit progressivement — jamais une condition de naissance.

Une ZUMRA est une unité particulière du réseau. Elle ne doit jamais être confondue avec le Programme ZUMRA dans son ensemble.

## Naissance (ZUMRA-HUMAN-BIRTH-001)

La naissance d’une ZUMRA reste bien plus légère que sa structuration ultérieure. Tout adhérent actif peut la proposer, seul, en quatre moments humains : son activité principale, ce qu’il veut changer (l’objectif fondateur), comment il commence (mode de présence, lieu si présentiel/hybride, capacité d’accueil/formation) et le nom de la ZUMRA.

Seuls le nom, le domaine (activité principale), l’objectif fondateur et le mode de présence sont exigés à la naissance. La charte interne, la localisation, la capacité d’accueil/formation et les activités dérivées sont tous différables — nullable en base, jamais rétro-remplis pour les ZUMRA nées avant ce chantier (toutes déjà pourvues d’une charte, aucune n’est affectée).

Le proposant devient membre fondateur. Il choisit explicitement s’il accepte le rôle de premier responsable ; ce rôle ne lui est pas attribué silencieusement, et son absence ne bloque jamais la naissance — les cinq responsabilités relèvent de la structuration, jamais de l’existence (`DOCTRINE-GAMAD.md` §6.4).

La création ouvre l’état `CONSTITUTING`. Elle ne vaut ni validation, ni financement, ni reconnaissance officielle.

### Charte interne différable (mini-audit Phase B)

La charte devient obligatoire seulement au passage `READY` (`evaluateStructuralReadiness()`), jamais à la naissance — la doctrine elle-même ne l’exigeait qu’à cette étape (`ZUMRA-DOCTRINE-INVARIANTE.md` §10), pas à la création (§7). Un responsable la rédige quand il le souhaite via `ZumraGroupService::setCharter()`, réservé aux responsables et à la seule phase `CONSTITUTING` — elle ne se réécrit jamais silencieusement une fois la ZUMRA sortie de constitution. Le gate de contribution collective (art. 6.3, `ContributionService`, exige `VALIDATED`) reste inchangé : une ZUMRA sans charte ne peut jamais dépasser `CONSTITUTING`.

### Activités dérivées

Une ZUMRA peut développer des activités secondaires et sous-activités, toujours rattachées à son activité principale — jamais un moyen d’étendre arbitrairement son périmètre vers un secteur sans rapport (ex. une ZUMRA technologique appliquant ses compétences à l’agriculture reste technologique, elle ne devient pas une ZUMRA agricole). `ZumraGroupActivity` (`dg_zumra_group_activities`) porte cette filiation par un texte humain obligatoire (`relation_to_principal`) — jamais une validation automatique de cohérence, jamais une taxonomie globale rigide. Déclarables dès la naissance ou après coup, réservées aux responsables (`ZumraGroupService::addActivity()`).

### Capacité d’accueil/formation

`welcome_capacity` (nullable) porte la réponse humaine à « comment votre ZUMRA pourra-t-elle accueillir des personnes qui souhaitent apprendre cette activité ? » — déjà capable d’accueillir et former, progressivement, ou doit d’abord trouver des transmetteurs. Un signal, jamais une promesse ni un critère de readiness ; potentiellement exploitable par un futur matching humain, non construit dans ce chantier.

## Gouvernance fondatrice

Les cinq sièges invariants sont créés dès le dossier : premier responsable, deux adjoints distincts, responsable financier et responsable des relations, affaires sociales et religieuses.

Un siège vacant reste visible comme vacant. Aucun profil fictif, matching ou automatisme ne peut accepter un rôle au nom d’une personne : un responsable propose (`ZumraGroupService::proposeRole`), la personne concernée accepte explicitement (`acceptRole`) — jamais l’inverse. La limite globale de responsabilités fondatrices simultanées (art. 8, administrable, initialement 3) est vérifiée à la fois à la création (attribution automatique du premier siège) et à chaque acceptation de rôle.

## Cycle de vie opérationnel (ZUMRA-COMP-001)

`ZumraGroup.state` distingue trois transitions successives, toutes portées par `ZumraGroupService`, jamais par un moteur parallèle :

- **CONSTITUTING → READY** : « le dossier est-il structurellement complet et prêt à être soumis à validation ? », jamais « les 7 critères doctrinaux sont-ils tous validés ? ». `evaluateStructuralReadiness()` vérifie les 6 critères de l’art. 10 réellement automatisables (identité Core, adhésion Programme active, domaine, objectif, charte, cinq responsabilités distinctes acceptées). Le 7e critère doctrinal — « contrôles de nom, de doublon, de risque et d’usurpation » — reste un **contrôle de conformité humain**, jamais automatisé : l’unicité technique du `slug` n’en est qu’une garantie partielle et n’est jamais présentée comme preuve qu’il est satisfait. Le critère « absence d’objet interdit/frauduleux » n’entre pas non plus dans l’évaluation, pour la même raison.
  - Automatique : déclenchée après l’acceptation d’un rôle si `auto_validation_enabled` l’autorise.
  - Manuelle : si `auto_validation_enabled=false`, l’autorité DG Afrique/GAMAD peut constater explicitement la readiness (`markReady()`) — le cycle ne devient jamais impossible faute d’automatisation. `CONSTITUTING → VALIDATED` directement reste toujours impossible : READY est une étape distincte, dans les deux cas.
- **READY → VALIDATED → ACTIVE → WARNED → SUSPENDED → REHABILITATING → ACTIVE** : décisions explicites de l’autorité DG Afrique/GAMAD (`PortalAdministrator`, jamais `isLeader()`), chacune journalisée dans `ZumraGroupEvent`.

`CONSTITUTING` reste un état opérationnel limité mais utilisable : Messagerie, Partage, Commentaire et proposition de Mission continuent de fonctionner dès la constitution, comme avant ce correctif — seul `SUSPENDED` bloque ces surfaces, décision produit ROADMAP-001 explicitement préservée.

## Appartenance

- une demande reste `REQUESTED` jusqu’à approbation d’un responsable ;
- une invitation reste `INVITED` jusqu’à acceptation du destinataire ;
- une personne peut appartenir à plusieurs ZUMRA ;
- un membre sans responsabilité active peut partir librement ;
- un responsable transmet d’abord sa charge afin de ne pas créer une vacance silencieuse ;
- tous les changements produisent des événements conservés.

## Cycle et maturité

Le statut opérationnel et la maturité sont distincts. Le seuil initial de 50 membres produit la maturité `ESTABLISHED`, sans plafonner la croissance. Le seuil est administrable ; les cinq responsabilités et le consentement ne le sont pas.

Une ZUMRA suspendue devient invisible de l’annuaire, sans suppression de son histoire.

## Invariants

1. adhésion active au Programme exigée pour proposer, demander ou accepter ;
2. aucune adhésion directe sans approbation ou acceptation ;
3. aucune nomination automatique ;
4. aucune identité canonique exposée dans l’interface ;
5. aucune contribution utilisée comme droit d’entrée, score ou pouvoir ;
6. nom, domaine, objectif et charte ne sont jamais inventés ;
7. actions sensibles limitées et journalisées ;
8. politique opérationnelle configurable sans affaiblir la doctrine.

## Critères de preuve

- membre inactif bloqué à la création ;
- dossier réel avec cinq sièges et un seul rôle accepté explicitement ;
- demande sans adhésion automatique puis approbation ;
- invitation sans adhésion automatique puis acceptation ;
- départ libre et protection des responsabilités actives ;
- configuration admin persistée ;
- proposition de rôle réservée aux responsables, acceptation réservée à la personne concernée ;
- limite de responsabilités fondatrices simultanées réellement appliquée (création et acceptation) ;
- cinq rôles acceptés et critères structurels réunis → READY, journalisé une seule fois ;
- VALIDATED/ACTIVE/WARNED/SUSPENDED/REHABILITATING/réactivation réservés à l’autorité DG Afrique/GAMAD ;
- CONSTITUTING reste opérationnel pour Messagerie/Partage/Commentaire/Mission ; seul SUSPENDED bloque ;
- aucune Organisation, aucun Projet, aucun Satellite créé automatiquement par le cycle de vie ;
- naissance réelle avec seulement activité, objectif, mode et nom — charte, localisation, capacité d’accueil et activités dérivées absentes et non requises ;
- ZUMRA sans charte jamais `READY`, gate de contribution collective inchangé ;
- charte complétée après coup par un responsable, jamais par un non-responsable, jamais après `CONSTITUTING` ;
- activité dérivée exigeant une filiation explicite non vide, réservée aux responsables ;
- ZUMRA historique (charte déjà renseignée avant ce chantier) inchangée ;
- migration, tests ciblés, non-régression et build verts sur VPS.
