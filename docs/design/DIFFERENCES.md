# Différences inévitables entre le handoff et le métier réel

**Statut :** notes d'implémentation du chantier « design identity reset », Phase 1 (Landing, Mon
espace, Fil d'action, navigation, design system) et Phase 2 (Hub ZUMRA, Besoins, Projets,
Personnes/Profil, fiches ZUMRA).
**Référence :** `docs/design/DESIGN-INVARIANTS.md`, `docs/design/reference/claude-2026-08-16/`.

Le handoff Claude est une matière de design ; l'implémentation ci-dessous se branche sur les
routes, services et permissions réellement disponibles (CAP-003 → CAP-022). Chaque écart
documenté ici respecte la règle : *conserver l'emplacement visuel de l'action, ne jamais lui
attribuer un faux comportement, documenter le contrat manquant.*

## Fil ZUMRA — carte Besoin

- **« Je peux aider »** est la seule action de contact réelle (`messages.need`, POST), affichée
  uniquement pour un besoin dont le membre n'est pas déjà décisionnaire (`NeedService::canDecide`).
- **« Je veux apprendre »** reste visible mais désactivé (`aria-disabled`, raison affichée au
  survol) : `CapabilityStatement` distingue bien `KIND_POSSESSED` / `KIND_LEARNING`, mais aucune
  action métier ne relie encore cette intention à un besoin précis — un seul canal de contact
  générique existe aujourd'hui.
- **« Contacter »** n'a pas été dupliqué séparément : il aurait pointé vers la même conversation
  que « Je peux aider ». Afficher deux boutons identiques aurait été moins honnête qu'un seul,
  correctement nommé.

## Fil ZUMRA — carte Projet

- **« Voir le projet »**, **« Commenter »** et **« Partager avec contexte »** sont réels.
- **« Participer »** et **« Contacter le porteur »** restent désactivés pour tout membre qui ne
  gère pas déjà le projet : `MessagingService::openProject()` exige `ProjectService::canDecide()`,
  réservé aux gestionnaires. Aucune action de participation n'existe aujourd'hui pour un membre
  extérieur — seuls les commentaires et le partage avec contexte lui sont ouverts.

## Fil ZUMRA — carte ZUMRA

- **« Découvrir cette ZUMRA »** et **« Demander à rejoindre »** sont réels
  (`zumra.groups.show`, `zumra.groups.request`).
