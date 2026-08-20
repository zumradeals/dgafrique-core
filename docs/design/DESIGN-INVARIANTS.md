# DG Afrique — Invariants de design

**Statut : CANONIQUE DESIGN — ADOPTÉ**  
**Version : 1.0** (voir addendum daté §16 pour la Landing)  
**Date d’adoption : 16 août 2026**  
**Référence visuelle :** `docs/design/reference/claude-2026-08-16/`

## 1. Autorité et portée

Ce document fixe les invariants d’identité visuelle et d’expérience de DG Afrique.

Il s’applique à toute nouvelle interface et à toute refonte d’interface existante. Il ne remplace jamais la doctrine métier, les contrats GAMAD Core, les règles de sécurité, de confidentialité, de consentement ou de gouvernance. En cas de conflit, la doctrine métier et les règles de sécurité priment ; le design doit s’y adapter.

Le handoff Claude remis le 16 août 2026 et archivé sous `docs/design/reference/claude-2026-08-16/` est la **référence de design adoptée**. L’archive complète permet de reconstruire à l’octet près la maquette, le design system, les composants, les fixtures d’exemple et le guide d’intégration.

Une maquette ultérieure, une demande ponctuelle, un commit ou un changement de composant ne peut pas remplacer implicitement cette référence. Une évolution de ces invariants doit être explicite, datée, versionnée et justifiée.

## 2. Identité produit à faire ressentir

**DG Afrique est un réseau social d’action.**

Il relie personnes, capacités, apprentissage/transmission, besoins, projets et ZUMRA pour transformer des possibilités humaines en actions réelles.

Les applications spécialisées — GamaDrive et futurs satellites — sont des **outils au service de l’action**. Elles constituent une famille secondaire et ne doivent pas concurrencer l’identité principale de DG Afrique.

Boussole UX :

> Une identité. Des personnes. Des capacités. Des actions. Des outils spécialisés.

Formulation de marque de référence :

> DG Afrique — le réseau où les capacités deviennent des actions.

## 3. Trois interfaces fondatrices

Les trois interfaces suivantes définissent le langage de l’ensemble du produit :

1. **Landing page = la promesse.** Elle donne envie d’entrer et explique le passage de la capacité à l’action.
2. **Mon espace = mon centre d’action.** Il indique ce qui mérite l’attention maintenant ; il ne devient pas un tableau de statistiques ou un catalogue de modules.
3. **Fil ZUMRA / Fil d’action = la vie collective.** Il montre des situations réelles et la possibilité d’agir, plutôt qu’un flux de consommation sociale.

Toute propagation du design vers Besoins, Projets, Personnes, Messages, Partages, Administration ou satellites doit rester reconnaissable comme appartenant au même univers.

## 4. Navigation

La navigation principale nomme la matière de l’action, pas des modules techniques :

**Fil · Mon espace · Personnes · Besoins · Projets · ZUMRA**.

Messages, partages reçus, apprentissage/transmission et autres fonctions restent accessibles de manière secondaire ou contextuelle lorsqu’il n’est pas utile de les mettre au premier niveau.

Les satellites ne prennent pas une entrée principale équivalente au réseau. Ils apparaissent sous **Mes outils** ou à l’endroit où une action réelle les rend utiles.

## 5. Matières, couleurs et typographie

Principes immuables de la version 1.0 :

- fond principal ivoire `#F8F3EA` ;
- sable `#EDE4D4` pour les variations de matière ;
- cartes ivoire clair `#FFFDF7` ;
- vert profond `#103028` pour les surfaces d’ancrage ;
- vert d’action `#1B4A3B` pour ce qui agit ;
- cuivre `#A9552B` pour les besoins / ce qui manque ;
- bleu nuit `#14314D` pour les outils spécialisés ;
- safran `#D9A02B` pour ce qui attend une décision humaine.

Le blanc pur ne doit pas redevenir le fond structurel dominant. La profondeur vient d’abord des matières, des contrastes et du rythme, pas d’un empilement de cartes blanches avec ombres.

Typographies de référence :

- **Instrument Serif** : voix humaine, grands titres, moments éditoriaux ;
- **Instrument Sans** : interface et lecture courante ;
- **IBM Plex Mono** : références, structure, micro-libellés techniques uniquement.

Les polices doivent être auto-hébergées lors de l’intégration ; la maquette autonome ne dicte pas les dépendances runtime de production.

## 6. Couleur = sens, jamais décoration arbitraire

Une même famille d’objet conserve son signal visuel d’un écran à l’autre. La couleur doit permettre de reconnaître la nature d’un contenu même si son texte est masqué.

L’or/safran est rare : il signale une action ou décision humaine attendue, pas chaque bouton.

