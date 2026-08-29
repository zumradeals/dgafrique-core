# ENGINE-TRUTH-001 — certification exhaustive du moteur

## Verdict exécutif

**Décision au 27 août 2026 : `HOLD — MOTEUR NON CERTIFIÉ PRODUCTION`.**

Le moteur DG Afrique n'est ni vide, ni improvisé. Son socle métier est vaste, cohérent sur de
nombreux invariants et nettement plus solide que la présentation actuelle. L'identité canonique,
les permissions, les machines d'état, les transactions, les verrous et la traçabilité financière
montrent un travail réel.

Il ne peut toutefois pas encore être déclaré « complet et stable » au niveau de preuve exigé pour
une production complète. Le blocage ne vient pas d'un manque général de backend : il vient de
quelques frontières critiques non certifiées — PostgreSQL/Redis, paiement, fédération, IA et
exploitation — ainsi que d'une contradiction restante sur les données de démonstration.

**La suppression du frontend n'est donc pas autorisée par ce lot.** ENGINE-TRUTH-001 est un audit
et un gel de vérité. Il ne modifie aucun service, modèle, contrôleur, route ou schéma métier.

## 1. Périmètre et méthode

### Révision auditée

- dépôt : `zumradeals/dgafrique-core` ;
- branche : `main` locale ;
- commit de départ : `e1de69f3adfc024cb183368d43ceb4805bae1533` ;
- historique local au départ : 247 commits accessibles ;
- `main` locale était en avance de 3 commits sur `origin/main` ;
- aucun push distant n'a pu être prouvé dans cet environnement.

### Inclus

- modèles, migrations, services Application et Infrastructure ;
- contrôleurs, middlewares, routes et permissions ;
- contrats GAMAD Core, GeniusPay, DeepSeek et satellites ;
- transactions, verrous, idempotence, ledger et Wallet ZAHAB ;
- tests présents, configuration des environnements et dépendances ;
- scheduler, queues, logs, santé, déploiement, sauvegarde et restauration ;
- référentiel des 84 capacités canoniques ;
- frontière entre moteur et présentation avant reconstruction du frontend.

### Exclus du verdict moteur

- qualité visuelle de l'interface actuelle ;
- parité pixel, responsive et accessibilité du futur frontend ;
- disponibilité réelle des prestataires, faute d'environnement de préproduction connecté ;
- conformité juridique formelle, qui exige une revue compétente distincte.

### Échelle

| État | Signification |
|---|---|
| `PASS` | Preuve statique ou exécutable suffisante dans le périmètre observé |
| `PARTIAL` | Construction sérieuse, mais preuve ou frontière incomplète |
| `FAIL` | Condition nécessaire à la certification absente ou contredite |
| `UNVERIFIED` | Impossible à exécuter dans l'environnement d'audit |

## 2. Empreinte réelle du moteur

| Élément | Mesure auditée |
|---|---:|
| Fichiers PHP sous `app/` | 286 |
| Modèles Eloquent | 81 |
| Migrations | 59 |
| Fichiers de routes | 22 |
| Routes nommées | 345 |
| Noms de route dupliqués | 0 |
| Limiteurs nommés utilisés / définis | 93 / 93 |
| Tests Feature / Unit | 99 / 3 |
| Méthodes de test détectées | 1 125 |
| Appels d'assertion détectés | 3 511 |
| Services utilisant `DB::transaction` | 135 appels |
| Verrous `lockForUpdate` | 126 appels |
| Seeders de démonstration | 13, plus leur classe de base |
| Jobs applicatifs / implémentations `ShouldQueue` | 0 / 0 |
| Workflows CI versionnés | 0 |

Les 81 tables explicitement déclarées par les modèles trouvent toutes une création correspondante
dans les migrations locales. Cette correspondance ne prouve pas qu'une migration complète passe
sur PostgreSQL, mais elle écarte un grand nombre de ruptures structurelles simples.

## 3. Résultats de contrôle

