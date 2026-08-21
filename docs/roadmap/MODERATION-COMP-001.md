# MODERATION-COMP-001 — Modération, discipline et recours

> **Ce n'est pas une CAP officielle.** Identifiant non-CAP (voir `docs/roadmap/ROADMAP-METIER-CANONIQUE.md` — section ROADMAP-003, « Pourquoi aucun numéro CAP n'est créé ici »). Aucune ligne `CAPABILITY-INDEX.md`/`CAPABILITY-COVERAGE.md` n'est modifiée par ce chantier ; le référentiel reste figé à exactement CAP-001–CAP-084 (`docs/AI-RULES.md:82`).

## Statut

**Phase A (audit) validée, Phase B (implémentation V1) livrée — 2026-09-22.** Ancrage doctrinal : `docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md` art. 19 (Modération, discipline et recours), art. 11.4 (Exclusion), art. 12 (Gouvernance), art. 13 (Droits sociaux — signalement), art. 20 (Protection des mineurs), art. 21 (Vie privée), art. 23.1/23.2 (Invariants et paramètres administrables).

Fissure comblée : l'art. 19 décrivait une capacité réelle sans aucun numéro CAP correspondant dans le référentiel V0.1 (constat posé par ROADMAP-003). `ZumraGroupMembership::STATUS_EXCLUDED` existait déjà comme constante déclarée mais totalement morte (aucune méthode ne la produisait) — le schéma anticipait déjà partiellement cette capacité.

## Phase A — rappel des conclusions validées

- Trois niveaux d'autorité déjà réels dans le runtime : auteur (niveau 1), responsables ZUMRA via `ZumraGroupService::isLeader()` (niveau 2), `PortalAdministrator` (niveau 3, seule autorité DG Afrique/GAMAD réellement disponible).
- `MessagingService::openSupport()` est un canal de communication réel mais non structuré — jamais le registre disciplinaire.
- Aucun code de modération, signalement, masquage ou décision disciplinaire n'existait avant ce chantier (recherche exhaustive confirmée).
- `ContextComment` et `MessageEntry` ne portaient aucun champ de statut ou de masquage.
- Architecture retenue : **HYBRIDE C→B** — (A) discipline ZUMRA interne réutilisant `ZumraGroupMembership`/`ZumraGroupService`/`ZumraGroupEvent`, (B) signalement transversal (`ModerationReport`, nouveau), (C) décision disciplinaire vivante (`ModerationDecision`, nouveau — un événement seul ne peut pas porter un recours en attente), (D) masquage local scopé à `ContextComment`/`MessageEntry`, sans moteur de visibilité universel.

## Décisions produit V1 (frozen)

1. **Autorité niveau 2** : les 5 responsables ZUMRA acceptés, via `ZumraGroupService::isLeader()` — aucune matrice d'autorisation nouvelle. **Exception PRIMARY_LEAD** : si la cible disciplinaire est le premier responsable, la décision escalade obligatoirement au niveau 3 (`PortalAdministrator`) — un pair ne peut jamais juger le premier responsable.
2. `ModerationReport` est **persistant et canonique** dès V1. `MessagingService::openSupport()` reste disponible comme canal de communication, jamais le registre disciplinaire.
3. **Le recours n'est pas suspensif** : la décision continue de produire ses effets pendant l'examen. L'autorité de recours peut CONFIRMER, MODIFIER ou LEVER la décision.
4. **Motifs V1** (constantes PHP, pas de table) : `VIOLENCE`, `THREAT`, `FRAUD`, `HARASSMENT`, `DISCRIMINATION`, `HATE`, `EXPLOITATION`, `MISAPPROPRIATION`, `IMPERSONATION`, `DANGEROUS_MISINFORMATION`, `OTHER` (`OTHER` exige `reason_details`).
5. **Masquage** : le contenu disparaît de la circulation ordinaire (y compris pour son propre auteur), n'est jamais détruit physiquement, reste consultable par l'autorité disciplinaire compétente comme preuve.

