# Expérience produit canonique — DG Afrique

## Statut

**CANONIQUE — Couche 04 (PRODUIT / UX), adopté 21 août 2026.** Ce document occupe la couche
« 04 — PRODUIT / UX » de la hiérarchie documentaire décrite dans
`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §30 — une couche que ce document est le premier à
occuper explicitement dans son ensemble (voir §1).

Autorité : subordonné à `docs/canon/DOCTRINE-GAMAD.md` (couche 00, raison d'être humaine de
GAMAD), `docs/foundation/DG-AFRIQUE-DOCTRINE.md` (couches 00-01),
`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` et `docs/capacites/OVERRIDES.md` (couche 02),
`docs/AI-RULES.md` (invariant d'identité produit supérieur) et le référentiel des 84 CAP
(couche 03) — jamais l'inverse. Ce document ne redéfinit aucune de ces sources ; il les
synthétise pour la couche produit/UX et cite chaque invariant qu'il rapporte.

Autorité sur : oriente les futures missions d'interface (UIUX-00x) et sert de référence de
canonisation pour `docs/design/DESIGN-INVARIANTS.md` (qui reste l'autorité visuelle/écran par
écran — ce document ne le remplace pas, il l'inscrit dans un modèle mental plus large).

---

## 1. Pourquoi ce document existe

`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §30 nomme explicitement six couches documentaires :

```text
00 — DOCTRINE / INVARIANTS
01 — MODÈLE CONCEPTUEL
02 — RÈGLES MÉTIER / STATUTS
03 — CAPACITÉS DU SYSTÈME
04 — PRODUIT / UX
05 — ARCHITECTURE TECHNIQUE
06 — CODE
```

Avant ce document, aucun fichier unique n'occupait la couche 04 dans son ensemble : le plus
proche candidat, `docs/design/DESIGN-INVARIANTS.md`, est un contrat **visuel et écran par écran**
(couleurs, typographie, composants, invariants par page) — précieux mais volontairement étroit.
`docs/architecture/ARCHITECTURE-PRODUIT-V2.md` fixe une **doctrine produit figée** par domaine
(ZUMRA/Projet/Fil/économie) mais ne construit pas de modèle mental unifié ni de parcours
utilisateur. Ce document comble cet espace en synthétisant — sans les dupliquer — les décisions
déjà prises ailleurs.

Ce document **n'invente aucune doctrine**. Chaque affirmation est reliée à sa source. Là où le
corpus ne tranche pas, la section correspondante le dit explicitement (§19) plutôt que de
combler le vide par une préférence de rédaction.

---

## 2. Ce que ce document n'est pas

- Ce n'est pas une spécification technique, une maquette, ni un cahier des charges
  (`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §0, même posture reconduite ici).
- Ce n'est pas le référentiel des 84 CAP : il ne modifie, ne renomme, ne referme et n'ouvre aucune
  CAP. `docs/capacites/CAPABILITY-INDEX.md` et `docs/capacites/CAPABILITY-COVERAGE.md` restent les
  seules autorités sur l'avancement métier.
- Ce n'est pas `docs/design/DESIGN-INVARIANTS.md` : les règles écran-par-écran, les tokens
  visuels et la procédure de gouvernance de refonte (§14 de ce dernier) restent en vigueur telles
  quelles et ne sont pas recopiées ici.
- Ce n'est pas une revue de code ni un audit de qualité technique — le §17 de ce document
  cartographie la maturité des interfaces du point de vue produit, pas du point de vue
  ingénierie.

---

## 3. Modèle mental — qu'est-ce que DG Afrique

**Source première : `docs/AI-RULES.md` (invariant d'identité produit supérieur), lui-même
traduction produit du principe fondateur « la Personne précède la structure » canonisé dans
`docs/canon/DOCTRINE-GAMAD.md` §3 — GAMAD ne commence ni par une ZUMRA, ni par une Organisation,
ni par DG Afrique lui-même, mais par la personne volontaire que ces structures existent pour
servir.**

> DG Afrique est un réseau social d'action. Il accompagne le développement humain et l'action
> collective, notamment à travers ZUMRA, les capacités, besoins, projets, missions,
> apprentissages, transmissions, preuves, opportunités et outils spécialisés.

Trois non-réductions explicites, toutes textuelles (aucune n'est une inférence) :

1. **DG Afrique n'est pas un portail web de type moteur de recherche** — `docs/AI-RULES.md`.
2. **DG Afrique n'est pas un catalogue d'applications, ni un « lanceur de satellites » comme
   finalité produit** — `docs/AI-RULES.md`. Confirmé par
   `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §3.4 (« Les satellites sont des outils ou produits
   spécialisés... Il ne reçoit pas le mot de passe GAMAD d'un membre ») et
   `docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §1 (« Les satellites ne sont pas des produits
   autonomes concurrents du réseau : ce sont des outils spécialisés contextuels »).
3. **GAMAD Core ne doit pas devenir le portail éditorial ou le réseau social visible** —
   `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §3.2. C'est DG Afrique, pas GAMAD Core, qui porte
   la couche sociale/visible.

Formule directrice retenue par `docs/AI-RULES.md` : *« La navigation, les recommandations et
l'intelligence du produit doivent servir le passage de la capacité à l'action humaine et
collective. »* — c'est le critère de recevabilité de toute décision produit/UX, repris comme
test doctrinal complet en §7 ci-dessous.

---

## 4. Architecture de l'écosystème

**Source : `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §3.**

| Couche | Rôle | Ce qu'elle n'est pas |
|---|---|---|
| **GAMAD** | Institution fondatrice, autorité de gouvernance, marque mère. | — |
| **GAMAD Core** | « Monde invisible de confiance » : identités canoniques, sessions, rôles/autorisations transversales, adhésions/attestations, décisions structurantes, écritures financières, audit. | Jamais le portail éditorial ou le réseau social visible (§3.2). |
| **DG Afrique** | « Porte publique et humaine » : capacités, besoins, opportunités, membres, ZUMRA, projets, structures GAMAD officielles, satellites, partenaires, preuves d'impact. | Possède le métier du portail et de ZUMRA, sous réserve des frontières contractuelles avec GAMAD Core et les satellites (§3.3). |
| **Satellites** | Outils/produits spécialisés (GamaDrive, Wasplex, G-Market, G-POS…), métier et données propres. | Ne reçoivent jamais le mot de passe GAMAD d'un membre (§3.4) ; commencent comme module extractible avant de devenir satellite autonome (`docs/capacites/OVERRIDES.md` OVR-005). |

Cette architecture explique pourquoi le produit DG Afrique ne doit jamais exposer les mécanismes
internes de GAMAD Core comme condition de compréhension pour agir
(`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §24 : « Le Core structure la réalité ; l'interface
traduit cette structure en expérience humaine compréhensible »).

---

## 5. La distinction ZUMRA

**Source : `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §2, et `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §5.**

ZUMRA est une **communauté organisée de personnes** réunies autour d'un domaine, d'un objectif et
d'un projet commun — jamais une simple structure technique. Sa devise : *Formation — Travail —
Adoration*. `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §5 précise : *« ZUMRA ne se réduit ni à une
startup, ni à une entreprise, ni à un groupe de discussion, ni à une équipe de projet. »*

Ce qu'une ZUMRA **n'est pas** (`ZUMRA-DOCTRINE-INVARIANTE.md` §2, liste textuelle) : un simple
groupe de discussion, un fil social sans finalité, une caisse informelle, une promesse de
financement, un classement de valeur humaine, ou un mécanisme permettant d'acheter une fonction,
une influence ou une vérité.

Distinctions à ne jamais fusionner dans le produit :

- **ZUMRA ≠ Organisation.** Une ZUMRA *peut conduire à* une startup, une entreprise, une
  organisation, une coopérative ou une association (§2), mais elle n'en est pas une par défaut :
  ce sont deux objets métier distincts avec leurs propres autorités (`ZumraGroupService` vs
  `OrganizationService`, confirmé par le code — jamais une matrice d'autorisation partagée).
- **ZUMRA ≠ Projet.** `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §10 : *« Projet, Activité, ZUMRA et
  structure juridique ne doivent jamais être confondus. »* Une ZUMRA peut porter un Projet, mais
  le Projet reste le centre d'action, la ZUMRA le centre d'organisation collective (§21).
- **ZUMRA ≠ « le réseau social » générique.** `docs/capacites/OVERRIDES.md` OVR-001 autorise une
  ZUMRA à avoir fil d'activité, relations entre membres, commentaires, partage et messagerie —
  mais seulement *« lorsqu'ils servent l'apprentissage, les besoins, les groupes et les projets »*,
  jamais comme finalité en soi, et *« aucun classement de valeur humaine ni mécanique d'attention
  artificielle »*.

Synthèse (`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §21) : **ZUMRA = centre d'organisation
collective. Projet = centre d'action. Fil = centre de circulation et de découverte.** Les trois
forment `Organisation ↔ Action ↔ Circulation` — jamais un seul objet fusionné.

---

## 6. Les boucles conceptuelles fondatrices

**Source : `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §17-22 et `docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §5.**

Trois boucles, toutes textuelles, à ne jamais réordonner ni simplifier dans une interface :

1. **Boucle Fil ↔ Projet** (§19) : `FIL → PROJET → ACTION → PREUVE → FIL`. Le Fil distribue
   l'attention ; le Projet la transforme en action.
2. **Boucle de développement** (§20) : `INTENTION → ACTION → PROJET → PREUVE → CAPACITÉ →
   CONFIANCE → MISE EN RELATION → NOUVELLE ACTION`.
3. **Boucle structurante V2** (`ARCHITECTURE-PRODUIT-V2.md` §5) : Cerveau Projet → besoin/capacité
   manquante → matching explicable → Fil personnalisé → personne/ZUMRA/opportunité pertinente →
   action humaine confirmée → équipe/Mission/Transmission → preuve/résultat → Fil d'action →
   nouvelles capacités.

Le chemin **Capacité → Besoin → Projet → Mission → Transmission/Preuve → capacité collective**
(hypothèse à vérifier posée en amont de cette mission) est **confirmé par le corpus**, mais pas
comme une chaîne linéaire obligatoire : `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §3 précise que
l'intention *peut* progressivement révéler `Personne → Capacité → Besoin → Projet → Relation →
ZUMRA → Action`, et §4 que le profil devient *conséquence* de l'action autant que condition
préalable (« Déclarée → Observée → Mobilisée → Prouvée → Reconnue »). Le produit doit donc
permettre d'entrer dans cette chaîne à n'importe quel maillon, jamais forcer un ordre unique
(voir §15 ci-dessous, parcours nouvel utilisateur).

---

## 7. Test doctrinal d'une nouvelle fonctionnalité produit/UX

**Source : `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §32, reproduit ici car il s'applique
directement à toute future décision UX.**

Avant toute fonctionnalité ou refonte majeure, huit questions doivent recevoir une réponse
positive ou la fonctionnalité doit être repensée avant implémentation :

1. Quelle réalité humaine cherchons-nous à servir ?
2. Aide-t-elle à passer de l'intention à l'action, à la connaissance ou au développement ?
3. Demandons-nous à l'utilisateur de comprendre une complexité que DG Afrique pourrait gérer pour
   lui ?
4. Respecte-t-elle la souveraineté humaine ?
5. Produit-elle ou exploite-t-elle une information déclarée, observée ou prouvée ?
6. Comment interagit-elle avec Personnes, Capacités, Besoins, Projet, ZUMRA et Fil ?
7. Que devient l'information ou la connaissance produite dans le temps ?
8. Une personne peu alphabétisée ou peu familière du numérique peut-elle comprendre l'intention
   principale de l'expérience ?

---

## 8. Les trois interfaces fondatrices

**Source : `docs/design/DESIGN-INVARIANTS.md` §3, §7, §8.**

| Interface | Rôle | Invariant central |
|---|---|---|
| **Landing** (`/decouvrir`, `foundation.blade.php`) et **Gateway** (`/`, `gateway.blade.php`) | La promesse — expliquer le réseau et permettre d'y entrer. | Gateway = entrée courte sans scroll ; Landing = version longue éditoriale (§16 addendum, confirmé par `IdentityAuthorityGuardTest`). |
| **Mon espace** (`/espace`) | Mon centre d'action. | Une priorité dominante, deux actions primaires maximum, aucun moteur de décision fictif (§7). N'affiche **jamais** de contenu de démonstration (§10, §17, §21 — répété à chaque addendum, seul écran à garder cette exception zéro). |
| **Fil** (`/activite`) | La vie collective. | Pertinence → compréhension → action ; « Pourquoi cela m'est montré ? » / « Que puis-je réellement faire ici ? » toujours répondables (§8). |

Ces trois interfaces ne sont pas interchangeables et ne doivent pas fusionner : la Landing
convainc, Mon espace agit, le Fil fait circuler.

---

## 9. Modèle de navigation

**Source : `docs/design/DESIGN-INVARIANTS.md` §4.**

Six entrées de premier niveau, textuelles : **Fil · Mon espace · Personnes · Besoins · Projets ·
ZUMRA.** Tout le reste (outils spécialisés, satellites, mesures, modération, administration) vit
sous un regroupement secondaire (« Mes outils » ou équivalent), jamais au même niveau que ces six
entrées.

Important pour éviter une confusion fréquente : **84 CAP ne signifie pas 84 entrées de menu.**
Le référentiel des capacités est un découpage d'implémentation et de contrat métier ; la
navigation est un regroupement d'usage humain. Une seule entrée de navigation (« Projets », par
exemple) peut s'appuyer sur une dizaine de CAP (maturité, matching, accompagnement, Cerveau du
Projet, financement…) sans qu'aucune de ces CAP n'apparaisse comme un onglet séparé.