| Porte de certification | État | Preuve / limite |
|---|---|---|
| Compilation Vite production | `PASS` | `npm run build` terminé avec succès, 53 modules transformés |
| Audit dépendances JavaScript de production | `PASS` | `npm audit --omit=dev` : 0 vulnérabilité sur 38 dépendances de production |
| Secrets évidents dans les fichiers suivis | `PASS` statique | 0 correspondance sur les signatures privées/tokens courants ; `.env` non suivi |
| Contrat des routes | `PASS` statique | 345 noms uniques ; audit précédent : aucune cible statique manquante |
| Limitation de débit | `PASS` statique | correspondance exacte entre 93 usages et 93 définitions |
| Correspondance modèles / tables | `PASS` statique | 81 tables de modèles couvertes par les migrations |
| Tests PHP | `UNVERIFIED` | PHP, Composer et `vendor/` absents de l'environnement d'audit |
| Audit Composer | `UNVERIFIED` | `composer audit` impossible ; une recherche externe vide ne vaut pas preuve d'absence |
| Migrations PostgreSQL | `UNVERIFIED` | ni PHP ni serveur/client PostgreSQL disponibles |
| Redis sessions/cache | `UNVERIFIED` | aucun Redis exécutable disponible |
| Intégration GAMAD Core | `PARTIAL` | client et tests simulés présents ; aucun test de contrat réel en préproduction |
| Intégration GeniusPay | `FAIL` pour activation production | réconciliation dépendante du retour navigateur ; autres lacunes détaillées ci-dessous |
| Intégration DeepSeek | `PARTIAL` | confirmation humaine avant mutation ; gouvernance des données externe non formalisée |
| Exploitation et reprise | `FAIL` | pas de runbook complet, preuve de sauvegarde/restauration, CI ou observabilité applicative |

## 4. Solidité effectivement constatée

### 4.1 Identité et session — solide statiquement

- GAMAD Core reste l'autorité d'identité ; aucun mot de passe membre parallèle n'est stocké.
- `RequireCoreMember` revalide la session auprès du Core et refuse les réponses incohérentes.
- Le bearer est conservé dans une session Laravel chiffrée ; l'exemple de configuration active
  également cookie `secure`, `httpOnly` et `sameSite=lax`.
- Connexion et déconnexion régénèrent l'identifiant de session et le jeton CSRF.
- L'administration repose sur une liste explicite de références Core, pas sur un paramètre client.
- Les mutations métier inspectées passent par `core.member`; les routes administratives ajoutent
  `portal.admin`.

**Verdict : `PASS` statique, `UNVERIFIED` contre un vrai GAMAD Core.**

### 4.2 Domaine métier — substantiel

Le dépôt contient des services et machines d'état réels pour profils, capacités, besoins, projets,
équipes, jalons, ZUMRA, rôles, adhésions, missions, transmissions, preuves, organisations,
partenariats, événements, messagerie, modération, contributions, ledger et Wallet ZAHAB.

Les mutations sensibles utilisent abondamment transactions et verrous de lignes. Les permissions
sont généralement contrôlées dans les services, pas seulement dans les vues. Les parcours étudiés
ne dépendent donc pas simplement de boutons de façade.

**Verdict : `PASS` statique sur l'existence du moteur ; certification dynamique encore requise.**

### 4.3 Ledger et Wallet ZAHAB — bonnes fondations

- écritures additives et clés de source idempotentes ;
- garde de reversal unique ;
- solde dérivé du ledger et non d'un compteur arbitraire ;
- débit sous verrou, refus d'un solde négatif ;
- clés d'opération déterministes sur les flux sensibles ;
- aucune route HTTP générique de crédit, débit ou transfert découverte ;
- tableaux financiers administratifs principalement en lecture.

Les garanties de concurrence les plus importantes reposent néanmoins sur des index et contraintes
PostgreSQL qui ne sont pas exercés par la configuration de test actuelle.

**Verdict : `PARTIAL` jusqu'au test PostgreSQL concurrent.**

### 4.4 Fédération — transport défensif, registre trop permissif

Le client fédéré vérifie que l'audience de la preuve reçue correspond à la référence produit. Le
handoff applique `no-store`, CSP restrictive, `no-referrer`, `nosniff`, `DENY` et transmet la preuve
par formulaire POST.

En revanche, un administrateur du portail peut associer une référence produit valide à n'importe
quelle `callback_url` HTTPS. L'URL de réception n'est pas liée par une configuration Core signée ou
une liste de destinations autorisées par produit. Un compte administrateur compromis pourrait donc
faire remettre un jeton valide à un hôte HTTPS tiers.

**Verdict : `PARTIAL`, activation satellite à geler avant verrouillage par produit.**

### 4.5 Paiement — logique métier prudente, récupération incomplète

Points solides :

- le retour navigateur n'est jamais accepté comme confirmation financière ;
- état, montant, environnement et référence distante sont vérifiés côté serveur ;
- les transitions finales sont transactionnelles et idempotentes ;
- le sandbox ne finalise rien sans interrupteur explicite ;
- les paiements sont désactivés par défaut dans `.env.example`.

Blocages :

