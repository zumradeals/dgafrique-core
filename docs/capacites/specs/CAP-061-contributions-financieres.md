# CAP-061 — Contributions financières

## Statut

**Audit (Phase A) et implémentation V1 (Phase B) — 2026-09-01.** Le domaine n'existait auparavant que comme une ligne `NOT_IMPLEMENTED` dans `CAPABILITY-COVERAGE.md`. Débloquée par ZUMRA-COMP-001 : avant ce correctif, aucune ZUMRA ne pouvait honnêtement atteindre `VALIDATED`, rendant impossible d'implémenter sans fabrication la condition doctrinale « ZUMRA validée » (art. 6.3) que la contribution collective exige.

Ancrage doctrinal : `ZUMRA-DOCTRINE-INVARIANTE.md` art. 6 « Contributions volontaires ».

## Qu'est-ce qu'une Contribution ?

Un **engagement volontaire, jamais une dette, jamais un investissement, jamais un score**. Deux formes distinctes, jamais confondues avec l'adhésion au Programme ZUMRA (CAP-007, obligatoire pour appartenir au réseau), un don, un financement de projet (CAP-063) ou un portefeuille/abonnement bancaire :

- **Contribution individuelle** (art. 6.2) : une personne s'engage pour elle-même. Sujet = payeur = la même personne.
- **Contribution collective ZUMRA** (art. 6.3) : une ZUMRA s'engage en tant que collectif. Sujet = la ZUMRA (`ZumraGroup`) ; payeur = une personne habilitée agissant en son nom.

`Contribution` = l'**ENGAGEMENT** (l'intention durable de contribuer). `ContributionPayment` = une **TENTATIVE** mensuelle. Les deux ne sont jamais confondus : un engagement survit à l'échec d'un paiement, un engagement `STOPPED` conserve tout son historique de paiements.

## Éligibilité

- **Individuelle** : nécessite une identité Core réelle et une `ZumraProgramMembership` **active** (`assertActiveProgramMember`) — pas d'adhésion ZUMRA (groupe) requise, l'adhésion concernée est celle du Programme (CAP-007). Un seul engagement individuel par personne, à vie (`unique(type, subject_type, subject_reference)`) ; `STOPPED` se réactive via `resume()`, ne se recrée jamais en doublon.
- **Collective** : nécessite une ZUMRA dans un état éligible — `VALIDATED`, `ACTIVE`, `WARNED` ou `REHABILITATING` (`ZUMRA_ELIGIBLE_STATES`). `CONSTITUTING`/`READY` sont refusés (pas encore reconnues). `SUSPENDED` bloque uniquement l'**initiation** d'un nouvel engagement ou d'un nouveau paiement — un engagement déjà `ACTIVE` n'est jamais annulé par une suspension. Ce domaine ne modifie **jamais** `ZumraGroup.state` ni `ZumraGroup.maturity`.

## Gouvernance à deux acteurs (contribution collective)

Doctrine art. 6.3 : « premier responsable et responsable financier ». Implémenté comme un cycle propose/approuve porté directement sur `Contribution` (relation 1:1 avec l'engagement, pas de table d'autorisation séparée) :

- **`proposeCollective()`** : tout titulaire d'un rôle `PRIMARY_LEAD` ou `FINANCE_LEAD` **accepté** (`ZumraGroupRole::STATUS_ACCEPTED`) peut initier — peu importe lequel des deux propose en premier.
- **`approveCollective()`** : l'**autre** rôle des deux doit approuver. Vérifications strictes : la même identité Core ne peut jamais proposer et approuver (`hash_equals` explicite, même si elle cumulait structurellement les deux rôles — défensif, bien que l'invariant « un rôle par personne » de l'art. 8 rende ce cumul déjà structurellement impossible) ; le rôle d'approbation doit être différent du rôle de proposition (422 sinon).
- Une fois l'engagement `ACTIVE`, **tout** responsable habilité (`isLeader()` — pas seulement les deux rôles d'autorisation) peut initier le paiement du mois : la gouvernance à deux acteurs porte sur l'ENGAGEMENT, jamais sur chaque paiement mensuel individuel (doctrine : « et », pas un double-contrôle perpétuel).