- **« Contacter un responsable »** n'apparaît que pour un membre déjà actif du groupe
  (`MessagingService::openZumra()` l'exige) ; les non-membres voient « Demander à rejoindre » à la
  place, et une demande déjà en cours affiche un état neutre plutôt qu'un bouton redondant.
- **« Partager avec contexte »** reste désactivé : CAP-022 (`ContextShareController`) ne couvre
  aujourd'hui que les besoins et les projets, pas les ZUMRA.

## Fil ZUMRA — colonne latérale

- **« Personnes à rencontrer »** est réel, réutilise `PersonRecommendationEngine` (le même moteur
  que la page Recommandations existante).
- Le bloc **« Vous pouvez agir »** du handoff (comptage personnalisé de besoins qui touchent les
  capacités déclarées) n'a pas été reproduit : aucun moteur de correspondance besoin↔capacité
  n'existe aujourd'hui (contrairement aux projets, où `ProjectMatchingEngine`/CAP-014 existe).
  L'inventer aurait été un moteur de décision fictif, explicitement interdit par
  `DESIGN-INVARIANTS.md` §7.
- Le bloc « Apprentissages et transmissions » reprend tel quel le texte du handoff : le handoff
  documente déjà lui-même ce contrat manquant (CAP-005/CAP-006), rien n'a été ajouté.
- Les cartes Besoin/Projet du Fil n'affichent pas systématiquement l'auteur ou le porteur
  (avatar + nom) : `ActivityFeedService` n'expose pas cette donnée aujourd'hui ; l'ajouter
  demanderait une jointure supplémentaire hors du périmètre de ce chantier.

## Mon espace — priorité du jour

Le handoff illustre un scénario précis (« deux personnes ont proposé leur aide depuis votre
dernière visite »), qui suppose un suivi de lecture des contributions reçues — inexistant
aujourd'hui. La priorité réelle est déduite, dans cet ordre, de ce qui est réellement disponible :

1. un besoin **proposé par ce membre à une ZUMRA** et encore `PROPOSED`, en attente d'une décision
   des responsables (`owner_type=GROUP`, `author_core_reference=membre`) — **pas** un besoin
   personnel : `NeedService::create()` ouvre toujours un besoin `PERSON` directement en `OPEN`, il
   n'existe donc aucun « besoin personnel proposé » à afficher ici ;
2. un projet personnel `ADOPTED` non démarré ;
3. une adhésion ZUMRA `PENDING_PAYMENT` ;
4. le premier événement pertinent du Fil qui concerne le membre ;
5. l'**absence totale de profil** — inviter à en commencer un ;
6. sinon, l'état vide honnête (« rien ne réclame une décision maintenant »).

**Le consentement d'orientation et la complétion du profil ne pilotent plus la priorité.** Le
consentement d'orientation (`orientation_consent`) est volontaire et révocable à tout moment
(CAP-004) : un refus ou un retrait ne rend jamais le profil « incomplet » aux yeux de Mon espace et
ne déclenche jamais l'invitation « continuer votre profil ». Dès qu'un profil existe, sa complétion
reste un indicateur purement informatif (affiché dans le repère « Compte vérifié · profil X % »),
sans jamais devenir une priorité imposée.

## Navigation

- La recherche du bandeau (« Rechercher une capacité, un besoin… ») reste un élément non
  interactif (`aria-disabled`), exactement comme sur l'ancienne landing : aucune recherche
  publique n'existe encore côté métier.
- **« Mes outils »** ne contient qu'un seul satellite réel : GamaDrive, via la passerelle fédérée
  existante (`federation.continue.gamadrive`). Les futurs satellites restent un espace réservé,
  visuellement présent et non cliquable.
- Le geste mobile **« Proposer une transmission »** pointe vers l'édition complète du profil
  (`member.profile.edit`) : il n'existe pas de formulaire dédié à la seule déclaration de
  transmission.
- Le geste mobile **« Partager avec contexte »** reste désactivé dans la feuille « Agir » : un
  partage exige toujours un besoin ou un projet cible précis, il n'existe pas de composeur
  générique.

## Landing

Les deux cartes « en ce moment » (« Un besoin en ce moment », « Un projet qui avance ») sont des
fixtures d'exemple explicitement annoncées par le mot **« Exemple »**, issues de
`resources/design-reference/demo-content.json` (copie exacte des fixtures du handoff). Conforme à
`DESIGN-INVARIANTS.md` §11 : jamais seedées, jamais présentées comme une donnée réelle, actions
désactivées avec la mention « créez votre compte pour voir les [besoins|projets] réels ».

## Phase 2 — Hub ZUMRA (`/zumra`)

- L'ancien écran simulait un « Fil ZUMRA » autonome (composeur désactivé, colonnes « Profils à
  suivre » / « Projets tendances » sans données réelles). Il est remplacé par un Hub ZUMRA :
  adhésion, Mes ZUMRA, Découvrir des ZUMRA, Proposer une ZUMRA, Ma Carte ZUMRA, demandes à décider.
  **« Voir les activités ZUMRA »** pointe vers `activity.index?type=ZUMRA` — le Fil global filtré,
  jamais un second fil (CAP-019). Aucune donnée composée n'est plus affichée sans base réelle.
- **« Proposer une ZUMRA »** reste visible mais désactivé, avec sa raison, pour un membre dont
  l'adhésion au Programme ZUMRA n'est pas `ACTIVE` (`ZumraGroupController::requireActiveProgramMembership`).