## Architecture finale

### (A) Discipline ZUMRA interne — aucune table nouvelle

`ZumraGroupService` reçoit cinq nouvelles méthodes, toutes défendues en profondeur (autorité revérifiée au niveau service, jamais seulement au niveau HTTP) :

- `exclude(group, actor, subject, reason, establishedThreshold)` — `ACTIVE|SUSPENDED → EXCLUDED`. Comble la lacune `STATUS_EXCLUDED`. `LEFT` (départ volontaire) reste strictement distinct — jamais réutilisé pour une décision disciplinaire.
- `suspendMember(...)` / `reinstate(...)` — suspension **individuelle**, jamais `ZumraGroup::STATE_SUSPENDED` (qui suspend la ZUMRA entière). Cible exclusivement `ZumraGroupMembership.status`, réversible.
- `revokeRole(group, actor, role)` — réutilise `ZumraGroupRole` (`ACCEPTED → VACANT`), aucun second système de rôles. `PRIMARY_LEAD` reste toujours niveau 3.
- `isPrimaryLead(group, subject)` — utilisée pour l'escalade obligatoire.
- `assertDisciplinaryAuthority(...)` — l'unique point qui applique la règle PRIMARY_LEAD, réutilisé par `exclude()` et `suspendMember()`.

Nouveaux `ZumraGroupEvent` : `MEMBER_EXCLUDED`, `MEMBER_SUSPENDED`, `MEMBER_REINSTATED`, `ROLE_REVOKED` — traçabilité factuelle uniquement. **`ZumraGroupEvent` ne porte jamais l'état vivant du recours** : cet état appartient exclusivement à `ModerationDecision`.

### (B) `ModerationReport` — signalement transversal

Table `dg_moderation_reports`. Cibles V1 strictement limitées à `CONTEXT_COMMENT`, `MESSAGE_ENTRY`, `ZUMRA_MEMBERSHIP` (aucun support universel non testé).

`context_type`/`context_reference` ne décrivent **pas** le contenu ciblé mais la **portée d'autorité niveau 2** : `'ZUMRA'` + id de groupe uniquement lorsque la cible relève réellement de la gouvernance d'une ZUMRA identifiable (contenu `ZUMRA_ACTIVITY`, message dans une conversation ZUMRA, adhésion ZUMRA) ; `null` sinon — ce qui réserve alors le signalement à DG Afrique (niveau 3) seul. Le mécanisme d'escalade explicite (`escalated_at`) retire définitivement un signalement des mains du niveau 2 : une ZUMRA ne peut ni l'intercepter, ni le bloquer, ni le clore avant DG Afrique (art. 19).

### (C) `ModerationDecision` — décision disciplinaire vivante

Table `dg_moderation_decisions`. Porte l'état vivant qu'un journal d'événements seul ne peut pas représenter : `status` (`ACTIVE`/`LIFTED`/`EXPIRED`/`MODIFIED`), `expires_at`, et l'ensemble des champs `appeal_*` (recours). Toute décision V1 provient d'un `ModerationReport` `PENDING` (`moderation_report_id` reste nullable au schéma pour ne pas fermer une évolution future, mais aucune voie HTTP V1 ne décide sans signalement).

Actions V1 (`ModerationDecision::ACTION_TYPES`) : `CONTENT_HIDDEN`, `WARNING`, `MEMBERSHIP_SUSPENSION`, `MEMBERSHIP_EXCLUSION`, `ROLE_REVOCATION`. **`LIMITATION` n'existe pas en V1** — aucun effet transversal borné n'a pu être identifié sans toucher de nombreux services ; documentée comme différée plutôt que simulée (voir « Limitations »).

### (D) Masquage local — `ContextComment`/`MessageEntry`