## Cycle de l'engagement

`Contribution.status` : **`PROPOSED` → `ACTIVE` ⇄ `PAUSED` → `STOPPED`** (STOPPED se réactive vers `ACTIVE`, jamais un nouvel engagement).

- Individuelle : `startIndividual()` crée directement en `ACTIVE` (pas de double-acteur à attendre).
- Collective : `proposeCollective()` crée en `PROPOSED` ; `approveCollective()` bascule en `ACTIVE`.
- `pause()`/`resume()`/`stop()` : réservés au titulaire (individuelle) ou à tout `isLeader()` (collective). `STOPPED` préserve tout l'historique des paiements — jamais de perte, jamais de duplication d'engagement à la reprise.

**« Non activée » n'est jamais un statut persisté.** C'est l'absence de toute ligne `Contribution` pour ce sujet — décision délibérée pour éviter deux vérités concurrentes (une valeur d'énum qu'aucune ligne réelle ne pourrait jamais porter). De même, les états doctrinaux par période (« à jour » / « paiement en attente » / « paiement échoué ») ne sont **jamais persistés sur l'engagement** : ils se **dérivent** du dernier `ContributionPayment` de la période concernée, pour la même raison.

## Paiement mensuel

`payPeriod(contribution, actor, period, purposeCode, successUrl, errorUrl)` — geste humain explicite, jamais d'auto-prélèvement, d'auto-facturation ni de dette en cas d'absence (aucune capacité d'abonnement récurrent n'existe côté GeniusPay — confirmé en Phase A, jamais supposée).

- `period` : format canonique `AAAA-MM` (regex stricte).
- Montant : lu depuis `ContributionConfiguration` (500 XOF individuelle / 2500 XOF collective par défaut, configurable par l'administration), **figé au moment du paiement** — jamais recalculé rétroactivement si la configuration change ensuite.
- Finalité (`purposeCode`) : doit référencer une `ContributionPurpose` **active** (422 sinon) parmi les 8 codes canoniques de l'art. 6.5.
- Ouverture : `ContributionConfiguration.individual_enabled`/`collective_enabled` doivent être explicitement activés par l'administration (409 sinon) — même garde-fou que CAP-007B (`payments.membership.enabled`), jamais dupliqué sous un nom différent pour le même rôle.

## Concurrence et idempotence

Pas de contrainte `UNIQUE(subject, period)` naïve, qui aurait bloqué toute nouvelle tentative après un échec. L'invariant réel : **aucun paiement `PENDING`/`PROCESSING`/`COMPLETED` concurrent pour le même (engagement, période)**, mais `FAILED`/`CANCELLED` autorise une nouvelle tentative pour la même période.

- Vérification applicative avant création (`whereIn('status', [PENDING, PROCESSING, COMPLETED])->exists()`).
- **Filet de sécurité réel** : index unique partiel Postgres `dg_contribution_payments_active_period_unique` sur `(contribution_id, period) WHERE status IN ('PENDING','PROCESSING','COMPLETED')`, posé via `DB::statement(...)` guardé `pgsql` (précédent du dépôt : `2026_08_18_000000_harden_mission_tables.php`). Une violation `23505` en course réelle est rattrapée en 409 applicatif, jamais une 500.
- `DB::transaction(..., 3)` + `lockForUpdate` sur le paiement et l'engagement, identique au motif `MembershipPaymentService`.

## GeniusPay — réutilisation

**Architecture A retenue** (tables neuves CAP-061, jamais de polymorphisme ni de migration de `dg_zumra_payments`/`dg_zumra_payment_receipts`) : `dg_zumra_payments.membership_id` est une FK dure vers `dg_zumra_program_memberships`, confirmée non réutilisable en Phase A. `MembershipPaymentService` n'a reçu aucune modification.

Seul `GeniusPayClient` est étendu :

- **`createContributionPayment(amount, currency, description, metadata, successUrl, errorUrl)`** — générique, contrairement à `createMembershipPayment()` qui reste verrouillée à 500 XOF « adhésion ». Réutilise `request()`/`normalize()` à l'identique.
- **Correctif de couplage découvert pendant l'extension** : `request()` (le constructeur HTTP générique, partagé par `createMembershipPayment()`, `createContributionPayment()` et `payment()`) vérifiait à tort `payments.membership.enabled`, ce qui aurait silencieusement bloqué tous les paiements et réconciliations CAP-061 dès que les paiements d'adhésion étaient désactivés. Corrigé en déplaçant cette vérification spécifique dans `createMembershipPayment()` elle-même ; `request()` ne vérifie plus que la connectivité générique au prestataire (environnement/clé/secret). Comportement CAP-007B strictement inchangé — les 13 tests `ZumraMembershipPaymentTest` restent verts après ce correctif.

Le prestataire reste seul responsable du checkout et du mouvement d'argent réel ; DG Afrique reste responsable de l'engagement, du sujet, du payeur, de la période, de la finalité, de la réconciliation, du reçu et de l'historique — aucune abstraction bancaire parallèle (pas de portefeuille, pas de solde, pas d'écriture en partie double).