## Phase 2 — Besoins, Projets

- Les écrans liste/fiche/création de Besoins et Projets reprennent le langage visuel des cartes du
  Fil (`x-dg.badge`, `x-dg.card`, `x-dg.actions`) sans réutiliser littéralement
  `x-dg.feed.need`/`x-dg.feed.project` : ces composants attendent la forme `$item` produite par
  `ActivityFeedService` (occurred_at, action_url, contact_url…), absente d'un `Need`/`Project` lu
  directement. Le contrat visuel (badge de catégorie, titre, résumé, actions) est identique.
- La fiche Projet utilise désormais `x-dg.stagewalk` (8 repères complets de
  `ProjectMaturityService::STAGES`) plutôt que le bandeau compact `x-dg.maturity` (6 segments) du
  Fil : la fiche détail a la place d'afficher le chemin complet ; la carte Fil reste compacte.
- **« Je veux apprendre »** n'apparaît toujours pas sur la fiche Besoin (aucune régression) : ce
  contrat métier manquant est documenté plus haut (§ Fil ZUMRA — carte Besoin) et reste inchangé.
- Le parcours d'autonomie (`projects.autonomy.*`) n'a pas été repris cette phase : il n'était pas
  listé dans le périmètre prioritaire (P4) et reste dans l'ancienne charte (`--navy`/`--ocean`…).

## Phase 2 — Personnes / Profil

- L'édition de profil progressive (`member/profile.blade.php`) conserve exactement son contrat
  JavaScript (`data-profile-steps`, `data-profile-step-target`, `data-profile-next/previous`,
  `resources/js/app.js`) : seule l'habillage visuel change (`x-dg.fieldset`, `.dg-steps`,
  `.dg-field`/`.dg-input`/`.dg-textarea`/`.dg-consent`, nouveaux cette phase dans `dg.css`).
- Les recommandations et la découverte de personnes affichent les raisons telles que produites par
  `PersonRecommendationEngine`/`PeopleDiscoveryController` : une liste de phrases, jamais un score
  ni un pourcentage de correspondance (CAP-011).

## Phase 2 — Fiches ZUMRA

- La gouvernance fondatrice (cinq responsabilités) utilise le nouveau composant `x-dg.seat` : un
  siège vacant reste explicitement vacant (`Aucune personne nommée` → `Siège vacant`), jamais
  complété par un profil fictif ni un matching automatique.
