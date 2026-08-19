# DG Afrique — Handoff Fil V2

Version 1.0 — 19 août 2026. Gouverné par `docs/design/DESIGN-INVARIANTS.md` §17
(Addendum daté — Fil V2). Ce document est le handoff final, corrigé, de la maquette
Fil V2 fournie le 19 août 2026 : la composition, la densité, les proportions et
l'identité visuelle de la maquette sont conservées telles quelles ; seuls les
écarts fonctionnels documentés ci-dessous ont été corrigés avant implémentation.

Implémentation de référence : `resources/views/activity/index.blade.php`,
`resources/views/components/dg/feed/*.blade.php`, `resources/css/fil-v2.css`,
`app/Http/Controllers/ActivityFeedController.php`.

## 1. Nature du Fil

Le Fil DG Afrique est un **fil d'action**, jamais un réseau de popularité. Chaque
carte répond dans l'ordre à :

1. **Pourquoi ceci m'est montré** (badge d'événement, contexte, auteur/lieu).
2. **Que puis-je faire maintenant** (une action primaire évidente, puis des
   actions secondaires).

Aucune mécanique de like, d'abonné, de classement ou de « trending » n'existe
nulle part sur cet écran (`tests/Feature/FilV2Test.php::test_fil_never_shows_popularity_mechanics`,
`tests/Feature/DesignInvariantsPhase2Test.php::test_besoins_projets_personnes_zumra_never_show_popularity_mechanics`).

## 2. Anatomy desktop (≥ 1024px)

Grille trois colonnes (`.dg-fil-grid`, ≥ 1024px) :

```
[ rail gauche 252px ] [ centre flexible ] [ rail droit 296px ]
```

- Largeur totale : `min(1560px, calc(100% - 40px))`, centrée.
- `gap`: 28px entre colonnes.
- Rail gauche et droit : `position: sticky; top: 24px`.

**Rail gauche** (`.dg-fil-rail`), de haut en bas :

1. « Filtrer le fil » — liste des 4 filtres réels (Tout/Besoins/Projets/ZUMRA),
   l'actif en fond pétrole plein.
2. « Mes ZUMRA » — seulement si l'identité a des adhésions actives (données réelles).
3. « Envie de contribuer ? » (`.dg-fil-contribute`) — trois intentions pédagogiques :
   Je peux aider / J'ai un besoin / Je veux apprendre (voir §5).
4. Bandeau doctrinal « Le fil s'arrête » (`.dg-band`, inchangé de la V1).

> Correction du 19 août 2026 (suite au retour post-déploiement) : le bouton
> « + Publier une action » du rail gauche a été retiré — redondant avec le
> composeur du centre, déjà visible sans interaction supplémentaire. Sur
> mobile, le composeur reste la toute première section visible de `<main>`
> (avant les filtres horizontaux et le flux) ; les deux rails restent
> entièrement masqués (`hidden lg:flex`, jamais affichés au-dessus ou en
> mélange avec le flux) en dessous de 1024px.

**Centre** (`<main>`) :

1. `<h1 class="sr-only">` — titre accessible, invisible (la maquette ne montre
   pas de gros titre à cet endroit : composition respectée).
2. Composeur (`.dg-fil-composer`, voir §4).
3. Barre d'outils (`.dg-fil-toolbar`) — filtres horizontaux (mobile) + tri
   « Les plus récents » et bascule « Voir uniquement mes suivis » (desktop,
   visuellement présente, désactivée avec raison — CAP de suivi non livré).
4. Flux (`.dg-feed`) — cartes d'exemple (si applicables, §9) puis cartes réelles,
   triées par `priority` puis `occurred_at` (logique héritée de
   `ActivityFeedService`, non modifiée).
5. Pagination réelle (`$feed->links('pagination.dg')`) puis message de fin de
   fil (§10).

**Rail droit** :

1. « Ce qui compte » (`.dg-fil-matters`, voir §6).
2. « Personnes à rencontrer » (`x-dg.card`, inchangé de la V1, données réelles).
3. Bandeau « Apprentissages et transmissions » (`.dg-band`, inchangé de la V1).

## 3. Anatomy mobile (< 1024px)

Une seule colonne. Rails gauche/droit masqués (`hidden lg:flex`) : leurs
contenus n'ont pas d'équivalent mobile dédié dans cette itération — le composeur
et les filtres horizontaux du centre couvrent les mêmes intentions.

1. En-tête mobile partagé (`dg-mobilebar`, composant `x-dg.shell`, inchangé).
2. Composeur (`.dg-fil-composer`) — même structure qu'en desktop ; les
   « types » (`.dg-fil-composer__types`) défilent horizontalement
   (`overflow-x: auto`) plutôt que de passer à la ligne.