Le bleu nuit appartient en priorité aux outils spécialisés, afin que GamaDrive et les satellites restent clairement intégrés mais distincts du cœur social d’action.

## 7. Mon espace : une priorité avant le reste

Mon espace doit donner une direction quotidienne.

- une seule priorité dominante à la fois ;
- au maximum deux actions principales dans ce bloc ;
- le reste est organisé dans le temps : **Ensuite**, **Cette semaine**, ou équivalent ;
- pas de grille de compteurs comme langage principal ;
- une progression n’est montrée que lorsqu’elle décrit un objet métier réel.

Le design ne crée pas de moteur de décision fictif : la priorité affichée doit être déduite d’objets et événements réellement disponibles, ou remplacée par un état vide honnête.

## 8. Fil d’action : pertinence → compréhension → action

Le Fil ne cherche pas à maximiser la réaction ou le temps passé.

Chaque élément doit répondre à deux questions :

1. **Pourquoi cela m’est montré ?**
2. **Que puis-je réellement faire ici ?**

Les actions visibles dépendent toujours des permissions métier réelles. Le design ne crée aucun droit.

Sont exclus comme signaux de valeur humaine ou de classement : likes, nombre d’abonnés, score d’engagement, viralité, classement des personnes, défilement infini conçu pour la dépendance.

Le fil peut s’arrêter et le dire.

## 9. Présence humaine digne

DG Afrique doit faire sentir les personnes sans imiter un réseau social de divertissement.

- avatars typographiques et portraits réels lorsqu’ils sont disponibles et autorisés ;
- anonymat assumé avec `Membre DG Afrique` lorsque le profil n’est pas découvrable ;
- `Vous` pour soi ;
- groupes/portraits collectifs uniquement lorsque l’équipe existe réellement ;
- pas de banque d’images générique utilisée pour simuler une communauté réelle.

## 10. États vides

L’état vide est un écran de plein droit.

Il doit dire honnêtement qu’aucune donnée réelle n’est disponible, expliquer pourquoi, proposer l’action qui peut débloquer la situation, rester chaleureux et ne jamais être remplacé automatiquement par des données fictives.

## 11. Données de démonstration — invariant de référence UX

Les fixtures incluses dans le handoff Claude sont conservées **intentionnellement** afin de ne pas perdre le fil des interactions et de fournir un scénario stable de référence visuelle.

Elles ne sont pas des données métier et ne doivent jamais :

- être seedées dans les tables métier ;
- créer de faux membres, besoins, projets, ZUMRA, paiements ou partenaires ;
- apparaître automatiquement lorsqu’un utilisateur réel n’a pas de données ;
- être présentées sans marquage comme une activité réelle de DG Afrique.

Elles peuvent être utilisées dans les maquettes et tests visuels, dans un **mode Exemple / Démonstration** explicitement activé en environnement non productif, ou dans une landing/documentation si chaque scénario est clairement marqué **Exemple**.

Cette règle constitue une décision produit explicite du 16 août 2026 : elle autorise un usage non productif **uniquement sous marquage Exemple/Démonstration**, tout en maintenant l’interdiction de les faire passer pour des données réelles.

## 12. Outils spécialisés

GamaDrive et les futurs satellites apparaissent de préférence au moment où ils servent une action : documents d’un projet, espace documentaire d’une ZUMRA ou besoin métier spécialisé.

Ils peuvent aussi être regroupés dans **Mes outils**, mais ne structurent pas la navigation principale du réseau.

## 13. Hiérarchie avec le métier

Le design doit toujours se brancher sur les routes réellement disponibles, les permissions et services métier existants, les états et transitions canoniques, les consentements et visibilités réels, et GAMAD Core comme autorité d’identité.

Un prototype peut illustrer une interaction future, mais l’implémentation ne doit pas inventer le backend pour satisfaire une maquette.

## 14. Gouvernance de changement

Pour remplacer ou modifier substantiellement cette direction :

1. identifier l’invariant concerné ;
2. expliquer le problème utilisateur qui justifie le changement ;
3. montrer l’impact sur Landing, Mon espace, Fil et navigation ;
4. vérifier la compatibilité doctrine, accessibilité, mobile et sécurité ;
5. adopter explicitement une nouvelle version datée ;
6. conserver l’ancienne référence dans `docs/design/reference/`.

Une retouche locale peut évoluer sans nouvelle version si elle ne contredit aucun invariant.

## 15. Ordre de propagation de la version 1.0

1. tokens / typographies / surfaces ;
2. navigation globale desktop + mobile ;
3. Fil d’action / ZUMRA ;
4. Mon espace ;
5. Landing page ;
6. propagation progressive vers les autres capacités.

Le backend CAP-001 → CAP-022 n’est pas réinitialisé par ce chantier : on refond l’expérience et l’identité visuelle, pas les contrats métier validés.