- `zumra.payment-status`, `zumra.receipt` et `zumra.card-verification` n'ont pas été repris cette
  phase : les deux premiers sont des écrans secondaires de retour de paiement ; le troisième est
  intentionnellement **public et non authentifié** (vérification signée d'une Carte ZUMRA) et ne
  doit de toute façon jamais recevoir la coquille membre (`x-dg.shell`) — il reste dans son propre
  gabarit minimal, charte incluse ou non selon un chantier ultérieur.

## Portail Projets — refonte visuelle du 20 août 2026 (addendum §18)

- Le formulaire de filtre (`method="GET" class="dg-filters"`, sélecteur de domaine, bouton
  « Explorer ») reste exactement le contrat existant et testé
  (`DesignInvariantsPhase2Test::test_besoins_projets_personnes_filter_forms_actually_submit`).
- La recherche par mot-clé, le tri et le bascule grille/liste ajoutés par la maquette n'ont pas de
  contrat métier aujourd'hui (aucune recherche indexée, le tri « Récents » est déjà le
  comportement par défaut de `Project::query()->latest()`, aucune vue liste n'existe) : ils restent
  visuellement présents mais désactivés (`disabled`, `title` expliquant pourquoi), jamais une
  fausse mutation Core — même discipline que la recherche du bandeau (`dg-topbar__search`).
- La progression de carte de la maquette (barre + pourcentage, ex. « 45 % ») n'a **pas** été
  reproduite littéralement : `ProjectMaturityService`/CAP-017 interdit explicitement d'afficher la
  maturité comme une note ou un pourcentage (voir le commentaire de `x-dg.maturity` et
  `DesignInvariantsPhase2Test::test_project_maturity_stagewalk_shows_all_eight_stages_not_a_percentage`).
  Chaque carte réutilise `x-dg.maturity` (repères en pointillés) accompagné du **libellé du repère
  courant** (« Prototype / expérimentation », etc.) plutôt qu'un chiffre.
- Le visuel de carte de la maquette (photographie par projet) n'a pas été reproduit : DG Afrique
  n'a pas de fichier image par domaine et `DESIGN-INVARIANTS.md` §9 interdit une banque d'images
  générique pour simuler une réalité qui n'existe pas encore. Chaque carte affiche à la place un
  aplat de teinte fonctionnelle + une icône de domaine (`x-dg.icon`), dans le même langage que le
  reste du design system.
- **Cartes de démonstration** : en l'absence de projet réel visible pour un domaine donné, le
  portail affiche jusqu'à trois cartes d'exemple (dont « GAMAD Technology ») issues de
  `resources/design-reference/projets-demo.json` via `ProjectDirectoryDemoContent`, sous la règle
  **DEMO-FIRST, REAL-DATA-TAKES-OVER** déjà appliquée au Fil V2 (§17) — appliquée ici domaine par
  domaine plutôt qu'à l'échelle de tout l'écran, pour rester fidèle au scénario demandé (« si la
  base ne contient que GAMAD Technology, les autres cartes restent des projections »). Chaque carte
  porte le suffixe **« · Exemple »**, toutes ses actions sont désactivées avec la raison accessible
  « Objet de démonstration — aucune action réelle n'est rattachée. », et elle disparaît dès qu'un
  projet réel existe pour son domaine. Ceci **modifie** le comportement de
  `DesignInvariantsPhase2Test::test_projets_directory_shows_an_honest_empty_state`, qui vérifie
  désormais l'état vide honnête sur un domaine qu'aucune carte réelle ni d'exemple ne couvre
  (`?domain=HEALTH`) plutôt qu'à la racine `/projets` — changement assumé et documenté, pas
  silencieux, couvert par `tests/Feature/ProjectsDirectoryDemoTest.php`.
