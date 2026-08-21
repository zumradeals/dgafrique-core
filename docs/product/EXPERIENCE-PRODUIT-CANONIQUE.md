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
