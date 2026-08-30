# CORE-COMPLETION-001 — Cadrage ZAHAB (audit seulement, aucune implémentation)

## 0. STOP doctrinal — à arbitrer avant toute suite

Une définition ZAHAB **existe déjà** dans le canon, dans deux documents distincts :

- `docs/architecture/ARCHITECTURE-PRODUIT-V2.md` §8 (« Wallet transversal, GAMAD Finance et ZAHAB »), listé dans les **« Décisions figées »** (§13) ;
- `docs/architecture/PVB-001-CONTRAT-FONCTIONNEL-CERVEAU-PROJET-V2.md` §22, qui liste « statut monétaire de ZAHAB » parmi les décisions **encore à traiter**.

Points de tension entre la définition donnée dans le mandat CORE-COMPLETION-001 et le canon existant :

| Point | Mandat CORE-COMPLETION-001 | Canon existant (`ARCHITECTURE-PRODUIT-V2.md`) |
|---|---|---|
| Parité | « 1 ZAHAB = 1 FCFA » — affirmé comme un fait définitionnel | « pourrait servir d'unité comptable interne à parité lisible avec le FCFA **uniquement si** [...] » — conditionnel, non tranché |
| Statut | Implicitement une capacité produit prête à cadrer | §13 : « statut exact de ZAHAB et cadre juridique/réglementaire » explicitement listé **« à formaliser avant implémentation »** |
| Rattachement architectural | Non mentionné — le mandat demande de « déterminer où le moteur ZAHAB doit vivre » comme une question ouverte | Déjà nommé : ZAHAB est « couche économique future/expérimentale **de GAMAD Finance** », une autorité distincte de GAMAD Core, **absente de tout code** (aucune classe, route ou client `GamadFinance*` trouvée dans `app/`) |
| Wallet transversal | Sections 10/16 du mandat demandent de recommander quels sujets ont besoin d'un Wallet | Déjà largement répondu par le canon §8.1 : personne, ZUMRA, Projet, fonds communautaire, acteur marchand — avec l'invariant déjà posé « le solde est une conséquence des écritures du ledger, jamais une valeur décorative ou arbitraire » |

**Aucune ligne de ce document n'écrase ou ne remplace la définition canonique existante.** Le contenu ci-dessous traite la définition du mandat comme une **base de travail pour cet audit uniquement**, explicitement non versée au canon (conformément à la §18 du mandat : « ajouter la définition de ZAHAB au canon seulement si [...] l'ajout ne contredit aucun canon existant » — ici une contradiction existe, donc rien n'est ajouté).

**Recommandation** : avant tout chantier d'implémentation, faire arbitrer par le porteur du canon (l'auteur d'`ARCHITECTURE-PRODUIT-V2.md`) lequel des deux textes prévaut — notamment sur la parité 1:1 affirmée comme un fait, et sur le rattachement à « GAMAD Finance » comme autorité séparée du dépôt `dgafrique-core`.

---

## A. État actuel — chaîne financière réelle

```
FCFA externe (carte, mobile money…)
        │
        ▼
   GeniusPay (prestataire externe réel)
        │  webhook / retour / reconciliation
        ▼
ZumraPayment (adhésion, 500 XOF)         ContributionPayment (individuelle/collective)
   status: PENDING→PROCESSING→COMPLETED     status: PENDING→PROCESSING→COMPLETED
        │                                        │
        ▼                                        ▼
ZumraPaymentReceipt                        ContributionReceipt
   (reçu, hash d'intégrité)                   (reçu, hash d'intégrité)
        │                                        │
        └────────────────┬───────────────────────┘
                          ▼
                  LedgerService::post*()
                          │
                          ▼
                dg_ledger_entries (append-only)
                  aucune colonne de solde
                          │
                          ▼
              LedgerController (API JSON)
        aucune vue Blade — pas d'accès UI aujourd'hui
```

Aucun Wallet, aucun solde stocké ou calculé, nulle part. Chaque paiement reste sa propre source de vérité (`ContributionPayment`/`ZumraPayment`) ; le Ledger n'en est qu'une **projection en lecture**, jamais réécrite.