- Le panneau « Aperçu de la communauté » (28 projets proposés, 156 membres impliqués…) est un
  agrégat de démonstration du même fichier, annoncé **« · Exemple »** — jamais un agrégat Core réel
  (aucune requête d'agrégation métier n'existe aujourd'hui pour ces quatre compteurs réseau).
- Le bandeau inférieur (« Explorez les domaines », « Trouvez des capacités », « Passez à
  l'action », « Ici, chaque idée compte ») pointe vers des routes réelles existantes (l'ancre vers
  la barre de filtre, `people.index`, `projects.brain.start`, `activity.index`) : aucune destination
  fictive.

## Dossier Projet / Vue d’ensemble — refonte visuelle du 20 août 2026 (addendum §19)

- Les onglets internes de la maquette (Activités/Équipe/Besoins/Ressources/Documents/
  Conversations) n'ont pas de route dédiée séparée aujourd'hui : ce sont des ancres réelles
  (`#dg-project-activite`, `#dg-project-equipe-detail`, etc.) vers des sections déjà présentes sur
  la page « Vue d'ensemble ». Rien n'a été fabriqué : cliquer sur un onglet mène toujours à un
  contenu réel, jamais à un lien mort ou à une page vide.
- Le bandeau horizontal de maturité réutilise `x-dg.stagewalk` sans modifier son DOM ni sa classe
  testée — seule `resources/css/project-detail.css` change la disposition (ligne + marqueurs
  circulaires en rang, au lieu d'une liste verticale), et uniquement à partir de 900px ; sous ce
  seuil la présentation d'origine du composant reste inchangée. Toujours aucun pourcentage
  (CAP-017).
- « Progression globale » (pourcentage affiché en colonne latérale) **n'est pas** un calcul
  métier : c'est une projection d'affichage dérivée de `crc32($project->id)`, jamais persistée,
  annoncée « · Projection ». Elle est volontairement indépendante du repère de maturité (qui, lui,
  reste gouverné par CAP-017) pour ne jamais laisser croire à un second calcul de maturité déguisé.
- « Documents & Preuves » est un état honnête nouveau : aucun modèle ne relie de document à un
  projet aujourd'hui (`Proof::ORIGIN_PROJECT` existe mais sert uniquement à construire le pool
  d'acquittement personnel d'un porteur, pas une liste de documents par projet). Le bouton
  « Ajouter un document » reste désactivé avec sa raison (espace GamaDrive non relié).
- « Activité récente » est en revanche entièrement réelle : `ProjectEvent::EVENT_LABELS`
  (nouvelle constante sur le modèle) traduit les codes d'événements déjà journalisés
  (`ProjectService`, `ProjectMaturityService`, `ProjectTeamService`, `ProjectAccompanimentService`,
  `ProjectSatelliteLauncherService`) en libellés lisibles ; l'acteur est affiché via son
  `discovery_display_name` ou « Membre DG Afrique » (anonymat assumé). « Voir toute l'activité → »
  reste désactivé, faute de journal dédié au-delà des six derniers événements affichés.
- « Suivre » (en-tête) et « Suivre les mises à jour » (actions rapides) restent désactivés : aucun
  système de notification par objet n'existe aujourd'hui pour un projet.
- L'« Espace porteur » (invitation par référence publique) et la gestion complète de l'équipe
  (demander/inviter/accepter/quitter/retirer, approbation des demandes en attente) sont
  entièrement conservés et fonctionnels — seulement redisposés : un aperçu compact (avatars +
  « Voir toute l'équipe → ») apparaît dans le flux principal, la gestion détaillée reste sur la
  même page, ancrée par l'onglet « Équipe ».

## Cerveau du Projet — refonte visuelle du 20 août 2026 (addendum §20, PVB-I05 V1)

- L'écran rejoint `x-dg.shell` (navigation globale réelle) et perd le bandeau de contournement
  `.dg-global-nav` ainsi que son propre `.pw-top`/`.pw-bottom` : ces deux navigations dupliquaient
  déjà la navigation réelle et sont supprimées plutôt que fusionnées.
- La colonne « Projets & conversations » liste désormais les vrais projets accessibles de l'acteur
  (même filtrage `ProjectService::canView` que `/projets`) au lieu d'un unique projet réel complété
  par une liste fabriquée non marquée (`Coopérative Maraîchère`, `Transformation Manioc`, etc. dans
  l'ancienne version). Les sous-conversations par projet (« Financement & Apports », « Équipement &
  Matériel »…) ont été retirées : aucun modèle ne les représente aujourd'hui — une seule
  `ProjectBrainConversation` existe par (projet, acteur) — et le conserver aurait maintenu un seed
  conversationnel alors qu'une vraie conversation existe déjà, contraire à la demande produit.
- « Missions (N) » est un nouveau bloc entièrement réel (`Mission::where('context_type','PROJECT')`,
  filtré par `MissionVisibilityService::canViewMission`) — l'ancienne version affichait deux
  Missions fictives non filtrées et non marquées.
- « Prochain jalon » devient réel (le prochain `ProjectMilestone` non complété), remplaçant un
  texte fixe « Validation local · dans 5 jours » sans aucune donnée réelle sous-jacente.
- « Avancement » (pourcentage) reste une projection d'affichage, mais désormais calculée par
  `Project::progressionSeed()` — la même formule que « Progression globale » sur la fiche projet
  (`/projets/{project}`, §19) — pour que les deux écrans montrent le même chiffre pour un même
  projet, plutôt que deux nombres fixes différents (35 % ici, 45 % sur la fiche) qui auraient laissé
  croire à deux calculs distincts.
- La carte d'exemple « Créer une équipe projet » (illustrant une action en attente de validation)
  est conservée uniquement dans l'état vide de la conversation, avec ses boutons explicitement
  `aria-disabled` et leur raison — l'ancienne version les affichait en permanence dans le fil réel,
  avec des `<button type="button">` sans aucune action, ambigus quant à leur fonctionnalité.
- « Projets archivés » affiche désormais un compte réel (filtré par visibilité) plutôt qu'un
  chiffre fixe (« 8 » dans l'ancienne version) ; reste désactivé avec sa raison tant qu'aucune vue
  dédiée n'existe pour parcourir ces projets.
- « Preuves récentes » et « Opportunités pour vous » restent des projections métier explicitement
  annoncées « · Exemple », avec leurs actions désactivées et leur raison — conformément à la
  philosophie « seeds de projection produit » explicitement demandée pour ces deux blocs
  spécifiquement (aucun module Documents/Preuves ni Opportunités n'existe encore pour un projet).

## Portail Besoins — refonte visuelle du 20 août 2026 (addendum §21)

- La carte d'exemple « Apprendre le forex » visible dans la maquette n'existait nulle part dans le
  dépôt avant ce chantier (vérifié par recherche exhaustive) — ce n'est pas une donnée
  précédemment présente à conserver, mais une nouvelle carte de démonstration introduite en
  suivant le patron déjà établi par `projets-demo.json` (§18) : `resources/design-reference/needs-demo.json`,
  chargée par `NeedDirectoryDemoContent`, jamais lue directement par la vue.
- Les tags multiples affichés sur la carte de démonstration (« Formation », « Finance »,
  « Développement personnel ») ne sont **pas** reproduits sur les cartes de besoins réels : le
  modèle `Need` n'a qu'un seul champ optionnel de ce type (`capability_label`), pas une liste de
  tags. Une carte réelle affiche donc au plus un seul tag (si `capability_label` est renseigné),
  jamais trois tags inventés — différence visuelle assumée plutôt qu'une donnée fabriquée.
- Le bouton signet (bookmark) est visuellement présent sur toutes les cartes mais désactivé avec
  sa raison (« La sauvegarde de besoins favoris arrivera prochainement. ») : aucune fonctionnalité
  de favoris n'existe aujourd'hui dans le dépôt, pour aucun objet (recherche exhaustive effectuée
  avant implémentation — aucun modèle, migration ou route).
- « Aperçu des besoins » (besoins ouverts/en attente/pourvus) est un calcul réel effectué par
  `NeedController::index()` sur l'ensemble des besoins visibles de l'identité (`NeedService::canView`),
  indépendant des filtres appliqués à la liste principale — pas une projection comme les
  statistiques réseau de `/projets` (§18), le modèle le permettant déjà.
- « Trier par » (fixé sur « Plus récents », déjà le comportement réel par défaut) et la bascule
  grille/liste restent visuellement présents mais désactivés avec leur raison, faute de contrat de
  tri alternatif ou de vue liste implémentés aujourd'hui.
- Le formulaire de filtre réel (catégorie + état, `method="GET" class="dg-filters"`, bouton
  « Filtrer ») est inchangé dans son contrat et positionné dans le même conteneur visuel que les
  contrôles projetés (tri, bascule vue), sans jamais être imbriqué dans un même `<form>` qui
  soumettrait des champs sans contrat métier.

## Périmètre non touché après la Phase 2

Connexion, Messages, Partages, Commentaires, `projects.autonomy.*`,
`zumra.payment-status`/`zumra.receipt`/`zumra.card-verification` et Administration conservent
l'ancienne charte (`--navy`/`--ocean`/`--cyan`…). Ces écrans n'étaient pas dans le périmètre
prioritaire des Phases 1 et 2 (cf. instructions de chantier, ordre P1→P6, « Messages/commentaires/
partages/écrans secondaires » explicitement classés après, Administration explicitement hors
priorité).
