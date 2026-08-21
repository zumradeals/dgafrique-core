# CAP-080 — Ce que devrait mesurer DG Afrique

## Statut

**Clarification doctrinale (2026-10-01) puis implémentation V1 — CLOSED.** Était `NOT_IMPLEMENTED`, marquée `DOCTRINE-À-CLARIFIER` par ROADMAP-003 : aucune infrastructure de métriques n'existait, et aucune doctrine ne définissait explicitement quoi mesurer.

## Clarification doctrinale — verdict

Aucune occurrence positive de « mesurer »/« métrique »/« indicateur » dans `ZUMRA-DOCTRINE-INVARIANTE.md` — la seule occurrence (art. 6.5) est une **interdiction** : *« Aucune contribution financière ne mesure la dignité, la moralité ou la valeur humaine d'une personne. »* Le corpus ne confirme ni n'infirme textuellement une définition positive — mais l'invariant supérieur de `docs/AI-RULES.md` (« DG Afrique est un réseau social d'action... doit servir le passage de la capacité à l'action humaine et collective ») fournit un critère directement exploitable et non inventé.

**Décision validée : CAP-080 = mesurer la capacité collective à transformer des capacités disponibles en actions et résultats réels — des faits collectifs et des flux métier, jamais la valeur des personnes.** Cohérent avec l'unique occurrence doctrinale existante (interdiction de mesurer la valeur humaine) et avec l'invariant produit supérieur. Domaine `Ecosystem Architecture` (siblings CAP-081/082/083/084, tous positionnels) confirme que CAP-080 n'a jamais exigé une infrastructure d'analytics comportementale.

## Interdictions strictes (respectées par construction)

Aucun score humain, classement de personnes, niveau de valeur, score d'engagement, KPI likes/vues/popularité, réputation, ranking, gamification, ni nouveau tracking comportemental. Chaque métrique produite est un entier collectif — vérifié par test (`test_no_score_ranking_or_person_level_field_exists_anywhere`, `test_metrics_never_expose_a_person_identity_or_list`).

## Architecture — projection de lecture pure, aucune table nouvelle

`ImpactMetricsService` (`app/Application/Ecosystem/ImpactMetricsService.php`) dérive chaque compteur directement des tables existantes (`Need`, `Project`, `Mission`/`MissionAssignment`, `Transmission`, `Proof`, `ZumraGroup`, `Organization`, `Partnership`, `CommunityEvent`, `LedgerEntry`) via de simples `count()`. **Aucune nouvelle table, aucun snapshot, aucun ETL, aucun cron** — chaque appel recalcule à la volée, cohérent avec le volume actuel du portail.

## Granularité

- **Portail** (`portal()`) : compteurs globaux — capacités déclarées, besoins exprimés/résolus, projets initiés/complétés, missions proposées/assignées/complétées, transmissions complétées, preuves produites/validées, ZUMRA/organisations actives, partenariats actifs, événements organisés, contributions confirmées (`LedgerEntry`, respecte CAP-061/062 : jamais un montant, seulement un compte).
- **ZUMRA** (`forZumraGroup()`) : mêmes familles de faits scopées à `owner_reference`/`context_reference` = ce groupe. Réservé aux membres actifs/responsables (délégation à l'adhésion, faute d'un `canView()` public sur `ZumraGroupService`).
- **Organisation** (`forOrganization()`) : effectif, partenariats actifs où l'organisation est fournisseur, événements organisés. Réutilise `OrganizationService::canView()` intégralement.

**Aucun tableau de performance individuel.** Aucune méthode ne retourne une liste de personnes ni un compte par personne — vérifié par test (recherche de préfixe d'identité dans le JSON produit).

## Autorisations — aucune matrice nouvelle

`portal()` : tout `core.member`. `forZumraGroup()`/`forOrganization()` réutilisent exactement les autorités déjà établies (`ZumraGroupService::isLeader()` + adhésion active ; `OrganizationService::canView()`).

## Frontières

Aucune mutation d'aucun domaine source — vérifié par test (`updated_at` inchangé après lecture). Aucun montant financier individuel ou agrégé exposé — seul un compte de mouvements confirmés.

## Limitation V1 documentée

Aucune vue personnelle (« mes faits ») n'est construite : aucune nécessité démontrée au-delà de ce que les vues de domaine existantes (Mes Missions, Mon espace, etc.) offrent déjà. À réviser si un besoin réel apparaît — jamais pour comparer ou classer des personnes entre elles.

## HTTP

`routes/cap080.php` : `GET /mesure` (portail), `GET /zumra/groupes/{group}/mesure`, `GET /organisations/{organization}/mesure` — lecture seule, aucune route de mutation.

## Preuve

`tests/Feature/ImpactMetricsTest.php` — 11 cas : calculs corrects par domaine, scoping strict ZUMRA/Organisation (aucune fuite croisée), autorisation (outsider refusé, membre/manager autorisé), absence de score/classement/valeur individuelle (recherche de mots interdits + type entier strict sur chaque valeur), absence de fuite d'identité, absence de mutation des domaines sources, endpoint HTTP portail.
