# Expérience produit canonique — DG Afrique

## Statut

**CANONIQUE — Couche 04 (PRODUIT / UX), adopté 21 août 2026.** Ce document occupe la couche
« 04 — PRODUIT / UX » de la hiérarchie documentaire décrite dans
`docs/foundation/DG-AFRIQUE-DOCTRINE.md` §30 — une couche que ce document est le premier à
occuper explicitement dans son ensemble (voir §1).

Autorité : subordonné à `docs/foundation/DG-AFRIQUE-DOCTRINE.md` (couches 00-01),
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

**Source première : `docs/AI-RULES.md` (invariant d'identité produit supérieur).**

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