1. aucune route webhook et aucun poller/scheduler de réconciliation n'ont été trouvés ; la
   réconciliation GeniusPay n'a lieu que lorsque l'utilisateur revient sur le portail ;
2. si l'utilisateur ferme le navigateur ou si le retour échoue, une tentative peut rester locale
   en `PENDING`/`PROCESSING` sans mécanisme automatique de rattrapage ;
3. les retours d'adhésion, contribution et acquisition relisent la tentative locale la plus récente
   au lieu de transporter un identifiant opaque signé de tentative ; un ancien retour peut donc
   déclencher la vérification d'une tentative plus récente ;
4. `GeniusPayClient` normalise montant, statut, environnement et référence, mais ne conserve pas
   la devise ni les métadonnées/purpose/identité du prestataire. Même si le prestataire les renvoie,
   le code ne peut pas les comparer à l'intention locale ;
5. le client dispose d'un timeout total, mais pas d'un `connectTimeout` distinct.

Ce constat ne signifie pas qu'un retour navigateur peut fabriquer un paiement : la requête de
vérification reste serveur-à-serveur. Il signifie que la reprise, la corrélation exacte et la preuve
de l'intention financière ne sont pas encore assez fortes pour ouvrir l'argent réel.

**Verdict : `FAIL` pour la production financière. Maintenir tous les flux externes désactivés.**

### 4.6 IA Projet — humain dans la boucle, politique de données absente

Le Cerveau Projet n'exécute pas silencieusement une mutation proposée par le modèle : l'action est
normalisée, limitée et exige une confirmation humaine. C'est un bon invariant.

Cependant l'historique de conversation et le contexte du projet sont transmis à DeepSeek. Aucun
contrat applicatif explicite de consentement à cette transmission, minimisation/redaction,
rétention, région de traitement, retrait ou journal de base légale n'a été découvert. Les règles
générales de confidentialité du corpus ne remplacent pas cette frontière opérationnelle.

**Verdict : `PARTIAL`. Désactiver l'IA en production jusqu'à décision et contrôle de gouvernance.**

## 5. Écart critique entre SQLite et PostgreSQL

`phpunit.xml` force :