## 16. Addendum daté — Landing « portail » (19 août 2026)

**Portée : Landing page uniquement** (`resources/views/foundation.blade.php`). Ce changement suit
la procédure de gouvernance du §14.

**Invariant concerné** : §2 (« DG Afrique est un réseau social d’action », pas un portail) et §3.1
(la Landing comme « la promesse » manifeste, adoptées le 16 août 2026).

**Problème utilisateur justifiant le changement** : une maquette a été fournie qui demande à la
Landing de fonctionner comme point d’entrée « hub », avec un accès direct à Fil/Besoins/Projets/
ZUMRA depuis quatre portes d’entrée visibles, une preuve chiffrée de l’ampleur du réseau, et un
aperçu concret de l’activité en cours — plutôt que de porter le manifeste seul. Décision produit
explicite : reproduire cette maquette telle quelle, chiffres de communauté inclus, jugés utiles
pour situer l’orientation et l’objectif du réseau d’action pour un nouvel arrivant.

**Ce qui change** :
- la Landing ajoute une porte d’entrée à quatre cartes (Fil/Besoins/Projets/ZUMRA), un bloc
  chiffré (« Une communauté engagée ») et des cartes d’activité (« Dans le réseau en ce moment »),
  tous marqués **Exemple** conformément au §11 — aucun de ces éléments n’est une donnée métier
  réelle, aucun n’est seedé dans les tables métier (`resources/design-reference/landing-portal-demo.json`) ;
- l’en-tête public gagne une recherche (désactivée, comme le reste du produit) et une icône de
  notification qui renvoie vers la connexion plutôt que d’afficher un compteur fictif pour un
  visiteur anonyme ;
- un bouton « copper » plein (`.dg-btn--copper`) est ajouté aux boutons existants pour l’appel à
  l’action principal de la Landing.

**Ce qui ne change pas** : Mon espace, le Fil d’action/ZUMRA connectés et la navigation
authentifiée (`x-dg.topbar`/`x-dg.tabbar`) restent gouvernés par les invariants 1.0 sans
modification — en particulier §7 (une priorité dominante), §8 (pas de mécaniques de popularité) et
§10 (état vide honnête). Le §11 (fixtures marquées Exemple, jamais seedées) s’applique sans
exception aux nouveaux blocs.

**Compatibilité vérifiée** : doctrine (aucune identité parallèle, aucun faux lien — les entrées de
pied de page sans destination réelle sont marquées « · bientôt » plutôt que rendues cliquables),
accessibilité (liens réels vs. `aria-disabled`, `aria-current`, `role="dialog"` pour les feuilles
mobiles), mobile (en-tête + tabbar dédiés, testés au viewport 390×844), sécurité (aucune nouvelle
surface serveur — la page reste un simple GET public).

**Référence visuelle** : maquette fournie par le demandeur le 19 août 2026 (non archivée dans
`docs/design/reference/` — capture d’écran transmise en conversation, reproduite dans
`resources/views/foundation.blade.php` et `resources/css/dg.css` §« Landing — portail »).

## 17. Addendum daté — Fil V2 (19 août 2026)

**Portée : Fil d’action uniquement** (`resources/views/activity/index.blade.php`,
`resources/views/components/dg/feed/*`). Ce changement suit la procédure de gouvernance du §14.

**Invariant concerné** : §10 (état vide honnête, jamais remplacé automatiquement par des données
fictives) et §11 (les fixtures Exemple étaient jusqu’ici réservées à la landing/documentation), et
révise l’affirmation du §16 selon laquelle « le Fil d’action […] connecté reste gouverné par les
invariants 1.0 sans modification ».

**Problème utilisateur justifiant le changement** : un nouveau Fil V2 a été spécifié (maquette +
correctifs fonctionnels fournis le 19 août 2026), avec une règle produit explicite **DEMO-FIRST,
REAL-DATA-TAKES-OVER** : quand un filtre du Fil n’a pas encore de donnée réelle, afficher des
cartes démonstratives réalistes plutôt que seulement un état vide, pour expliquer concrètement à
un membre ce que le réseau produit — jusqu’à ce qu’une donnée réelle existe pour ce filtre, auquel
cas la démonstration disparaît d’elle-même pour ce filtre.

**Ce qui change** :
- le Fil peut désormais afficher jusqu’à trois cartes d’exemple (Besoin, Projet, ZUMRA — voir
  `resources/design-reference/fil-demo.json`), chacune marquée **« · EXEMPLE »** sur son badge,
  **uniquement lorsque le filtre actif n’a aucune donnée réelle correspondante** et seulement en
  première page (`page=1`) — dès qu’une donnée réelle existe pour ce filtre, elle prend le pas et
  la carte d’exemple correspondante disparaît ;