## Réconciliation

Identique au motif CAP-007B, jamais affaibli : **le retour navigateur n'est jamais une preuve.** `returned()` ne lit jamais le paramètre `outcome` de la requête pour établir un fait — toute vérité provient de `reconcile()`, qui relit systématiquement le prestataire en serveur-à-serveur.

Seuls les champs réellement renvoyés par GeniusPay sont comparés à distance (`amount`, `environment`) ; la devise et la finalité restent des vérifications **locales** (GeniusPay ne renvoie ni l'une ni l'autre — confirmé en Phase A, jamais une comparaison distante inventée). Toute incohérence lève `CONTRIBUTION_PAYMENT_RECONCILIATION_MISMATCH`, jamais une confirmation silencieuse.

`sandbox` ne finalise (reçu + événement) que si `payments.geniuspay.sandbox_activation_allowed` est explicitement activé ; `live` finalise toujours. Même interrupteur que CAP-007B, jamais dupliqué sous un nom différent.

## Reçus

`ContributionReceipt` — même motif que `ZumraPaymentReceipt` : numérotation unique (`DGC-{année}-{aléatoire}`), hash d'intégrité SHA-256 sur les champs canoniques, émission idempotente (`issueReceipt()` vérifie l'existence avant création), immuable. Le retrait d'une `ContributionPurpose` n'altère jamais un reçu ni un paiement déjà émis (`purpose_id` en `restrictOnDelete()`).

## Finalités (art. 6.5)

Table réelle versionnée/auditée `dg_contribution_purposes` — **jamais un enum PHP**, pour permettre configuration, audit et retrait sans perdre l'historique. 8 codes canoniques seedés à la migration (acteur synthétique `MIGRATION-CAP-061`, précédent du dépôt : `2026_08_26_100000_create_satellites_table.php`) :

`ECOSYSTEM_SUSTAINABILITY`, `TRAINING`, `NEW_ZUMRA`, `VALIDATED_PROJECTS`, `INFRASTRUCTURE`, `SOLIDARITY`, `EMERGENCY`, `AUTHORIZED_FEES`.

Chaque paiement enregistre la finalité choisie — **aucun calcul de répartition** (hors périmètre CAP-061, réservé à un éventuel futur ledger).

## Frontière CAP-062 (Ledger / traçabilité) — non implémentée