3. Filtres horizontaux (pills, `overflow-x: auto`).
4. Flux — mêmes cartes qu'en desktop, `.dg-actions` empile les boutons en
   pleine largeur (`flex: 1`, hérité de `dg.css` `@media (max-width: 850px)`).
5. Barre de navigation mobile (`x-dg.tabbar`, inchangée : Fil · Personnes ·
   Agir · ZUMRA · Moi) + feuille AGIR (`x-dg.agir-sheet`).

## 4. Composeur — corrections appliquées (§10 de la demande)

`.dg-fil-composer` (carte blanche, `border-radius: var(--dg-radius-card)`,
`padding: 22px 24px`) :

- **En-tête** : avatar (initiale de l'identité connectée) + texte d'invite
  « Quoi de neuf dans le réseau ? ».
- **Types** (`.dg-fil-composer__types`) — cinq entrées, mappées **uniquement**
  vers des routes métier réelles ou désactivées avec raison :

  | Type | État | Destination / raison |
  |---|---|---|
  | Besoin | actif | ancre vers le formulaire d'expression de besoin, intégré juste en dessous (`needs.store`, existant, aucune logique modifiée) |
  | Projet | actif | `route('projects.create')` |
  | ZUMRA | actif | `route('zumra.groups.create')` |
  | Mission | **désactivé** | « Une Mission se crée depuis un Projet, une ZUMRA ou un Besoin existant. » (aucune route de création autonome — `routes/cap069.php` n'expose que `projects.missions.create`, `zumra.groups.missions.create`, `needs.missions.create`) |
  | Événement | **désactivé** | « Aucun objet Événement n'existe encore dans le produit. » (la maquette le montre ; aucun objet métier Événement n'existe — non transformé en fonctionnalité) |

- Le formulaire d'expression de besoin existant (titre, contexte, catégorie,
  image, soumission) est conservé **sans modification de logique** sous les
  types, comme avant la refonte.

## 5. Rail gauche — « Envie de contribuer ? »

Trois intentions, toutes vers des routes réelles (jamais des scores ou profils
humains) :

| Intention | Destination |
|---|---|
| Je peux aider | `route('needs.index')` — parcourir les besoins ouverts |
| J'ai un besoin | `route('needs.create')` |
| Je veux apprendre | `route('transmissions.create')` (le formulaire y permet de choisir le rôle « J'apprends ») |

Ces trois intentions restent des raccourcis génériques d'entrée dans le
produit : elles ne sont **jamais** transformées en compteur, score ou
classement de personnes.

## 6. Rail droit — « Ce qui compte » (correction obligatoire, §8 de la demande)

Le bloc chiffré fictif (« 25K+ membres / 1 200+ projets lancés / 45 pays
connectés ») présent sur la maquette source a été **supprimé** du Fil : ces
chiffres n'existent que sur la Landing (`landing-portal-demo.json`, marqués
Exemple, hors périmètre du Fil). Remplacé par :

**Ce qui compte** (`.dg-fil-matters`) — quatre lignes fixes, aucune donnée
dynamique :

1. Agir utilement — Chaque action compte, même petite. (icône `handshake`)
2. Collaborer simplement — Travaillons ensemble, sans classement. (icône `people`)
3. Transmettre — Partageons pour multiplier l'impact. (icône `rocket`)
4. Respect & dignité — Un réseau humain avant tout. (icône `heart`)

## 7. Actions par type de carte

Chaque carte réelle réutilise les composants `x-dg.feed.*` déjà conformes à la
doctrine (non redessinés — seuls le composeur, les rails et l'habillage
`fil-v2.css` sont nouveaux) :

### Besoin (`x-dg.feed.need`)