---

## 10. Mécaniques sociales interdites

**Sources croisées, toutes convergentes : `docs/design/DESIGN-INVARIANTS.md` §8,
`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §14, `docs/capacites/OVERRIDES.md` OVR-001,
`docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §4.2 et §12, `docs/AI-HANDOFF.md`.**

Interdiction constante et répétée dans plus de 35 fichiers (confirmé par recherche exhaustive) :
**aucun like, aucun abonné/follower, aucun score d'engagement, aucune viralité comme moteur de
classement, aucun score d'influence humain, aucun classement de personnes, aucune réputation
achetable, aucune gamification, aucun défilement infini pensé pour créer une dépendance.**

`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` §14 autorise un tri par récence/pertinence/preuves mais
jamais un classement de valeur humaine, et jamais un tri achetable par contribution financière —
règle reprise identiquement pour le matching (§18) et pour le Fil V2
(`ARCHITECTURE-PRODUIT-V2.md` §4.2 : *« pas de popularité comme finalité »*). CAP-080 a vérifié
et testé cette règle jusque dans les métriques collectives elles-mêmes
(`test_no_score_ranking_or_person_level_field_exists_anywhere`,
`docs/capacites/specs/CAP-080-mesure-collective.md`).

Distinction fine mais réelle apportée par `ARCHITECTURE-PRODUIT-V2.md` §10.3 : **pertinence
organique ≠ sponsorisation commerciale.** Une offre peut être montrée à la fois parce qu'elle est
pertinente et parce qu'elle est sponsorisée, mais ces deux raisons doivent rester distinguables et
la sponsorisation clairement signalée — le paiement ne doit jamais se faire passer pour une
recommandation objective, ni écraser les besoins/projets réellement pertinents. Ce mécanisme
n'est pas implémenté aujourd'hui (aucun outil spécialisé marchand connecté au Fil) ; il est classé
question ouverte en §19.

---

## 11. Le Fil comme surface stratégique

**Source : `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §17-19,
`docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §4, `docs/capacites/specs/CAP-055-fil-activite-intelligent.md`.**

Le Fil n'est **pas pensé comme un réseau social traditionnel** — c'est le *système de circulation
des mouvements* de DG Afrique (§17). Il répond à deux questions simultanément : *« Que se
passe-t-il dans le réseau ? »* et *« Qu'est-ce qui peut être utile pour moi et pourquoi ? »*
(`ARCHITECTURE-PRODUIT-V2.md` §4.1).

**Pourquoi le « Fil intelligent » (CAP-055) a été construit** — c'est la deuxième question qui
manquait : CAP-019 posait déjà un Fil en lecture, projeté depuis les journaux `*Event` existants,
sans deuxième source de vérité et avec visibilité déléguée aux autorités canoniques de chaque
domaine ; CAP-055 y ajoute une **pertinence personnelle additive et déterministe** — une activité
liée à une relation métier réelle du lecteur (porteur d'un Besoin, membre actif d'une équipe
Projet, assigné à une Mission, membre actif d'une ZUMRA, auteur d'une Transmission ou d'une
Preuve) reçoit une **raison explicable en phrase** (jamais un nombre) et remonte dans la
hiérarchie de priorité — sans jamais être retirée de cette hiérarchie ni filtrée si elle n'a pas
de relation personnelle. **« Pertinence personnelle » signifie donc : priorisation explicable
fondée sur une relation métier réelle, jamais un score, jamais une exclusion.**

Publication depuis le Fil : *« une publication structurante doit produire ou utiliser un objet
métier réel »* (`ARCHITECTURE-PRODUIT-V2.md` §4.3) — publier une recherche d'aide depuis le Fil
crée un vrai `Need`, jamais un post social parallèle sans objet métier.

---

## 12. Données de démonstration — doctrine DEMO-FIRST, REAL-DATA-TAKES-OVER

**Source : `docs/design/DESIGN-INVARIANTS.md` §11 et ses addenda §16-§18, §21 ;
`resources/design-reference/README.md`.**

Invariant de base (§11, décision produit explicite du 16 août 2026) : les fixtures de
démonstration ne sont **jamais** des données métier, ne doivent jamais être seedées, ne doivent
jamais créer de faux membres/besoins/projets/ZUMRA/paiements/partenaires, ne doivent jamais
apparaître automatiquement quand un utilisateur réel n'a pas de données, et doivent toujours être
marquées comme non réelles.

