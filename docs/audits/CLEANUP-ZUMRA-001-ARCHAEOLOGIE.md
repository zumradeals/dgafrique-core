# CLEANUP-ZUMRA-001 — archéologie Cerveau, Core et finances

## Méthode et limite runtime

La recherche couvre modèles, migrations, services, contrôleurs, routes web, commandes, providers,
middleware, seeders, tests, configuration, vues et documentation. 305 fichiers contiennent au
moins un des termes de recherche. Les éléments ci-dessous ont ensuite été vérifiés par leurs
appels, routes, migrations et tests, plutôt que classés sur la seule présence d'un mot.

L'audit établit ce que le dépôt branche. Il ne peut pas attester que les secrets GeniusPay,
GAMAD Core ou DeepSeek sont présents sur un déploiement donné. « État runtime » signifie donc
« chemin applicatif effectivement câblé » ; les intégrations externes restent conditionnées par
leur configuration d'environnement.

## Matrice demandée

| Capacité | Code trouvé | État runtime | Utilisée par | Décision |
| --- | --- | --- | --- | --- |
| Paiement adhésion | `ZumraPayment`, `MembershipPaymentService`, `ZumraMembershipPaymentController`, tables `dg_zumra_payments` | **EXISTE ET UTILISÉE**, mais démarrage désactivé par défaut (`ZUMRA_PAYMENT_ENABLED=false`) et finalisation sandbox protégée | ZUMRA / CAP-007B | Conserver |
| 500 XOF d'adhésion | `config/payments.php`, contrôles stricts dans `GeniusPayClient` et `MembershipPaymentService` | **EXISTE ET UTILISÉE** ; montant et devise vérifiés côté configuration, création et réconciliation | ZUMRA | Conserver |
| Wallet | Aucun modèle, table, service ou route wallet ; le terme n'apparaît que comme futur outil documentaire ou interdit de schéma | **NON IMPLÉMENTÉE** | Aucun runtime | Ne pas reconstruire dans ce nettoyage ; ne pas appeler le ledger « wallet » |
| Ledger | `LedgerEntry`, `LedgerService`, `LedgerBackfillCommand`, routes `/finances/ledger*` et `/administration/ledger` | **EXISTE ET UTILISÉE** ; journal additif en lecture, projection de paiements confirmés, sans solde ni compte | Adhésion et contributions / CAP-062 | Conserver |
| Solde / balance | Aucune colonne de solde ; test explicite d'absence dans `LedgerTest` | **NON IMPLÉMENTÉE** | Aucun runtime | Conserver cette absence tant qu'un vrai wallet n'est pas conçu |
| Compte financier | Aucun compte comptable ou compte de paiement local ; « compte » actif désigne surtout le compte d'identité Core | **NON IMPLÉMENTÉE** côté finance | Identité Core uniquement | Ne pas confondre avec wallet/account financier |
| Contribution individuelle | `Contribution`, `ContributionPayment`, `ContributionService::startIndividual/payPeriod`, routes `/contributions*` | **EXISTE ET UTILISÉE** ; engagement actif possible, paiement mensuel explicitement initié ; paiements désactivés par défaut | Personne membre ZUMRA / CAP-061 | Conserver |
| Contribution ZUMRA | `ContributionService::proposeCollective/approveCollective`, routes sous `/zumra/groupes/{group}/contribution*` | **EXISTE ET UTILISÉE** ; double autorité explicite, aucune suspension automatique | Espace ZUMRA / CAP-061 | Conserver |
| Reçus | `ZumraPaymentReceipt`, `ContributionReceipt`, services d'émission et routes de lecture autorisées | **EXISTE ET UTILISÉE** ; reçus immuables avec empreinte d'intégrité | Adhésion et contributions | Conserver |
| GeniusPay | `GeniusPayClient`, `config/payments.php`, appels de création et réconciliation serveur-à-serveur | **EXISTE ET UTILISÉE** lorsque configurée ; disponibilité réelle du prestataire **INCERTAINE** depuis cet environnement | Adhésion et CAP-061 | Conserver ; vérifier secrets et environnement au déploiement |
| Financement Projet | `ProjectFunding`, `ProjectFundingService`, routes CAP-063 | **EXISTE ET UTILISÉE**, mais ne déplace aucun argent et n'appelle ni GeniusPay ni ledger | Projet / CAP-063 | Conserver comme déclaration de financement, jamais comme paiement |
| GAMAD Core | `GamadCoreClient`, `RequireCoreMember`, sessions, inscription de compte, preuve d'identité et création/résolution d'organisation | **EXISTE ET UTILISÉE** si `GAMAD_CORE_*` est configuré ; toutes les surfaces membres repassent par `currentSession()` | Identité, session et identité organisationnelle | Conserver |
| Cerveau Projet | interface `ProjectBrainAiProvider`, provider DeepSeek, contrôleurs/services/modèles/routes `/projets/*/cerveau` | **EXISTE ET UTILISÉE** avec repli déterministe si l'IA est indisponible | Domaine Projet DG Afrique | Conserver ; ce n'est pas le GAMAD Core |
| CAP-061 | migrations, configuration, 6 modèles, service, contrôleurs/routes, administration et 45 tests ciblés | **EXISTE ET UTILISÉE** ; l'ouverture des paiements individuels/collectifs est désactivée par défaut | DG Afrique + domaine ZUMRA + GeniusPay | Conserver intégralement |
| CAP-062 | migration, modèle, service, commande de backfill, lectures membre/admin et 30 tests ciblés | **EXISTE ET UTILISÉE** | Paiements CAP-007B et CAP-061 | Conserver intégralement |
| Attestation ZUMRA | carte et vérification de carte ZUMRA | **EXISTE ET UTILISÉE** ; explicitement ni paiement ni preuve de contribution | Adhésion ZUMRA | Conserver |
| Décisions / audit | événements de domaine, décisions de modération et de matching, empreintes de snapshots, ledger et reçus | **EXISTE ET UTILISÉE** dans plusieurs domaines | DG Afrique | Hors suppression ; plusieurs mécanismes spécialisés, pas un moteur unique |
| Contrat financier générique | Aucun agrégat ou interface contractuelle générique trouvé | **NON IMPLÉMENTÉE** | Aucun runtime | Ne pas inventer dans ce chantier |