| Action | État |
|---|---|
| Je peux aider | active (masquée si l'auteur consulte son propre besoin — `can_decide`) |
| Je veux apprendre | **désactivée** — « L'intention « je veux apprendre » n'est pas encore distinguée par le besoin métier. » |
| Commenter | active |
| Partager avec contexte | active |

### Projet (`x-dg.feed.project`)

| Action | État |
|---|---|
| Voir le projet | active |
| Participer | **désactivée** — « Aucune action de participation n'existe encore pour un membre qui ne gère pas ce projet. » |
| Commenter | active |
| Partager avec contexte | active |
| Contacter le porteur | **désactivée** — « Le contact direct du porteur n'est ouvert qu'aux personnes qui gèrent déjà ce projet. » |

### ZUMRA (`x-dg.feed.zumra`, surface `.dg-deep` pétrole profond)

| Statut du membre | Action secondaire |
|---|---|
| Membre actif | Contacter un responsable (active) |
| Demande envoyée | Demande en cours (**désactivée** — « Votre demande d'adhésion est déjà en cours d'examen par les responsables. ») |
| Non membre | Demander à rejoindre (active) |

« Découvrir cette ZUMRA » toujours active. « Partager avec contexte »
**toujours désactivée** — « Le partage avec contexte ne couvre pas encore les
ZUMRA, seulement les besoins et projets. »

### Mission / Transmission / Preuve / Besoin résolu

`x-dg.feed.mission`, `.transmission`, `.proof`, `.resolved` — même fil global,
jamais un fil autonome. Chaque variante conserve provenance (badge + contexte),
raison de visibilité (revalidée à la projection par
`MissionVisibilityService`/`TransmissionVisibilityService`/`ProofVisibilityService`)
et une action suivante réelle (Commenter/Partager quand disponibles ; « Voir »
toujours actif).

## 8. États actif/désactivé — contrat visuel

- Actif : `<a>` (GET) ou `<form>` (POST/PUT, CSRF inclus) réel — jamais un
  faux bouton (`x-dg.btn`, voir sa documentation interne).
- Désactivé : `<span aria-disabled="true" role="button" title="…">` — la
  raison est **toujours** visible au clavier/lecteur d'écran via `title`,
  jamais seulement en tooltip visuel.
- Style désactivé global : `.dg-btn[aria-disabled='true']` → bordure
  pointillée `var(--dg-line-dashed)`, texte `var(--dg-faint)`,
  `cursor: not-allowed`.

## 9. Règle de contenu de démonstration — DEMO-FIRST, REAL-DATA-TAKES-OVER

Voir `docs/design/DESIGN-INVARIANTS.md` §17 pour la décision de gouvernance
complète (cette règle **révise** l'invariant précédent qui excluait tout
contenu Exemple du Fil connecté).

- Source : `resources/design-reference/fil-demo.json` — trois cartes fixes
  (Besoin/Projet/ZUMRA), reprenant les exemples fournis dans la demande
  (« Former douze jeunes… », « Accès à l'eau potable… », « Réunion
  hebdomadaire du collectif »).
- Déclenchement (`ActivityFeedController::index`) : uniquement **page 1** et
  uniquement si le flux réel du filtre actif est **vide**. Filtre `ALL` → les
  trois cartes ; filtre `NEEDS`/`PROJECTS`/`ZUMRA` → seulement la carte
  correspondante.
- Une carte réelle, même unique, **supprime toutes** les cartes d'exemple du
  filtre courant (jamais de mélange visuel réel/exemple).
- Rendu : `x-dg.feed.demo` — badge « {Type} · Exemple », bordure de carte
  **pointillée** (`.dg-card--demo`) en plus du badge, pour qu'une carte
  d'exemple ne soit jamais confondue visuellement avec une carte réelle même
  hors contexte (capture d'écran isolée, lecteur d'écran).
- **Aucune action câblée** : toutes les actions de ces cartes sont
  désactivées avec la raison « Objet de démonstration — aucune action réelle
  n'est rattachée. » — jamais un faux backend pour satisfaire la maquette.
- Jamais seedées dans les tables métier ; jamais mélangées silencieusement au
  flux réel (`tests/Feature/FilV2Test.php`).

## 10. État vide et fin de fil

- **État vide honnête** (fallback défensif, atteint seulement si
  `fil-demo.json` ne couvrait pas le filtre courant) : bandeau `.dg-deep` +
  « Rien ne bouge encore près de vous. » + `x-dg.empty` + CTA réels
  (Exprimer un besoin / Rejoindre une ZUMRA). Inchangé de la V1.
- **Fin de fil** : dès que `$feed->hasMorePages()` est faux (et le flux réel
  non vide), message centré « Le fil s'arrête ici. Revenez quand une action
  réelle aura avancé. » — jamais de défilement infini.

## 11. Typographie, couleurs, rayons, ombres

Aucun nouveau token : `fil-v2.css` consomme exclusivement les variables
Identity V2 déjà définies dans `resources/css/identity-v2.css` (pétrole
`--dg-petrol`/`--dg-petrol-deep`, orange `--dg-orange`/`--dg-orange-deep`,
ivoire `--dg-ivory-v2`, `--dg-radius-card`/`-control`/`-pill`,
`--dg-shadow-card`) et la typographie déjà en place (`dg-display`, `dg-body`,
`dg-meta`, police Instrument Sans/Serif). Aucune valeur codée en dur en dehors
des cas déjà présents dans les composants `x-dg.feed.*` hérités (dégradés
pétrole `.dg-deep`, teintes de tags).

## 12. Composants (fichiers réels)

| Composant conceptuel | Fichier |
|---|---|
| FeedLayout / FeedLeftRail / FeedRightRail | `resources/views/activity/index.blade.php` (grille `.dg-fil-grid`, pas de composants séparés — la mise en page est propre à cette seule vue) |
| FeedComposer | `.dg-fil-composer` dans `activity/index.blade.php` |
| FeedFilters | `.dg-fil-filters` (rail) + formulaire GET (mobile), même vue |
| NeedFeedCard | `resources/views/components/dg/feed/need.blade.php` |
| ProjectFeedCard | `resources/views/components/dg/feed/project.blade.php` |
| ZumraFeedCard | `resources/views/components/dg/feed/zumra.blade.php` |
| MissionFeedCard | `resources/views/components/dg/feed/mission.blade.php` |
| TransmissionFeedCard | `resources/views/components/dg/feed/transmission.blade.php` |
| ProofFeedCard | `resources/views/components/dg/feed/proof.blade.php` |
| ResolvedFeedCard | `resources/views/components/dg/feed/resolved.blade.php` |
| DemoCard (Besoin/Projet/ZUMRA) | `resources/views/components/dg/feed/demo.blade.php` |
| FeedActions | `resources/views/components/dg/actions.blade.php` + `dg/btn.blade.php` |
| EndOfFeed / EmptyState | inline dans `activity/index.blade.php` (`x-dg.deep`, `x-dg.empty`) |
| MobileFeedHeader | `x-dg.shell` / `dg-mobilebar` (composant sitewide, non dupliqué) |

## 13. Responsive — points de rupture

- `1024px` : bascule 3 colonnes ↔ 1 colonne (`.dg-fil-grid`).
- `850px` (héritée de `dg.css`) : `.dg-actions .dg-btn { flex: 1 }` — actions
  pleine largeur sur mobile.
- Testé fonctionnellement (assertions HTML complètes) aux tailles standard du
  projet (1440×960 desktop, 390×844 mobile) via la suite Feature ; voir §15
  pour la limite de vérification visuelle rencontrée dans cet environnement.

## 14. Écart connu, hors périmètre

En vérifiant l'écran d'erreur, la feuille AGIR (`x-dg.agir-sheet`, composant
partagé site-large, non modifié ici) a été observée avec un lien « Proposer
une transmission » pointant vers `route('member.profile.edit')` au lieu de
`route('transmissions.create')` (la route réelle, confirmée existante et
correctement utilisée par le rail gauche du Fil V2, §5). Ce n'est pas un écart
introduit par le Fil V2 et n'a pas été corrigé ici, car `x-dg.agir-sheet` est
partagé par tout le site — signalé pour correction séparée.

## 15. Visual QA

**Vérifié** : suite Feature complète (`tests/Feature/FilV2Test.php`, 7 tests)
sur le HTML rendu réel — cartes d'exemple marquées, disparition dès donnée
réelle, composeur (routes réelles + raisons de désactivation), rail gauche
(routes réelles), rail droit (« Ce qui compte », absence de chiffres
fabriqués), absence de mécaniques de popularité, message de fin de fil. Suite
complète du projet : 371/371 tests verts après ce chantier.

**Non vérifié visuellement en navigateur dans cette session** : toute page
authentifiée (dont `/activite`) revalide la session à **chaque requête**
auprès de GAMAD Core (`RequireCoreMember` → `GamadCoreClient::currentSession()`).
Le `GAMAD_CORE_BASE_URL` réel (`console.dgafrique.com`) n'est pas joignable
depuis cet environnement de développement (comportement déjà rencontré et
documenté lors du correctif du bug HTTP 500 de la refonte Connexion/Inscription).
Une tentative de contournement (serveur GAMAD Core local factice) a été
engagée mais bloquée par une dérive d'infrastructure locale supplémentaire
(authentification Postgres) hors périmètre de ce chantier et nécessitant un
changement de configuration système qui a été refusé par le classificateur de
permissions de cette session — abandonné au profit de la vérification par
tests, jugée suffisante et plus fiable que des captures d'écran ponctuelles.
**À faire par le prochain développeur ayant accès à un GAMAD Core joignable**
(ou un environnement de dev avec un mock stable) : comparer visuellement
1440×960 et 390×844 à la maquette source pour les critères suivants :

- le centre reste dominant, les rails ne volent jamais l'attention ;
- les cartes ne ressemblent pas à Facebook (pas de like, pas d'avatar rond,
  pas de compteur de popularité) ;
- le CTA métier de chaque carte est évident (couleur pleine, position) ;
- les démonstrations sont clairement marquées (badge + bordure pointillée) ;
- le bouton AGIR reste identifiable (desktop + mobile) ;
- la bottom-nav mobile est identique au reste du site ;
- aucune carte mobile interminable, aucune ambiguïté sur les actions
  disponibles.
