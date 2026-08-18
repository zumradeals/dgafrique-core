# FICHE D'IMPLÉMENTATION — DISPONIBILITÉ

**Statut :** READY FOR IMPLEMENTATION
**Version :** 1.0
**Racine référentielle :** CAP-025 — DISPONIBILITÉ (domaine « Capabilities & Intelligence »)
**Expression produit :** DISPONIBILITÉ
**Nouveau CAP :** non
**Nature :** primitive additive sur le Profil (CAP-003), consommée par Découverte (CAP-009) et Recommandation (CAP-010)
**Base de conception :** `docs/capacites/CAPABILITY-COVERAGE.md` (CAP-025, NOT_IMPLEMENTED), directive maître 2026-08-18 §38, `app/Models/PersonProfile.php`, `ProfileConfiguration.php`, `PersonRecommendationEngine.php`

Ce document part directement en **READY** : la directive maître (§38) tranche déjà la question de fond
(« éviter un calendrier géant générique si le besoin métier est seulement *quand cette personne
peut-elle agir ?* ») et le patron d'implémentation (déclaration additive sur le Profil) est déjà
établi par `participation_mode`/`intentions`. Aucun point à trancher ne reste ouvert.

---

## 1. Intention

Aucune primitive de disponibilité n'existe aujourd'hui — confirmé par l'audit
(`CAPABILITY-COVERAGE.md`, CAP-025 NOT_IMPLEMENTED) : seul `PersonProfile.participation_mode`
existe (un mode de participation, pas un signal temporel), et `MissionBlocker::TYPE_PERSON_UNAVAILABLE`
n'est qu'un blocage réactif ouvert automatiquement quand le dernier exécutant quitte une Mission —
jamais une déclaration volontaire.

> **Une disponibilité déclarée répond à une seule question : « cette personne peut-elle être
> sollicitée maintenant ? » Elle ne devient jamais un calendrier de créneaux, jamais une obligation,
> jamais un critère de sélection automatique ou de score.**

## 2. Ce que DISPONIBILITÉ n'est pas

- **pas** un calendrier avec des créneaux horaires — évité explicitement par la directive maître §38 ;
- **pas** un filtre qui bloque une invitation — Mission/Transmission restent des formulaires réels,
  jamais une automatisation qui refuse à la place de la personne invitée ;
- **pas** un critère de score ou de classement dans le matching — seulement une raison
  supplémentaire, explicable, ajoutée à une recommandation déjà qualifiée par un vrai
  rapprochement de capacité (même patron que `location_context`/`participation_context`
  dans `PersonRecommendationEngine`) ;
- **pas** une obligation — la disponibilité reste facultative comme tout le reste du profil, avec
  un état vide honnête (« non précisée »).

## 3. Position dans le référentiel

- **CAP-003 — Profil de capacités** : la disponibilité est un champ additionnel de
  `PersonProfile`, dans la section déjà existante « Vos préférences de collaboration »
  (`ProfileConfiguration::defaults()['sections']['collaboration']`), au même titre que
  `participation_mode`. Pas de nouvelle table, pas de nouveau modèle — additif pur.
- **CAP-009 — Découverte de personnes** : affichée sur la fiche personne (`/personnes/{reference}`)
  si la personne a consenti à la découverte (`discovery_consent`), au même niveau que le mode de
  participation.
- **CAP-010 — Recommandation** : consommée comme raison supplémentaire (jamais seule, jamais un
  critère de tri) dans `PersonRecommendationEngine::reasons()`.
- **CAP-069 (Missions) / CAP-006 (Transmission)** : pas d'intégration dans les flux d'invitation en
  v1 (ceux-ci utilisent une saisie `discovery_reference` libre, pas une liste de candidats
  enrichissable sans réécriture d'écran) — noté hors périmètre v1 (§6).

## 4. Doctrine à ne jamais casser

1. Aucune donnée financière ou de score ne mesure jamais la disponibilité (doctrine anti-score,
   répétée dans quasiment chaque fiche du référentiel).
2. La disponibilité reste une déclaration volontaire et révocable, jamais déduite automatiquement
   du comportement (pas de calcul depuis l'activité récente, l'IA n'a pas d'autorité de décision —
   directive maître §16).
3. Aucune donnée de démonstration réelle.

## 5. Modèle de données

Additif sur `dg_person_profiles` (même patron que `2026_08_15_170000_add_discovery_consent_to_person_profiles.php`) :

```text
availability_status    string, nullable   -- OPEN | LIMITED | PAUSED
availability_note       text,   nullable   -- ex. "disponible le week-end uniquement"
availability_updated_at timestampTz, nullable -- posé automatiquement à chaque changement de statut
```

Trois états seulement, en langage humain jamais technique :

- `OPEN` — « Disponible pour de nouvelles sollicitations »
- `LIMITED` — « Disponibilité réduite »
- `PAUSED` — « En pause pour le moment »

`null` = non précisée (état vide honnête, jamais interprété comme indisponible).

## 6. UX

- Formulaire de profil (`resources/views/member/profile.blade.php`, section collaboration) :
  un choix à 3 options + une note libre facultative.
- Fiche personne découvrable (`discovery/show.blade.php`) : affichée dans le bloc `dl` déjà existant
  (mode de participation / domaines d'intérêt), à côté du mode de participation.
- Aucun badge alarmant, aucune couleur d'urgence — même famille de badges neutres que le reste du
  profil.

## 7. Hors périmètre v1 (POST-BETA)

- intégration dans les flux d'invitation Mission/Transmission (nécessiterait de remplacer la saisie
  `discovery_reference` libre par un sélecteur de candidats enrichi — refonte d'écran plus large,
  hors proportion pour cette primitive) ;
- créneaux horaires/calendrier ;
- rappels automatiques de mise à jour de la disponibilité (utiliserait CAP-054 Notifications le
  moment venu, pas construit maintenant) ;
- écran d'administration dédié (le champ reste géré via `ProfileConfiguration`/`PortalSetting`
  comme le reste du formulaire de profil).

## 8. Definition of Done

- migration additive, réversible ;
- modèle `PersonProfile` mis à jour (fillable, casts, constantes) ;
- formulaire de profil : déclaration + note ;
- fiche personne découvrable : affichage conditionné au consentement de découverte ;
- `PersonRecommendationEngine` : raison supplémentaire, jamais seule, jamais un tri ;
- tests : déclaration, affichage conditionné au consentement, non-régression du matching
  (une disponibilité seule ne crée jamais une recommandation) ;
- `php artisan test`, `npm run build`, `git status --short` verts.
