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

## Périmètre non touché après la Phase 2

Connexion, Messages, Partages, Commentaires, `projects.autonomy.*`,
`zumra.payment-status`/`zumra.receipt`/`zumra.card-verification` et Administration conservent
l'ancienne charte (`--navy`/`--ocean`/`--cyan`…). Ces écrans n'étaient pas dans le périmètre
prioritaire des Phases 1 et 2 (cf. instructions de chantier, ordre P1→P6, « Messages/commentaires/
partages/écrans secondaires » explicitement classés après, Administration explicitement hors
priorité).
