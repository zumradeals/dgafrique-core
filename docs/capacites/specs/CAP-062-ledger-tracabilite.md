# CAP-062 — Ledger / traçabilité

## Statut

**Audit (Phase A) et implémentation V1 (Phase B) — 2026-09-08.** Le domaine n'existait auparavant que comme une ligne `NOT_IMPLEMENTED` dans `CAPABILITY-COVERAGE.md`, sans aucune spec ni aucun code (`docs/capacites/CAPABILITY-INDEX.md:70` : « CAP-062 — LEDGER / TRAÇABILITÉ »).

Ancrage doctrinal : `ZUMRA-DOCTRINE-INVARIANTE.md` art. 6.5 (« Chaque paiement produit une référence, un montant, une devise, une finalité, une période, un statut, un reçu et une écriture traçable. Un remboursement produit une écriture inverse ; il ne supprime pas l'écriture initiale. ») et art. 23.1 (« la traçabilité des décisions et fonds » — invariant absolu).

## Qu'est-ce que CAP-062 ?

**Un journal financier simple, immuable, additif.** Une écriture (`LedgerEntry`) par paiement réellement CONFIRMÉ — jamais avant, jamais pour un paiement seulement tenté. CAP-062 **n'est pas** un wallet, un compte bancaire, un solde dépensable, un système de crédit, une monnaie interne, un moteur double-entry, un moteur d'allocation, ni un moteur de financement de Projet.

`LedgerEntry` est une **projection**, jamais une source de vérité. Le flux strict est :

```
GeniusPay → reconcile() du domaine appelant → paiement CONFIRMÉ → reçu → LedgerEntry.
```

Le ledger ne modifie **jamais** un paiement source, une `Contribution`, une `ZumraGroup`, une `ZumraProgramMembership`, ni ne gouverne une personne. Aucune méthode `updateEntry()`/`deleteEntry()` n'existe : une écriture postée n'est jamais réécrite. Une correction future produirait une nouvelle écriture (`reverses_entry_id`, schéma préparé, aucun workflow implémenté en V1).

## Sources V1

Deux sources alimentent le ledger, sans jamais fusionner leurs modèles respectifs :

- **`CONTRIBUTION_PAYMENT`** → `ContributionPayment` (CAP-061).
- **`MEMBERSHIP_PAYMENT`** → `ZumraPayment` (CAP-007B, adhésion au Programme ZUMRA).

Aucune source `Organization`, `Partnership` ni `Project` : aucun flux financier réel n'existe pour ces domaines (confirmé par l'audit Phase A — zéro champ financier, zéro flux).

## Modèle : `LedgerEntry`

Table `dg_ledger_entries`, clé UUID, motif identique aux autres tables `dg_*` du dépôt :

| Champ | Rôle |
|---|---|
| `source_type` / `source_id` | Traçabilité vers le paiement source — jamais dupliqué au-delà du nécessaire |
| `entry_type` | `PAYMENT` en V1 ; `REVERSAL`/`CORRECTION` réservés au schéma, jamais produits |
| `reverses_entry_id` | FK nullable auto-référencée, `restrictOnDelete()`, inutilisée en V1 |
| `amount` (`unsignedInteger`) | Montant exact du paiement confirmé — jamais un float |
| `currency` (`char(3)`) | Devise du paiement — jamais agrégée entre devises différentes |
| `purpose_code` | Nullable ; code `ContributionPurpose` pour CAP-061, `ZumraPayment::PURPOSE_MEMBERSHIP` pour CAP-007B |
| `period` | Nullable ; `AAAA-MM` pour CAP-061, toujours `NULL` pour l'adhésion (non périodique) |
| `payer_core_reference` | Qui a réellement initié le paiement |
| `subject_type` / `subject_reference` | `PERSON` ou `ZUMRA_GROUP` (constantes réutilisées de `Contribution`) — à qui appartient l'engagement |
| `occurred_at` | Moment réel de confirmation (`completed_at` du paiement source) |
| `posted_at` | Moment d'écriture de la ligne ledger — distinct d'`occurred_at` pour permettre le backfill (`occurred_at < posted_at` sur un historique) |