Les deux portes d'entrée (adhésion, contribution) sont fermées par défaut (`ZUMRA_PAYMENT_ENABLED=false`, `ContributionConfiguration::individual_enabled=false`/`collective_enabled=false`), et la contribution n'a de surcroît **aucune porte d'entrée dans l'UX** (§5 de l'audit principal).

---

## B. État cible proposé — avec ZAHAB (schéma, non décidé)

```
FCFA externe
        │
        ▼
   GeniusPay (inchangé, seul mouvement d'argent réel)
        │
        ▼
   Acquisition ZAHAB (nouvel événement économique :
   « un paiement FCFA confirmé crédite un Wallet ZAHAB »)
        │
        ▼
   Wallet ZAHAB (Personne | ZUMRA | Organisation)
   solde = SUM(mouvements Ledger le concernant)
   jamais une valeur stockée indépendamment
        │
        ├──► utilisation : adhésion, contribution, achat outil spécialisé…
        │        │
        │        ▼
        │    Mouvement ZAHAB (débit sujet A, raison métier obligatoire)
        │        │
        │        ▼
        └──► LedgerService::post() — MÊME service, nouvelle source_type
                          │
                          ▼
                dg_ledger_entries (schéma étendu, toujours append-only)
                          │
                          ▼
                  Preuve / reçu (réutilise ContributionReceipt/ZumraPaymentReceipt
                  ou un ReceiptService générique factorisé)
```

**Principe de conception à respecter (pas encore implémenté)** : le Wallet ZAHAB est une **vue/expérience** sur des écritures Ledger, jamais une deuxième vérité. Concrètement, cela veut dire que la table Wallet (si elle existe) ne doit **jamais** porter elle-même une colonne `balance` mise à jour par une écriture directe — soit elle n'a pas de colonne de solde du tout et le solde se calcule à la demande par agrégation Ledger, soit elle porte un solde **caché en cache**, recalculable à tout moment à partir du Ledger, jamais la seule source. Cette contrainte fait explicitement écho à l'invariant déjà posé dans le canon (§8.1 d'`ARCHITECTURE-PRODUIT-V2.md`) : « le solde est une conséquence des écritures du ledger, jamais une valeur décorative ou arbitraire. »

---

## C. Modèle recommandé (sans code, sans migration)

- **Wallet** : un sujet (`subject_type`/`subject_reference`, même vocabulaire que `Contribution`/`LedgerEntry` aujourd'hui) détient un Wallet. Statut (actif/suspendu), devise/unité explicite (ZAHAB, jamais implicite), timestamps.
- **Propriétaire/sujet** : Personne, ZUMRA — voir §D pour Organisation et Projet.
- **Mouvement** : jamais « transfert » nu — un mouvement porte toujours une **raison métier** parmi un ensemble fermé et énuméré (acquisition, paiement d'adhésion, contribution, aide reçue, financement reçu, remboursement — voir §11 du mandat). Montant entier, devise explicite, sujet source, sujet destination (le cas échéant), référence à l'événement métier d'origine.
- **Ledger** : réutilisé tel quel dans son principe (append-only, jamais réécrit, idempotent par `UNIQUE(source_type, source_id)`) — chaque mouvement Wallet devient une écriture Ledger, exactement comme un `ContributionPayment` confirmé en devient une aujourd'hui.
- **Paiement** : la frontière externe (FCFA→ZAHAB) reste un paiement au sens actuel (`ContributionPayment`/`ZumraPayment`) — ZAHAB ne réinvente pas cette brique, il la consomme en aval.
- **Reçu** : chaque mouvement significatif (surtout externe↔interne) produit une preuve, sur le modèle déjà existant (`integrity_hash`).
- **Affectation** : un mouvement peut porter une affectation optionnelle (ex. Wallet ZUMRA → dépense affectée à un Projet particulier) sans que le Projet ait besoin de son propre Wallet — voir §D.
- **Idempotence** : même discipline que `LedgerService::post()` aujourd'hui (contrainte unique + relecture au lieu d'une double écriture).
- **Autorisations** : par sujet, sur le même modèle que `LedgerController` aujourd'hui (une personne voit son propre Wallet, un responsable ZUMRA voit celui de sa ZUMRA — jamais un accès croisé implicite).

---

## D. Réutilisation de CAP-061/CAP-062

Réutilisable tel quel, sans reconstruction :

- `LedgerEntry`/`LedgerService` — le moteur d'écriture immuable est déjà exactement ce dont un Wallet ZAHAB a besoin en aval. Il faudrait probablement élargir `source_type` pour couvrir de nouveaux types de mouvements ZAHAB (acquisition, dépense interne), sans toucher au principe d'immutabilité déjà en place.
- `Contribution`/`ContributionPayment`/`ContributionService` — le workflow individuel/collectif (propose→approuve, gating par configuration) est directement transposable comme mode de **dépense** d'un Wallet ZAHAB, pas seulement comme paiement FCFA direct.
- `GeniusPayClient` — reste la seule porte FCFA↔ZAHAB, aucune raison de la dupliquer.
- Les reçus (`ContributionReceipt`/`ZumraPaymentReceipt`) — le patron `integrity_hash` est directement réutilisable pour les preuves de mouvement ZAHAB.

**Ce que ZAHAB ne doit pas réutiliser tel quel** : les migrations et modèles actuels n'ont **aucune** notion de solde ou de sujet-détenteur persistant — c'est la vraie pièce manquante, pas une extension des tables existantes.

---

## E. Manques réels (ce qui reste à développer, non fait ici)

1. Le modèle Wallet lui-même (table, sujet, statut) — inexistant.
2. Le calcul/la mise en cache d'un solde dérivé du Ledger — inexistant (le Ledger actuel n'a même pas de méthode d'agrégation).
3. Un service de mouvement ZAHAB avec catalogue fermé de raisons métier — inexistant.
4. Une UI Wallet (voir/aucune UI n'existe même pour les contributions CAP-061 actuelles — §5 de l'audit principal) — à concevoir dès le départ avec une vraie surface utilisateur, pas seulement une API JSON comme le Ledger aujourd'hui.
5. Le statut juridique/réglementaire de ZAHAB — explicitement listé « à formaliser » par le canon existant, non résolu par cet audit ni par le mandat.
6. L'arbitrage GAMAD Finance vs. domaine local `dgafrique-core` (§0 ci-dessus).

---

## F. Risques

- **Double vérité financière** si un solde Wallet est un jour stocké et mis à jour indépendamment du Ledger (déjà mis en garde par le canon existant, §8.1).
- **Double dépense / concurrence** : le dépôt a déjà un patron pour ce risque (index unique partiel Postgres sur `dg_contribution_payments`, cf. migration CAP-061) — à reproduire pour tout débit ZAHAB.
- **Fraude / falsification de solde** si le calcul du solde n'est pas strictement dérivé du Ledger.
- **Paiements externes** : la frontière GeniusPay reste le seul point de contact avec de l'argent réel — tout bug côté acquisition ZAHAB doit être conçu pour ne jamais pouvoir créer de la valeur sans paiement confirmé correspondant (invariant « aucune création arbitraire de valeur », déjà dans la liste du mandat §12).
- **Implications réglementaires futures** : le canon est déjà explicite — aucune promesse de convertibilité, de stablecoin ou d'adossement ne doit être faite tant que le cadre n'est pas établi. Le mandat CORE-COMPLETION-001 dit la même chose avec des mots différents ; les deux s'accordent sur ce point précis, seule la parité 1:1 pose la tension notée en §0.
- **Confusion GAMAD Finance / dgafrique-core** : si ZAHAB est un jour développé directement dans ce dépôt sans clarifier son rattachement à une éventuelle autorité « GAMAD Finance » externe (comme GAMAD Core l'est aujourd'hui pour l'identité), le travail pourrait devoir être redéplacé plus tard — coût architectural évitable en tranchant cette question d'abord.

---

## Invariants financiers — liste du mandat, complétée/corrigée selon le code réel présent

La liste du mandat (§12) est globalement solide et cohérente avec ce que le code existant pratique déjà pour CAP-061/062. Ajouts suggérés à partir de ce qui a été observé dans le dépôt :

- **Séparation stricte entre l'engagement et la tentative de paiement**, déjà pratiquée par `Contribution` (engagement, à vie) vs `ContributionPayment` (tentative, par période) — à reproduire pour tout mouvement ZAHAB récurrent.
- **Jamais de statut `COMPLETED` déduit d'une absence de réponse du prestataire** — déjà un invariant strict de `GeniusPayClient::normalize()`, à conserver texto pour toute nouvelle intégration de paiement.
- **Reconciliation serveur-à-serveur, jamais la confiance au seul retour navigateur** — déjà le patron `ZumraPayment::returned()`/`payment()` ; ZAHAB devra suivre la même discipline si des mouvements FCFA↔ZAHAB transitent par un prestataire externe.
- **Un affichage de solde ne doit jamais être calculé côté client** — corollaire direct de « le solde est une conséquence des écritures du ledger », à formuler explicitement comme invariant technique (pas seulement produit).
