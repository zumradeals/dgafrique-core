# FICHE D'IMPLÉMENTATION — DOSSIER D'ACCOMPAGNEMENT

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-046 — DOSSIER D'ACCOMPAGNEMENT
**Expression produit :** fiche accompagnement projet (`/projets/{project}/accompagnement`)
**Nouveau CAP :** non
**Nature :** enrichissement de vue uniquement, aucune nouvelle table, aucune nouvelle autorité
**Base de conception :** `docs/capacites/specs/CAP-016-accompagnement-dg-afrique.md` (« CAP-045/046
détailleront plus tard la file de demandes et le dossier d'accompagnement enrichi »),
`ProjectAccompanimentAction` (déjà existant), précédent CAP-044 (synthèse honnête, jamais un score)

---

## 1. Intention

`/projets/{project}/accompagnement` n'affiche aujourd'hui les interventions que comme une
chronologie brute, sans synthèse ni moyen de filtrer par type ou par partenaire — pénible dès
qu'un accompagnement dure et accumule des actions.

> **Le dossier d'accompagnement gagne une synthèse honnête (comptes réels par type et par source)
> et des filtres par type d'appui et par intervenant/partenaire — jamais un score, jamais un
> classement, toujours la même chronologie sous-jacente.**

## 2. Ce que ce chantier n'est pas

- **pas** une nouvelle table ni un nouveau champ — `ProjectAccompanimentAction` porte déjà tout ce
  qui est nécessaire (`action_type`, `delivery_source`, `provider_label`, `occurred_at`) ;
- **pas** une évaluation de la qualité des interventions — la synthèse compte des faits réels
  (nombre d'interventions par type/source), jamais une note ;
- **pas** un changement d'autorité — la page reste réservée au porteur autorisé
  (`ProjectService::canDecide`), exactement comme aujourd'hui.

## 3. Synthèse

Sur `/projets/{project}/accompagnement`, au-dessus de la chronologie : un résumé compté par type
d'appui (ex. « 3 Orientation, 2 Formation ») et par source (« 4 DG Afrique, 2 Partenaire ») —
calculé depuis les interventions réellement enregistrées, aucune agrégation pondérée.

## 4. Filtres

Deux filtres combinables sur la chronologie, appliqués en GET (`?type=&partenaire=`) pour rester
partageables et sans état caché : par type d'appui (liste des types réellement configurés) et par
intervenant/partenaire (liste des `provider_label` réellement présents dans l'historique du
projet, jamais une liste globale). Un filtre qui ne retourne rien affiche l'état vide honnête déjà
utilisé ailleurs, pas une erreur.

## 5. Permissions

Aucune permission nouvelle — la page reste gardée par `ProjectService::canDecide`, déjà vérifié en
amont par le contrôleur.

## 6. Hors périmètre v1

- export ou impression du dossier ;
- filtrage cross-projet (reste `/administration/accompagnement-projets`, CAP-045) ;
- historique des filtres appliqués.

## 7. Definition of Done

- synthèse par type/source affichée, exclusivement composée de comptes réels ;
- filtres par type et par partenaire fonctionnels, combinables, état vide honnête si aucun résultat ;
- tests : synthèse reflète les interventions réelles, filtre par type isole les bonnes actions,
  filtre par partenaire isole les bonnes actions, combinaison des deux filtres, aucune régression
  sur l'autorité existante ;
- `php artisan test`, `npm run build`, `git status --short` verts.