Contrainte d'idempotence : `UNIQUE(source_type, source_id)` — un paiement source ne produit jamais plus d'une écriture, pour toujours (contrairement aux tentatives de paiement elles-mêmes, qui peuvent légitimement se répéter après échec). Index `(subject_type, subject_reference)` et `period` pour les lectures attendues.

Volontairement absents (surarchitecture V1 rejetée en Phase A) : `LedgerAccount`, `LedgerPosting`, `Wallet`, `Balance`, `LedgerEvent`. Une écriture immuable et horodatée constitue déjà sa propre trace d'audit — dupliquer un journal d'événements par-dessus serait redondant, contrairement à `Contribution`/`ZumraGroup` dont l'état mute et nécessite un journal séparé.

## Posting

`LedgerService::postContributionPayment(ContributionPayment $payment)` et `LedgerService::postMembershipPayment(ZumraPayment $payment)` — chacune vérifie `status === COMPLETED` (sinon retourne `null`, aucune écriture), cherche une écriture existante par `(source_type, source_id)` (idempotence applicative), sinon en crée une, avec un filet de sécurité `QueryException` code `23505` (contrainte unique) qui retrouve l'écriture déjà postée par un appel concurrent plutôt que d'échouer.

Appelé exactement une fois par chemin de confirmation réel :

- `ContributionService::reconcile()` — dans la branche `STATUS_COMPLETED`, immédiatement après `issueReceipt()`, à l'intérieur de la même transaction verrouillée (`lockForUpdate`) que CAP-061 utilise déjà pour garantir qu'un même paiement ne se finalise qu'une fois.
- `MembershipPaymentService::reconcile()` — même emplacement, après `issueReceipt()`, à l'intérieur de la transition `PENDING_PAYMENT → ACTIVE` déjà verrouillée par CAP-007B.

`PENDING`, `PROCESSING`, `FAILED`, `CANCELLED` : aucune écriture — aucun argent n'a réellement bougé. Le seul retour navigateur (`returned()`) ne crée jamais d'écriture : toute vérité provient de `reconcile()` serveur-à-serveur, jamais du paramètre `outcome`.

## Idempotence et concurrence

`UNIQUE(source_type, source_id)` est l'autorité finale. Le service la respecte à trois niveaux : recherche préalable (chemin rapide, sans exception), tentative de création, puis récupération de l'écriture existante en cas de violation de contrainte (chemin de course — `reconcile()` concurrent, ou `reconcile()` et `ledger:backfill` exécutés en parallèle). Jamais d'erreur utilisateur sur une simple course bénigne, jamais de doublon.

## Backfill

Commande `php artisan ledger:backfill` (`LedgerBackfillCommand`) — utilise **exactement** le même `LedgerService` que le runtime, aucune logique de posting dupliquée. Parcourt tous les `ContributionPayment`/`ZumraPayment` en statut `COMPLETED`, ignore tout le reste, ne modifie jamais les paiements source. Déterministe et rejouable à volonté : l'idempotence vient uniquement de la contrainte `UNIQUE`, jamais d'un état de progression suivi par la commande elle-même.

**Ordre de déploiement documenté :** (1) déployer la migration — les nouveaux paiements confirmés commencent immédiatement à poster normalement via `reconcile()` ; (2) exécuter `ledger:backfill` pour rattraper l'historique antérieur au déploiement ; (3) la contrainte `UNIQUE(source_type, source_id)` absorbe proprement tout recouvrement entre les paiements déjà postés par `reconcile()` entre-temps et ceux que le backfill retrouve — aucun doublon possible, dans n'importe quel ordre d'exécution.

## Devise et montants

`unsignedInteger` (unités mineures entières), jamais un float — convention reprise à l'identique de `dg_contribution_payments`/`dg_zumra_payments`. `char(3)` pour la devise, conservée sur chaque écriture ; XOF est aujourd'hui la seule devise réellement configurée, mais le champ n'encode aucune hypothèse de devise unique éternelle. Aucune agrégation entre devises différentes n'est implémentée.

## Autorisations et confidentialité