## Ce que le dépôt possède réellement

### Paiement et traçabilité

Le paiement d'adhésion est un flux complet : création distante GeniusPay, tentative locale,
réconciliation serveur-à-serveur, activation conditionnelle de l'adhésion, reçu et projection
ledger. Le retour navigateur n'est jamais une preuve. Le montant canonique est 500 XOF.

CAP-061 est également implémentée : engagement volontaire individuel ou collectif, cycle
pause/reprise/arrêt, tentative mensuelle explicite, finalité administrable, réconciliation,
reçu et ledger. Les valeurs par défaut sont 500 XOF individuel et 2 500 XOF collectif, mais les
deux interrupteurs de paiement sont fermés par défaut. L'engagement et le paiement sont deux
objets distincts.

Le ledger est réel, mais volontairement limité : une projection immuable des paiements confirmés.
Il n'expose aucune écriture manuelle, aucun débit/crédit arbitraire, aucun solde calculé, aucune
réserve disponible et aucun compte. Il ne constitue donc pas un wallet.

### Où se trouve le « cerveau »

- **GAMAD Core** est l'autorité externe d'identité et de session. Le client actif crée/vérifie les
  comptes, authentifie, résout l'identité, prouve l'identité et provisionne/résout certaines
  identités organisationnelles. `core.member` revalide la session distante sur les surfaces
  protégées.
- **DG Afrique** porte les modèles et services métier locaux : ZUMRA, projets, besoins,
  contributions, paiements, reçus, ledger, décisions et événements d'audit.
- **Domaine ZUMRA** porte adhésion, naissance, gouvernance, responsabilités et contributions
  collectives. GeniusPay est une infrastructure de paiement partagée, pas l'autorité ZUMRA.
- **Cerveau Projet** est une orchestration DG Afrique branchée sur DeepSeek via
  `ProjectBrainAiProvider`. L'IA prépare des propositions ; les mutations passent par les services
  métier et exigent une confirmation humaine. Ce Cerveau n'est ni hébergé ni exécuté par GAMAD
  Core dans le code actuel.

### Contrats présents mais limites observées

- `ProjectBrainAiProvider` est un vrai contrat branché sur DeepSeek.
- Les documents PVB décrivent des outils futurs vers Core, dont
  `wallet.get_authorized_view`; ils sont **LEGACY/FUTURS DOCUMENTAIRES**, sans implémentation
  runtime correspondante.
- Plusieurs messages du Cerveau emploient « créé dans le Core » alors que les modèles Projet et
  Besoin sont persistés dans la base DG Afrique par les services locaux. Cette ambiguïté est
  **INCERTAINE** et doit être arbitrée doctrinalement ; elle n'est pas corrigée dans cette PR de
  nettoyage.
- La disponibilité réelle de GeniusPay, GAMAD Core et DeepSeek en production reste **INCERTAINE**
  sans inspection des secrets et appels réseau du VPS.

## Éléments orphelins ou legacy

Aucun modèle, migration, service, contrôleur ou route financier/Core n'a été démontré orphelin.
Les occurrences « wallet » relèvent de documentation prospective et de garde-fous qui attestent
son absence. Les documents historiques CAP/PVB sont conservés comme preuves d'architecture et
ne constituent pas, à eux seuls, un runtime.

**Décision d'audit : aucune suppression de code financier, paiement, contribution, ledger,
GeniusPay, Core ou Cerveau dans CLEANUP-ZUMRA-001.**
