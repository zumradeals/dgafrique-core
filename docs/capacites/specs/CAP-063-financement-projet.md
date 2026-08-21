# CAP-063 — Financement de projet

## Statut

**Audit (Phase A) et implémentation V1 (Phase B) — 2026-09-15.** Le domaine était documenté `DEPENDENCY_BLOCKED` sans aucune dépendance technique réelle (constat déjà posé par ROADMAP-001/002, confirmé et précisé par l'audit CAP-063 Phase A).

Ancrage doctrinal : `ZUMRA-DOCTRINE-INVARIANTE.md` art. 15.3 — un Projet non validé peut recevoir « des manifestations d'intérêt, des promesses non encaissées de soutien », mais « ne peut recevoir aucun paiement réel avant identification du porteur, adoption, **budget**, **gouvernance financière**, régime de propriété et **règles de décaissement** ». Trois de ces six préconditions (budget, gouvernance financière, règles de décaissement) sont absentes du runtime DG Afrique et ne peuvent être satisfaites sans fabriquer une capacité de décaissement que `GeniusPayClient` ne fournit pas (confirmé en Phase A).

## Décision produit — V1 strictement déclarative

**CAP-063 V1 permet à un Projet de formaliser un besoin financier, un objectif chiffré et son cadre d'utilisation prévu. Elle ne représente jamais un mouvement d'argent réel.**

Explicitement **hors périmètre** de cette V1 : paiement projet-spécifique, collecte projet-spécifique, décaissement, transfert, payout, wallet, escrow, investissement, prêt, dette, equity, rendement, ROI, dividende. Aucun de ces concepts n'existe dans le code livré.

Tout financement réel continue de passer exclusivement par la finalité `VALIDATED_PROJECTS` déjà livrée par CAP-061 — fonds non affectés à un Projet précis, décision d'usage humaine hors plateforme (art. 15.2 : « DG Afrique ou GAMAD intervient »).

## Architecture — `ProjectFunding`, pas une extension de `Need`

**Décision : nouvel objet déclaratif séparé, `Need` non étendu.**

Justification par preuve :

- `Need` (`dg_needs`) ne porte aucun champ numérique ni devise — sa sémantique est qualitative (`title`, `context`, `category`, `capability_label`, `collaboration_mode`). Ajouter `target_amount`/`currency`/`intended_use` y aurait été sans sens pour l'immense majorité des `Need` (recherche de capacité, d'entraide non financière).
- `CAP-013-besoin.md:39` énonce explicitement : « CAP-013 ne crée pas de projet ou de financement » — étendre `Need` au financement aurait directement contredit la frontière déjà posée par sa propre spec.
- Le précédent CAP-061 (Architecture A : tables neuves plutôt que polluer un domaine existant) est directement transposable ici.

`ProjectFunding` reste néanmoins un objet **minimal** : une seule table, aucune sur-architecture, réutilisation stricte de l'autorité et du journal d'événements déjà établis pour `Project`.

## Modèle : `ProjectFunding`

Table `dg_project_fundings` :

| Champ | Rôle |
|---|---|
| `project_id` | FK `dg_projects`, `cascadeOnDelete()` (motif `ProjectTeamMember`) |
| `status` | `OPEN` / `CLOSED` / `CANCELLED` |
| `target_amount` (`unsignedInteger`) | Montant cible — jamais un float, validé `> 0` à la frontière HTTP |
| `currency` (`char(3)`) | Devise déclarée — jamais agrégée, jamais collectée |
| `purpose` (text) | Justification du besoin |
| `intended_use` (text) | Usage prévu des fonds si le besoin était comblé |
| `conditions` (text, nullable) | Conditions déclarées optionnelles |
| `author_core_reference` | Qui a déclaré |
| `decided_by_core_reference` (nullable) | Qui a clôturé/annulé |
| `closing_note` (nullable) | Motif de clôture/annulation |
| `opened_at` / `closed_at` / `cancelled_at` | Horodatage par transition (motif `Contribution`) |

**Volontairement absents** (art. 4 de la mission — invariant central) : `collected_amount`, `paid_amount`, `balance`, `available_balance`, `wallet_balance`. Aucun de ces champs ne prétendrait à un fait financier réel qui n'existe pas.

Contrainte : `UNIQUE(project_id) WHERE status = 'OPEN'` — un Projet ne porte qu'une seule déclaration active à la fois ; une nouvelle déclaration reste possible après clôture ou annulation d'une précédente. Index unique partiel Postgres (motif déjà établi par CAP-061/062), doublé d'une vérification applicative sous `lockForUpdate()` et d'un filet de sécurité sur violation de contrainte (jamais une 500 sur une course bénigne).

## Cycle de vie

**`OPEN → CLOSED`, `OPEN → CANCELLED`.** Machine volontairement réduite par rapport aux exemples de la Phase A (`DRAFT`/`PROPOSED`/`APPROVED`/`FUNDED`/`PAID`/`COLLECTED`/`DISBURSED` — tous rejetés) : la création n'est autorisée qu'à l'autorité de décision du Projet (`ProjectAuthority::canDecide`), qui possède déjà l'autorité complète — aucun palier `DRAFT`/`PROPOSED` supplémentaire n'aurait de justification, contrairement à `Need::OWNER_PROJECT` où un simple membre d'équipe peut proposer sans décider. `CLOSED` et `CANCELLED` sont tous deux terminaux et distincts (transparence : un besoin comblé autrement n'est pas la même histoire qu'un besoin retiré par erreur — art. 17 : « difficultés et changements »).

## Éligibilité du Projet