- une carte d’exemple ne porte **aucune action câblée** : toutes ses actions sont visuellement
  conservées mais désactivées, avec la raison accessible « Objet de démonstration — aucune action
  réelle n’est rattachée. » (conforme au §13 — jamais un faux backend pour satisfaire une
  maquette) ;
- ces cartes ne sont jamais écrites dans les tables métier, jamais seedées, et restent gouvernées
  par le §11 (marquage Exemple obligatoire, pas de faux compteurs de traction).

**Ce qui ne change pas** : Mon espace n’affiche toujours aucun contenu de démonstration (état vide
honnête intégral, §10 sans exception) ; les cartes réelles du Fil gardent exactement leurs actions
et leurs raisons d’indisponibilité déjà conformes (CAP-019/020/021/022) ; §7 (une priorité
dominante) et §8/annexe « pas de mécaniques de popularité » restent pleinement en vigueur — les
cartes d’exemple n’affichent aucun compteur de likes/partages/classement, seulement des faits qui
décrivent l’objet (ex. « 3 compétences recherchées »).

**Compatibilité vérifiée** : doctrine (démonstration jamais confondue avec du réel — badge
« · EXEMPLE » + actions désactivées avec raison), accessibilité (`aria-disabled`, raisons de
désactivation lisibles au clavier/lecteur d’écran), mobile (cartes testées au viewport 390×844),
sécurité (fixtures statiques servies en lecture seule, aucune écriture déclenchée par leur
affichage).

**Référence visuelle** : maquette + correctifs fonctionnels fournis par le demandeur le
19 août 2026 (transmis en conversation, non archivés dans `docs/design/reference/` ; handoff
complet dans `docs/design/handoffs/DG-AFRIQUE-FIL-V2-HANDOFF.md`).

## 18. Addendum daté — Portail Projets (20 août 2026)

**Portée : écran Projets uniquement** (`resources/views/projects/index.blade.php`,
`app/Http/Controllers/ProjectController.php`, `app/Application/Projects/ProjectDirectoryDemoContent.php`).
Ce changement suit la procédure de gouvernance du §14.

**Invariant concerné** : §11 (les fixtures « Exemple » étaient jusqu'ici réservées à la landing et
au Fil) et révise, pour cet écran, l'énoncé implicite selon lequel Besoins/Projets/Personnes/ZUMRA
n'affichent que des données réelles ou un état vide honnête — comme le Fil V2 (§17) l'avait déjà
fait pour le Fil.

**Problème utilisateur justifiant le changement** : une maquette a été fournie pour le portail
Projets, avec une demande produit explicite : DG Afrique est encore en construction, et le réseau
réel ne contient aujourd'hui aucun projet publié (ni même le projet « GAMAD Technology » cité en
exemple, absent de toute table métier ou seed existant — vérifié avant implémentation). Plutôt que
d'inventer une fausse donnée Core pour « GAMAD Technology », ou de montrer un écran vide alors que
la maquette montre volontairement plusieurs projets, la même règle **DEMO-FIRST,
REAL-DATA-TAKES-OVER** que le Fil V2 est appliquée ici, avec un raffinement : domaine par domaine
plutôt qu'à l'échelle de tout l'écran, pour rester fidèle au scénario explicitement demandé
(« si la base réelle ne contient que GAMAD Technology, conserve GAMAD Technology comme projet réel
et complète l'écran avec des projections pour les autres »).

**Ce qui change** :
- le portail Projets peut désormais afficher jusqu'à trois cartes d'exemple (dont « GAMAD
  Technology », « Bibliothèque Solidaire », « Ensemble pour la propreté » — voir
  `resources/design-reference/projets-demo.json`), chacune marquée **« · Exemple »** sur son
  badge de domaine, **uniquement lorsqu'aucun projet réel visible n'existe pour le domaine de la
  carte** et seulement en première page (`page=1`) — dès qu'un projet réel existe pour ce domaine,
  il prend le pas et la carte d'exemple correspondante disparaît d'elle-même
  (`ProjectDirectoryDemoContent::demoCards()`) ;
- une carte d'exemple ne porte **aucune action câblée** : ses trois actions (« Ouvrir le
  Cerveau », « Trouver des capacités », « Dossier ») restent visuellement présentes mais
  désactivées, avec la raison accessible « Objet de démonstration — aucune action réelle n'est
  rattachée. » (conforme au §13 — jamais un faux backend pour satisfaire une maquette) ;
- un panneau « Aperçu de la communauté » affiche quatre compteurs réseau de démonstration
  (projets proposés/membres impliqués/projets en cours/projets réalisés), également issus de
  `projets-demo.json` et annoncés **« · Exemple »** — aucun agrégat métier réel n'existe
  aujourd'hui pour ces quatre compteurs ;