Plus petit changement suffisant : un seul champ `hidden_at` (timestamp nullable) ajouté à chacune des deux tables. Sa présence signifie masqué. Aucun soft-delete générique, aucun champ `status` supplémentaire. Les lectures ordinaires (`ContextCommentService::thread()`, `MessagingService::thread()`, `MessagingService::inbox()`) filtrent `whereNull('hidden_at')`. L'accès disciplinaire/preuve interroge directement le modèle sans ce filtre (`ContextComment::query()->find($id)`), sans mécanisme dédié supplémentaire.

## Invariants absolus rendus exécutables

Conformément à l'art. 23.1 et au mandat, les invariants suivants sont désormais réellement appliqués (et testés — voir « Preuve ») :

- **Droit au signalement** : tout membre peut signaler ; aucune ZUMRA ne peut intercepter un signalement niveau 3 ou empêcher l'escalade.
- **Droit à l'explication** : `ModerationDecisionController::mine()` expose motif, autorité, date, durée éventuelle et voie de recours pour toute décision concernant la personne.
- **Droit au recours** : `requestAppeal()`/`decideAppeal()`, non suspensif.
- **Réponse proportionnée** : cinq actions distinctes, chacune avec un effet réel borné (aucune sanction décorative).
- **Traçabilité des décisions** : chaque `ModerationDecision` porte motif, autorité, date, décideur.
- **Conservation des preuves** : masquage jamais destructeur ; aucune route de suppression physique n'existe pour `ContextComment`/`MessageEntry`.
- **Escalade vers l'autorité supérieure** : recours niveau 2 → niveau 3 obligatoire (`decideAppeal()` exige `PortalAdministrator`).
- **Impossibilité pour une ZUMRA de bloquer un signalement vers DG Afrique** : `escalated_at` retire le signalement du périmètre niveau 2 ; le niveau 3 voit **tous** les signalements sans exception, y compris ceux qu'une ZUMRA n'a jamais eu le droit de voir.

## Confidentialité du signalant (art. 21) — critique

`reporter_core_reference` n'existe **structurellement que sur `ModerationReport`**, jamais sur `ModerationDecision`. La personne visée n'a donc, par construction, aucun moyen d'accéder à l'identité du signalant même en cas de fuite d'implémentation future de `ModerationDecisionController`. Côté niveau 2, `ModerationReportService::presentForZumraLeader()` omet explicitement `reporter_core_reference` — seul `presentForAdministrator()` (niveau 3) l'inclut. Testé négativement (`test_the_reporter_identity_is_never_exposed_to_the_reported_person`, `test_the_reporter_identity_stays_protected_in_a_message_report_too`, `test_the_reporter_identity_stays_secret_during_the_appeal`).

## Messages privés — frontière stricte (art. 21)

Un responsable ZUMRA ne peut **jamais** parcourir une conversation privée tierce : `ZumraGroupModerationController` et `ModerationReportService` ne créent aucune nouvelle voie d'accès à `MessageEntry`/`MessageConversation` — `reportMessageEntry()` exige `MessagingService::canAccess()` (le participant reporte lui-même). Le signalement conserve uniquement la référence du message et un extrait tronqué (`ModerationReportService::targetExcerpt()`, 300 caractères) — jamais le fil complet. `MessagingService::canAccess()`/`assertAccess()` ne sont **pas modifiés** par ce chantier (diff vérifié).

## PRIMARY_LEAD — règle spéciale

Si la cible d'`exclude()`, `suspendMember()` ou d'une décision de niveau 2 est le `PRIMARY_LEAD` du groupe, l'autorité de niveau 2 est refusée (403) et seule `PortalAdministrator` peut agir. Vérifié à deux niveaux redondants : `ModerationDecisionService::decideAsZumraLeader()` (avant d'appeler le service ZUMRA) et `ZumraGroupService::assertDisciplinaryAuthority()` (défense en profondeur).

## Warning — aucun effet caché (art. 21)