```text
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Or au moins dix migrations contiennent des branches spécifiques à PostgreSQL. Elles ajoutent
notamment :

- bornes `CHECK` sur les exécuteurs de Mission ;
- unicité partielle du financement actif d'un Projet ;
- unicités partielles de décisions de modération ;
- unicité de paiement actif par période de contribution ;
- contraintes de type/direction Wallet et Ledger ;
- unicité des reversals du ledger ;
- conversions de type absentes sous SQLite.

95 des 99 fichiers Feature emploient `RefreshDatabase`, ce qui donne une bonne isolation, mais ils
restent exécutés sur un moteur différent. La suite actuelle ne peut donc pas prouver les garanties
réelles de concurrence, de type et de contrainte attendues en production.

**Condition non négociable : une suite PostgreSQL jetable doit devenir une porte CI obligatoire.**

## 6. Vérité des 84 capacités

`docs/capacites/CAPABILITY-COVERAGE.md`, déclaré canonique, contient exactement :

| État | Nombre |
|---|---:|
| `CLOSED` | 60 |
| `PARTIAL` | 5 |
| `DOC_ONLY` | 15 |
| `DEPENDENCY_BLOCKED` | 2 |
| `NOT_IMPLEMENTED` | 2 |

Capacités non fermées :

- `PARTIAL` : CAP-023, CAP-047, CAP-051, CAP-053, CAP-056 ;
- `DEPENDENCY_BLOCKED` : CAP-070, CAP-079 ;
- `NOT_IMPLEMENTED` : CAP-077, CAP-078.

Les 15 `DOC_ONLY` ne sont pas automatiquement des défauts logiciels. De même, une capacité peut
être légitimement hors V1. Mais le mot « moteur complet » est faux tant qu'un contrat de périmètre
V1, approuvé et testable, ne classe pas explicitement chaque capacité non fermée en exclusion,
dépendance ou obligation avant lancement.

Le snapshot placé au début de `ROADMAP-METIER-CANONIQUE.md` est par ailleurs périmé : il annonce
58 capacités fermées et laisse CAP-068/CAP-080 non implémentées, tandis que ses sections ultérieures
et le registre canonique les donnent livrées, pour un total de 60. Cette contradiction est un
défaut de gouvernance documentaire, pas une preuve de défaut métier.

## 7. Contradiction PRODUCTION-TRUTH-001

La décision utilisateur demandait une suppression totale des données fictives. Le lot précédent a
correctement supprimé les fixtures injectées au runtime et a rendu `DatabaseSeeder` vide, mais il a
conservé dans le dépôt :

- 13 seeders `*DemoSeeder` ;
- une classe `DemoSeeder` qui les interdit seulement si `APP_ENV=production` ;
- des tests qui installent encore certains de ces univers fictifs.

La garantie obtenue est donc : **« pas de démonstration dans un runtime correctement déclaré
production »**, et non **« suppression totale des données fictives du dépôt »**.

Ce garde-fou est utile mais dépend de la justesse de `APP_ENV`; il ne satisfait pas la décision
littérale. Le moteur ne doit pas être certifié sur une formulation plus favorable que la réalité.

**Verdict : `FAIL` de vérité à corriger par un lot explicite, sans nettoyage aveugle d'une base.**

## 8. Exploitation et résilience

Éléments présents :

- route Laravel générique `/up` ;
- scheduler horaire pour les occurrences de Missions ;
- configuration cible PostgreSQL, Redis, Nginx, PHP-FPM et Supervisor ;
- commande de backfill ledger rejouable ;
- logs Laravel standards.

Éléments absents ou sans preuve versionnée :

- readiness vérifiant PostgreSQL, Redis et dépendances critiques ;
- workflow CI ;
- script/manifeste reproductible de déploiement ;
- configuration Supervisor/systemd/cron réelle ;
- worker réellement nécessaire aujourd'hui — aucun Job/`ShouldQueue` n'existe malgré Redis queue ;
- observabilité applicative, métriques, traces et alertes ;
- runbook d'incident ;
- procédure de sauvegarde, restauration testée et preuve datée ;
- stratégie de rollback de migration et de release ;
- smoke tests de préproduction ;
- contrôles automatiques empêchant `APP_DEBUG=true`, `APP_ENV!=production`, `MAIL_MAILER=log` ou
  des secrets absents au démarrage de production.

`docs/deployment/VPS-BOOTSTRAP.md` se présente lui-même comme un aperçu et dit que le déploiement
final sera généré plus tard. Il ne peut pas servir de preuve opérationnelle.

**Verdict : `FAIL` pour une bascule production.**

## 9. Frontière moteur / frontend

Le métier est organisé en services, mais il n'est pas un backend headless indépendant :

- les contrôleurs rendent directement des vues Blade ;
- les routes serveur constituent le contrat de navigation ;
- la couche `Application` contient 497 appels à `abort`, `abort_if` ou `abort_unless`, donc une
  dépendance explicite au modèle d'erreur HTTP Laravel ;
- supprimer `resources/views` avant remplacement ferait échouer des routes pourtant métier ;
- les tests Feature comportent aussi des assertions de présentation et de design.

Ce couplage est acceptable si la nouvelle interface reste Blade/Livewire, conformément à l'ADR.
Il ne l'est pas si l'intention devient un SPA séparé ou une API publique sans couche d'adaptation.

La règle de suppression est donc :

1. préserver modèles, migrations, services, infrastructure, middlewares et routes métier ;
2. figer les contrats de parcours par tests de fumée indépendants du style ;
3. construire le nouveau shell et les nouveaux presenters sur les mêmes contrats ;
4. remplacer atomiquement chaque vue/asset ancien une fois son successeur vérifié ;
5. supprimer les assertions purement visuelles obsolètes seulement avec leur écran remplacé ;
6. ne jamais faire un `rm` global du frontend avant qu'un nouveau rendu couvre chaque route.

## 10. Registre des blocages

| ID | Priorité | Blocage | Condition de fermeture |
|---|---|---|---|
| ENG-001 | P0 | Aucune exécution PHP de la suite dans cet audit | Suite complète verte avec versions et sortie archivées |
| ENG-002 | P0 | Garanties PostgreSQL non exercées en CI | `migrate:fresh`, tests et tests de concurrence sur PostgreSQL |
| ENG-003 | P0 | Réconciliation financière sans webhook/poller durable | Réconciliation authentifiée, périodique et idempotente avec alertes |
| ENG-004 | P0 | Sauvegarde/restauration/rollback non prouvés | Restauration chronométrée d'une sauvegarde de préproduction |
| ENG-005 | P0 | Pas de CI ni déploiement reproductible | Pipeline bloquant avec tests, audits, migration et smoke |
| ENG-006 | P1 | Retours de paiement corrélés au « plus récent » | Identifiant d'essai opaque, signé et à usage contrôlé |
| ENG-007 | P1 | Devise/métadonnées distantes non liées à l'intention | Contrat provider documenté et champs vérifiés ou preuve d'absence |
| ENG-008 | P1 | Callback satellite non lié au produit Core | Allowlist immuable/signée par `product_reference` |
| ENG-009 | P1 | Gouvernance DeepSeek absente | Politique, consentement, minimisation, rétention et interrupteur |
| ENG-010 | P1 | Données de démonstration encore versionnées | Décision PRODUCTION-TRUTH-002 exécutée et tests réécrits |
| ENG-011 | P1 | Périmètre V1 non signé | Matrice des 84 CAP : inclus, exclu ou dépendance, avec critères |
| ENG-012 | P1 | Readiness et observabilité insuffisantes | dépendances contrôlées, métriques, alertes et runbooks |
| ENG-013 | P2 | Roadmap canonique contradictoire | snapshot synchronisé au registre de couverture |
| ENG-014 | P2 | Couche Application couplée aux erreurs HTTP | accepté pour Blade ou adapté avant toute architecture API |

## 11. Protocole de certification obligatoire

### Porte A — environnement reproductible

Sur un runner propre correspondant au VPS cible :

```bash
php -v
composer validate --strict
composer install --no-interaction --prefer-dist
composer audit
php artisan about
php artisan route:list
npm ci
npm audit --omit=dev
npm run build
```

La preuve doit inclure PHP 8.4, extensions installées, version PostgreSQL, version Redis et hashes
des lockfiles. Aucun secret ne doit apparaître dans les logs.

### Porte B — base réelle

Sur une base PostgreSQL jetable :

```bash
php artisan migrate:fresh --force
php artisan test
```

Puis, sur une autre base jetable : migration depuis la dernière version de préproduction,
vérification des données, rollback autorisé lorsqu'il est sûr, et restauration de sauvegarde.

Des tests parallèles doivent provoquer les courses critiques : paiements d'une même période,
double débit Wallet, double reversal, financement actif concurrent, validation/modération
concurrentes et génération récurrente.

### Porte C — contrats externes

- GAMAD Core : session valide/expirée, indisponibilité, mauvais produit, mauvaise audience ;
- GeniusPay sandbox : création, abandon navigateur, callback tardif, retry, statut incohérent,
  doublon, rattrapage scheduler et indisponibilité ;
- fédération : callback autorisée par produit, token jamais transmis ailleurs ;
- DeepSeek : données minimisées, erreur, timeout, réponse malformée et aucune mutation sans humain.

### Porte D — exploitation

- préproduction isolée et TLS ;
- `APP_ENV=production`, `APP_DEBUG=false`, secrets présents et permissions fichiers minimales ;
- config/routes/views mis en cache sans erreur ;
- scheduler actif et observable ;
- sauvegarde automatique chiffrée ;
- restauration réellement exécutée ;
- rollback release testé ;
- alertes sur erreurs, latence, indisponibilité Core, paiement en attente et échec de scheduler ;
- `/up` pour la vie du processus et une readiness distincte pour les dépendances indispensables.

### Porte E — vérité produit

- décision explicite et vérifiée sur les 84 CAP ;
- suppression réelle des seeders fictifs si la décision « totale » est maintenue ;
- aucune base ayant reçu un seeder de démonstration promue en production ;
- import des seules données légitimes, avec inventaire et validation humaine.

## 12. Décision de séquencement

Ordre recommandé avant toute reconstruction visuelle :

1. `ENGINE-RUNTIME-001` — runner PHP/PostgreSQL/Redis et CI bloquante ;
2. `ENGINE-FINANCE-001` — réconciliation durable et corrélation exacte ;
3. `PRODUCTION-TRUTH-002` — exécuter littéralement la suppression des démonstrations ;
4. `ENGINE-BOUNDARIES-001` — verrouiller fédération et gouvernance IA ;
5. `ENGINE-OPS-001` — déploiement, readiness, observabilité, backup/restore/rollback ;
6. `ENGINE-SCOPE-001` — signer le périmètre V1 des 84 capacités ;
7. nouvelle exécution d'`ENGINE-TRUTH-001` sur préproduction ;
8. seulement après `GO`, reconstruction atomique de la présentation selon BRAND-DOCTRINE-001.

## Conclusion

La formule juste est :

> **Le moteur DG Afrique est avancé et prometteur, mais pas encore certifié.**

Il mérite d'être conservé. Il ne mérite pas encore qu'on lui attribue une stabilité non prouvée.
La décision drastique reste donc cohérente : ne plus investir dans l'ancien frontend, ne pas le
supprimer prématurément, fermer d'abord les portes P0/P1 du moteur, puis construire une seule
interface neuve sur des contrats certifiés.
