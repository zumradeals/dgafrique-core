# CAP-055 — Fil d’activité intelligent

## Finalité canonique

Aider une personne à comprendre : que se passe-t-il ? Qu’est-ce qui me concerne ? Où puis-je agir ? Qu’est-ce qui a besoin de mon attention ? Qu’est-ce qui avance dans mon écosystème ?

## Relation avec CAP-019

CAP-019 (Fil d’activité) a déjà posé l’architecture correcte : un fil **en lecture**, projeté depuis les journaux métier déjà existants (`dg_need_events`, `dg_project_events`, `dg_zumra_group_events`, `dg_mission_events`, `dg_transmission_events`, `dg_proof_events`), sans deuxième source de vérité, avec une hiérarchie de priorités métier simple, une déduplication par objet et une visibilité entièrement déléguée aux autorités canoniques de chaque domaine (`NeedService::canView()`, `ProjectService::canView()`, `MissionVisibilityService::canViewMission()`, `TransmissionVisibilityService::canView()`, `ProofVisibilityService::canView()`).

CAP-055 **prolonge** CAP-019, il ne le remplace pas. La seule capacité manquante à l’audit était la dimension personnelle : la priorité était identique pour tous les lecteurs, quelle que soit leur relation réelle à l’objet.

## Ce que CAP-055 ajoute

Une couche de **pertinence personnelle**, additive et déterministe, dans `ActivityFeedService` :

- une activité liée à une relation métier réelle de l’identité lectrice (elle porte le Besoin/Projet, elle est membre actif de l’équipe Projet, elle est assignée à la Mission, elle est membre actif de la ZUMRA, elle a proposé la Transmission, elle a soumis la Preuve) reçoit une **raison explicable** (`relevance_reason`, une phrase réelle, jamais un nombre) ;
- cette relation fait remonter l’activité au-dessus de la hiérarchie de priorité métier habituelle (`RELEVANCE_BOOST`), sans jamais la retirer de la hiérarchie ni en faire un score de valeur humaine ;
- une activité sans relation personnelle reste visible et n’est **jamais filtrée** — la pertinence personnelle priorise, elle ne cache pas l’activité réelle du réseau.

## Ce que CAP-055 n’est pas

- **pas une fusion avec CAP-054 (Notifications)** : `NotificationSourceRegistry` reste le canal personnel distinct (« ce qui me concerne précisément », potentiellement plus large et incluant les propres actions filtrées de l’acteur) ; le Fil reste le flux partagé/contextuel. Aucune donnée ni logique n’est partagée entre les deux registres, qui restent deux adaptateurs indépendants sur les mêmes journaux `*Event`, comme documenté dans `NOTIFICATIONS.md` ;
- **pas une fusion avec CAP-064 (Opportunités)** : une opportunité est une possibilité d’action projetée par `OpportunityEngine`, jamais un événement qui s’est produit. Elle n’est pas injectée dans le tableau d’activités du Fil ;
- **pas un algorithme d’engagement** : aucun compteur de vues, de likes, de followers ou de viralité — invariant déjà testé par CAP-019/`ActivityFeedTest` et reconduit ici ;
- **pas une nouvelle table** : la pertinence est calculée à la lecture, à partir de `ProjectTeamMember`, `MissionAssignment`, `ZumraGroupMembership` et des champs d’auteur/porteur déjà présents sur chaque objet — aucune nouvelle persistance.

## Sources de la relation personnelle

| Domaine | Source interrogée | Raison affichée |
|---|---|---|
| Besoin | `Need.author_core_reference` | « Besoin que vous avez publié. » |
| Projet (porteur) | `Project.initiator_core_reference` / `owner_reference` (OWNER_PERSON) | « Projet que vous portez. » |
| Projet (équipe) | `ProjectTeamMember` (statut `ACTIVE`) | « Projet auquel vous participez. » |
| Mission | `MissionAssignment` (statuts `CURRENT_STATUSES`) | « Mission qui vous concerne. » |
| ZUMRA | `ZumraGroupMembership` (statut `ACTIVE`, déjà calculé pour `is_active_member`) | « Activité de votre ZUMRA. » |
| Transmission | `Transmission.proposed_by_core_reference` | « Transmission que vous avez proposée. » |
| Preuve | `Proof.submitted_by_core_reference` | « Preuve que vous avez soumise. » |

Chaque requête de relation est **groupée par source** (une requête par lot d’identifiants avant la boucle d’assemblage), jamais une requête par ligne — aucun nouveau N+1 introduit par cette capacité.

## Hors périmètre v1

- Besoin/Projet n’ont pas de taxonomie de capacité structurée comparable à `CapabilityStatement.normalized_label` (constat déjà posé par l’audit CAP-064) : aucune pertinence par capacité déclarée n’est ajoutée au Fil pour éviter de fabriquer un rapprochement non fiable ;
- exposition d’une Opportunité dans le Fil : rejetée pour cette V1 (catégorie conceptuelle distincte, cf. « Ce que CAP-055 n’est pas ») ;
- le coût en mémoire déjà existant de `items()` (jusqu’à 80 lignes par source, assemblage et tri en PHP à chaque requête, quelle que soit la page demandée) est un constat d’audit, pas une régression introduite ici — non traité par cette capacité, à documenter séparément si un futur CAP doit l’adresser.

## Visibilité

Inchangée : chaque source revalide la visibilité via son autorité canonique avant projection ; la pertinence personnelle ne contourne, n’étend et ne remplace aucune règle d’autorisation existante.

## Preuve

- `tests/Feature/ActivityFeedRelevanceTest.php` : pertinence par domaine, priorisation sans suppression, isolation entre identités, absence de mutation, déterminisme, absence de score numérique, rendu réel de la raison sur `/activite`.
- `tests/Feature/ActivityFeedTest.php` et `tests/Feature/FilV2Test.php` (CAP-019) : non-régression, toujours verts.