`WARNING` ne modifie jamais matching, visibilité, contribution, finance, score ou profil — c'est une décision purement traçable et informative, avec une durée d'affichage administrable (`warning_default_duration_days`) sans effet d'application.

## LIMITATION — différée hors V1

Aucun effet transversal borné (« interdiction temporaire de publier dans un contexte ZUMRA », donné en exemple par le mandat) n'a pu être rattaché à une autorité existante sans toucher de nombreux services de publication distincts (`ContextCommentService`, `MessagingService`, potentiellement `NeedService`/`ProjectService`). Conformément au mandat (« ne jamais créer une sanction décorative »), `LIMITATION` est explicitement absente de `ModerationDecision::ACTION_TYPES` en V1 — dette documentée, pas simulée.

## Expiration — calcul à la lecture

Pas de scheduler ni de commande. `ModerationDecisionService::withExpiryApplied()` recalcule l'état à chaque lecture (`myDecisions()`, `requestAppeal()`) : une décision `ACTIVE` dont `expires_at` est dépassé passe `EXPIRED`, et pour `MEMBERSHIP_SUSPENSION`, `ZumraGroupService::reinstate()` est appelé automatiquement (`SUSPENDED → ACTIVE`).

## `PortalSetting` — paramètres réellement consommés

`ModerationConfiguration` (`app/Application/Moderation/ModerationConfiguration.php`), clé `moderation.configuration` : `warning_default_duration_days` (défaut 90), `suspension_default_duration_days` (défaut 30). Aucun paramètre mort — `rehabilitation_duration` n'a pas été créé faute d'un point de consommation réel en V1 (aucune réhabilitation automatique individuelle). **Limitation documentée** : aucun écran d'administration dédié n'existe encore ; la valeur reste modifiable via `PortalSetting` comme tout paramètre administrable du dépôt.

## Frontières absolues — vérifiées par test

- **ActivityFeed** : aucun événement de modération n'est dans `ActivityFeedService::ZUMRA_EVENTS` (whitelist explicite, non modifiée par ce chantier) — structurellement impossible d'apparaître au Fil.
- **Matching/recommandations** : aucune référence à `CapabilityStatement`, `OpportunityEngine`, `MissionMatchingEngine`, `ProjectMatchingEngine`, `PersonRecommendationEngine`, `TransmissionMatchingEngine` dans `app/Application/Moderation`.
- **Finance** : aucune référence à `Contribution`, `ContributionPayment`, `LedgerEntry`, `ProjectFunding`, GeniusPay dans `app/Application/Moderation`.
- **Propriété/identité** : aucune décision ne modifie `PersonProfile`, `Organization`, `Project.owner_*`, `ProjectTeamMember`, `Partnership`. Seules exceptions disciplinaires légitimes : adhésion ZUMRA, rôle ZUMRA, masquage de contenu.

## Migrations

Additives uniquement :

- `2026_09_22_100000_create_moderation_reports_and_decisions_tables.php` — `dg_moderation_reports`, `dg_moderation_decisions`, deux index uniques partiels Postgres (`dg_moderation_decisions_active_per_report_unique`, `dg_moderation_decisions_active_per_target_unique`).
- `2026_09_22_100100_add_moderation_masking_to_comments_and_messages.php` — `hidden_at` sur `dg_context_comments` et `dg_message_entries`.

Aucune modification de `dg_zumra_group_memberships` (le champ `status` était déjà un `string(24)` libre — `SUSPENDED` y tient sans migration).

## Concurrence et idempotence

Même patron que CAP-061/062/063 : verrouillage applicatif (`lockForUpdate()`) sous transaction + filet de sécurité `QueryException` code `23505` sur les index uniques partiels. Une double décision sur un même signalement, ou deux décisions actives simultanées sur une même cible, sont toutes deux impossibles — testé au niveau applicatif et au niveau base (insertion brute concurrente).