- ces cartes et compteurs ne sont jamais écrits dans `dg_projects` ni aucune autre table métier,
  jamais seedés (`database/seeders/DatabaseSeeder.php` reste intentionnellement vide), et restent
  gouvernés par le §11 (marquage Exemple obligatoire, pas de faux compteurs de traction) ;
- la maturité d'un projet (CAP-017) continue de **ne jamais** s'afficher comme un pourcentage, y
  compris sur ces cartes de démonstration : la maquette montre une barre de progression chiffrée
  (« 45 % »), reproduite ici par `x-dg.maturity` (repères en pointillés) accompagné du libellé du
  repère courant plutôt que d'un chiffre — voir `docs/design/DIFFERENCES.md`.

**Ce qui ne change pas** : Mon espace continue de n'afficher aucun contenu de démonstration (§10
sans exception) ; §7 (une priorité dominante) et §8 (pas de mécaniques de popularité) restent
pleinement en vigueur — aucune carte n'affiche de likes, d'abonnés ou de classement. Le formulaire
de filtre par domaine (`method="GET" class="dg-filters"`) garde exactement son contrat et son test
existants.

**Compatibilité vérifiée** : doctrine (démonstration jamais confondue avec du réel — badge
« · Exemple » + actions désactivées avec raison, exactement le contrat du Fil V2), accessibilité
(`aria-disabled`, raisons de désactivation lisibles au clavier/lecteur d'écran, alternatives
textuelles sur les icônes décoratives), mobile (grille à une carte par ligne, panneau communauté
repositionné sous la grille, testé aux viewports 390×844 et 430×932), sécurité (fixtures JSON
statiques servies en lecture seule, aucune écriture déclenchée par leur affichage, aucune nouvelle
surface serveur).

**Référence visuelle** : maquette fournie par le demandeur le 20 août 2026 (transmise en
conversation, non archivée dans `docs/design/reference/`, reproduite dans
`resources/views/projects/index.blade.php` et `resources/css/projects-directory.css`).

## 19. Addendum daté — Dossier Projet / Vue d’ensemble (20 août 2026)

**Portée : la page détail d’un projet uniquement** (`resources/views/projects/show.blade.php`,
`app/Http/Controllers/ProjectController.php::show()`, `app/Models/ProjectEvent.php`,
`resources/css/project-detail.css`). Ce changement suit la procédure de gouvernance du §14.
`resources/views/projects/overview-v2.blade.php` (route `projects.overview`, la page « Projet
vivant » du workspace Cerveau) n’est pas concerné : c’est une interface distincte, non gouvernée
par `x-dg.shell`, hors du périmètre de cette maquette.

**Invariant concerné** : §11 (démonstration marquée uniquement) et §13 (brancher sur les routes et
services réels) — cette refonte réorganise visuellement une page déjà quasi-entièrement réelle
(problème/solution/bénéficiaires, jalons, équipe, besoins, accompagnement, transitions de statut)
sans qu’aucun invariant n’ait besoin d’être révisé pour l’essentiel du contenu.

**Ce qui change** :
- Nouvel en-tête avec actions réelles (**Partager** → `shares.project`, **Ouvrir le Cerveau** →
  `projects.brain.show`) et un badge **Actif/Archivé** dérivé honnêtement de `Project.status`
  (`!= ARCHIVED`), distinct du statut métier affiché dans « Informations clés ».
- Des onglets internes (Vue d’ensemble/Activités/Équipe/Besoins/Ressources/Documents/
  Conversations) implémentés comme des **ancres réelles vers des sections de cette même page** —
  jamais des pages fabriquées ou des liens morts, conformément à la consigne de ne pas construire
  arbitrairement un contenu d’onglet qui n’existe pas encore.
- Le bandeau de maturité passe d’une présentation verticale à une présentation **horizontale sur
  desktop** (≥ 900px) : réutilise le composant `x-dg.stagewalk` **sans toucher à son DOM ni à sa
  classe testée** (`DesignInvariantsPhase2Test::test_project_maturity_stagewalk_shows_all_eight_stages_not_a_percentage`
  reste vert tel quel) — seule une feuille de style scoping (`project-detail.css`) transforme la
  disposition. Sous 900px, la présentation verticale d’origine du composant reste inchangée, ce qui
  correspond aussi à la direction demandée pour mobile (« maturité affichée verticalement »).
  Aucun pourcentage n’apparaît sur ce composant, toujours conformément à CAP-017.
- **« Progression globale » (X %)** est une **projection d’affichage** distincte de la maturité,
  clairement annoncée « · Projection » : un entier déterministe dérivé de `crc32(project->id)`,
  calculé à l’affichage, **jamais persisté, jamais un calcul métier réel**. Le bouton « Voir le
  détail → » associé reste désactivé avec sa raison, faute de moteur de progression canonique —
  même discipline que `overview-v2.blade.php` (« La progression reste une projection jusqu’au
  moteur de progression canonique »).