Aucune nouvelle matrice de permissions : réutilisation stricte de l'autorité déjà établie par CAP-061/CAP-007B. Une personne (`GET /finances/ledger`, `GET /finances/ledger/{entry}`) ne voit que les écritures dont elle est le sujet (`subject_type=PERSON`, `subject_reference=elle-même`) ; un responsable ZUMRA habilité (`isLeader()`, la même autorité que CAP-061 utilise déjà pour les paiements collectifs) voit les écritures de sa ZUMRA ; l'administration (`GET /administration/ledger`, `PortalAdministrator` via `portal.admin`) voit le ledger global. Jamais public. Aucune exposition dans `ActivityFeed`, un profil, une recommandation ou une opportunité (confirmé par l'audit Phase A — zéro intégration existante, et aucune n'est ajoutée ici).

## HTTP

Surface de lecture uniquement — aucune écriture ne naît jamais d'une requête HTTP :

- `GET /finances/ledger`, `GET /finances/ledger/{entry}` (membre) ;
- `GET /administration/ledger` (administration, lecture/audit uniquement).

Aucun `POST`/`PUT`/`PATCH`/`DELETE` d'écriture manuelle.

## Frontière GeniusPay

Inchangée par rapport à CAP-061/CAP-007B : GeniusPay reste seul responsable du mouvement réel de l'argent. Le ledger ne l'appelle jamais et ne décide jamais d'un statut de paiement — il observe uniquement un paiement déjà confirmé par le domaine appelant.

## Frontière CAP-063 (Financement de projet)

Aucun financement de Projet, aucun wallet Projet, aucun escrow, aucun solde Projet, aucun compte comptable Projet dans cette PR. CAP-063 reste entièrement séparée, non implémentée.

## Frontière GAMAD Core (CAP-067)

La doctrine (art. 3.2) énonce que GAMAD Core « porte ou atteste » certaines écritures financières, mais **aucun runtime correspondant n'existe** dans ce dépôt (`GamadCoreClient` ne porte aucune méthode financière). CAP-062 V1 reste **entièrement locale à DG Afrique** — aucun endpoint Core n'est fabriqué, aucun hack d'identité organisationnelle n'est introduit. Cette limite est documentée ici sans être comblée, dans la même posture que CAP-067/070 « gelées » ailleurs dans la roadmap.

## Remboursement

Aucun workflow refund en V1. `ZumraPayment::STATUS_REFUNDED`/`ContributionPayment::STATUS_REFUNDED` restent des statuts acceptés mais jamais produits par aucun code (confirmé Phase A). Le schéma (`entry_type`, `reverses_entry_id`) permet une compensation future sans refonte, mais aucun remboursement n'est inventé ici.

## Invariants sociaux absolus

Aucun score, rang, privilège, sanction sociale, dette, crédit utilisateur, wallet implicite ni monnaie interne. Le ledger décrit des faits financiers ; il ne gouverne jamais les personnes. Vérifié par lecture directe des colonnes réelles de `dg_ledger_entries` dans `tests/Feature/LedgerTest.php` (aucune colonne `balance`/`wallet`/`credit`/`score`).

## V1 implémentée

- `LedgerEntry` (`dg_ledger_entries`) ;
- `LedgerService` : `postContributionPayment()`/`postMembershipPayment()`, idempotents, jamais de mutation de la source ;
- intégration dans `ContributionService::reconcile()` et `MembershipPaymentService::reconcile()` (posting synchrone, aucune nouvelle transaction externe) ;
- commande `ledger:backfill` (déterministe, idempotente, rejouable) ;
- `LedgerController` (membre) et `Administration\LedgerController` (audit global) — lecture uniquement.

## Preuve

`tests/Feature/LedgerTest.php` — 30 cas : posting CAP-061 (COMPLETED/PENDING/PROCESSING/FAILED/CANCELLED, retour navigateur seul jamais suffisant), posting CAP-007B (mêmes statuts), idempotence (reconcile répété, posting direct répété, backfill répété, backfill après reconcile), backfill (historique CAP-061+CAP-007B récupéré, non-COMPLETED ignorés, source jamais modifiée), snapshot exact (montant/devise/période/finalité/payeur/sujet pour individuel et collectif, période nulle pour l'adhésion), immutabilité (retrait d'une finalité n'altère jamais une écriture déjà postée, `LedgerService` n'expose aucune méthode de mutation), absence de mutation de `Contribution`/`ZumraGroup` par le posting, absence de colonne wallet/balance/credit, autorisations (personne/étranger/leader ZUMRA/non-leader/administrateur/non-administrateur) via de vraies requêtes HTTP authentifiées.