Aucun ledger, aucune écriture comptable dans cette PR. Chaque `ContributionPayment` confirmé porte néanmoins déjà tous les champs qu'un futur CAP-062 additif nécessiterait : référence, montant, devise, finalité, période, statut, reçu, `payer_core_reference` (`initiated_by_core_reference`), `subject_type`/`subject_reference`.

## Frontière CAP-063 (Financement de projet) — non implémentée

`VALIDATED_PROJECTS` reste un **simple code de destination doctrinal** parmi les 8 finalités — il n'implique jamais qu'un Projet réel est financé. Aucun modèle `Project` n'est touché, aucun lien financier vers un Projet n'existe.

## Invariants sociaux absolus

Aucun score, rang, badge, priorité, visibilité ou privilège dérivé du montant, de la fréquence ou de l'absence de contribution. Aucune dette. Aucune suspension automatique de ZUMRA liée à un paiement manqué. Aucun portefeuille, solde ou écriture en partie double. Vérifié par réflexion sur les colonnes réelles des modèles dans `tests/Feature/ContributionTest.php` (aucune colonne `score`/`rank`/`debt`/`balance`/`wallet`).

## Exposition HTTP (surface minimale)

- **Membre** : `GET /contributions`, `POST /contributions/individuelle`, `POST /contributions/{contribution}/{pause,reprise,arret,paiement}`, `GET /contributions/{contribution}/paiements/retour`, `GET /contributions/recus/{receipt}`.
- **ZUMRA** : `POST /zumra/groupes/{group}/contribution` (proposition), `POST /zumra/groupes/{group}/contribution/approbation`.
- **Administration** (`PortalAdministrator`) : `GET/PUT /contributions` (configuration), `POST /contributions/finalites/{purpose}/{retrait,reactivation}`.

Limitation de débit sur toutes les routes de mutation/paiement (`contribution-write`, `contribution-payment`, `contribution-payment-status`, `contribution-configuration`).

## V1 implémentée

- `Contribution`, `ContributionPayment`, `ContributionReceipt`, `ContributionPurpose`, `ContributionEvent` (`dg_contributions`, `dg_contribution_payments`, `dg_contribution_receipts`, `dg_contribution_purposes`, `dg_contribution_events`) ;
- `ContributionConfiguration` (motif `ZumraGroupConfiguration`/`PortalSetting`) ;
- `ContributionService` : `startIndividual/proposeCollective/approveCollective/pause/resume/stop/payPeriod/reconcile/isLeader`, réutilisant `ZumraGroupService`, `GeniusPayClient` — aucune règle d'autorisation ou de cycle de vie ZUMRA dupliquée ;
- `ContributionController` (membre), `ContributionConfigurationController` (administration).

## Preuve

`tests/Feature/ContributionTest.php` — 45 cas : adhésion Programme requise, réversibilité complète individuelle avec préservation d'historique à la réactivation après `STOPPED`, refus d'engagement en double, portes d'état ZUMRA (`CONSTITUTING`/`READY` refusés, `VALIDATED`/`ACTIVE`/`WARNED`/`REHABILITATING` acceptés, `SUSPENDED` refuse la proposition et le nouveau paiement sans jamais toucher `ZumraGroup.state`), gouvernance à deux acteurs (ordre indifférent, même personne refusée, même rôle refusé, tiers sans rôle refusé, paiement impossible avant approbation), mécanique de paiement (montant par type, finalité désactivée refusée, retrait de finalité sans altération de l'historique, double paiement mensuel refusé, `FAILED`/`CANCELLED` autorisent une nouvelle tentative, `PENDING` bloque la concurrence), réconciliation (retour navigateur jamais suffisant, réconciliation serveur confirme et émet un reçu idempotent, incohérences montant/environnement/devise toutes rejetées, bascule sandbox on/off), reçus, invariants sociaux (absence de score/rang/dette/wallet/solde par réflexion, aucun Projet/Organisation/Satellite créé automatiquement), contrat de données minimal CAP-062, régression CAP-007B (`test_membership_payments_still_work_after_the_genius_pay_client_generalization`).