- **« Documents & Preuves »** est un nouveau bloc honnête : aucun modèle ne relie de document à un
  projet aujourd’hui (`Proof` n’a pas de portée projet exploitable ici) — état vide réel («aucun
  document pour le moment ») + action « Ajouter un document » visuellement présente mais
  désactivée avec sa raison (espace documentaire GamaDrive non relié).
- **« Activité récente »** est un nouveau bloc **entièrement réel** : les six derniers
  `ProjectEvent` du projet (`ProjectEvent::EVENT_LABELS`, nouvelle constante), avec l’acteur
  affiché via son `discovery_display_name` ou « Membre DG Afrique » (anonymat assumé, §9). Le CTA
  « Voir toute l’activité → » reste désactivé avec sa raison, faute de journal dédié.
- **« Suivre » / « Suivre les mises à jour »** (en-tête et colonne latérale) restent désactivés
  avec leur raison : aucun mécanisme de notification par objet n’existe aujourd’hui — ceci n’est
  pas un mécanisme de popularité (§8), seulement une préférence de suivi non encore câblée.
- La colonne latérale (« Progression globale », « Informations clés », « Actions rapides ») est
  nouvelle mais chaque champ d’« Informations clés » (domaine, statut, visibilité, créé le,
  dernière activité) et chaque action de « Actions rapides » (Cerveau, `projects.matching` si
  `canDecide`, `shares.project`) proviennent de données ou de routes déjà réelles.
- Aucune capacité existante n’a été supprimée : gestion d’équipe (inviter/demander/accepter/
  quitter/retirer), besoins du projet, repositionnement de maturité avec historique,
  accompagnement DG Afrique, transitions de statut, et l’avertissement « Aucun financement n’est
  ouvert ici » restent mot pour mot identiques, seulement redisposés dans la page.

**Ce qui ne change pas** : §7/§8/§10 restent pleinement en vigueur (pas de mécanique de
popularité, état vide honnête partout où aucune donnée réelle n’existe). Aucune donnée de
démonstration n’a été introduite pour cette page — contrairement à `/projets` (§18), il n’existe
aucun fichier de fixtures ici : quand une donnée réelle manque, la page l’affiche honnêtement vide
plutôt que de la remplacer par un exemple.

**Compatibilité vérifiée** : doctrine (aucune fausse mutation Core — chaque bouton pointe vers une
route réelle ou est visuellement désactivé avec sa raison), accessibilité (`aria-disabled`,
raisons lisibles, ancres de navigation réelles au clavier), mobile (colonne unique, onglets
défilables horizontalement, maturité verticale, testé aux viewports 390×844), sécurité (aucune
nouvelle surface serveur, `ProjectController::show()` garde exactement ses autorisations
`canView`/`canDecide` existantes).

**Référence visuelle** : maquette fournie par le demandeur le 20 août 2026 (transmise en
conversation, non archivée dans `docs/design/reference/`, reproduite dans
`resources/views/projects/show.blade.php` et `resources/css/project-detail.css`).

## 20. Addendum daté — Cerveau du Projet / PVB-I05 V1 (20 août 2026)

**Portée : l'écran de conversation du Cerveau uniquement** (`resources/views/projects/brain.blade.php`
et ses partiels `resources/views/projects/partials/brain-*.blade.php`,
`app/Http/Controllers/ProjectBrainController.php::show()`, `resources/css/project-brain.css`,
`Project::progressionSeed()`). Ce changement suit la procédure de gouvernance du §14. Le flux de
naissance du projet (`projects.brain.start.*`, `resources/views/projects/brain-start.blade.php`)
et `resources/views/projects/overview-v2.blade.php` (route `projects.overview`) ne sont pas
concernés — périmètres distincts, non touchés par cette refonte.

**Invariant concerné** : §11 (démonstration marquée uniquement), §13 (brancher sur les routes et
services réels), et corrige une régression accumulée au fil de PVB-I05.1/.2/.3 : l'écran affichait
deux bandeaux de navigation superposés (le contournement `.dg-global-nav` injecté par
`portal.blade.php` **et** son propre en-tête `.pw-top`), sa palette pétrole/orange
(`project-workspace-v2.css`, PVB-I05.2) était neutralisée par un bloc `&lt;style&gt;` inline navy/violet
resté dans la vue, et la colonne « Projets & conversations » ne listait jamais qu'un seul projet
réel (`Project::query()-&gt;whereKey($project-&gt;id)`) complétée par une liste de projets et de
sous-conversations **entièrement fabriquée et non marquée** — en contradiction directe avec §11.