## HTTP

Surface minimale (`routes/moderation.php`, chargé par `AppServiceProvider::boot()`), JSON pour la lecture / redirection `back()` pour l'écriture (patron CAP-063) :

- `POST /moderation/commentaires/{comment}/signalement`, `/moderation/messages/{entry}/signalement`, `/moderation/adhesions/{membership}/signalement` — signalement (niveau 1).
- `GET /moderation/mes-signalements`, `POST /moderation/signalements/{report}/escalade`.
- `GET /moderation/mes-decisions`, `POST /moderation/decisions/{decision}/recours`.
- `GET|POST /zumra/groupes/{group}/moderation[/{report}/decision]` — niveau 2, `core.member` (autorité revérifiée en service).
- `GET|POST /administration/moderation[...]` — niveau 3, `core.member` + `portal.admin`.

Rate limiters dédiés (`AppServiceProvider::boot()`) : `moderation-report` (6/min), `moderation-decision` (15/min) — protègent contre l'abus sans jamais bloquer structurellement le droit au signalement (aucun quota métier permanent).

## État de livraison

- **Modèles** : `ModerationReport`, `ModerationDecision`.
- **Services** : `ModerationReportService`, `ModerationDecisionService`, `ModerationConfiguration`.
- **Contrôleurs** : `ModerationReportController`, `ModerationDecisionController`, `ZumraGroupModerationController`, `Administration\ModerationController`.
- **Étendus** : `ZumraGroupService` (5 méthodes), `ZumraGroupMembership` (`STATUS_SUSPENDED`), `ContextComment`/`MessageEntry` (`hidden_at`), `ContextCommentService`/`MessagingService` (filtrage lecture).
- **Tests** : `tests/Feature/ModerationTest.php` — 57 cas couvrant les 6 catégories mandatées (signalement, messages privés, modération de contenu, ZUMRA, recours, effets cachés interdits) plus concurrence/idempotence/expiration/LIMITATION-différée.
- **Régression** : suite complète 656 tests, mêmes 4 tests non-passants préexistants par nom exact, aucune régression nouvelle.

## Limitations V1 documentées

- `LIMITATION` différée (voir plus haut).
- Recours sur une décision déjà niveau 3 : aucune autorité GAMAD distincte n'étant techniquement disponible, seul un réexamen administratif par `PortalAdministrator` reste possible — jamais présenté comme un « recours GAMAD » réel.
- Levée (`LIFTED`) d'une exclusion ou d'une révocation de rôle : la décision est tracée comme levée, mais la ré-admission/le rétablissement du rôle **ne sont pas automatiques** — ils exigent une nouvelle décision de gouvernance ZUMRA explicite (invitation/proposition de rôle), pour ne jamais rétablir silencieusement un statut sans consentement frais.
- `ModerationConfiguration` n'a pas d'écran d'administration dédié (voir « PortalSetting »).
- Le signalement d'un `ContextComment` hors contexte ZUMRA (`NEED`/`PROJECT`/`MISSION`/`TRANSMISSION`/`PROOF`) ne revérifie pas la visibilité complète du contexte porteur (Need/Project/...) — seulement que le signalant n'est pas l'auteur. Risque jugé mineur (identifiants UUID non devinables, aucune donnée du commentaire n'est renvoyée au signalant).
- `myDecisions()`/`myReports()` restent bornées (`limit(200)`/`limit(100)`) sans pagination — suffisant pour le volume V1, à revisiter si le volume grandit.

## Doctrine à clarifier restante

- L'art. 20 (mineurs) reste hors périmètre technique : `PersonProfile` ne porte toujours aucune donnée de minorité fiable (confirmé Phase A) ; aucune détection automatique n'a été ajoutée, conformément au mandat.
- La définition précise d'un « effet réel borné » pour `LIMITATION` reste à trancher avec un exemple métier concret avant toute implémentation future.
