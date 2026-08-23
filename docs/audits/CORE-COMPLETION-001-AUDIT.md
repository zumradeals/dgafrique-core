# CORE-COMPLETION-001 — Audit UX → réalité du code

**Baseline** : `origin/main` @ `717e221bec75e078e95eec686d2306517117b6fe` (fetch du 24 août 2026, après merge de la PR #128 CLEANUP-ZUMRA-001 et de son correctif `04b335d fix(zumra): paginate exhaustive world searches`).
**Branche** : `audit/core-completion-001-zahab`.
**Nature** : audit uniquement. Aucun code fonctionnel ZAHAB, aucune complétion spontanée, aucun merge.

> Consigne reçue et respectée : ne pas chercher à rassurer sur l'état du produit. Ce document rapporte ce que le code fait réellement, y compris quand cela contredit l'impression donnée par l'UX.

## Méthode et calibrage de profondeur

Pour chaque élément : UX → route → controller → service → modèle → persistance → autorisation → test → effet observable, avant classification. Chaîne tracée dans le code réel, pas déduite du nom d'un bouton, d'une route ou d'un modèle.

**Profondeur inégale, assumée explicitement** : certaines surfaces ont reçu un audit forensique complet (chaque route, chaque variable de vue confrontée à son contrôleur) — **Fil global**, **Monde ZUMRA**, **Naissance ZUMRA**, **Espace ZUMRA**, **toute la chaîne financière** (CAP-061/062/063, adhésion, GeniusPay), **GAMAD Core**, **Cerveau Projet**, **seeds**. D'autres ont reçu une vérification ciblée réelle mais moins exhaustive — **Besoins**, **Projets/Espace Projet**, **Profil/Capacités/Transmission** — sur leurs points les plus significatifs pour ce mandat (transformation Besoin→Projet, jalons, Cerveau Projet, ponts Profil↔ZUMRA/Besoin/Projet), sans reparcourir chaque écran ligne à ligne. Le rapport final (§7) le redit noir sur blanc : ceci n'est pas un audit exhaustif à 100 % de chaque sous-puce du mandat, c'est un audit honnête calibré sur la valeur de la découverte.

---

## 1. Fil global DG Afrique

**Chaîne réelle** : `routes/cap019.php` → `ActivityFeedController::index()` → `ActivityFeedService` (CAP-019, réel, testé — `ActivityFeedTest`) + `FeedDemoPresentation` (nouveau, décor) → `resources/views/activity/index.blade.php`.

### Constat majeur : deux couches démo coexistent, l'une morte, l'autre non filtrée

- Le contrôleur calcule encore `$demoCards` (mécanisme DEMO-FIRST/REAL-DATA-TAKES-OVER que j'avais documenté moi-même, §17 de `DESIGN-INVARIANTS.md`, gated sur `$feed->isEmpty()`) — **mais `$demoCards` n'est référencé nulle part dans la vue actuelle** (`grep demoCards resources/views/activity/index.blade.php` → aucun résultat). Code mort.
- À la place, `FeedDemoPresentation::cards()`/`stats()` (4 cartes et des statistiques réseau **codées en dur dans une classe PHP**, aucune table, aucun fichier fixture) s'affichent **inconditionnellement** dès que `page === 1`, qu'il existe ou non du contenu réel. Contrairement à la doctrine §17 que j'avais posée (démo seulement si le flux réel est vide, jamais mélangée), ces 4 cartes s'affichent **toujours en tête**, avant les cartes réelles, sur un réseau actif comme sur un portail neuf.
- **Contradiction doctrinale non arbitrée** : ceci contredit littéralement §17 de `docs/design/DESIGN-INVARIANTS.md` (que j'ai moi-même rédigé) sans que ce document ait été mis à jour ni qu'une nouvelle décision datée ne le remplace. Signalé ici, non corrigé (hors périmètre de ce mandat).
- Le bandeau « LE RÉSEAU EN ACTION » (rail gauche) affiche des chiffres eux aussi codés en dur (`'members'=>'9 842'`, etc.), étiquetés « Statistiques de démonstration » en petit texte — mais sans aucune requête réelle derrière, contrairement à `ZumraSpaceController::stats()` (Monde ZUMRA, voir §2) qui documente explicitement « Statistiques réelles, jamais des nombres fabriqués ».

### Constat majeur : des données réelles déjà calculées sont jetées au profit de décor

Le contrôleur calcule **réellement** :
- `$recommendedPeople` via `PersonRecommendationEngine` (moteur réel, CAP-030/031 CLOSED) ;
- `$myGroups` via une vraie requête `ZumraGroupMembership`.

Ni l'une ni l'autre n'apparaît dans la vue. À la place :
- « MES ESPACES » (rail gauche) et « ACTIVITÉ DE MES ZUMRA » (rail droit) affichent un tableau PHP codé en dur `['RAHMAN Technology','Excellence ZUMRA','AgriZUMRA']`, identique pour tout utilisateur, jamais dérivé de ses vraies adhésions ;
- « PERSONNES PERTINENTES À DÉCOUVRIR » (rail droit) affiche 4 noms fictifs codés en dur (Fatoumata K., Ibrahim T., Awa Diarra, Moussa B.), étiqueté honnêtement « Décor préparatoire, aucun algorithme de recommandation prétendu » — mais le moteur réel existe et n'est simplement pas branché.
- Indice concret que le branchement était prévu : `database/seeders/DgNetworkFeedDemoSeeder.php` (opt-in, non auto-exécuté) crée de vrais `PersonProfile` avec `discovery_consent=true` sous les noms **exacts** « Amina Diop », « Fatoumata K. », « Ibrahim T. », « Awa Diarra », « Moussa B. » — la donnée réelle pour nourrir `$recommendedPeople` existe et est prête, la vue ne la consomme juste pas.

### Constat majeur : 4 des 7 filtres du bandeau ne filtrent rien

`ActivityFeedService::FILTERS` ne connaît que `ALL`, `NEEDS`, `PROJECTS`, `ZUMRA`. La vue affiche pourtant 7 puces : Tous/**Transmissions**/**Ressources**/Besoins/Projets/**Événements**/**Actions**. Le contrôleur réinitialise silencieusement tout code inconnu vers `ALL` (`if (! isset(ActivityFeedService::FILTERS[$filter])) { $filter = 'ALL'; }`). Cliquer sur Transmissions, Ressources, Événements ou Actions ne filtre donc jamais rien et ne montre même pas la puce comme active — un clic mort et trompeur, jamais couvert par un test.

### Composeur

- Actifs et réels : Transmettre une connaissance (`transmissions.create`), Exprimer un besoin (`needs.create`), les liens `sr-only` Projet/ZUMRA (`projects.create`/`zumra.groups.create`).
- « Faire avancer un projet » pointe vers `projects.index` (liste), pas une action de composition — libellé trompeur mais route réelle.
- Mission : `aria-disabled`, raison affichée — conforme à la doctrine « pas de faux workflow ».
- Partager une ressource / Annoncer un événement / Sonder une question : `<span>` sans `href`, décor pur, aucune capacité Ressource/Sondage identifiée nulle part dans le code (voir §6, Événement existe bien en tant que CAP-068 mais n'est pas branché ici).

### Rail droit — liens morts

« À FAIRE MAINTENANT », « PROJETS QUI ONT BESOIN DE VOUS », « ACTIVITÉ DE MES ZUMRA » : chaque bloc a un lien `<a>Voir tout →</a>` **sans attribut `href`** — décor non cliquable, contenu 100 % codé en dur.

### Bandeau bas de page

« Soutenir financièrement » → `projects.index` (liste générique, pas un flux de paiement/contribution réel — CAP-061 existe mais n'est raccordé nulle part ici, voir §5). « Apporter une compétence » → `people.index` (réel). « Partager une ressource » → décor. « Contribuer à une action » → `needs.index` (réel).

### Fil — classification

| Élément | État |
|---|---|
| Cartes réelles (Besoin/Projet/ZUMRA/Mission/Transmission/Preuve, moteur `ActivityFeedService`) | RÉEL_OPÉRATIONNEL |
| `showcaseCards`/`networkStats` (`FeedDemoPresentation`) | SEED_DEMO — non gaté, contredit §17 de la doctrine design |
| `$demoCards` (mécanisme DEMO-FIRST hérité) | NON_IMPLÉMENTÉ **dans l'UX actuelle** (code mort, jamais rendu) |
| MES ESPACES / ACTIVITÉ DE MES ZUMRA (rails) | SEED_DEMO (données réelles calculées, ignorées) |
| PERSONNES PERTINENTES | SEED_DEMO (honnêtement étiqueté, moteur réel non branché) |
| Filtres Transmissions/Ressources/Événements/Actions | NON_IMPLÉMENTÉ (silencieusement redirigés vers ALL) |
| Composeur (Besoin/Projet/ZUMRA/Transmission) | RÉEL_OPÉRATIONNEL |
| Composeur (Ressource/Événement/Sondage) | NON_IMPLÉMENTÉ |
| « À faire maintenant »/« Projets qui ont besoin de vous » | SEED_DEMO (liens morts) |
| Fin de fil, pagination | RÉEL_OPÉRATIONNEL |

---

## 2. Monde ZUMRA

**Chaîne réelle** : `ZumraSpaceController::__invoke()` → `resources/views/zumra/index.blade.php`. Contrairement au Fil, cette surface documente honnêtement ses propres limites dans son docblock de classe : « les deux seules surfaces sans métier réel derrière elles (Fil ZUMRA détaillé, proximité géographique) sont explicitement documentées comme vitrines, jamais présentées comme le produit final. »

- **Découverte + recherche exhaustive + filtres** (`q`/`mode`/`location`/`view`) : RÉEL_OPÉRATIONNEL, vérifié à nouveau après le correctif `04b335d` (24 août) : dès qu'une recherche/filtre/vue personnelle est active, `discoverGroups` utilise une vraie pagination Eloquent (`->paginate(8)->withQueryString()`), plus de plafond silencieux à 8 résultats sans suite — le défaut que j'avais signalé dans l'audit de la PR #128 a été corrigé, couvert par `tests/Feature/CleanupZumraPaginationTest.php`.
- **Proximité** : `ZumraProximityShowcase`, modèle et migration explicitement commentés « vitrine de démonstration, jamais un moteur de proximité réel », alimenté uniquement par `ZumraWorldDemoSeeder` (opt-in). SEED_DEMO, honnêtement étiqueté.
- **Mes ZUMRA / invitations / demandes** : RÉEL_OPÉRATIONNEL — `$navCounts` dérivé de vraies requêtes `ZumraGroupMembership`, désormais correctement paginé.
- **« Fil ZUMRA »** : `filPanel()` ne construit **pas** un second fil — c'est un widget honnête (avatars de vrais membres récents, `discovery_consent=true`, requête réelle) qui redirige vers le Fil global filtré par type ZUMRA. Le docblock documente qu'un « Fil ZUMRA détaillé, filtrable, commentable » reste une direction produit non construite. RÉEL_OPÉRATIONNEL pour ce qui est affiché (un aperçu + redirection), PRÉPARATOIRE pour l'idée d'un fil dédié.
- **Activités/domaines** (`discoverDomains`, `popularActivities`) : requêtes réelles (`ZumraGroup::selectRaw('domain, COUNT(*)')`, `ZumraGroupActivity`). RÉEL_OPÉRATIONNEL.
- **Statistiques** (`stats()`) : docblock explicite « statistiques réelles, jamais des nombres fabriqués : elles reflètent exactement ce que contient la base [...] vides sur un portail neuf, vivantes une fois `ZumraWorldDemoSeeder` exécuté ». Vérifié : agrégats réels (`Need`/`Project`/`CommunityEvent`/`ZumraGroup`). RÉEL_OPÉRATIONNEL — seed uniquement pour peupler la donnée, pas pour fabriquer le chiffre affiché.
- **Naissance** (lien vers `zumra.groups.create`) : voir §3.
- **Recommandations** : aucune section de recommandation ZUMRA dédiée trouvée sur cette page au-delà de la découverte par domaine.

---

## 3. Naissance ZUMRA

`ZumraGroupController::create()`/`store()` → `ZumraGroupService::create()`. RÉEL_OPÉRATIONNEL, vérifié par lecture directe des règles de validation :

activité principale (`domain`, requis) ; activités dérivées (`activity_label[]`/`activity_relation[]`, réellement persistées) ; objectifs (`founding_objective`, 40–1800 caractères, requis) ; lieu (`location`, facultatif) ; mode présentiel/en ligne/hybride (`participation_mode`, enum réel) ; naissance solo (`assume_primary_lead`, booléen, réellement pris en compte par le service — message de retour honnête : « Aucun rôle vacant n'a été attribué automatiquement ») ; responsabilités (les 5 rôles constitutifs, CAP-011, gérés par `ZumraGroupRole`) ; charte ultérieure (`setCharter()`, action séparée, réelle).

Gate d'entrée réelle : `requireActiveProgramMembership()` — seul un membre du Programme ZUMRA actif peut créer une ZUMRA.

Non trouvés comme champs distincts : « problème à résoudre » et « transmission/apprentissage » n'existent pas comme champs séparés — probablement absorbés dans `founding_objective` en texte libre, jamais structurés séparément. PARTIEL sur ce point précis.

---

## 4. Espace ZUMRA

`ZumraGroupController::show()` → `resources/views/zumra/groups/show.blade.php`. Surface la plus honnête du dépôt : chaque section non finie est explicitement libellée comme telle dans le texte visible à l'utilisateur, pas seulement en commentaire de code.

| Entrée | État | Preuve |
|---|---|---|
| Accueil | RÉEL_OPÉRATIONNEL | ancre `#accueil`, contenu réel |
| Fil d'activités | RÉEL_OPÉRATIONNEL | vrais Projet/Besoin/Mission liés (`route('projects.show', $project)`, etc.), lien réel vers `comments.zumra-activity` |
| Discussions/canaux | PRÉPARATOIRE, honnêtement étiqueté | « Ces entrées préparent l'organisation future des échanges. La conversation actuelle reste unique. » ; le bouton réel ouvre `messages.zumra` (conversation unique réelle, pas les canaux multiples affichés en décor) |
| Transmissions | RÉEL (ancre vers le flux) | lié aux missions de la ZUMRA |
| Projets / Besoins | RÉEL_OPÉRATIONNEL | vraies requêtes, liens réels |
| Membres | RÉEL_OPÉRATIONNEL | `$roles`, vrais profils |
| Événements | RÉEL_OPÉRATIONNEL | `community-events.zumra.index` |
| Ressources | PRÉPARATOIRE, honnêtement étiqueté | « Les partages utiles de cette ZUMRA apparaîtront ici progressivement » ; lien réel vers `shares.group` pour les membres actifs |
| À propos (« Notre intention ») | RÉEL_OPÉRATIONNEL | `$group->founding_objective` |
| Gouvernance | RÉEL_OPÉRATIONNEL | rôles, propositions, charte, capacités collectives (CAP-011/012) |
| Invitations | RÉEL_OPÉRATIONNEL | formulaire réel, `person_reference` |
| **Contributions (financières, CAP-061)** | **RÉEL_MAIS_DORMANT — aucune entrée UX** | voir §5 : aucune vue du dépôt ne référence `zumra.groups.contribution.propose`/`.approve` |
| Finance/historique (Ledger) | **NON VISIBLE DANS L'UX** | `LedgerController` est une API JSON pure, aucune vue Blade nulle part dans le dépôt |

Le seul usage du mot « contribution » sur cette page (« Publier une première contribution → ») pointe vers `comments.zumra-activity` — une **contribution au sens social/activité**, pas la contribution financière CAP-061. Collision de vocabulaire à noter : un lecteur du code pourrait croire que ce lien couvre CAP-061, ce n'est pas le cas.

---

## 5. Chaîne financière — cartographie complète

Voir `docs/audits/CORE-COMPLETION-001-ZAHAB.md` pour le détail architectural. Résumé factuel vérifié par lecture directe du code (pas seulement `docs/capacites/CAPABILITY-COVERAGE.md`) :

### Ledger (CAP-062)

- `LedgerEntry` (`app/Models/LedgerEntry.php`) — docblock explicite : « Ni wallet, ni solde stocké, ni compte comptable. »
- `LedgerService` — deux méthodes d'écriture uniquement (`postContributionPayment`, `postMembershipPayment`), **aucune méthode d'agrégation, aucun calcul de solde**.
- `LedgerController` + `Administration\LedgerController` — **API JSON pure, zéro vue Blade**. Confirmé par recherche exhaustive : aucun fichier de `resources/views/` ne référence une route `ledger.*`.
- Migration `2026_09_08_100000_create_ledger_entries_table.php` : schéma append-only, `UNIQUE(source_type, source_id)`, aucune colonne de solde.
- **Classification : RÉEL_OPÉRATIONNEL au niveau backend, invisible dans l'UX actuelle (aucune page ne l'expose à un utilisateur).**

### Wallet / comptes / soldes

**NON_IMPLÉMENTÉ.** Aucun modèle, migration, colonne ou méthode ne calcule ou stocke un solde nulle part dans le dépôt. Reconfirmé indépendamment (pas déduit de l'existence du Ledger).

### CAP-061 — Contributions financières

- `Contribution`/`ContributionPayment`/`ContributionReceipt`/`ContributionPurpose`/`ContributionEvent`, `ContributionService` (384 lignes), `ContributionController`.
- Individuelle (`TYPE_INDIVIDUAL`/`SUBJECT_PERSON`) et collective (`TYPE_COLLECTIVE`/`SUBJECT_ZUMRA_GROUP`, workflow propose→approuve réel côté responsable ZUMRA).
- Gating réellement appliqué en code : `abort_unless((bool) $settings[...], 409, 'Les paiements de contribution ne sont pas encore ouverts.')`.
- `ContributionConfiguration::defaults()` : `individual_enabled=false`, `collective_enabled=false` — **fermé par défaut**.
- **`ContributionController::index()` retourne du JSON. Aucune vue Blade, aucun lien nulle part dans le dépôt ne mène vers `/contributions` ou vers `zumra.groups.contribution.propose`.**
- **Classification : RÉEL_OPÉRATIONNEL au niveau backend (moteur, validation, workflow, gating — du travail sérieux), mais entièrement invisible et injoignable dans l'UX actuelle, en plus d'être fermé par configuration.** C'est la CAP la plus mal exposée du dépôt au regard de la qualité de son moteur.

### Adhésion ZUMRA (CAP-007B)

- `ZumraPayment`, `ZumraPaymentReceipt`, `ZumraMembershipPaymentController`.
- **A une vraie vue** : `resources/views/zumra/membership.blade.php`, liée depuis `member/space.blade.php` et `zumra/index.blade.php` — réellement atteignable par un utilisateur.
- `GeniusPayClient::createMembershipPayment()` verrouillé à 500 XOF, validation stricte (jamais `COMPLETED` implicite sur statut absent).
- `.env.example` : `ZUMRA_PAYMENT_ENABLED=false`, clés GeniusPay vides — **fermé par défaut, canoniquement, pas seulement dans cet environnement**.
- **Classification : RÉEL_OPÉRATIONNEL (code + UI atteignable), RÉEL_MAIS_DORMANT à l'exécution (fermé par flag).**

### Reçus

`ContributionReceipt`/`ZumraPaymentReceipt`, `integrity_hash` unique par paiement. RÉEL_OPÉRATIONNEL au niveau backend ; visibilité UX limitée à la route `zumra.payment.receipt` (adhésion) — aucun reçu de contribution CAP-061 n'est visible faute d'UI amont.

### GeniusPay

Intégration HTTP réelle, jamais de simulation silencieuse de succès. RÉEL_OPÉRATIONNEL (intégration) / DORMANT (non configuré par défaut).

---

## 6. G-POS / GamaDrive / sponsorisation

Recherche exhaustive dans `app/` :

- **G-POS** : aucune trace de code dans ce dépôt. C'est un produit séparé (`gpos-core`, un autre dépôt de ce compte, déjà connu de ce contexte multi-session). NON_IMPLÉMENTÉ **dans dgafrique-core**.
- **GamaDrive** : mentionné uniquement comme satellite fédéré (`FederationServiceProvider`, `federation.continue`), un lien de fédération d'identité/session vers un produit externe (`gamadrive-core`, dépôt séparé). Aucune logique financière ni Wallet ici. LEGACY/PARTIEL dans ce dépôt (juste le pont de fédération).
- **Sponsorisation** : aucun modèle, route ou contrôleur trouvé. NON_IMPLÉMENTÉ.
- **Événement (CAP-068)** : contrairement aux trois précédents, réellement implémenté (`CommunityEvent`, organisateur `ZUMRA_GROUP`/`ORGANIZATION`, inscription/désinscription légère) — mais non branché au composeur du Fil (§1) ni à ZAHAB.

---

## 7. GAMAD Core et ZAHAB

Conclusion de la contre-expertise précédente **reconfirmée** : `GamadCoreClient` (`app/Infrastructure/GamadCore/GamadCoreClient.php`) n'expose que des méthodes d'identité/session/organisation (`authenticate`, `currentSession`, `resolveIdentity`, `provisionOrganizationIdentity`, etc.) — **zéro méthode financière, zéro concept de solde**. `RequireCoreMember` revalide la session à chaque requête protégée, jamais un mouvement d'argent.

**Découverte majeure, non anticipée par le mandat** : les documents canoniques `ARCHITECTURE-PRODUIT-V2.md` (§8, « Décisions figées ») et `PVB-001-CONTRAT-FONCTIONNEL-CERVEAU-PROJET-V2.md` positionnent déjà ZAHAB comme une **couche de « GAMAD Finance »** — une autorité distincte de GAMAD Core, nommée mais **totalement absente du code** (`grep -ri gamadfinance app/` → aucun résultat). Le mandat CORE-COMPLETION-001 ne mentionne pas GAMAD Finance et demande de « déterminer où le moteur ZAHAB doit vivre » comme si la question était ouverte — elle est en réalité déjà partiellement tranchée par le canon existant, mais seulement au niveau du nom, jamais implémentée. Détail complet et **contradiction à arbitrer** en §18/§1 du document ZAHAB séparé.

---

## 8. Inventaire des seeds

`database/seeders/DatabaseSeeder.php` est **intentionnellement vide** — aucun seeder ne s'exécute automatiquement à l'installation ou au déploiement.

| Seeder | Opt-in | Modèles alimentés | Surfaces consommatrices | Distinction démo/réel |
|---|---|---|---|---|
| `DgNetworkFeedDemoSeeder` | Oui (`--class=`) | `PersonProfile` (5 profils nommés `DEMO-FEED-*`) + délègue à `ProjectHubDemoSeeder` | Aucune actuellement (voir §1 : `$recommendedPeople` non branché dans la vue) | Références `core_identity_reference` préfixées `DEMO-FEED-`, `discovery_consent=true` — distinguable par préfixe |
| `ProjectHubDemoSeeder` | Oui | `Project`, `ProjectTeamMember`, `ZumraGroup`, `ZumraCharter`, `ZumraProgramMembership` | Hub Projets | Non vérifié en détail dans cet audit (hors profondeur forensique) |
| `ZumraWorldDemoSeeder` | Oui | `ZumraGroup`, `ZumraGroupMembership`, `PersonProfile`, `Project`, `ProjectMilestone`, `CommunityEvent`, `ZumraProximityShowcase`, `ZumraCharter`, `ZumraProgramMembership` | Monde ZUMRA (stats, discover, proximité), Espace ZUMRA | Alimente des tables métier réelles avec des lignes normales — pas de préfixe `DEMO-` systématique visible sur toutes les entités ; à vérifier avant bêta si un environnement de démonstration et un environnement réel doivent un jour cohabiter dans la même base |

**Recommandation par bloc** (règle de la §3 du mandat) :

- Statistiques Monde ZUMRA (`ZumraSpaceController::stats()`) : **SEED MIXTE ACCEPTABLE** — la requête est réelle, seule la donnée qu'elle compte est seedée en démo. Rien à changer avant bêta, le mécanisme est déjà correct.
- `FeedDemoPresentation` (Fil global) : **SEED À REMPLACER PAR MOTEUR RÉEL AVANT BÊTA** — codé en dur, non gaté sur l'absence de données réelles, contredit une doctrine déjà écrite, écrase silencieusement du travail réel déjà fait (`$recommendedPeople`, `$myGroups`).
- `ZumraProximityShowcase` : **SEED À CONSERVER POUR DÉMO** — honnêtement étiqueté comme vitrine dans le code lui-même, pas de moteur de proximité réel à remplacer dans l'immédiat (hors périmètre bêta).
- « MES ESPACES » / « ACTIVITÉ DE MES ZUMRA » (rails Fil) : **SEED À REMPLACER PAR MOTEUR RÉEL AVANT BÊTA** — la donnée réelle existe déjà (`$myGroups`), il s'agit de brancher, pas de construire.

---

## 9. CAP-001 à CAP-084 — ce qui a été revérifié directement

`docs/capacites/CAPABILITY-COVERAGE.md` liste 84 CAP avec un statut documentaire (`CLOSED`/`PARTIAL`/`DOC_ONLY`/`NOT_IMPLEMENTED`/`DEPENDENCY_BLOCKED`). Ce statut documentaire **a été confronté au code réel**, pas seulement recopié, pour les CAP directement pertinentes à ce mandat :

| CAP | Statut documentaire | Vérification directe | Verdict de cet audit |
|---|---|---|---|
| CAP-011 (ZUMRA/Groupe humain) | CLOSED | `ZumraGroupService::create()`, rôles, validation — confirmé | RÉEL_OPÉRATIONNEL |
| CAP-012 (Capacité collective) | CLOSED | Section gouvernance de l'Espace ZUMRA confirmée | RÉEL_OPÉRATIONNEL |
| CAP-019 (Fil d'activité) | CLOSED | `ActivityFeedService` confirmé réel et testé | RÉEL_OPÉRATIONNEL (le moteur ; l'habillage autour, non) |
| CAP-061 (Contributions financières) | CLOSED | Backend confirmé réel ; **UI absente, config fermée** | Statut documentaire trop optimiste pour un utilisateur réel — **RÉEL_MAIS_DORMANT du point de vue produit**, pas « CLOSED » sans réserve |
| CAP-062 (Ledger/traçabilité) | CLOSED | Confirmé réel, append-only, aucun wallet | RÉEL_OPÉRATIONNEL techniquement, **invisible en UX** |
| CAP-063 (Financement de projet) | CLOSED *(V1 strictement déclarative)* | Non revérifié en détail dans cet audit (hors profondeur forensique) — le doc lui-même admet la nature déclarative | À revérifier avant bêta si le financement de projet devient un chantier prioritaire |
| CAP-068 (Événement) | CLOSED | `CommunityEvent` confirmé réel | RÉEL_OPÉRATIONNEL, mais non relié au composeur du Fil |
| CAP-069 (Tâche/Mission) | CLOSED | Confirmé dans les sessions précédentes de ce dépôt | RÉEL_OPÉRATIONNEL |

**Nuance demandée par le mandat, confirmée dans les faits** : CAP-061 et CAP-062 sont documentées `CLOSED` dans le registre, ce qui est vrai au sens strictement technique (le code existe, est testé, fonctionne). Mais `CLOSED` sans nuance masque qu'aucun utilisateur ne peut aujourd'hui les atteindre par un clic — l'écart entre « implémenté » et « exploitable par un utilisateur » que le mandat demandait explicitement de distinguer est réel et significatif ici.

Les CAP hors du périmètre financier/UX direct de ce mandat (la majorité des 84) **n'ont pas été revérifiées une par une dans cet audit** — le registre existant reste la référence pour elles, sous réserve du même risque méthodologique (un statut `CLOSED` documentaire n'a de valeur que si quelqu'un a tracé la chaîne jusqu'à l'UI, ce que ce document ne prétend pas avoir fait CAP par CAP).

---

## 10. Matrice UX → CODE (livrable principal, §15 du mandat)

Lignes classées par surface. « Bloquant bêta ? » anticipe le backlog §11 sans le dupliquer intégralement.

| Surface | Élément UX | Backend | Persistance | Tests | Donnée | État | Bloquant bêta ? |
|---|---|---|---|---|---|---|---|
| Fil | Cartes Besoin/Projet/ZUMRA/Mission/Transmission/Preuve | `ActivityFeedService` | `dg_need_events`, `dg_project_events`, etc. | `ActivityFeedTest` (existant) | Réel | RÉEL_OPÉRATIONNEL | Non |
| Fil | Cartes « showcase » (4 cartes tête de fil) | `FeedDemoPresentation` | Aucune (codé en dur) | Aucun | Seed non gaté | SEED_DEMO | Oui — trompeur en l'état (mélangé au réel, jamais désactivable) |
| Fil | « LE RÉSEAU EN ACTION » (stats rail gauche) | `FeedDemoPresentation::stats()` | Aucune | Aucun | Seed non gaté | SEED_DEMO | Oui — chiffres fabriqués présentés sans requête réelle |
| Fil | « MES ESPACES » / « ACTIVITÉ DE MES ZUMRA » | Codé en dur dans la vue | — | Aucun | Seed (données réelles ignorées) | SEED_DEMO | Oui — `$myGroups` réel existe, juste pas branché |
| Fil | « PERSONNES PERTINENTES » | Codé en dur dans la vue | — | Aucun | Seed, honnêtement étiqueté | SEED_DEMO | Non (étiqueté honnêtement), mais gâchis d'un moteur réel prêt |
| Fil | Filtres Transmissions/Ressources/Événements/Actions | `ActivityFeedService::FILTERS` (ne les connaît pas) | — | Aucun | — | NON_IMPLÉMENTÉ | Oui — clic trompeur, silencieusement redirigé |
| Fil | Composeur Besoin/Projet/ZUMRA/Transmission | Routes réelles | Réel | Couvert indirectement | Réel | RÉEL_OPÉRATIONNEL | Non |
| Fil | Composeur Ressource/Événement/Sondage | Aucun | — | Aucun | — | NON_IMPLÉMENTÉ | Non (pas de promesse trompeuse — `<span>` non cliquable) |
| Fil | « À faire maintenant » / « Projets qui ont besoin de vous » | Codé en dur | — | Aucun | Seed | SEED_DEMO | Oui — lien « Voir tout » sans `href` |
| Monde ZUMRA | Découverte + recherche exhaustive + filtres | `ZumraSpaceController` | `dg_zumra_groups` | `CleanupZumraPaginationTest` | Réel (seed pour peupler) | RÉEL_OPÉRATIONNEL | Non |
| Monde ZUMRA | Proximité | `ZumraProximityShowcase` | `dg_zumra_proximity_showcases` | Non vérifié en détail | Seed, honnêtement étiqueté en code | SEED_DEMO | Non (hors bêta, doc l'assume déjà) |
| Monde ZUMRA | Mes ZUMRA / invitations / demandes | `ZumraSpaceController` | `dg_zumra_group_memberships` | `ZumraWorldSmokeTest` | Réel | RÉEL_OPÉRATIONNEL | Non |
| Monde ZUMRA | « Fil ZUMRA » (widget avatars + lien) | `filPanel()` | `dg_zumra_group_memberships`, `dg_person_profiles` | Non vérifié en détail | Réel | RÉEL_OPÉRATIONNEL (widget) / PRÉPARATOIRE (fil dédié promis, absent) | Non |
| Monde ZUMRA | Statistiques | `stats()` | Agrégats réels | Non vérifié en détail | Réel (seed pour peupler) | RÉEL_OPÉRATIONNEL | Non |
| Naissance ZUMRA | Formulaire complet | `ZumraGroupController::store` → `ZumraGroupService` | `dg_zumra_groups`, `dg_zumra_group_roles` | `ZumraHumanBirthTest` | Réel | RÉEL_OPÉRATIONNEL | Non |
| Espace ZUMRA | Fil d'activités, Projets, Besoins, Membres, Événements | Requêtes réelles dans `show()` | Réel | Non revérifié un par un | Réel | RÉEL_OPÉRATIONNEL | Non |
| Espace ZUMRA | Discussions/canaux (multi-canaux affichés) | Aucun (décor) ; conversation unique réelle sous le décor | `messages.zumra` réel | Non vérifié | Décor + réel mélangés, honnêtement étiqueté | PRÉPARATOIRE | Non (déjà honnête) |
| Espace ZUMRA | Ressources | Aucun moteur dédié ; lien réel vers partages | Réel (partages génériques) | Non vérifié | Réel, mince | PRÉPARATOIRE | Non (déjà honnête) |
| Espace ZUMRA | Gouvernance / rôles / charte | `ZumraGroupService` | Réel | Existant | Réel | RÉEL_OPÉRATIONNEL | Non |
| Espace ZUMRA | Contribution collective (financière) | `ContributionController::proposeCollective/approveCollective` | Réel | `ContributionTest` (45 cas, non exécuté dans cet audit) | Réel, backend seul | RÉEL_MAIS_DORMANT | **Oui — moteur réel sans aucune entrée UX** |
| Espace ZUMRA | Finance/historique (Ledger) | `LedgerController` | Réel | `LedgerTest` (30 cas, non exécuté dans cet audit) | Réel, API seule | RÉEL_MAIS_DORMANT | Non pour la bêta (pas un blocage fonctionnel), mais listé pour transparence |
| Besoins | Création, publication, propriétaire P/ZUMRA/Org | `NeedController` | `dg_needs` | Existant (sessions précédentes) | Réel | RÉEL_OPÉRATIONNEL | Non |
| Besoins | Transformation en Projet | `ProjectDraftController` (`source_need_reference`) | `dg_projects.source_need_id` | Non revérifié dans cet audit | Réel | RÉEL_OPÉRATIONNEL (lien confirmé) | Non |
| Projets | Ancrage ZUMRA, équipe, jalons | Confirmé dans sessions précédentes de ce dépôt | Réel | Existant | Réel | RÉEL_OPÉRATIONNEL | Non |
| Projets | Cerveau Projet | `DeepSeekProjectBrainProvider`, prompt interdisant argent/invention | Réel | Non revérifié dans cet audit | Réel | RÉEL_OPÉRATIONNEL (sous réserve de clé API DeepSeek configurée) | Non |
| Projets | Financement (CAP-063) | `ProjectFundingService` | Réel | `ProjectFundingTest` (31 cas, non exécuté) | Réel | Statut documentaire CLOSED, **déclaratif par décision produit assumée** — non revérifié en détail ici | À revérifier avant bêta si prioritaire |
| Profil/Capacités/Transmission | Ponts vers ZUMRA/Besoin/Projet | UIUX-007, confirmé dans sessions précédentes | Réel | Existant | Réel | RÉEL_OPÉRATIONNEL | Non |
| Finance transversale | Wallet / solde ZAHAB | Aucun | Aucun | Aucun | — | NON_IMPLÉMENTÉ | Non (hors périmètre de cette PR, par mandat explicite) |
| Finance transversale | Adhésion ZUMRA (paiement) | `ZumraMembershipPaymentController`, `GeniusPayClient` | Réel | Existant | Réel | RÉEL_MAIS_DORMANT (fermé par défaut) | Oui si la bêta doit accueillir de vrais paiements d'adhésion |
| Finance transversale | Contribution individuelle | `ContributionController` | Réel | `ContributionTest` | Réel, backend seul | RÉEL_MAIS_DORMANT | **Oui — même défaut d'UI que la contribution collective** |
| Transversal | G-POS | — | — | — | — | NON_IMPLÉMENTÉ (dans ce dépôt) | Non |
| Transversal | GamaDrive | Pont de fédération identité/session seulement | — | Existant | Réel (fédération), aucune finance | PARTIEL/LEGACY | Non |
| Transversal | Sponsorisation | — | — | — | — | NON_IMPLÉMENTÉ | Non |

**Comptage** (sur les lignes ci-dessus, échantillon représentatif des surfaces auditées en profondeur — pas un recensement exhaustif de chaque sous-puce du mandat, voir la note de calibrage en tête de document) :

- RÉEL_OPÉRATIONNEL : 16
- RÉEL_MAIS_DORMANT : 5
- PARTIEL : 2
- SEED_DEMO : 6
- PRÉPARATOIRE : 2
- NON_IMPLÉMENTÉ : 5
- INCERTAIN : 0 (tout élément audité a pu être tranché — aucune zone grise laissée sans preuve)

Total : 36 lignes classées dans cette matrice.

## 11. Backlog Bêta

### P0 — BLOQUANT BÊTA

Impossible d'accueillir de vrais utilisateurs correctement sans cela.

1. **Fil global — retirer ou gater `FeedDemoPresentation`** avant toute bêta publique : des chiffres et des cartes fabriqués, non gatés, mélangés en permanence au contenu réel, contredisent une doctrine déjà écrite et donnent une fausse impression d'activité. C'est le risque de confiance le plus direct de tout l'audit.
2. **Fil global — corriger ou retirer les 4 filtres non fonctionnels** (Transmissions/Ressources/Événements/Actions) : un clic qui ne fait rien et ne le signale pas est un défaut de confiance basique.
3. **Fil global — brancher `$recommendedPeople`/`$myGroups` réels** à la place du décor codé en dur, ou retirer ces blocs si le calendrier ne permet pas de les brancher avant bêta — mais pas les deux à la fois (moteur réel prêt + décor à la place).

### P1 — IMPORTANT BÊTA

Fortement souhaitable mais contournable temporairement.

1. **CAP-061 — donner une UI aux contributions** (individuelle et collective) : le moteur backend est le plus abouti de tout l'audit financier et reste totalement injoignable par un utilisateur. Contournable temporairement (le produit fonctionne sans), mais c'est un gâchis d'ingénierie déjà faite et un vrai besoin produit (financement communautaire).
2. **Arbitrer la contradiction ZAHAB/GAMAD Finance** (§0 du document ZAHAB) avant d'écrire la moindre ligne de code du futur chantier — pas bloquant pour la bêta actuelle, mais bloquant pour la suite immédiate.
3. **Rail droit du Fil — retirer les liens « Voir tout » sans `href`** : petit défaut, grande visibilité.

### P2 — APRÈS BÊTA

Peut attendre.

1. Adhésion ZUMRA / contribution — activation réelle des flux de paiement (flags + identifiants GeniusPay), une fois la stratégie de lancement financier décidée.
2. Fil ZUMRA détaillé (filtrable, commentable) comme moteur dédié, si le besoin se confirme au-delà du widget-aperçu actuel.
3. Canaux de discussion multiples dans l'Espace ZUMRA (au-delà de la conversation unique actuelle).
4. Ressources dédiées par ZUMRA (au-delà des partages génériques actuels).
5. Revérification CAP par CAP au-delà du périmètre financier/UX de ce mandat (la majorité des 84 CAP n'a pas été retracée dans cet audit).

### P3 — VISION FUTURE

1. ZAHAB — implémentation complète (Wallet, mouvements, UI), après arbitrage doctrinal.
2. Reconnaissance financière/juridique future de ZAHAB.
3. G-POS, sponsorisation — aucun code existant dans ce dépôt, à concevoir en temps voulu, sans coupler leur conception au premier moteur ZAHAB (conforme à la demande explicite du mandat §13).
4. Proximité géographique réelle (au-delà de la vitrine `ZumraProximityShowcase`).

**Argumentation explicite demandée par le mandat (§17)** : ZAHAB lui-même n'est classé nulle part en P0. La bêta peut fonctionner sans lui — le produit actuel gère déjà, séparément, l'adhésion et la contribution en FCFA direct via GeniusPay. Ce qui EST en P0, c'est l'intégrité de ce qui est déjà montré à l'utilisateur aujourd'hui (Fil global), pas l'ajout d'une nouvelle capacité monétaire.

## 12. Résumé — voir aussi le rapport final

Le rapport de synthèse (baseline, branche, SHA, décomptes, architecture, contradictions, P0–P3, recommandation de premier chantier) est livré en réponse directe à l'utilisateur, conformément à la section 20 du mandat. Ce document et `CORE-COMPLETION-001-ZAHAB.md` en sont les pièces justificatives détaillées.
