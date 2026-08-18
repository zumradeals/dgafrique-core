# FICHE D'IMPLÉMENTATION — MATURITÉ CALCULÉE PAR SIGNES, PAS PAR DÉCRET

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-044 — MATURITÉ CALCULÉE PAR SIGNES, PAS PAR DÉCRET
**Expression produit :** fiche projet (`/projets/{project}`)
**Nouveau CAP :** non
**Nature :** panneau consultatif additif, aucune écriture sur `Project.maturity`
**Base de conception :** `docs/capacites/specs/CAP-017-maturite.md` (§ Frontière avec CAP-044, §
Invariant — « aucun score humain ou score opaque de projet »), `docs/design/DESIGN-INVARIANTS.md`
(§7 « pas de grille de compteurs », §8 « score d'engagement » exclu), précédent
`PersonRecommendationEngine`/`ProjectMatchingEngine` (raisons factuelles, jamais un score affiché)

---

## 1. Intention

CAP-017 a délibérément laissé la maturité 100% décrétée par le porteur (`ProjectMaturityService`),
en réservant explicitement à CAP-044 une **estimation** fondée sur des signaux observables — jamais
une formule, jamais un score, jamais un remplacement du repère décrété (CAP-017 §Invariant :
« aucun score humain ou score opaque de projet »).

> **Un panneau consultatif affiche des signaux réels et honnêtes (jalons réalisés, équipe active,
> besoins résolus, dernière activité) à côté du repère décrété par le porteur — jamais un score,
> jamais un pourcentage, jamais une écriture automatique sur `Project.maturity`.**

## 2. Ce que ce chantier n'est pas

- **pas** un remplacement de `ProjectMaturityService::change()` — reste l'unique chemin d'écriture
  sur `Project.maturity`, toujours décidé par le porteur habilité (`canDecide`) ;
- **pas** un score agrégé ni un pourcentage — chaque signal est un ratio ou un compte honnête
  attaché à un objet métier réel (jalons, équipe, besoins), jamais combiné en une note unique
  (`x-dg.stagewalk` porte déjà l'avertissement « jamais une note, jamais un pourcentage ») ;
- **pas** une promotion/rétrogradation automatique de repère — aucun signal ne déclenche jamais un
  appel à `ProjectMaturityService::change()`.

## 3. Signaux

`ProjectSignalsEngine::forProject(Project $project): array<string>` — chaque élément est une phrase
factuelle en français, construite uniquement à partir d'objets réels déjà existants, omise si aucun
objet réel ne la fonde (jamais de « 0 » fabriqué sur un objet inexistant) :

- jalons (`ProjectMilestone`) : « X jalon(s) réalisé(s) sur Y » — si au moins un jalon existe ;
- équipe (`ProjectTeamMember` CAP-041) : « X membre(s) actif(s) dans l'équipe » — si au moins un
  membre actif ;
- besoins (`Need` CAP-042, `owner_type = PROJECT`) : « X besoin(s) résolu(s) sur Y exprimés » — si
  au moins un besoin non archivé existe ;
- contributions (`ContextComment`, contexte PROJECT) : « X contribution(s) déposée(s) » — si au
  moins une existe ;
- activité récente (`ProjectEvent`, tous types confondus) : « Dernière activité observée le
  JJ/MM/AAAA » à partir du dernier événement.

Aucun total pondéré, aucune priorité affichée : un ordre d'affichage fixe et lisible (jalons →
équipe → besoins → contributions → dernière activité), pas un tri par pertinence.

## 4. Interface

Nouveau bloc « Signaux observés » dans la carte « Maturité — repères de capacité » existante
(`projects/show.blade.php`), sous le `x-dg.stagewalk`, avant le fieldset de repositionnement
réservé au porteur. Visible à tout acteur ayant déjà accès à la fiche (`canView`, déjà vérifié par
le contrôleur). Mention explicite : « Ces signaux n'attribuent ni ne modifient aucun repère. Seul
le porteur décide. » État vide honnête si aucun signal ne peut être établi (« Aucun signal observé
pour l'instant. »).

## 5. Permissions

Aucune nouvelle autorité : le panneau est calculé pour tout acteur qui `canView` déjà le projet
(vérifié en amont par `ProjectController::show()`), sans distinction supplémentaire — les signaux
ne révèlent rien que la fiche ne montre déjà par ailleurs (jalons et historique de maturité sont
déjà publics sur la fiche ; équipe et besoins ont leurs propres cartes déjà visibles).

## 6. Hors périmètre v1

- toute suggestion automatique de repère (« ce projet semble prêt pour EXPERIMENT ») — resterait
  une décision fabriquée, même formulée prudemment ;
- historique temporel des signaux (courbe d'évolution) — un instantané honnête suffit à ce stade ;
- signaux d'accompagnement (CAP-016) — laissés hors périmètre pour rester borné, ajoutables plus
  tard sans changer la forme du moteur.

## 7. Definition of Done

- `ProjectSignalsEngine` construit uniquement des phrases factuelles à partir d'objets réels,
  jamais de score/pourcentage, jamais d'écriture sur `maturity` ;
- panneau visible sur `/projets/{project}`, état vide honnête si aucun signal ;
- tests : chaque signal apparaît seulement quand l'objet réel existe, aucun signal fabriqué à
  partir de zéro objet, `ProjectMaturityService::change()` reste inchangé et seul chemin d'écriture ;
- `php artisan test`, `npm run build`, `git status --short` verts.