Ce principe s'est **assumé comme évolutif** — chaque évolution est datée, documentée et jamais
silencieuse. Trois écrans appliquent désormais la règle **DEMO-FIRST, REAL-DATA-TAKES-OVER**
(introduite pour le Fil V2, §17, puis étendue à Projets §18 et Besoins §21) : jusqu'à trois cartes
d'exemple (« · Exemple » ou « · EXEMPLE »), affichées **uniquement** filtre par filtre /
domaine par domaine / catégorie par catégorie tant qu'aucune donnée réelle n'existe pour ce
segment, disparaissant automatiquement dès qu'une donnée réelle apparaît, jamais persistées,
actions toujours visuellement présentes mais `aria-disabled` avec une raison accessible (« Objet
de démonstration — aucune action réelle n'est rattachée. »).

**Mon espace reste la seule exception à zéro tolérance** : aucun contenu de démonstration n'y est
jamais montré, sous aucune condition (§10, §17, §21 — répété identiquement à chaque addendum). La
distinction est intentionnelle : le Fil/Projets/Besoins sont des portails de découverte où une
absence de données réelles peut légitimement montrer ce que l'écran pourrait contenir ; Mon
espace est un espace personnel où toute donnée doit être authentiquement celle de la personne.

Cinq fichiers de fixtures existent aujourd'hui (`resources/design-reference/`) : `demo-content.json`
et `landing-portal-demo.json` (Landing), `fil-demo.json` (Fil), `projets-demo.json` (Portail
Projets), `needs-demo.json` (Portail Besoins) — chacun lu par un seul écran, jamais partagé, jamais
seedé (`database/seeders/DatabaseSeeder.php` reste intentionnellement vide).

---

## 13. États vides honnêtes

**Source : `docs/design/DESIGN-INVARIANTS.md` §10, §19, §20.**

Quand aucune donnée réelle n'existe pour un écran ou un bloc qui n'a pas de fixture de
démonstration associée, l'état vide doit être **pleinement assumé, jamais remplacé
silencieusement par une fausse donnée.** Les addenda Dossier Projet (§19) et Cerveau du Projet
(§20) illustrent cette discipline en continu : « Documents & Preuves », « Activité récente »,
« Suivre les mises à jour » affichent chacun un état vide réel ou une action visuellement présente
mais désactivée avec sa raison exacte (absence d'espace documentaire relié, absence de journal
dédié, absence de mécanisme de notification par objet) plutôt qu'un contenu fabriqué.

---

## 14. Hypothèse vérifiée — « le nouvel utilisateur ne doit pas entrer dans un logiciel de
modules »

**Verdict : CONFIRMÉ PAR LE CORPUS**, avec sources multiples et convergentes (pas une inférence
isolée) :

- `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §2 : *« DG Afrique ne doit pas obliger l'utilisateur à
  comprendre son architecture interne avant de pouvoir agir. »* et §24 : *« Le Core structure la
  réalité ; l'interface traduit cette structure en expérience humaine compréhensible. Les
  référentiels, CAP, états techniques et relations internes appartiennent au système et ne
  doivent pas être une condition de compréhension pour agir. »*
- §28 (simplicité progressive) : *« Un débutant peut commencer par "Qu'est-ce que vous voulez
  faire ?" [...] La profondeur fonctionnelle augmente avec le besoin ; elle ne doit pas être
  imposée dès le premier écran. »*
- `docs/AI-RULES.md` : DG Afrique n'est explicitement **pas** un « catalogue d'applications » ni
  un « lanceur de satellites ».
- `docs/design/DESIGN-INVARIANTS.md` §4 confirme la conséquence directe côté navigation : six
  entrées humaines, pas 84 entrées CAP (voir §9 ci-dessus).

Aucune source contraire trouvée. Ce n'est donc pas seulement compatible avec le corpus : c'est un
invariant explicite et répété à trois niveaux documentaires distincts (fondation, règles produit,
design).

---

## 15. Parcours nouvel utilisateur

**Aucune doctrine « onboarding » ou « nouvel utilisateur » n'existe nommément dans le corpus**
(recherche exhaustive confirmée : zéro occurrence de « onboarding », « nouvel utilisateur »,
« premier arrivant » dans `docs/`). Le parcours ci-dessous est donc une **construction dérivée**
des invariants existants (§2 à §14 ci-dessus), pas une doctrine préexistante retrouvée telle
quelle — marqué explicitement comme tel, conformément à la règle de ne jamais promouvoir une
construction au rang d'invariant sans preuve.

| Étape | Ce qu'il faut faire comprendre | Ce qu'il faut activer | Ce qu'il ne faut PAS montrer trop tôt | Capacité backend disponible |
|---|---|---|---|---|
| **ARRIVÉE** (`/` Gateway) | DG Afrique est un réseau où les capacités deviennent des actions — pas un moteur de recherche ni un catalogue d'outils. | Un choix d'intention simple (aider / besoin / apprendre — déjà présent sur `gateway.blade.php`), jamais un formulaire. | Le référentiel CAP, ZUMRA comme structure juridique, la profondeur du Cerveau du Projet. | Gateway existant (§8 ci-dessus). |
| **COMPRÉHENSION** (`/decouvrir` si besoin d'en savoir plus) | « De la capacité à l'action » — exemples marqués Exemple, jamais présentés comme réels. | Rien d'engageant encore ; simple lecture. | Statistiques de vanité (déjà explicitement testé : `assertDontSee('4 250')`). | Landing existante. |
| **IDENTITÉ / COMPTE** | Un compte DG Afrique est distinct d'une adhésion ZUMRA (`ARCHITECTURE-PRODUIT-V2.md` §2.1) — l'un ne suppose pas l'autre. | Compte utilisable **sans** adhésion ZUMRA. | Ne jamais présenter l'adhésion ZUMRA comme une étape obligatoire de création de compte. | `AccountRegistrationController`, `MemberSessionController`. |
| **PREMIÈRE INTENTION** | *« L'intention peut précéder le profil complet »* (§3 fondation) — dire ce qu'on cherche à faire compte plus que remplir un profil exhaustif. | Une déclaration simple : capacité, besoin, ou envie d'apprendre — un seul champ de départ, pas un formulaire multi-écrans. | Le profil complet, la taxonomie de capacités structurée, tout formulaire administratif. | `CapabilityStatement`, `Need::create`. |
| **PREMIÈRE ACTION UTILE** | Le profil se construit aussi par l'action, pas seulement en le déclarant (§4 fondation). | Une action concrète et proche : répondre à un Besoin réel visible, rejoindre une ZUMRA existante, ou consulter Mon espace (état vide honnête si rien encore). | Un moteur de recommandation opaque, une file de missions avancées, l'ensemble du Fil non filtré. | Mon espace, Fil filtré, `PeopleDiscoveryController`. |
| **RETOUR ET APPROFONDISSEMENT** | Le Fil fait circuler le mouvement — la personne peut désormais comprendre pourquoi une activité lui est montrée. | Fil personnalisé (CAP-055), pertinence explicable. | Toute mécanique de dépendance (défilement infini, notifications de rétention). | `ActivityFeedService`, CAP-055. |

Ce parcours reste une proposition dérivée, ouverte à révision par une future mission UX
(voir §20) — sa légitimité vient de la cohérence avec les invariants cités, pas d'une autorité
propre.

---

## 16. Architecture UX conceptuelle

**Regroupement conceptuel dérivé des interfaces déjà existantes (§8, §9, §17) — pas une nouvelle
barre de navigation définitive.** Ce document ne tranche délibérément pas de maquette de
navigation : cela relève de `docs/design/DESIGN-INVARIANTS.md` et de sa procédure de gouvernance
(§14 de ce dernier).

Trois strates conceptuelles, cohérentes avec `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §21 et
`docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §1 :

```text
STRATE 1 — ENTRÉE ET PROMESSE
  Gateway (/) · Landing (/decouvrir) · Auth/Compte

STRATE 2 — LES TROIS CENTRES (navigation de premier niveau)
  Fil (circulation) · Mon espace (action personnelle) · [Personnes · Besoins · Projets · ZUMRA]
  (découverte et objets métier)

STRATE 3 — OUTILS SPÉCIALISÉS ET SURFACES SECONDAIRES
  Missions · Organisations · Partenariats · Événements · Contributions · Mesure collective ·
  Modération · Administration · satellites fédérés
  → jamais au même niveau de navigation que la strate 2 (§9) ; accessibles depuis le contexte
    (une fiche Projet, une fiche ZUMRA) plutôt que depuis un menu plat de 84 entrées.
```

Cette architecture explique pourquoi des domaines entièrement fonctionnels côté backend
(Partenariats, Événements, Mesure collective — voir §17-18) n'ont délibérément pas encore
d'entrée de navigation dédiée : ils sont *accessibles en contexte* (depuis une ZUMRA, une
Organisation, un Projet) plutôt que promus au rang de centre autonome — cohérent avec §9, pas une
lacune à combler mécaniquement CAP par CAP.

---

## 17. État réel des interfaces existantes

**Source : audit exhaustif route-par-route mené pour cette mission (`routes/web.php`, chaque
`routes/cap*.php`, `routes/moderation.php`, et chaque contrôleur/vue associés).**

| Statut | Domaines |
|---|---|
| **UI mature** (vue Blade réelle, fortement componentisée `<x-dg.*>`) | Gateway, Découverte/Landing, Espace membre, Profil, Fil/activité, Personnes (discovery), Recommandations, Besoins, Projets (dont Cerveau du Projet), Missions, ZUMRA, Organisations, Preuves, Transmissions, Notifications. |
| **UI technique** (vue Blade existe mais reste minimale/non componentisée) | Auth (connexion/inscription), Messagerie, Commentaires, Partages, tous les écrans de configuration Administration, paiement/carte/reçu ZUMRA. |
| **JSON uniquement** (backend complet, aucune vue) | Opportunités (`OpportunityEngine`), Partenariats (cycle de vie complet : activer/pause/reprendre/retirer/clôturer), **Événements** (CommunityEvent, CAP-068), Contributions, Ledger/Finance, **Mesure collective** (ImpactMetrics, CAP-080), Modération (membre et admin), Financement de projet. |
| **Inexistant à l'échelle d'une page** | Aucun domaine nommé n'est totalement absent de route/contrôleur — seule la couche « page consultable par un humain » manque pour le groupe JSON uniquement ci-dessus. |

Deux incohérences relevées par l'audit, à connaître pour prioriser une future mission UI (non
traitées ici, ce document reste documentaire) :

- **Contributions** (JSON uniquement) et **paiement d'adhésion ZUMRA** (a des vues
  `payment-status`/`receipt`) sont conceptuellement le même type de flux (retour de paiement +
  reçu) mais un seul des deux a une interface.
- **Missions** et **Transmissions** sont des machines à états quasi identiques
  (proposer→démarrer→bloquer→valider→annuler, participation, matching, jalons) dupliquées comme
  deux domaines parallèles — cohérent avec « un engin, deux applications » déjà noté dans
  `routes/cap069.php`, mais double la surface à maintenir visuellement.

---

## 18. Fonctionnalités backend actuellement invisibles

Domaines avec un moteur métier complet, testé, mais **aucune interface humaine** :

- **Partenariats** (`Partnership`) — cycle de vie entier (activation, pause, reprise, retrait,
  clôture) sans aucune page.
- **Événements** (`CommunityEvent`, CAP-068) — le domaine le plus récemment clarifié
  doctrinalement (voir `docs/capacites/specs/CAP-068-evenement.md`) : organisation par ZUMRA ou
  Organisation, inscription/désinscription, visibilité interne/publique — entièrement JSON.
- **Mesure collective** (`ImpactMetricsService`, CAP-080) — portrait chiffré du portail, d'une
  ZUMRA ou d'une Organisation, strictement anti-classement — entièrement JSON.
- **Opportunités** (`OpportunityEngine`) — moteur existe, contrairement à ses domaines-cousins
  Découverte/Recommandations qui ont chacun une page dédiée.
- **Modération** (membre et admin) — file de signalements et décisions, aucune console.
- **Ledger/Finance** et **Financement de projet** — traçabilité complète des écritures, aucune
  vue humaine.

Ces domaines ne sont pas des lacunes de doctrine : ce sont des capacités déjà closes ou
partiellement livrées (voir `docs/capacites/CAPABILITY-COVERAGE.md` pour le statut exact de
chaque CAP) qui attendent une décision produit sur *comment* et *où* les rendre visibles — matière
de la prochaine mission UI (§20), pas de ce document.

---

## 19. Décisions révisables et questions ouvertes

Classification stricte : **A = invariant produit** (ne peut être changé sans révision doctrinale
explicite et documentée), **B = décision UX existante mais révisable** (déjà en vigueur, changeable
par la procédure de gouvernance normale), **C = question ouverte** (aucune décision prise).

**A — Invariants produit** (déjà couverts en détail ci-dessus, rappelés ici) : identité « réseau
social d'action » (§3) ; interdiction des mécaniques de popularité/score/classement (§10) ; Mon
espace jamais de démonstration (§12) ; le Cerveau conseille, l'humain décide
(`ARCHITECTURE-PRODUIT-V2.md` §3.2) ; séparation compte/adhésion/contribution
(`ARCHITECTURE-PRODUIT-V2.md` §2.1, §7.1) ; ZUMRA ≠ Organisation ≠ Projet (§5).

**B — Décisions UX existantes mais révisables :**

- La règle DEMO-FIRST, REAL-DATA-TAKES-OVER (§12) s'est déjà révisée deux fois (introduite pour
  le Fil, étendue à Projets puis Besoins) — le corpus l'autorise explicitement à continuer
  d'évoluer par la procédure de gouvernance de `DESIGN-INVARIANTS.md` §14.
- Le regroupement de la strate 3 (§16) — quels domaines JSON-only méritent une interface, et sous
  quelle forme (page dédiée vs accès contextuel) — n'est fixé par aucune source ; c'est une
  proposition de lecture, pas un arbitrage produit.
- Le mécanisme de sponsorisation commerciale (`ARCHITECTURE-PRODUIT-V2.md` §10.2-10.4) est
  « candidat sérieux » mais non implémenté — sa politique d'affectation des revenus reste à
  formaliser.

**C — Questions ouvertes (aucune décision prise dans le corpus) :**

- Aucune doctrine d'onboarding formelle n'existe (§15 : parcours dérivé, pas retrouvé).
- Le terme définitif pour l'« extension opérationnelle d'une ZUMRA » (actuellement « Base »,
  provisoire) reste non tranché — `docs/foundation/DG-AFRIQUE-DOCTRINE.md` §8.
- Les Appels à projets (`ARCHITECTURE-PRODUIT-V2.md` §6) sont un « candidat sérieux, doctrine
  fonctionnelle à formaliser avant implémentation » — aucune UX n'existe encore à leur sujet.
- La gouvernance du fonds communautaire ZUMRA, la vérification des organisations/partenaires, et
  le statut juridique de ZAHAB restent listés comme « à formaliser avant implémentation » par
  `ARCHITECTURE-PRODUIT-V2.md` §13.

---

## 20. Gouvernance de ce document et prochaine mission recommandée

Toute évolution de ce document doit être consciente, argumentée, documentée, et distincte d'une
simple décision d'implémentation — même principe que
`docs/foundation/DG-AFRIQUE-DOCTRINE.md` (formule de clôture). Une contradiction entre ce document
et une source de couche 00-03 doit toujours se résoudre en faveur de la source de couche
inférieure (numéro plus petit) ; en cas de doute, la hiérarchie tranche, jamais une préférence de
rédaction.

**Mission UI suivante recommandée (UIUX-001, sous réserve de revue de ce document par
l'auteur du mandat) : le parcours nouvel utilisateur / première compréhension de DG Afrique**,
périmètre conceptuel `Gateway → Compréhension → Compte/Identité → Première intention → Première
action utile → Retour via Fil personnalisé` (§15 ci-dessus). Ce choix découle directement du
principe établi par la présente mission (§14) : l'interface s'organise selon l'expérience humaine,
pas selon la liste des CAP ni les trous d'interface du backend — **la priorité n'est donc pas
choisie en fonction des surfaces actuellement JSON uniquement** (§17-18). Partenariats,
Événements, Opportunités, Finance, Modération et Mesure collective restent des surfaces UI à
construire, mais seront intégrées progressivement dans les parcours pertinents plutôt que traitées
comme la prochaine priorité isolée. Toute future mission UIUX-001 doit respecter §9 (pas de
nouvelle entrée de navigation de premier niveau imposée par un trou d'interface), §12-13 (aucune
donnée de démonstration sans nécessité prouvée) et la procédure de gouvernance de
`docs/design/DESIGN-INVARIANTS.md` §14.

---

## 21. Addendum daté — UIUX-001 Phase B, Première intention (21 août 2026)

**Portée : le parcours nouvel utilisateur** (`gateway.blade.php`, `foundation.blade.php`,
`AccountRegistrationController.php`, `member/space.blade.php`, `MemberSpaceController.php`,
`activity/index.blade.php` — corrections de jargon uniquement). Suit la procédure de gouvernance
du §20.

**Décisions rendues durables par cette mission** (catégorie A pour ce qui suit, révisent la
catégorie C — question ouverte — de la « première intention » posée au §19) :

- **Routeur de « première intention »** : un membre sans relation réelle avec le réseau
  (`MemberSpaceController::isNewMember()`, dérivé uniquement de données métier existantes —
  jamais un champ `onboarding_completed`) voit sur `/espace` quatre intentions humaines — *Je peux
  apporter quelque chose / J'ai un besoin / Je veux découvrir / Je veux participer* — au lieu des
  actions rapides habituelles. C'est un **routeur UX vers des capacités déjà existantes**, jamais
  un nouveau modèle métier : Besoin → `needs.create` (inchangé) ; Découvrir → `activity.index`
  (inchangé) ; Participer → `zumra.groups.index`/`organizations.index` (inchangé, seulement
  reformulé en langage humain avant le terme ZUMRA) ; Apporter → capacité légère (ci-dessous). Un
  membre déjà actif garde les actions rapides habituelles, « Ouvrir ZUMRA » incluse — jamais forcé
  de repasser par ce routeur.
- **Capacité légère** (`QuickCapabilityController`) : une phrase suffit à déclarer une première
  capacité. Réutilise exactement `CapabilityStatementSynchronizer` et le domaine
  `CapabilityStatement` déjà établi — aucun modèle parallèle, aucune nouvelle table. Le profil
  complet en 7 étapes reste la voie d'approfondissement, inchangée.
- **Découverte publique limitée et réelle** (`LandingController::publicMoments()`) : un visiteur
  anonyme voit désormais, dans « Dans le réseau en ce moment », de vrais Besoins/Projets dès qu'ils
  existent en visibilité `PUBLIC` — même règle DEMO-FIRST, REAL-DATA-TAKES-OVER déjà établie pour
  le Fil/Projets/Besoins (§12), étendue ici à la Landing. Aucune règle d'autorisation nouvelle :
  réutilise exactement `NeedService::canView()`/`ProjectService::canView()`, dont la branche
  `VISIBILITY_PUBLIC` était déjà, avant cette mission, indépendante de l'identité de l'acteur.
- **Correction de fuite de jargon** : le mot « GAMAD » (institution invisible, §4) et les
  identifiants techniques `CAP-xxx` ne doivent jamais apparaître dans l'expérience normale —
  corrigé aux points où ils fuyaient réellement à l'écran (messages d'inscription/vérification,
  rail du Fil), testé par des assertions sur la réponse HTTP réelle plutôt que sur le seul code
  source Blade.
- **Correction de priorité** : `MemberSpaceController::priority()` ne peut plus jamais promouvoir
  l'activité d'un inconnu sans relation personnelle réelle (`relevance_reason`, CAP-055) au rang de
  priorité dominante — cohérent avec le test « Pourquoi cela m'est montré ? » du §8 de
  `DESIGN-INVARIANTS.md`, désormais également respecté par Mon espace, pas seulement par le Fil.

**Question ouverte non tranchée par cette mission** : le comportement des liens de découverte de
la Landing menant à un domaine complet (Fil/Besoins/Projets/ZUMRA) reste un mur de connexion —
seule la section « Dans le réseau en ce moment » a été rendue réelle. Reste catégorie C.

---

## 22. Addendum daté — UIUX-002 Phase B, boucle d'action quotidienne (21 août 2026)

**Portée : Mon espace, Notifications, Fil, Opportunités** (`MemberSpaceController.php`,
`NotificationSourceRegistry.php`/`NotificationService.php`, `ZumraAttentionSource.php`,
`OpportunityController.php`, `ZumraSpaceController.php`, `ZumraGroupController.php`). Suit la
procédure de gouvernance du §20.

**Responsabilités respectives, rendues stables par cette mission** :

- **Mon espace = priorité personnelle.** Une seule action dominante à la fois, jamais une liste —
  invariant conservé (§9 ci-dessus). Le reste de l'attention reste découvrable via un signal
  texte discret (jamais un badge), pas une seconde liste.
- **Notifications = l'ensemble des éléments qui demandent information ou action.** Source complète
  et personnelle, jamais concurrente de la priorité dominante de Mon espace (CAP-054).
- **Fil = circulation du réseau.** Ce que fait bouger le réseau, avec pertinence personnelle
  explicable quand elle existe, jamais un filtre (CAP-055, inchangé).
- **Opportunités = possibilités d'action pertinentes et explicables.** Une projection en lecture
  seule des objets métier existants (CAP-064), jamais un score ni un classement affiché — chaque
  carte explique « pourquoi » à partir d'une relation métier réelle.

**Décisions rendues durables** :

- Les signaux ZUMRA réellement actionnables (demande d'adhésion à décider pour un responsable,
  responsabilité fondatrice proposée personnellement) ont désormais **une seule source
  applicative** (`ZumraAttentionSource`), réutilisée par Mon espace, Notifications et le hub ZUMRA
  — jamais trois définitions indépendantes du même fait.
- Une responsabilité proposée (`ZumraGroupRole::STATUS_PROPOSED`) est désormais découvrable et
  acceptable via la fiche de la ZUMRA — aucun refus n'est proposé, cette transition n'existant pas
  dans le métier.
- « Pour vous maintenant »/« Cette semaine » sur Mon espace ne montrent plus que des items
  personnellement pertinents (relevance_reason réel) — jamais une activité générique du réseau
  présentée comme personnellement destinée au membre.
- L'ordre inter-domaines de la priorité dominante reste volontairement inchangé (historique de
  construction, pas un moteur de score) — seule une insertion minimale (ZUMRA, à côté de l'unique
  autre maillon ZUMRA déjà présent) a été faite. Une révision plus large de cet ordre reste hors
  périmètre, à documenter séparément si elle devient nécessaire.

**Hors périmètre, restant catégorie C** : CommunityEvent, Partnership, « Mes organisations »
(gaps backend réels, cf. rapport UIUX-002 Phase A §18) ; toute transformation de « À faire » en
liste sur Mon espace (tension explicitement non résolue avec l'invariant d'une seule priorité
dominante, cf. rapport Phase A §9).

**Mise à jour (§22) — « Mes Organisations » n'est plus un gap backend, voir §27 :** l'audit
Phase A d'UIUX-006 (22 août 2026) a réaudité ce constat après CAP-067 : la donnée nécessaire
(appartenance active à une Organisation) était déjà triviale à requêter — `manageableOrganizations()`
existait même déjà comme fonction privée réutilisée trois fois pour un autre usage. Le gap était
UI, jamais backend. UIUX-006 Phase B a livré la surface manquante dans Mon espace.

---

## 23. Addendum daté — UIUX-003 Phase B, navigation contextuelle et premier espace Événement (22 août 2026)

**Portée : Besoin/Projet (retour de contexte), ZUMRA (espace contextuel), Organisation (fiche),
CommunityEvent/CAP-068 (première fiche)** (`NeedController.php`/`needs/show.blade.php`,
`ProjectController.php`/`projects/show.blade.php`, `ZumraGroupController.php`/
`zumra/groups/show.blade.php`, `OrganizationController.php`/`organizations/show.blade.php`,
`CommunityEventController.php`/`community-events/show.blade.php`). Suit la procédure de
gouvernance du §20. Fait suite au constat d'architecture de l'audit UIUX-003 Phase A : ZumraGroup
est un vrai carrefour relationnel (Besoin/Projet/Mission/Événement/Preuve s'y rattachent tous),
Organization reste une structure de bord (seule relation réelle : organisatrice d'Événement).

**Décisions rendues durables** :

- **Retour de contexte généralisé.** Le patron déjà établi sur Mission/Proof (`contextUrl`/
  `contextLabel`) s'étend maintenant à la fiche Besoin et à la fiche Projet : quand le propriétaire
  (ZUMRA ou Projet) est réellement résolu côté serveur, son nom devient un lien réel vers sa fiche ;
  sinon le texte neutre déjà existant reste inchangé. Aucune relation n'est jamais fabriquée pour
  l'occasion.
- **La ZUMRA devient un espace contextuel réel, pas un nouveau menu.** Sa fiche montre désormais
  ses Missions et Événements réels (via `MissionService::forContext()`/
  `CommunityEventService::forZumraGroup()`, déjà existants, jamais recalculés), visibles seulement
  pour un membre actif ou un responsable — exactement l'audience déjà autorisée par ces services.
  Le contenu déjà présent (gouvernance, Besoins, Projets) n'a pas été redesigné.
- **L'Organisation reste une structure de bord.** Sa fiche gagne uniquement ses Événements
  réellement organisés (relation `organizer_type=ORGANIZATION` déjà réelle) — aucune relation
  Organisation→Projet/Besoin/Mission n'existe dans le métier et aucune n'a été inventée ici. Ce
  choix n'est pas une simplification temporaire : c'est le reflet exact de ce que confirme
  l'audit Phase A (absence de `OWNER_ORGANIZATION`, d'`OrganizationMissionContext`, de route
  `/organisations/{o}/besoins`).
- **CommunityEvent (CAP-068) reçoit sa première interface humaine.** Une fiche dédiée répond
  clairement à où suis-je / qui organise / quand / puis-je participer : organisateur réel avec
  retour vers lui, date, état, visibilité, et une action d'inscription/désinscription strictement
  gouvernée par `CommunityEventService::canView()`/`register()`/`unregister()` — jamais une
  autorité nouvelle. Aucun catalogue global « Événements » n'existe dans la navigation ; un
  Événement se découvre uniquement depuis son organisateur réel.
- **Topbar et tabbar restent inchangés** (Fil · Personnes · Agir · ZUMRA · Moi) — la découverte
  contextuelle ne devient jamais un nouveau niveau de navigation globale.

**Hors périmètre, restant catégorie C** : Partnership (chantier séparé) ; Ledger, ImpactMetrics,
Modération, Finance (non touchés) ; l'anomalie `projects/overview-v2`/`pv-seed` (dette UI
distincte, non traitée ici) ; tout moteur de priorité ou de recommandation nouveau pour les
Événements — la fiche n'affiche que ce que le service métier autorise déjà, sans classement.

**Proposition de portée pour UIUX-004** : achever la boucle de gouvernance de l'Événement
(gestion — modifier/annuler/marquer tenu, aujourd'hui exposée en JSON seulement) et instruire
Partnership, seul lien réel restant entre Organisation et le reste du réseau.

---

## 24. Addendum daté — UIUX-004, parcours organisateur complet de l'Événement (22 août 2026)

**Portée : fiche CommunityEvent uniquement** (`CommunityEventController.php`,
`community-events/show.blade.php`, plus l'affichage d'état des cartes Événement déjà introduites
par UIUX-003 sur `zumra/groups/show.blade.php`/`organizations/show.blade.php`). Suit la procédure
de gouvernance du §20. Ferme la boucle ouverte par UIUX-003 : découvrir → consulter → participer
→ organiser → constater l'état final.

**Décisions rendues durables** :

- **La fiche Événement devient le centre complet du parcours**, pas seulement sa consultation.
  Pour l'organisateur réel (`ZumraGroupService::isLeader()`/`OrganizationService::isManager()`,
  jamais une autorité recalculée), elle expose désormais les trois transitions déjà existantes
  du service — modifier, annuler, marquer tenu — au même endroit que la consultation, sans
  jamais créer de workflow ou d'écran de gestion séparé.
- **Vocabulaire produit stabilisé pour le cycle de vie de l'Événement** : `SCHEDULED` → « À
  venir », `COMPLETED` → « Tenu », `CANCELLED` → « Annulé ». Ce vocabulaire s'applique
  uniformément à la fiche et aux cartes Événement affichées depuis une ZUMRA ou une Organisation
  — celles-ci doivent toujours refléter l'état réel de l'Événement, jamais seulement sa date.
- **Une information agrégée peut être montrée à l'organisateur quand le service l'autorise déjà
  et que rien n'est inventé pour l'occasion.** Le nombre d'inscrits (`participants()->count()`,
  déjà réservé à l'organisateur par le service) est affiché comme fait informatif — jamais la
  liste des identités, qui reste strictement interne au service.
- **Protection contre le clic accidentel proportionnée à l'impact réel** : l'action positive et
  attendue (marquer tenu) reste un geste simple ; l'action qui affecte les personnes déjà
  inscrites (annuler) est reléguée derrière une révélation explicite et une confirmation, suivant
  le patron déjà établi ailleurs dans le portail (retrait d'équipe/d'assignation) plutôt qu'un
  mécanisme nouveau.
- **Une seule action toujours visible, le reste en retrait.** Sur la fiche, l'organisateur voit
  un bouton principal (marquer tenu) et deux révélations secondaires (modifier, annuler) — jamais
  une barre de plusieurs boutons d'action équivalents, y compris à 390px.

**Hors périmètre, restant catégorie C** : Partnership, Ledger, ImpactMetrics, Modération, Finance,
Opportunités, l'architecture de Mon espace, la navigation globale desktop/mobile, l'anomalie
`overview-v2`/`pv-seed` — aucun n'a été touché. La liste nominative des inscrits reste strictement
réservée au service (`CommunityEventService::participants()`), jamais exposée dans cette fiche.

---

## 25. Addendum daté — UIUX-005 Phase B, Organisation comme acteur établi & Partnership (22 août 2026)

**Portée : couche Partnership (CAP-065) humanisée sur ses contextes réels** —
`PartnershipService.php`, `PartnershipController.php`, `PresentsPartnerships` (nouveau trait
partagé), `x-dg.partnership-row`/`x-dg.partnership-propose-form` (nouveaux composants), fiches
Organisation/Besoin/Projet/ZUMRA. Suit la procédure de gouvernance du §20. Fait suite à l'audit
CORE-DG-001 (alignement GAMAD Core), qui a confirmé que rien de cette portée ne dépend du Core :
tout reste consommable depuis le runtime DG Afrique existant.

**Décision produit centrale, à préserver strictement** : les capacités intrinsèques d'une
Organisation (« ce qu'elle sait apporter ») ne sont **jamais** déduites de ses Partnerships. Un
Partnership est une collaboration concrète dans un contexte réel, pas une déclaration de
capacité générale — les confondre transformerait silencieusement chaque proposition ponctuelle en
entrée de catalogue permanent. Le runtime ne possède aujourd'hui aucune décoration de capacité
Organisation indépendante d'un Partnership (CAP-067 en dépendrait, hors périmètre) ; tant que ce
manque n'est pas comblé, aucune section « Ce que cette Organisation peut apporter » n'est
fabriquée à partir des `capability_label` existants.

**Décisions rendues durables** :

- **Collaborations, pas capacités.** La fiche Organisation gagne une section « Collaborations »
  (les Partnerships où elle est fournisseur, filtrés par `PartnershipService::canView()`), jamais
  un catalogue de capacités. Besoin/Projet/ZUMRA gagnent symétriquement une section
  « Partenariats » pour les collaborations liées à leur propre contexte.
- **Aucune fiche Partnership autonome.** Chaque carte (`x-dg.partnership-row`) porte toute
  l'information utile — fournisseur, ce qui est apporté, contexte réel, état — et les actions du
  cycle directement en ligne. Un Partnership n'est jamais un centre produit séparé, cohérent avec
  la strate 3 (§16) : accessible en contexte, jamais promu à un niveau de navigation autonome.
- **La proposition ne part jamais du vide.** Le formulaire « Notre organisation peut apporter… »
  n'apparaît que depuis un Besoin/Projet/ZUMRA déjà consultable, réservé aux organisations que
  l'acteur gère réellement (`OrganizationService::isManager()`) — jamais depuis la fiche
  Organisation elle-même, et jamais un bouton global « Créer un partenariat ». Après soumission,
  l'acteur reste dans le contexte d'origine (jamais redirigé vers une réponse JSON).
- **Vocabulaire humain stabilisé pour le cycle Partnership** : `PROPOSED` → « Proposé », `ACTIVE`
  → « Actif », `PAUSED` → « En pause », `ENDED` → « Terminé ». Aucune transition « Refuser »
  n'existe dans le service ; aucune n'a été inventée dans l'interface.
- **Autorité jamais recalculée.** `PartnershipService::isProviderActor()`/
  `canManageAsContextAuthority()` (promue/ajoutée publique dans ce chantier, sans changement de
  comportement) restent la seule décision réelle ; les gabarits n'affichent un bouton que si le
  service confirmerait l'action.
- **Le fournisseur `PERSON` reste intact.** Aucune nouvelle interface n'a été construite pour ce
  parcours (resté JSON, comme avant), mais les Partnerships déjà proposés par une Personne
  s'affichent correctement partout où un Partnership est montré — « Vous » pour le fournisseur
  lui-même, son nom de découverte s'il l'a consenti, « Membre DG Afrique » sinon (même doctrine
  que §9).

**Architecture identité Organisation — doctrine confirmée par l'audit CORE-DG-001** :

```
Identité/Organisation GAMAD Core (CAP-CORE-001/002)
   → projection Organisation DG Afrique (LOCAL_PROJECTION)
   → projection commerciale G-POS (LOCAL_PROJECTION distincte)
```

Une seule vérité canonique côté Core, deux projections produit indépendantes qui ne se
connaissent pas entre elles mais pourront un jour partager la même racine d'identité. **Une
Organisation DG Afrique peut exister durablement sans jamais avoir de présence G-POS** — G-POS
est un outil spécialisé/extractible (catalogue, produits/services, relations commerciales), pas
une extension automatique de toute Organisation. Une future intégration pourra permettre à un
acteur G-POS déjà existant de rejoindre DG Afrique comme Organisation en partageant la même
racine Core, sans jamais fusionner les deux domaines : DG Afrique reste le réseau social d'action,
G-POS reste l'outil commercial. Le lien Core lui-même (CAP-067) n'est pas construit dans ce
chantier — l'audit CORE-DG-001 a confirmé que le Core possède déjà l'identité et le registre
d'organisations (CAP-CORE-001/002, `GO`), mais qu'aucune délégation ne permet encore à un satellite
autorisé (dont DG Afrique, `PRD-GAMAD-005`) de créer ou rattacher une Organisation Core — un
chantier Core séparé, pas une omission de celui-ci.

**Sponsorisation et Fil — doctrine préservée, rien construit ici** : une Organisation pourra un
jour promouvoir un contenu commercial dans le Fil, notamment issu de son catalogue G-POS, selon le
mécanisme déjà posé par `ARCHITECTURE-PRODUIT-V2.md` §10 (pertinence ≠ sponsorisation, signalée
explicitement, jamais confondue avec une recommandation organique). Aucun code de sponsorisation
n'existe ni n'est introduit ici — ce paragraphe documente seulement que la doctrine reste
compatible avec cette évolution future.

**Hors périmètre, restant catégorie C** : CAP-067 (identité Organisation Core), G-POS (aucun
code, aucun faux catalogue/prix/stock), sponsorisation Fil (doctrine seule), gouvernance
Organisation (inviter/approuver/retirer un membre, modifier l'identité — service déjà prêt,
aucune route/vue construite ici), CAP-CORE-021 Matching (audité, non consommé — `CORE_NOT_READY`).

**Mise à jour (§25) — CAP-067 fermée depuis, voir §26 :** l'affirmation ci-dessus (« aucune
décoration de capacité Organisation indépendante d'un Partnership ») ne vaut plus depuis la
fermeture de CAP-067 (22 août 2026). La décision produit centrale du §25 — ne jamais déduire une
capacité Organisation d'un Partnership — reste, elle, intégralement en vigueur et a précisément
guidé la conception de CAP-067 : la nouvelle section « Capacités » est un fait déclaré
explicitement, jamais une lecture des Partnerships.

## 26. Addendum daté — CAP-067, capacités des Organisations & raccordement canonique GAMAD Core (22 août 2026)

**Portée : identité canonique + capacités de l'Organisation** — `OrganizationService.php`,
`GamadCoreClient.php` (nouvelles méthodes `provisionOrganizationIdentity()`/`createOrganization()`/
`resolveOrganizationByIdentity()`), `OrganizationCapabilityService.php` (nouveau),
`OrganizationCapabilityController.php` (nouveau), fiche Organisation. Consomme la délégation
livrée par CORE-ORG-DELEGATION-001 (`gamad-core`, PR #85) — aucun code `gamad-core` modifié ici.
Spec complète : `docs/capacites/specs/CAP-067-identite-organisationnelle.md`.

**Ce qui change, en une phrase par décision** :

- **Identité canonique réelle.** Toute nouvelle Organisation est raccordée à une identité
  (CAP-CORE-001) et une fiche (CAP-CORE-002) canoniques GAMAD Core avant toute écriture locale —
  `core_identity_reference`/`core_organization_reference`/`core_link_status` sur `Organization`.
  Un échec Core interrompt la création ; aucune fausse Organisation locale n'est jamais finalisée.
- **Capacités Organisation, réutilisant le moteur existant.** `CapabilityStatement` porte
  désormais un `holder_type` (`PERSON`/`ORGANIZATION`) ; un porteur Organisation ne touche jamais
  `core_identity_reference` (réservée aux personnes). Une capacité Organisation reste un fait
  métier explicite, déclaré par un manager habilité — **jamais** déduit d'un Partnership, d'un
  Projet, d'un événement ni des capacités personnelles du manager (§25 confirmé, pas contredit).
  `KIND_POSSESSED` seulement, aucun score, aucun raccordement au moteur de matching.
- **ATTACH délibérément arrêté, pas construit.** La résolution d'une Organisation Core existante
  par son identité (`GET /organisations/resolution/{identite}`) est une lecture pure, déjà câblée
  côté client Core, mais **aucun parcours produit ne l'expose** : DG Afrique ne dispose d'aucune
  preuve d'autorité permettant de garantir qu'un acteur qui « retrouve » une Organisation Core
  existante en est un représentant légitime. Inventer cette règle aurait ouvert un risque
  d'appropriation arbitraire — gap documenté (§ dédiée de la spec CAP-067), pas comblé par un
  raccourci.
- **Organisations déjà existantes : `UNLINKED`, jamais un rapprochement par nom.** Aucune
  migration automatique. CAP-066 datant de deux jours seulement, aucune Organisation de production
  réelle n'existe à régulariser à ce stade.
- **G-POS et Matching : architecture préservée, rien construit.** Le modèle permettra un jour
  qu'une Organisation G-POS soit retrouvée depuis DG Afrique via sa référence canonique (une fois
  ATTACH construit), et qu'un besoin se corresponde à une capacité `ORGANIZATION` (une fois les
  moteurs de matching explicitement étendus) — aucun code de l'un ou l'autre ici.

**Hors périmètre, restant catégorie C** : ATTACH (gap documenté ci-dessus), lien Partnership ↔
`CapabilityStatement` Organisation réelle (CAP-065 continue de traiter un fournisseur Organisation
par déclaration libre `capability_label` — un futur petit chantier CAP-065 pourrait le faire
converger avec le chemin déjà réel de la Personne, non traité ici pour rester dans le périmètre
demandé), raccordement au moteur de matching, gouvernance Organisation Core (activer/suspendre/
dissoudre/retirer — réservée à `AUT-GAMAD-001`), tout code G-POS.

## 27. Addendum daté — UIUX-006, Organisation comme acteur du réseau (22 août 2026)

**Portée : évolution UI uniquement, aucun nouveau moteur métier** — `MemberSpaceController.php`/
`member/space.blade.php` (« Mes Organisations »), `OrganizationController.php`/
`organizations/show.blade.php` (fiche recomposée, membres humanisés). Suit la procédure de
gouvernance du §20. Fait suite à l'audit Phase A qui a réétabli, après CAP-065/CAP-067 (§26,
PR #111), l'état réel de la fiche Organisation, ses chemins de découverte et le statut de
« Mes Organisations ».

**Décisions rendues durables** :

- **« Mes Organisations » vit dans Mon espace, jamais dans la navigation globale.** Un bloc
  compact et persistant (jamais réservé au routeur de première intention UIUX-001) apparaît dès
  qu'un membre appartient activement à au moins une Organisation — réutilisant strictement
  `OrganizationMembership` actif, aucun nouveau concept de propriété. Une seule Organisation ouvre
  directement sa fiche ; plusieurs restent distinguables et individuellement ouvrables. Un membre
  sans Organisation ne voit aucun bloc, ni petit ni grand — le routeur de première intention
  (§21) reste la seule porte de découverte initiale, cohérent avec §9 (aucune nouvelle entrée de
  navigation imposée par un trou d'interface).
- **La fiche Organisation raconte une histoire en quatre temps**, sans fusionner aucun modèle
  métier : identité → « Ce que cette organisation peut apporter » (capacités CAP-067, lecture
  seule pour tous) → « Dans le réseau » (Collaborations et Événements regroupés visuellement,
  jamais un pseudo-fil fabriqué) → « Gestion » (manager seul, gestes déjà réels — déclarer/retirer
  une capacité — jamais une interface non cadrée fabriquée pour remplir la section : invitation,
  approbation, retrait de membre et modification de l'identité restent hors de cette PR, comme
  déjà noté au §25). Une absence de Collaboration et d'Événement produit un état discret, jamais
  une carte massive.
- **Capacités en vocabulaire humain.** Le libellé « Ce que cette organisation peut apporter »
  remplace le terme technique « Capacités » sur la fiche — aucune fuite de `CAP-067`,
  `CapabilityStatement`, `holder_type` ni de référence Core, vérifié par test sur la réponse HTTP
  réelle (même discipline que §21).
- **Membres avec une identité lisible.** La carte Membres résout désormais un libellé humain par
  membre (`Vous` pour l'acteur courant, un nom de découverte volontairement consenti, sinon
  `Membre DG Afrique`) au lieu de ne répéter que le rôle — même convention que
  `PresentsPartnerships::partnershipProvider()` (§25), jamais une référence Core exposée.
- **Correction d'un bogue de largeur intrinsèque `<fieldset>`.** La vérification mobile obligatoire
  à 390px a révélé qu'un `<fieldset>` contenant un long libellé de capacité et un bouton force un
  débordement horizontal indépendamment de tout `min-width:0` posé sur ses enfants flex (le
  navigateur calcule la largeur intrinsèque du `<fieldset>` lui-même) — corrigé en posant
  `min-width:0` sur le `<fieldset>` de la section Gestion. Un correctif local à cette section, pas
  une revue globale de tous les `<x-dg.fieldset>` du portail.

**Hors périmètre, restant catégorie C** : `ActivityFeedService`/CAP-055 (l'absence
d'Organisation/Partnership/CommunityEvent dans le Fil reste un chantier distinct, documenté par
l'audit Phase A — non corrigée ici), `ImpactMetrics` sur la fiche, matching Organisation, G-POS,
ORG-ATTACH, sponsorisation, catalogue commercial, gouvernance de membre (invitation/approbation/
retrait), modification de l'identité, redesign général.

## 28. Addendum daté — UIUX-007 Phase B, ponts UX de la Personne (22 août 2026)

**Portée : fermeture de ponts UX entre capacités déjà réelles, aucun nouveau modèle métier.**
Fait suite à l'audit Phase A (PERSONNE → POSSIBILITÉS → MOUVEMENT) et à la doctrine humaine
canonisée dans `docs/canon/DOCTRINE-GAMAD.md` §6 (une ZUMRA est réelle et opérationnelle dès sa
création par son premier responsable). Cette section ne duplique pas cette doctrine — elle
n'enregistre que les décisions UX qu'elle rend concrètes. Fichiers touchés : `member/space.blade.php`
et `MemberSpaceController.php` (routeur, rail « Mes structures », Opportunités), `zumra/groups/
show.blade.php` (collaborateurs, Besoin/Projet, Événement), `needs/create.blade.php`,
`projects/create.blade.php` et leurs contrôleurs (préremplissage contextuel), `NeedController.php`/
`needs/show.blade.php` et `ProjectController.php`/`projects/show.blade.php` (pont vers Mission),
`organizations/show.blade.php` + `CommunityEventController.php` + nouvelle vue
`community-events/create.blade.php` (gabarit d'Événement manquant), `discovery/show.blade.php` +
`TransmissionController.php`/`transmissions/create.blade.php` (Personne → Transmission),
`transmissions/show.blade.php` + `ProofController.php`/`proofs/create.blade.php` (Transmission →
Preuve).

**Décisions rendues durables** :

- **Créer sa ZUMRA n'est jamais présenté comme nécessitant un collectif préalable.** Le routeur de
  première intention (§21) n'affiche cette porte que pour un tout nouveau membre — qui, par
  construction (`isNewMember()`), n'a encore aucune adhésion active au Programme ZUMRA — et mène
  donc honnêtement à l'adhésion d'abord, jamais à un lien qui échouerait. Le lien de création
  directe réel vit dans le rail « Mes structures » de Mon espace, pour un membre du Programme actif
  sans groupe : « Vous pouvez créer votre ZUMRA dès maintenant — Seul·e, sans attendre de réunir un
  collectif au préalable. » Ceci corrige une lacune découverte pendant cette mission : une première
  version plaçait ce lien réel à l'intérieur du routeur de première intention lui-même, où il
  n'était en pratique jamais atteignable (le routeur n'apparaît que quand l'adhésion est encore
  absente).
- **Développer son collectif reste un geste du responsable, jamais un moteur de recrutement.** La
  fiche ZUMRA ajoute « Trouver des collaborateurs → » (réutilise `people.index` tel quel) réservé au
  responsable, et « + Créer un besoin » / « + Proposer un projet pour cette ZUMRA » réservés à tout
  membre actif — aucun compteur, objectif ou récompense de recrutement.
- **Le contexte d'origine se conserve sans fabriquer d'autorité.** Depuis la fiche ZUMRA, créer un
  Besoin ou un Projet préremplit le porteur collectif uniquement si l'acteur y est déjà membre actif
  (même liste que le formulaire vérifie déjà lui-même) — jamais une confiance accordée au seul
  paramètre d'URL.
- **Le gabarit de création d'Événement manquant a été construit** pour ZUMRA et Organisation : la
  logique métier (`CommunityEventService::createForZumraGroup/createForOrganization`) existait déjà
  depuis CAP-068, seule la vue GET manquait. Un seul formulaire partagé, même autorité que le
  service (`ZumraGroupService::isLeader()` / `OrganizationService::isManager()`), jamais dupliquée.
  La fiche Organisation garde son format en quatre temps (§27) — un seul lien de plus dans
  « Gestion », jamais un tableau de bord.
- **Une personne découverte peut recevoir une proposition de Transmission, jamais une relation
  automatique.** « Proposer une transmission » depuis une fiche Personne réutilise le moteur
  Transmission existant tel quel : la personne visée est invitée (`TransmissionParticipationService
  ::invite()`), avec le statut `INVITED`, jamais `ACCEPTED` — son acceptation explicite reste
  entièrement requise. Le préremplissage du formulaire ne fait confiance qu'à un profil réellement
  découvrable et consentant au moment de l'affichage, jamais au seul paramètre d'URL.
- **Une Transmission réellement terminée ouvre une porte facultative vers le Carnet de preuves,
  jamais une preuve fabriquée automatiquement.** Le pont n'apparaît que pour un participant accepté
  d'une Transmission au statut `COMPLETED_CONFIRMED`/`COMPLETED_BY_CONTEXT`. Le préremplissage du
  Carnet de preuves vérifie côté serveur que l'acteur a réellement participé à la Transmission citée
  avant de préremplir quoi que ce soit — un visiteur non participant n'obtient aucun préremplissage,
  même avec le bon paramètre d'URL.
- **« Je peux apporter cette capacité » sur un Besoin réutilise la seule transition métier qui
  représente déjà réellement une réponse humaine à un Besoin.** Le mini-audit préalable (mandaté
  avant tout code) a confirmé que `NeedMissionContext::canPropose()` — adhésion Programme ZUMRA
  active + visibilité + Besoin non archivé — est déjà ouvert à tout membre concerné, pas seulement à
  l'autorité de décision du Besoin ; c'est ce mécanisme, déjà réel, qui est rendu visible
  (`needs.missions.create`). Partnership n'a délibérément pas été choisi : c'est une relation métier
  distincte, jamais une réponse générique « aider ».
- **« Proposer une Mission » depuis un Projet rend visible une porte déjà réelle mais jusqu'ici
  cachée dans le Cerveau.** Même autorité que le Cerveau (`ProjectMissionContext::canPropose()`),
  aucune logique dupliquée, le Cerveau reste le lieu de gestion complet.
- **Mon espace regroupe visuellement « Mes ZUMRA » et « Mes Organisations » sous « Mes structures »**
  — un simple partage de libellé de survol entre deux cartes qui restent distinctes : aucune fusion
  de modèle, ZUMRA ≠ Organisation.
- **Opportunités reste la même projection de Missions compatibles (§18) — le moteur n'a pas été
  étendu.** Sa découvrabilité depuis Mon espace est améliorée par un compte réel (« 3 Missions
  rapprochées… » au lieu d'un texte générique) quand des opportunités existent, réutilisant
  `OpportunityEngine::forIdentity()` tel quel — jamais injecté dans le Fil.
- **Correction d'un bogue de migration pré-existant, hors du périmètre UX de cette mission mais
  bloquant toute régression complète en local.** Trois migrations (`fix_project_brain_*_actor_core
  _reference_type`, datées du 19-20 août 2026) exécutaient une syntaxe `ALTER COLUMN … TYPE …`
  propre à PostgreSQL sans le garde `DB::connection()->getDriverName() === 'pgsql'` déjà en usage
  ailleurs dans le projet (`harden_mission_tables.php` et consorts) — elles faisaient donc échouer
  toute la suite de tests sous SQLite en local. Corrigées pour suivre exactement la convention
  déjà établie (guard + no-op sous SQLite, colonne déjà assez souple pour ne pas nécessiter de
  conversion de type). Aucun changement de logique métier.

**Dette documentée, non résolue ici (catégorie C)** :

- **`experience_proofs_text` (profil complet) et le Carnet de preuves (`Proof`) restent deux
  systèmes distincts et non reliés.** Le premier est une liste libre auto-déclarée (portfolio,
  certificat, référence) sans aucune vérification tierce ; le second porte témoin, reconnaissance de
  contexte et contestation. Cette mission documente la divergence sans y toucher : aucune migration,
  aucune suppression, aucun rapprochement de données. Un futur chantier pourrait clarifier si le
  champ texte libre doit rester un complément narratif du profil ou converger vers le Carnet de
  preuves — décision volontairement laissée ouverte.
- **La fiche Projet présente un débordement horizontal mobile pré-existant** (bandeau d'actions
  d'en-tête et onglets d'ancrage « Besoins/Ressources/Documents/Conversations »), confirmé identique
  sur `main` et donc non causé par cette mission. Le nouveau lien « Proposer une Mission → » de
  cette mission n'y contribue pas (vérifié élément par élément à 390px). Non corrigé ici — hors
  périmètre des ponts UX demandés.
- **Formation** : aucun nouvel écran créé, les trois dimensions déclaratives existantes restent
  inchangées. Un lien léger a été ajouté depuis la déclaration rapide de capacité (Mon espace) vers
  le profil complet — les deux écrivent déjà le même champ (`existing_skills`/`CapabilityStatement`),
  aucune duplication introduite.

**Hors périmètre, restant catégorie C** : cycle de vie ZUMRA (`CONSTITUTING`→`ACTIVE`, seuil de 50,
cinq responsabilités), GAMAD Core, Wasplex, G-POS, refonte du Fil, tout modèle de progression
humaine (`HumanProgress`, `GamadLevel`, `StabilityScore`, `RecruitmentScore`, `SpiritualScore`) —
aucun n'a été touché ni introduit.

## 29. Addendum daté — UIUX-008 Phase B, fermeture des portes existantes (22 août 2026)

**Portée : correction de ruptures concrètes déjà identifiées (audit Phase A), aucune refonte de
Mon espace, aucun nouveau modèle métier, aucune extension générale du Fil/Notifications.** Fichiers
touchés : `components/dg/agir-sheet.blade.php` (lien corrigé), `member/space.blade.php` (rail « Mes
outils »), `projects/index.blade.php` (voie directe), `OrganizationController.php` +
`organizations/show.blade.php` + `routes/web.php` (approbation d'adhésion), `activity/index.blade.php`
(copie obsolète retirée), `OpportunityEngine.php` (exclusion des Missions déjà rejointes).

**Décisions rendues durables** :

- **Invariant d'indépendance au Cerveau, désormais explicite :** *« Toute action métier essentielle
  exposée par DG Afrique doit conserver une voie utilisable sans Cerveau lorsque le domaine possède
  déjà une interface déterministe correspondante. »* Cet invariant ne constitue pas une doctrine
  générale sur l'économie de l'IA (Crédit IA, coût, fournisseurs) — ce sujet reste un chantier séparé,
  volontairement non abordé ici. Il documente uniquement ce que `/projets` respecte désormais : le
  Cerveau du Projet reste mis en avant (bouton d'en-tête, carte héro, accompagnement), mais une voie
  directe (« Créer directement un projet → ») reste toujours visible sur la même carte, sans dupliquer
  le formulaire ni dégrader le Cerveau.
- **La sheet Agir ne propose plus qu'un lien de Transmission réellement fonctionnel.** Le lien
  pointait vers `member.profile.edit` (bogue identifié par l'audit Phase A) ; c'était la seule porte
  globale vers la création de Transmission, et elle était inopérante.
- **Mes transmissions et Mon carnet de preuves rejoignent Missions et Opportunités dans le rail
  « Mes outils » de Mon espace.** Ces deux pages existaient et fonctionnaient déjà ; elles n'étaient
  simplement reliées depuis aucune navigation avant. Aucun tableau de bord n'a été créé, aucune
  donnée agrégée nouvelle.
- **Une demande d'adhésion à une Organisation peut désormais être approuvée.**
  `OrganizationService::approveRequest()` existait déjà sans route ni interface ; seule une surface
  manager minimale a été ajoutée sur la fiche Organisation, réservée à `isManager()`, réutilisant
  strictement le service existant. Aucun refus n'a été introduit : aucune transition métier de refus
  n'existe pour une demande `REQUESTED` (`removeMember()` exige `STATUS_ACTIVE`), et aucune n'a été
  inventée.
- **Une Mission à laquelle la personne a déjà une relation active (offerte, invitée ou acceptée —
  `MissionAssignment::CURRENT_STATUSES`) ne se présente plus comme une Opportunité.** Une relation
  résolue (déclinée, retirée par la personne, libérée, retirée par l'autorité) ne l'exclut jamais :
  la Mission redevient une possibilité réelle une fois la personne effectivement libre d'y répondre
  à nouveau. Aucun score introduit, aucune extension à d'autres types d'Opportunités.
- **Le rail du Fil ne prétend plus que la Transmission « rejoindra » le Fil plus tard** — elle y est
  déjà projetée depuis que `ActivityFeedService` la couvre ; le texte obsolète a été retiré, aucun
  moteur du Fil n'a été modifié pour ce point.

**Limites documentées (non résolues ici, catégorie C)** : CommunityEvent et Partnership restent
absents du Fil et des Notifications ; aucune vue consolidée multi-domaines des engagements en cours
n'existe ; `priority()` n'a pas été retouché. Ces sujets nécessitent des missions séparées.

**Hors périmètre, restant catégorie C** : CommunityEvent/Partnership dans Fil/Notifications, activité
Organisation dans le Fil, vue consolidée « mes engagements », refonte de `priority()`, nouvelle
timeline personnelle, Cerveau/Crédit IA, GAMAD Core, cycle de vie ZUMRA — aucun n'a été touché.

## 30. Addendum daté — UIUX-009A, fondation humaine et identité GAMAD (22 août 2026)

**Portée : fondation visuelle transversale et traductions mécaniques de jargon, aucune refonte de
parcours métier.** Détail complet des tokens/composants/traductions dans
`docs/design/DESIGN-INVARIANTS.md` §22 (l'autorité de référence pour cette mission) ; ce qui suit
ne stabilise que les décisions produit qui dépassent le seul design visuel.

**Décisions rendues durables** :

- **La divergence entre le fond canonique documenté et le fond réellement rendu était un bug
  d'architecture CSS, pas une décision produit contestée entre deux choix légitimes.**
  `identity-v2.css` définissait, hors de toute couche `@layer`, une règle de fond concurrente de
  celle de `dg.css` — elle l'emportait systématiquement par accident de cascade. L'autorité produit
  n'a donc jamais vu la couleur que `DESIGN-INVARIANTS.md` v1.0 déclarait canonique. Cette
  découverte a rouvert la décision de fond (voir DESIGN-INVARIANTS.md §22) plutôt que de simplement
  restaurer l'ancienne valeur.
- **Une parenté chromatique réelle existe désormais entre DG Afrique et la console institutionnelle
  de l'écosystème GAMAD**, construite sur les valeurs de marque déjà normalisées par cette dernière
  (jaune/bleu/vert), jamais estimées à l'œil sur le logo. Cette parenté n'efface pas la distinction
  déjà établie par `DOCTRINE-GAMAD.md` §12 entre GAMAD (doctrine/mouvement), GAMAD Core
  (infrastructure institutionnelle) et DG Afrique (portail humain) : DG Afrique reste une expression
  humaine et sociale, pas un clone de la console institutionnelle — voir la section « CONSERVER /
  NE PAS REPRENDRE » de l'audit UIUX-009 Phase A.
- **Le jargon technique transversal (CAP-0XX affiché, enums bruts, mentions du backend d'identité)
  est traité comme une dette mécanique, pas comme une question de tonalité.** Les tableaux de
  traduction ajoutés (`TransmissionParticipant::STATUS_LABELS`, `ProofWitness::STATUS_LABELS`,
  `ProofReference::TYPE_LABELS`, `MissionAssignment::ROLE_LABELS`/`STATUS_LABELS`,
  `MissionBlocker::TYPE_LABELS`, `MissionDependency::TYPE_LABELS`, et les
  `VISIBILITY_LABELS`/`ORIGIN_LABELS` correspondants) suivent exactement le patron déjà établi par
  `Transmission::STATUS_LABELS`/`Proof::STATUS_LABELS` — complétés, jamais réinventés. Le statut
  ZUMRA `EXCLUDED`/`SUSPENDED`, qui s'affichait en anglais brut au moment le plus vulnérable pour un
  membre, est désormais traduit.
- **Cette fondation ne referme aucun des chantiers C/D identifiés par l'audit UIUX-009 Phase A.** Le
  formulaire de création manuelle de Projet, le champ de récurrence Mission (texte libre RRULE),
  l'écran de financement de Projet et la découverte de Mission restent des reconstructions
  explicitement réservées à de futures Phases B — cette mission leur transmet automatiquement la
  nouvelle fondation visuelle (tokens, composants, focus, erreurs de formulaire) sans masquer leur
  dette structurelle.

**Limites documentées (non résolues ici)** : cinq feuilles de style de parcours spécifiques
(`member-space-v2.css`, `fil-v2.css`, `project-brain.css`, `projects-directory.css`,
`project-workspace-v2.css`) consomment encore les anciens noms de tokens « V2 » via un alias de
compatibilité plutôt que les noms canoniques — migration individuelle réservée à de futures Phases
B « parcours ». `Mon espace` continue de violer concrètement l'invariant §7 (une seule priorité
dominante), documenté par l'audit Phase A et non corrigé par cette fondation — réservé à une Phase
B dédiée à la hiérarchie d'action.

**Hors périmètre** : formulaire de création manuelle de Projet, champ de récurrence Mission, écran
de financement de Projet, découverte de Mission, hiérarchie d'action de Mon espace, portail public
vs. application membre (Landing), Cerveau/Crédit IA, toute règle ou transition métier — aucun n'a
été touché.

## 31. Addendum daté — UIUX-009B, création humaine progressive d'un Projet (23 août 2026)

**Portée : reconstruction complète du parcours manuel de naissance d'un Projet — un formulaire
monolithique devient un parcours court, progressif, sauvegardable et reprenable, indépendant du
Cerveau.** Ce chantier était explicitement réservé « Phase B » par l'audit UIUX-009 Phase A et par
l'addendum §30 ci-dessus.

**Décisions rendues durables** :

- **Un brouillon de Projet est une entité à part entière, hors `dg_projects` et hors namespace
  Cerveau — jamais un état `DRAFT` inventé sur `Project`.** Les colonnes texte de `dg_projects` sont
  `NOT NULL` au niveau schéma (une ligne à moitié remplie ne peut pas exister), et le calcul de
  quota de `ProjectService::create()` compte toute ligne hors `COMPLETED`/`ARCHIVED` — un brouillon
  déguisé en `Project` aurait donc silencieusement consommé le quota avant même d'être complet. Le
  nouveau modèle `App\Models\ProjectDraft` (`dg_project_drafts` : UUID, acteur, payload JSON
  progressif, étape courante, statut, horodatages) reprend la forme déjà éprouvée en production par
  `ProjectBrainIntent` (statuts `DRAFT`/`CREATED`, reprise par UUID stable), sans jamais le
  réutiliser directement : le Cerveau reste une seconde porte d'entrée entièrement indépendante,
  jamais une dépendance du parcours déterministe.
- **La convergence finale est unique.** `ProjectDraftService::confirm()` appelle exactement le même
  `ProjectService::create()`, inchangé, que `ProjectBrainProjectBirthService::confirm()` pour le
  Cerveau — aucune règle métier (adhésion Programme, compatibilité porteur/régime, quota de projets
  actifs, `maturity` toujours `IDEA`) n'est dupliquée ni réécrite pour ce nouveau chemin.
- **Les jalons détaillés sortent de la naissance du Projet.** `milestones` était une exigence de
  validation du seul contrôleur (`ProjectController::store()`, supprimé), jamais une règle de
  `ProjectService::create()` — `ProjectList::fromText('')` accepte déjà une chaîne vide et ne crée
  alors aucun jalon, comportement inchangé. Les jalons deviendront un des premiers pas du Projet
  après sa naissance, jamais une condition pour naître.
- **Les portes bloquantes s'expliquent au plus tôt, jamais après coup.** L'absence d'adhésion
  Programme ZUMRA active est signalée avant même qu'un brouillon soit créé ; le quota de projets
  actifs est vérifié dès la toute première question de contenu (« Pour qui »), pas seulement à la
  confirmation finale — application directe d'un constat de l'audit UIUX-009 Phase A (règles
  invisibles ne se révélant qu'en échec de soumission).
- **Le parcours est utilisable entièrement sans Cerveau, par construction, pas par accident** : dix
  étapes courtes (« Pour qui » → « Votre idée », découpée en quatre questions successives (nom,
  résumé, problème, solution) → « À qui » → « Où et comment », lieu conditionnel → « Objectifs » →
  « Ce qui pourrait manquer », collections dynamiques différables → « Relire », résumé humain avec
  édition par section avant confirmation). Chaque étape peut être sauvegardée explicitement
  (« Enregistrer et continuer plus tard », sans validation de complétude) et reprise plus tard au
  point exact où elle a été laissée, jamais depuis le début. `ProjectBrainIntent` et sa mémoire
  restent entièrement intacts et non référencés par ce code.
- **L'illustration facultative du Projet (`image_path`, CAP-013/014/019) survit à la disparition du
  formulaire unique** : elle se demande désormais à la toute dernière étape (« Relire »), au moment
  de la confirmation, plutôt qu'au milieu d'un long formulaire — cohérent avec sa nature
  différable et « à l'ajout rapide ».

**Bogue corrigé en cours de mission (routage, pas une décision produit)** : la route générique
`POST /projets/proposer/{draft}/{step}` (`step` = `[a-z]+`) était déclarée avant les routes
nommées `abandonner`/`confirmer`, qui satisfont toutes deux ce même motif — Laravel routait donc
silencieusement une confirmation ou un abandon vers la validation d'étape générique, qui les
rejetait en 404. Les routes nommées sont désormais déclarées en premier. Le limiteur de débit
`project-write` (8/min, dimensionné pour l'ancien formulaire à une seule soumission et les
réponses occasionnelles du Cerveau) sous-dimensionnait aussi un parcours à dix étapes courtes plus
ajout/retrait de collections ; un limiteur dédié `project-draft-write` (40/min) a été introduit
pour ce seul chemin, sans toucher au limiteur du Cerveau.

**Limites documentées (non résolues ici)** : le brouillon ne conserve qu'un seul exemplaire actif
par acteur (`findOrStart`) — une personne qui démarre volontairement une seconde idée en parallèle
doit d'abord abandonner ou terminer la première ; ce choix suit la sémantique déjà établie par
`ProjectBrainIntent` et n'a pas été reconsidéré ici.

**Hors périmètre** : jalons détaillés (deviennent un premier pas post-naissance, mission séparée),
Cerveau/`ProjectBrainIntent` (non modifiés), écran de financement de Projet, découverte de Mission,
hiérarchie d'action de Mon espace au-delà de la carte de reprise ajoutée, toute règle métier de
`ProjectService::create()` — aucun n'a été touché.

**Correction (PROJET-ZUMRA-INVARIANT-001, §32 ci-dessous)** — cet addendum décrivait l'étape
« Pour qui » comme un choix strict entre porteur personnel sans ZUMRA et porteur ZUMRA, et
qualifiait le régime de propriété de « calculé silencieusement à partir du porteur » sans jamais
demander de ZUMRA pour le régime personnel. Un arbitrage doctrinal ultérieur a établi qu'un Projet
appartient toujours à une ZUMRA, y compris sous gouvernance personnelle — voir §32 pour la
correction complète du parcours et du modèle de données.

## 32. Addendum daté — PROJET-ZUMRA-INVARIANT-001, ancrage ZUMRA obligatoire (23 août 2026)

**Portée : correction doctrinale portant sur l'appartenance d'un Projet à une ZUMRA, appliquée au
parcours déterministe (#117) et au Cerveau.** Un audit ciblé, mené sans code après la livraison de
UIUX-009B, a établi que la notion de « Projet personnel accompagné » telle qu'implémentée était
doctrinalement incorrecte : elle traitait la gouvernance personnelle comme une alternative à
l'appartenance ZUMRA, alors que l'invariant canonique est qu'**un Projet appartient toujours à une
ZUMRA**, qu'il soit gouverné par une Personne seule ou collectivement.

**Décisions rendues durables** :

- **Modèle conceptuel à quatre axes, désormais orthogonaux.** `initiator_core_reference` (qui a
  initié), `zumra_group_id` (dans quelle ZUMRA le Projet grandit — nouvelle colonne `dg_projects`,
  nullable uniquement pour compatibilité historique), `owner_type`/`owner_reference` (qui décide
  aujourd'hui — Personne ou ZUMRA collectivement) et `property_regime` (le régime du Projet, jamais
  une preuve d'absence de ZUMRA). Pour un Projet à gouvernance `GROUP`, la ZUMRA gouvernante est
  aussi la ZUMRA d'ancrage — un seul choix, jamais deux. Pour un Projet à gouvernance `PERSON`,
  l'ancrage est distinct et toujours requis (`ProjectService::create()` — `abort` explicite si
  absent, jamais de valeur par défaut fabriquée).
- **Une ZUMRA solo est un ancrage valide, pas un cas à inventer.** `ZumraGroupService::create()`
  démarre déjà toute ZUMRA à l'état `CONSTITUTING` avec un seul membre actif, sans exiger les cinq
  responsabilités fondatrices pour exister (elles ne conditionnent que `READY`/`VALIDATED`) — la
  mécanique existait déjà et sert désormais aussi de socle à l'invariant Projet.
- **L'étape « Pour qui » du parcours déterministe devient « Dans quelle ZUMRA ce Projet va-t-il
  grandir ? ».** Le membre choisit une ZUMRA dont il est membre actif (obligatoire, quelle que soit
  la gouvernance choisie ensuite) ; « pour moi-même » sans ZUMRA n'est plus une réponse possible, au
  niveau serveur et non seulement au niveau de la copie. Sans ZUMRA existante, un module dédié
  (`projects.draft.zumra.create`/`store`, réutilisant `ZumraGroupService::create()` sans logique
  dupliquée) permet de démarrer une ZUMRA solo explicitement, jamais silencieusement, puis revient
  automatiquement au brouillon — jamais perdu pendant ce détour.
- **Le Cerveau converge vers le même invariant, sans jamais créer de ZUMRA automatiquement.**
  `ProjectBrainProjectBirthService` distingue désormais `contentReady()` (le récit narratif est
  structuré par l'IA) de `ready()` (contenu structuré ET ZUMRA choisie par la personne) —
  `context['zumra_group_reference']` vit hors de `project_state`, donc hors de portée de l'IA
  conversationnelle. Tant qu'aucune ZUMRA n'est choisie, l'interface présente explicitement cette
  décision humaine plutôt que le bouton de confirmation.
- **Besoin d'origine restauré, resté facultatif.** L'ancien formulaire unique permettait de
  rattacher un Projet à un `source_need_reference` ; #117 avait perdu cette possibilité en la
  forçant à `null`. Elle réapparaît à l'étape « Votre idée » (nom du Projet), présentée seulement si
  la personne a déjà des Besoins ouverts/en cours, jamais imposée.
- **`ProjectAuthority::canView()` reconnaît désormais la visibilité `GROUP` via l'ancrage ZUMRA** —
  gap déjà présent avant cette mission (la visibilité `GROUP` n'était honorée que pour les Projects
  à gouvernance `GROUP`, jamais pour un ancrage ZUMRA sur gouvernance personnelle). Le repliement
  préexistant `owner_type=PERSON` + visibilité `GROUP` → `PRIVATE` dans `ProjectService::create()`
  reste inchangé : cette mission ne l'a pas retouché, n'ayant reçu mandat que d'ancrer, jamais
  d'étendre une capacité de visibilité au-delà de ce qui existait.
- **Missions, Matching, financement et Partnerships n'ont reçu aucune logique parallèle** — tous
  délèguent déjà à `ProjectAuthority::canView()`/`canDecide()`, qui absorbe l'invariant sans
  modification de leur côté.

**Compatibilité** : les Projects nés avant cette évolution conservent `zumra_group_id = null` et
restent intégralement lisibles et fonctionnels (`ProjectAuthority`, Fil, Missions, financement,
Partnerships) — aucun backfill automatique, aucune ZUMRA fabriquée pour eux, aucune donnée
supprimée.

**Doctrine amendée** : `docs/capacites/specs/CAP-014-projet.md` (suppression de la fausse
disjonction « personnel accompagné ou porté par une ZUMRA ») et `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`
(clarification d'interprétation ajoutée au préambule, art. 15.1 non réécrit — même patron que la
clarification solo-ZUMRA déjà présente, suivant la procédure de gouvernance §26 du canon).

**Hors périmètre** : migration des Projects historiques (stratégies proposées à l'audit, aucune
exécutée), gouvernance collective (`GROUP`) au-delà de sa forme déjà existante, notification des
responsables ZUMRA à la proposition d'un Projet personnel ancré (lacune déjà présente avant cette
mission, non aggravée, non résolue), toute évolution de `Need`/`Proof` vers un ancrage ZUMRA
analogue.

## 33. Addendum daté — ZUMRA-HUMAN-BIRTH-001, naissance humaine d'une ZUMRA spécialisée (23 août 2026)

**Portée : la naissance d'une ZUMRA devient bien plus légère que sa structuration ultérieure,**
conformément à l'arbitrage du dépositaire GAMAD : une ZUMRA est un centre d'incubation humain
spécialisé autour d'une activité (pas d'activité, pas de ZUMRA), qui peut naître avec une seule
Personne — les cinq responsabilités relèvent de sa gouvernance ultérieure, jamais d'une condition
d'existence. Un audit exhaustif (Phase A, sans code) a établi que le modèle `ZumraGroup` traitait
déjà `domain` comme un champ non-inventable dès l'origine (CAP-011), mais qu'aucune activité
dérivée, aucune capacité d'accueil/formation et aucune localisation n'étaient représentables, et
que la charte — bien que la doctrine canonique ne l'exige qu'au passage `READY`
(`ZUMRA-DOCTRINE-INVARIANTE.md` §10, jamais §7) — était imposée dès la création par une
implémentation plus stricte que le canon lui-même.

**Décisions rendues durables** :

- **Quatre moments humains, un seul écran.** Le formulaire de naissance (`/zumra/groupes/proposer`)
  se réorganise en « Votre activité » (activité principale), « Ce que vous voulez changer »
  (objectif fondateur), « Comment vous allez commencer » (mode, lieu si pertinent, capacité
  d'accueil/formation, premier responsable optionnel) et « Votre ZUMRA » (nom). Aucune
  sauvegarde/reprise multi-requête n'a été construite — contrairement au parcours Projet
  (UIUX-009B), le formulaire est assez court pour rester un seul envoi ; sa légèreté vient de ce
  qu'il ne demande plus, pas d'une mécanique de brouillon supplémentaire.
- **La charte interne devient différable.** Mini-audit dédié avant code (§7 de la doctrine ne
  l'exige jamais à la création, seul §10 la place parmi les critères READY ;
  `evaluateStructuralReadiness()` traite déjà une charte vide comme non satisfaite, sans nouvelle
  logique) : `internal_charter` est désormais nullable, complétable après coup par un responsable
  via `ZumraGroupService::setCharter()`, réservé à la phase `CONSTITUTING`. Le gate de contribution
  collective (`ContributionService`, exige `VALIDATED`) reste inchangé — une ZUMRA sans charte ne
  peut simplement jamais dépasser `CONSTITUTING`. Les deux formulaires de démarrage express intégrés
  au parcours Projet et au Cerveau (issus de PROJET-ZUMRA-INVARIANT-001) suivent la même règle, pour
  éviter qu'une naissance en contexte Projet reste plus lourde que la naissance principale.
- **Activités dérivées avec filiation explicite, jamais une taxonomie globale.** Nouveau modèle
  `ZumraGroupActivity` (`dg_zumra_group_activities`) : un libellé et un texte humain obligatoire
  expliquant comment l'activité dérive, spécialise ou applique l'activité principale — jamais une
  validation automatique de cohérence, jamais un référentiel rigide fabriqué ici. Déclarables dès la
  naissance (facultatif) ou après coup depuis la fiche, réservées aux responsables.
- **Capacité d'accueil/formation, un signal jamais un critère.** `welcome_capacity` (nullable)
  répond à « comment votre ZUMRA pourra-t-elle accueillir des personnes qui souhaitent apprendre
  cette activité ? » (déjà capable, progressivement, ou doit d'abord trouver des transmetteurs) —
  potentiellement exploitable par un futur matching humain, non construit dans ce chantier.
- **Localisation alignée sur le patron déjà établi.** `location` (nullable, texte libre) reprend
  exactement la forme de `Project.location`/`Need.location`/`CommunityEvent.location` — aucune
  logique de rayon géographique n'existe nulle part dans le code, et ce chantier n'en invente pas.

**Compatibilité** : toutes les ZUMRA nées avant ce chantier ont déjà une charte non vide (contrainte
`NOT NULL` depuis l'origine) — aucune n'est affectée par la colonne devenue nullable, aucun
rétro-remplissage, aucune donnée supprimée.

**Doctrine amendée** : `docs/capacites/specs/CAP-011-zumra-groupe-humain.md` (naissance légère,
charte différable, activités dérivées, capacité d'accueil documentées) et
`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` (deux clarifications additives au préambule : la charte
comme critère READY et non de création — correction d'une implémentation plus stricte que le canon,
non une modification du canon — et la filiation obligatoire des activités dérivées vers l'activité
principale ; aucun article renuméroté ou réécrit).

**Hors périmètre** : ZUMRA → Organisation (gap confirmé en Phase A — `ProjectAutonomyPathway`
déclare une forme cible sans jamais créer d'`Organization` réelle ni la relier — documenté,
non construit), tout matching géographique ou par activité, toute taxonomie globale des activités.