**Ce qui change** :
- L'écran rejoint enfin `x-dg.shell` (navigation globale réelle, identique à `/projets` et
  `/projets/{project}`) : plus de double bandeau, plus de pied de page dupliqué. Le bloc `&lt;style&gt;`
  inline est supprimé ; `resources/css/project-brain.css` (nouveau, scopé à `projects.brain.show`
  uniquement) porte la palette pétrole/orange déjà établie.
- **Colonne gauche** : `ProjectBrainController::show()` construit désormais une vraie liste « mes
  projets » (même filtrage `ProjectService::canView` que `ProjectController::index()`), avec un
  indicateur réel « conversation active » par projet (existence d'une `ProjectBrainConversation`
  pour l'acteur courant) — les sous-conversations fictives (« Financement & Apports », etc.) ont
  été retirées : une seule conversation réelle existe par (projet, acteur) aujourd'hui, et le §12
  de la demande produit demande explicitement d'éviter les seeds conversationnels quand une vraie
  conversation existe.
- **Fil de conversation** : entièrement inchangé dans son contrat métier — mêmes messages
  (`ProjectBrainMessage`), même carte de brouillon en attente liée à `message.meta.draft_reference`
  et `ProjectBrainDraft`, mêmes routes `projects.brain.needs.confirm`/`.drafts.cancel`, même
  formulaire de composition (`projects.brain.needs.prepare`, champ `message`). L'ancienne carte
  d'exemple « Créer une équipe projet » (non reliée à un brouillon réel, boutons
  `type="button"` sans action) est conservée comme illustration **uniquement dans l'état vide**
  (avant toute conversation réelle), avec ses actions explicitement désactivées
  (`aria-disabled="true"`, raison accessible) plutôt que des boutons silencieusement inertes.
- **Colonne droite « Projet vivant »** : Besoins, Missions (nouveau : `Mission::where('context_type',
  'PROJECT')`, filtré par `MissionVisibilityService::canViewMission`, jamais interrogé depuis cet
  écran auparavant) et Équipe sont désormais entièrement réels, avec état vide honnête quand ils
  sont vides — remplaçant le remplissage silencieux par des données fictives non marquées. Le
  « Prochain jalon » devient réel (`$project-&gt;milestones()-&gt;where('status','!=','COMPLETED')-&gt;first()`)
  au lieu d'un texte fixe avec un faux compte à rebours. L'« Avancement » (pourcentage) reste une
  projection d'affichage clairement annoncée, désormais portée par `Project::progressionSeed()`
  (même formule que « Progression globale » sur `/projets/{project}`, §19, pour que les deux écrans
  montrent le même chiffre pour un même projet plutôt que deux projections divergentes). « Preuves
  récentes » et « Opportunités pour vous » restent des projections métier explicitement annoncées
  « · Exemple », cohérentes avec la demande produit (§12) — aucune n'est jamais écrite en base.
- La liste « Projets archivés » affiche un compte réel (filtré `canView`) plutôt qu'un chiffre fixe,
  et reste désactivée avec sa raison tant qu'aucune vue dédiée n'existe pour la parcourir.
- Tiroirs mobiles (`&lt;details&gt;`, sans JavaScript) pour « Projets & conversations » et
  « Projet vivant », conformément à la consigne de rendre les deux colonnes latérales accessibles
  sans jamais réduire simplement les trois colonnes desktop.

**Ce qui ne change pas** : aucune mutation Core silencieuse — chaque bouton pointe vers une route
réelle ou est désactivé avec sa raison ; la confirmation explicite avant création d'un besoin
(CAP existant) reste l'unique chemin d'écriture. §7/§8/§10 restent pleinement en vigueur.

**Compatibilité vérifiée** : doctrine (les seuls contenus de démonstration restants — carte
d'exemple à l'état vide, Preuves récentes, Opportunités — sont explicitement marqués et
désactivés), accessibilité (`aria-disabled`, raisons lisibles, tiroirs `&lt;details&gt;` navigables au
clavier), mobile (colonne unique, tiroirs pour les deux panneaux latéraux, testé au viewport
390×844), sécurité (`ProjectBrainController::show()` garde `ProjectService::canView` sur le projet
courant et sur chaque projet listé dans la colonne gauche ; `MissionVisibilityService::canViewMission`
filtre les Missions affichées).

**Référence visuelle** : maquette fournie par le demandeur le 20 août 2026 (transmise en
conversation, non archivée dans `docs/design/reference/`, reproduite dans
`resources/views/projects/brain.blade.php` et `resources/css/project-brain.css`).

## 21. Addendum daté — Portail Besoins (20 août 2026)

