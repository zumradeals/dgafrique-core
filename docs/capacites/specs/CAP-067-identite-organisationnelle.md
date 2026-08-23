# CAP-067 — Identité organisationnelle

## Statut

**Fermée — 22 août 2026.** Débloquée par CORE-ORG-DELEGATION-001 (GAMAD Core, PR #85, merge `20a3fc4`), qui a livré la délégation explicite, révocable et auditée permettant à `PRD-GAMAD-005` (DG Afrique) d'inscrire une identité organisationnelle (CAP-CORE-001, canal `PRODUIT_RECONNU`) puis sa fiche organisationnelle (CAP-CORE-002, `ACTION_INSCRIRE`). Ce chantier consomme cette délégation — il ne modifie aucun code `gamad-core`.

## Ce que CAP-067 répond

« Comment une Organisation DG Afrique existe-t-elle comme acteur/identité dans l'écosystème (GAMAD Core) ? » — distinct de CAP-066 (« qu'est-ce que l'Organisation ? »).

## Architecture

```
GAMAD Core
Organisation canonique (CAP-CORE-001/002)
       │
       │ core_identity_reference / core_organization_reference
       ▼
DG Afrique
Organization (dg_organizations)
       │
       ├── profil/page réseau (CAP-066)
       ├── capacités (ce chantier)
       ├── collaborations (CAP-065/UIUX-005)
       └── événements (UIUX-003)
```

DG Afrique reste souverain sur ces objets métier locaux. GAMAD Core reste souverain sur l'identité canonique — jamais l'inverse.

## A — Identité canonique (CREATE)

`Organization` porte trois champs nouveaux : `core_identity_reference`, `core_organization_reference` (tous deux nullable, uniques), `core_link_status` (`LINKED`/`UNLINKED`, défaut `UNLINKED`). Aucun doublon sémantique créé : `founder_core_reference` (le fondateur, une personne) reste inchangé et distinct.

`OrganizationService::create()` demande le raccordement Core **avant** toute écriture locale :

1. `GamadCoreClient::provisionOrganizationIdentity($name)` → `POST /identites` (canal `PRODUIT_RECONNU`, type `organisation`) via la session produit `PRD-GAMAD-005`.
2. `GamadCoreClient::createOrganization($identityReference, [...])` → `POST /organisations` (CAP-CORE-002), avec `type_organisation_reference` mappé depuis `Organization::CORE_TYPE_MAP` (vocabulaire local ARCH-006 → vocabulaire fermé `PolitiqueOrganisations::TYPES_ORGANISATION` de GAMAD Core) et `classification_reference` dérivée de la visibilité (`PUBLIC` → `PUBLIC_ECOSYSTEME`, `PRIVATE` → `INTERNE`).
3. Seulement alors, la transaction locale (`Organization`, `OrganizationMembership` du fondateur, événement `ORGANIZATION_CREATED`) s'exécute.

**Garanties transactionnelles — honnêtement bornées.** GAMAD Core et DG Afrique sont deux systèmes distincts : aucune transaction distribuée n'existe ni n'est prétendue. Si l'étape 1 ou 2 échoue (`CoreUnavailableException`/`CoreProtocolException`/`CoreSessionRejectedException`), l'exception remonte avant toute écriture locale — `OrganizationController::store()` la capture et renvoie un message d'erreur, sans finaliser de fausse Organisation locale. Si l'étape 1 réussit mais l'étape 2 échoue, une identité Core organisationnelle orpheline (sans fiche CAP-CORE-002) peut subsister côté Core : CAP-CORE-001 est un registre en ajout seul, sans opération d'annulation d'identité. Ce n'est pas un risque d'intégrité (aucune donnée locale n'est corrompue, l'identité orpheline reste simplement inutilisée et invisible depuis DG Afrique), mais une limite connue, documentée plutôt que masquée.

## B — Capacités de l'Organisation

Réutilise strictement le moteur de capacités existant (CAP-016, `CapabilityStatement`) — aucun second système. `holder_type` (`PERSON`/`ORGANIZATION`) distingue le porteur ; un porteur `ORGANIZATION` renseigne `organization_id`, jamais `core_identity_reference` (qui devient nullable et reste réservée aux porteurs `PERSON`). Un index unique partiel (`WHERE holder_type = 'ORGANIZATION'`) protège l'unicité `(organization_id, kind, normalized_label)` symétriquement à l'index existant pour `PERSON`. Aucune ligne existante n'est modifiée ; aucun consommateur existant (moteurs de matching, recommandations, profil) n'a besoin d'être touché — ils continuent de ne lire que des porteurs `PERSON`.

`OrganizationCapabilityService::declare()/archive()/list()` — `KIND_POSSESSED` seulement (« ce que la structure sait apporter », pas le triptyque possédé/apprentissage/transmission propre au parcours pédagogique d'une personne). Autorisé aux seuls managers habilités (`OrganizationService::isManager()`). Une capacité Organisation est un fait métier explicite, **jamais déduit** :

- ni d'un Partnership (depuis la convergence CAP-065/CAP-067, c'est l'inverse : un `Partnership` référence une `CapabilityStatement` Organisation réellement déclarée ici, jamais le contraire — voir `CAP-065-partenaire-fournisseur-capacite.md`) ;
- ni d'un Projet, d'un événement ;
- ni du texte `provider_label` d'un `ProjectAccompanimentAction` ;
- ni des capacités personnelles de son manager (une `CapabilityStatement` `PERSON` du fondateur n'apparaît jamais dans `OrganizationCapabilityService::list()`).

Aucun score, niveau, classement ni popularité. `matching_consent` reste toujours `false` pour un porteur `ORGANIZATION` : aucun raccordement au moteur de matching dans ce chantier (voir « Matching » ci-dessous).

Exposition : `POST /organisations/{organization}/capacites`, `DELETE /organisations/{organization}/capacites/{capability}` ; section « Capacités » sur la fiche Organisation, visible selon les mêmes règles que la fiche elle-même (`OrganizationService::canView()`) — pas de visibilité indépendante par capacité.

## ATTACH — délibérément arrêté

CORE-ORG-DELEGATION-001 n'a créé aucun endpoint d'appropriation ; la résolution Core (`GET /organisations/resolution/{identite}`, `GamadCoreClient::resolveOrganizationByIdentity()`) est une pure lecture. Retrouver une Organisation Core existante ne rend jamais son demandeur propriétaire, ni dirigeant, ni représentant — aucune mutation d'autorité Core.

**Ce chantier n'a construit aucun parcours « rattacher une projection DG Afrique à une Organisation Core déjà existante ».** DG Afrique ne dispose d'aucune preuve suffisante pour autoriser cette création de projection : aucun mécanisme ne permet aujourd'hui de vérifier qu'un acteur DG Afrique représente légitimement une Organisation Core qu'il n'a pas lui-même créée via CAP-067 (le mandat CAP-CORE-003 reste borné aux fonctions institutionnelles fixes, voir `CAP-066-organisation.md` §8). Inventer une règle de correspondance ici (par exemple « le premier arrivé revendique ») créerait un risque d'appropriation arbitraire. **Gap documenté, pas construit :** un futur chantier devra définir cette preuve d'autorité avant d'exposer un parcours ATTACH côté produit ; `GamadCoreClient::resolveOrganizationByIdentity()` existe déjà comme brique de lecture, prête à être réutilisée le jour où cette preuve existera.

## Organisations DG Afrique déjà existantes

Aucune migration automatique par rapprochement de nom. `core_link_status` vaut `UNLINKED` par défaut pour toute Organisation créée avant ce chantier — honnête, jamais un faux raccordement inventé. CAP-066 ayant été livrée le 20 août 2026 (deux jours avant ce chantier), aucune Organisation de production réelle n'existe à ce jour ; la régularisation d'éventuelles Organisations `UNLINKED` reste un chantier futur explicite, une fois la preuve d'autorité ATTACH définie ci-dessus.

## Matching — non raccordé, modèle prêt

Ce chantier ne raccorde délibérément pas les capacités Organisation au moteur de matching (`MissionMatchingEngine`, `OpportunityEngine`, `PersonRecommendationEngine`, etc.). Le modèle le permettra cependant sans nouvelle refonte : ces moteurs interrogent `CapabilityStatement` en scopant toujours par un `core_identity_reference` de personne (`PersonProfile::where('core_identity_reference', ...)`) — un porteur `ORGANIZATION` (`core_identity_reference` toujours `NULL`) n'y apparaît jamais aujourd'hui, par construction, sans filtre supplémentaire nécessaire. Un futur chantier « besoin ↔ capacité ORGANIZATION » devra explicitement étendre ces moteurs pour lire aussi les porteurs `ORGANIZATION` ; ce n'est pas fait ici.

## G-POS

Aucun code G-POS dans ce chantier. L'architecture est préservée pour qu'une Organisation créée depuis G-POS soit un jour retrouvable dans DG Afrique via sa référence canonique (`core_identity_reference`), sans duplication — via le mécanisme ATTACH à construire (voir ci-dessus), jamais par une Organisation DG Afrique fabriquée automatiquement depuis G-POS.

## Hors périmètre (délibérément)

- **ATTACH d'une Organisation Core existante** — gap documenté ci-dessus, pas construit.
- **Raccordement au moteur de matching** — modèle prêt, non câblé (voir ci-dessus).
- **Gouvernance Organisation Core** (activer/suspendre/dissoudre/retirer côté Core) — CAP-067 délègue uniquement la création ; toute autre action reste réservée à `AUT-GAMAD-001` (voir CORE-ORG-DELEGATION-001).
- **Aucun code `gamad-core`, aucun code G-POS, aucune nouvelle ACL, aucun système parallèle de capacités.**

## Preuve

- `tests/Feature/OrganizationTest.php` — 22 cas, dont `test_core_references_are_persisted_on_creation`, `test_core_failure_finalizes_no_local_organization`.
- `tests/Feature/OrganizationCapabilityTest.php` — 9 cas : déclaration explicite, manager autorisé, non-manager/étranger refusés, aucune capacité personnelle attribuée implicitement, doublon refusé, archivage, visibilité sur la fiche, isolation entre deux Organisations, aucun effet sur les Partnerships.
- `tests/Feature/GamadCoreClientTest.php` — 6 cas nouveaux : provisionnement d'identité, création de fiche, réponse Core incomplète traitée en erreur de protocole, résolution ATTACH en lecture seule, résolution d'une identité inconnue (`null`).
- `tests/Feature/PartnershipTest.php`, `tests/Feature/PartnershipHttpTest.php`, `tests/Feature/CommunityEventTest.php`, `tests/Feature/CommunityEventHttpTest.php`, `tests/Feature/CommunityEventOrganizerJourneyTest.php`, `tests/Feature/ImpactMetricsTest.php` — fixtures mises à jour (raccordement Core simulé), suite complète verte, aucune régression UIUX-005.
- Suite complète du dépôt : 799/799 verts.