Seuls les Projets `ADOPTED` ou `IN_PROGRESS` peuvent porter une déclaration (création et mise à jour). Justifié directement par l'art. 15.3 : la précondition « adoption » exclut `PROPOSED` ; `ARCHIVED` et `COMPLETED` sont exclus par cohérence avec le cycle `ProjectService` existant (un projet clos ou archivé n'exprime plus de besoin actif). Clôturer/annuler une déclaration existante reste toujours possible quel que soit l'état du Projet — mettre fin à une déclaration ne doit jamais être bloqué.

## Autorisations

**Aucune matrice parallèle.** `ProjectFundingService` reçoit `ProjectService` en dépendance et délègue intégralement `canDecide()`/`canView()` — même autorité déjà réutilisée par CAP-041 (équipe) et CAP-042 (besoins de projet). Pour un Projet `GROUP`, `canDecide()` délègue à `ZumraGroupService::isLeader()` (les 5 rôles fondateurs indifféremment) — **la double approbation `PRIMARY_LEAD`+`FINANCE_LEAD` de CAP-061 n'est pas reprise** : CAP-063 V1 ne déplaçant jamais d'argent, rien ne justifie une gouvernance financière à deux acteurs pour une simple déclaration.

## Visibilité

Aucune règle propre : `canView()` délègue à `ProjectAuthority::canView()`. Une déclaration financière ne rend jamais un Projet `PRIVATE` plus visible qu'il ne l'est déjà — elle hérite strictement de la visibilité du Projet.

## Événements

Réutilisation stricte de `ProjectEvent` (même table que CAP-016/041/042, aucune nouvelle table d'audit) : `FUNDING_DECLARATION_CREATED`, `FUNDING_DECLARATION_UPDATED`, `FUNDING_DECLARATION_CLOSED`, `FUNDING_DECLARATION_CANCELLED`. Aucun événement `PAYMENT_*`/`FUNDED`/`DISBURSED` — rien de tel ne se produit.

## Frontière CAP-061

`Contribution`/`ContributionPayment`/`ContributionPurpose`/`ContributionService` ne sont **pas modifiés**. `VALIDATED_PROJECTS` reste une finalité analytique non affectée à un Projet précis, sans FK vers `Project`. `ProjectFunding` n'est jamais réutilisé comme `Contribution` (frontière métier incompatible : engagement récurrent réversible vs déclaration ponctuelle d'un besoin — voir l'audit Phase A §21).

## Frontière CAP-062

Aucune `LedgerEntry` n'est créée par CAP-063 : une déclaration de besoin n'est pas un fait financier confirmé. `LedgerService`/`ledger:backfill` ne sont pas modifiés, aucun `source_type` n'est ajouté.

## Frontière GeniusPay

`GeniusPayClient` n'est **pas modifié** et n'est **jamais appelé** par `ProjectFundingService` — aucune URL de paiement, aucune réconciliation, aucun reçu. Vérifié par test (`Http::assertNothingSent()`).

## Invariants de propriété

Créer, modifier, clôturer ou annuler une déclaration ne modifie jamais `Project.owner_type`/`owner_reference`, `ProjectTeamMember`, `Organization`, `Partnership`, `CapabilityStatement`, ni un rôle `ZumraGroupRole`. Vérifié par test.

## Dette et score

Aucune dette, aucune obligation de paiement, aucun score, aucune réputation, aucune pénalité, aucun classement, aucune promesse automatique. La table ne porte aucune colonne `balance`/`wallet`/`debt`/`score` (vérifié par réflexion sur les colonnes réelles en test).

## HTTP

Surface minimale, routes canoniques `/projets/{project}/financement` (motif `routes/cap041.php`) :

- `GET /projets/{project}/financement` (consultation, `canView`) ;
- `POST /projets/{project}/financement` (déclaration, `canDecide`) ;
- `PATCH /projets/{project}/financement` (mise à jour, `canDecide`, seulement si `OPEN`) ;
- `POST /projets/{project}/financement/cloturer` (`canDecide`) ;
- `POST /projets/{project}/financement/annuler` (`canDecide`).

Aucune surface d'administration : aucune règle doctrinale ne justifie un workflow d'approbation admin pour une simple déclaration, et `PortalAdministrator` ne devient pas financeur automatique.

## V1 implémentée

- `ProjectFunding` (`dg_project_fundings`) ;
- `ProjectFundingService` : `create/update/close/cancel/canView`, réutilisant strictement `ProjectService` — aucune autorité ni journal d'audit dupliqué ;
- `ProjectFundingController` — surface minimale, aucune route de paiement.

## Preuve

`tests/Feature/ProjectFundingTest.php` — 31 cas : création autorisée (porteur personne, `isLeader()` ZUMRA) et refusée (étranger, membre non habilité), éligibilité du Projet (`PROPOSED`/`ARCHIVED`/`COMPLETED` refusés, `ADOPTED`/`IN_PROGRESS` acceptés), validation HTTP réelle (`target_amount` positif entier, `currency` 3 caractères), mise à jour (autorisée, étrangère refusée, sur déclaration clôturée refusée), clôture/annulation (autorisées, déjà terminées refusées, étrangères refusées), unicité (une seule déclaration `OPEN` par Projet, nouvelle déclaration possible après clôture, index unique partiel testé directement), visibilité (`PRIVATE`/`GROUP` héritées du Projet), frontières CAP-061/CAP-062/GeniusPay (aucune `Contribution`/`ContributionPayment`/`LedgerEntry` créée, aucun appel HTTP sortant), invariants de propriété (aucune mutation `Project`/équipe/`Partnership`/`Organization`/rôle ZUMRA), absence de colonne dette/score/solde.