**Portée : l'écran `/besoins` uniquement** (`resources/views/needs/index.blade.php`,
`app/Http/Controllers/NeedController.php::index()`,
`app/Application/Needs/NeedDirectoryDemoContent.php`, `resources/css/needs-directory.css`). Ce
changement suit la procédure de gouvernance du §14. `needs.show`, `needs.create` et les routes
satellites (commentaires, partages, missions liées à un besoin) ne sont pas concernés.

**Invariant concerné** : §11 (fixtures « Exemple », centralisées, jamais persistées) et §18
(DEMO-FIRST, REAL-DATA-TAKES-OVER, déjà appliqué à `/projets`), étendus ici à un troisième écran.

**Problème utilisateur justifiant le changement** : une maquette a été fournie pour le portail
Besoins, avec une carte d'exemple (« Apprendre le forex ») déjà présente dans la maquette aux
côtés d'un besoin réel. Vérification effectuée avant implémentation : cette chaîne n'existe nulle
part dans le dépôt (ni vue, ni fixture, ni seeder) — ce n'est donc pas une dette existante à
corriger, mais un contenu à introduire comme démonstration, suivant exactement le même patron que
« GAMAD Technology » sur `/projets` (§18).

**Ce qui change** :
- Le portail Besoins peut désormais afficher une carte d'exemple (« Apprendre le forex », voir
  `resources/design-reference/needs-demo.json`), marquée **« · Exemple »** sur son badge de
  catégorie, **uniquement lorsqu'aucun besoin réel visible n'existe pour la catégorie de la
  carte** et seulement en première page — dès qu'un besoin réel existe pour cette catégorie
  (`TRAINING` pour cette carte), elle disparaît d'elle-même
  (`NeedDirectoryDemoContent::demoCards()`), et n'est jamais écrite dans `dg_needs` ni seedée
  (`database/seeders/DatabaseSeeder.php` reste intentionnellement vide) ;
- ses actions (« Comprendre le besoin → », signet) restent visuellement présentes mais
  désactivées, avec la raison accessible « Objet de démonstration — aucune action réelle n'est
  rattachée. » (conforme au §13) ;
- **« Aperçu des besoins »** (besoins ouverts/en attente/pourvus) est en revanche un **calcul
  réel**, pas une projection : `NeedController::index()` tallie les statuts réels
  (`OPEN`/`PROPOSED`/`RESOLVED`) sur l'ensemble des besoins visibles de l'identité, indépendamment
  des filtres appliqués à la liste — le modèle permettait déjà ce calcul, la règle §12 de la
  demande produit (« préférer systématiquement un calcul réel lorsqu'il est disponible ») a été
  suivie plutôt que d'en faire une projection comme les statistiques réseau de `/projets` (§18) ;
- l'action « signet » (sauvegarder un besoin) apparaît visuellement sur toutes les cartes,
  réelles et de démonstration, mais reste désactivée avec sa raison sur les deux : aucune
  fonctionnalité de favoris n'existe aujourd'hui pour aucun objet du portail (vérifié avant
  implémentation — aucun modèle, migration ou route ne l'implémente) ;
- le tri (« Trier par ») et la bascule grille/liste sont présentés visuellement (fidélité à la
  maquette) mais désactivés avec leur raison : aucun contrat de tri autre que « plus récents »
  (déjà le comportement réel par défaut) ni de vue liste n'existe aujourd'hui.

**Ce qui ne change pas** : le formulaire de filtre réel (`method="GET" class="dg-filters"`,
catégorie + état, bouton « Filtrer ») garde exactement son contrat et son test existants ; aucune
capacité métier de `NeedController` n'a été retirée. Les tags multiples visibles sur la carte de
démonstration ne sont **pas** reproduits sur les cartes réelles : `Need` n'a pas de champ de tags
multiples (seul `capability_label`, un champ unique optionnel, existe et s'affiche comme tel) —
fabriquer plusieurs tags pour un besoin réel aurait été une donnée inventée, écart assumé et
documenté plutôt qu'une fausse fonctionnalité (voir `docs/design/DIFFERENCES.md`).

**Compatibilité vérifiée** : doctrine (démonstration jamais confondue avec du réel — badge
« · Exemple » + actions désactivées avec raison, exactement le contrat de `/projets`),
accessibilité (`aria-disabled`, raisons lisibles au clavier/lecteur d'écran), mobile (filtres
empilés, cartes pleine largeur, panneau « Aperçu des besoins » repositionné dans le flux, testé au
viewport 390×844), sécurité (aucune nouvelle surface serveur, `NeedController::index()` garde
exactement `NeedService::canView` existant sur chaque besoin).

**Référence visuelle** : maquette fournie par le demandeur le 20 août 2026 (transmise en
conversation, non archivée dans `docs/design/reference/`, reproduite dans
`resources/views/needs/index.blade.php` et `resources/css/needs-directory.css`).
