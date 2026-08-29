# ENGINE-RUNTIME-001 — preuve dynamique du moteur

## Statut

`CERTIFIÉ — STAGES A, B, C ET D PASS` au commit
`97890d0ef317f7c6e6b5c6465a115367d5a79ffe`. Le socle reproductible, les invariants
transactionnels concurrents, les frontières externes simulées et les mécanismes d’exploitation ont
réussi sur le runner VPS isolé.

Cette certification ferme la porte dynamique d’ENGINE-RUNTIME-001. Le verdict consolidé
`ENGINE-TRUTH-FINAL-001`, obtenu le 29 août 2026, autorise désormais la suppression contrôlée du
frontend historique et la construction du frontend neuf. Il n'autorise pas encore la mise en
production du produit complet, qui conserve ses portes propres.

## Baseline et séparation

L'application en service reste dans `/var/www/dgafrique-core` et utilise la base
`dgafrique_portal`. ENGINE-RUNTIME-001 doit exclusivement utiliser :

- clone : `/var/www/dgafrique-engine-runtime-001` ;
- base : `dgafrique_engine_runtime_001` ;
- rôle : `dgafrique_engine_runtime` ;
- Redis application : DB 14 ;
- Redis cache : DB 15 ;
- URL locale non publiée : `http://127.0.0.1`.

Le bootstrap refuse tout autre chemin, une cible déjà existante, un clone modifié, une base Redis
non vide ou une exécution depuis le dépôt de production.

## Interdictions

- ne jamais copier le `.env` de production ;
- ne jamais restaurer la base `dgafrique_portal` dans le runner ;
- ne jamais lancer `migrate:fresh` depuis `/var/www/dgafrique-core` ;
- ne jamais activer GeniusPay, DeepSeek ou un endpoint GAMAD Core réel ;
- ne jamais publier ce runner dans Nginx ;
- ne jamais utiliser les seeders de démonstration pour la certification.

## Stage A — socle reproductible

Le Stage A vérifie :

1. lockfiles et prérequis Composer ;
2. audit Composer et audit npm de production ;
3. migration complète depuis une base PostgreSQL vide ;
4. statut de toutes les migrations ;
5. suite PHPUnit du moteur avec PostgreSQL forcé et ancien frontend explicitement exclu ;
6. écriture, lecture et suppression de clés temporaires dans les Redis 14/15 ;
7. build Vite de production ;
8. génération des caches Laravel de config, routes et vues ;
9. présence du scheduler Mission ;
10. journal daté et commit exact.

`phpunit.engine-runtime.xml` remplace le SQLite mémoire de `phpunit.xml` par PostgreSQL, mais garde
cache, session et queue en mémoire pendant la suite métier. Redis est vérifié séparément afin que
les tests restent déterministes.

### Frontière de certification

Le groupe PHPUnit `legacy-frontend` contient désormais 7 contrats de présentation liés à la
carrosserie condamnée. Les suites qui dépendaient des univers `*DemoSeeder` ont été supprimées
avec ces données par `PRODUCTION-TRUTH-002`. Les contrats restants :

- restent versionnés et exécutables par la configuration PHPUnit normale tant que leur écran existe ;
- ne sont ni supprimés, ni convertis artificiellement en succès ;
- sont exclus uniquement de `phpunit.engine-runtime.xml` ;
- disparaîtront ou seront remplacés avec le frontend qu'ils décrivent.

Les tests métier présents dans des suites mixtes restent dans la certification. Les assertions de
filtrage Personnes portent désormais sur la collection réellement filtrée transmise à la vue, et
non sur l'intégralité d'une page contenant aussi un rail récent non filtré.

### Premier passage du 27 août 2026

Sur le commit `354d743`, le runner a obtenu :

- lockfiles, audits et prérequis : PASS ;
- 60 migrations PostgreSQL depuis une base vide : PASS ;
- 1 120 tests PASS, 20 FAIL, 5 350 assertions ;
- arrêt avant Redis, build et caches, conformément au fail-fast.

Les vingt échecs se répartissent ainsi :

| Classe | Nombre | Décision |
|---|---:|---|
| Couplages à la présentation condamnée | 15 | contrats visuels groupés ; tests mixtes recentrés sur les données |
| Callback de fédération figé sur le domaine de production | 1 | assertion liée à la configuration active |
| Comparaisons temporelles UTC / Europe-Berlin | 4 | session PostgreSQL applicative forcée en UTC |

Le serveur PostgreSQL reste volontairement en `Europe/Berlin`, car il est partagé. La connexion
DG Afrique impose désormais `DB_TIMEZONE=UTC`, valeur également appliquée par défaut quand la
variable est absente. Les colonnes concernées restent en `timestamp with time zone` ; aucune
donnée et aucune configuration PostgreSQL globale ne sont modifiées.

### Deuxième passage du 27 août 2026

Sur le commit `718d0e6`, le contrat UTC, les 60 migrations et les 1 103 tests moteur avec
5 097 assertions ont tous réussi. Le runner s'est néanmoins arrêté avec le code `1`, avant Redis
et le build, car `php artisan test` ajoutait sa propre option de configuration à celle fournie par
le script. PHPUnit a signalé `Option --configuration cannot be used more than once`.

Le runner appelle désormais directement `vendor/bin/phpunit` avec l'unique configuration
`phpunit.engine-runtime.xml`. Ce correctif ne change ni le périmètre ni le résultat des tests.

### Passage concluant du 27 août 2026

Sur le commit `0be1e62`, le Stage A a réussi intégralement sur le VPS isolé :

- validation stricte Composer, prérequis plateforme et audits de sécurité : PASS ;
- installation npm et audit des dépendances de production : PASS ;
- connexion applicative PostgreSQL explicitement en UTC : PASS ;
- 60 migrations depuis une base PostgreSQL vide : PASS ;
- 1 103 tests moteur, 5 097 assertions, zéro échec en 3 min 29,776 s : PASS ;
- écritures/lectures/suppressions temporaires Redis 14 et 15 : PASS ;
- build Vite de production, 53 modules transformés : PASS ;
- caches Laravel de configuration, routes et vues : PASS ;
- commande récurrente Mission présente dans le scheduler : PASS.

Le script s'est terminé avec le code `0` et le verdict
`ENGINE-RUNTIME-001 STAGE A: PASS`. La preuve brute est conservée sur le runner dans
`storage/logs/engine-runtime/20260827T222249Z.log`. Pendant toute l'exécution, le dépôt de
production est resté au commit `2a1847c` sans modification de fichier suivi.

## Stage B — invariants sous concurrence PostgreSQL

Le Stage B lance deux processus PHP indépendants, donc deux connexions PostgreSQL distinctes. Une
barrière les aligne avant chaque opération ; pour les courses sur une ligne existante, le processus
de contrôle conserve brièvement le verrou concerné afin que les deux workers attendent réellement
le même point de sérialisation. Il vérifie :

1. deux créations concurrentes du même sujet produisent un seul Wallet ;
2. deux débits de 80 sur un solde de 100 ne peuvent jamais produire un solde négatif ;
3. deux crédits portant la même clé d'idempotence retournent la même écriture unique ;
4. deux postings concurrents d'un paiement déjà confirmé projettent une seule écriture Ledger ;
5. deux compensations distinctes d'une même écriture ne produisent qu'un seul reversal ;
6. deux contributions de 80 vers une cible de 100 ne dépassent jamais la cible ;
7. deux workers du scheduler ne créent qu'une occurrence et n'avancent la récurrence qu'une fois.

`scripts/engine-runtime/run-stage-b-vps.sh` refuse le dépôt de production, les paiements externes
actifs, une base autre que `dgafrique_engine_runtime_001` et tout fichier suivi modifié. Il remet
uniquement cette base isolée à zéro, puis conserve son rapport daté dans le même répertoire que le
Stage A. La projection d'un paiement déjà confirmé est testée sans aucun appel GeniusPay ; les
contrats réseau simulés appartiennent au Stage C.

### Premier lancement du Stage B — runner arrêté avant les courses

Sur le commit `b497128`, les 60 migrations ont réussi, puis le runner s'est arrêté avant PHPUnit :
l'imbrication des chaînes Bash/Tinker avait retiré les apostrophes SQL de
`current_setting('TIMEZONE')`. PostgreSQL a donc interprété `TIMEZONE` comme un nom de colonne et
refusé la requête. Aucun test concurrent ni invariant métier n'avait encore été exécuté ; ce
résultat ne constitue pas un échec du moteur. Le garde-fou interroge désormais la même valeur avec
`SHOW TIMEZONE`, sans imbrication d'apostrophes. La production est restée sur `2a1847c`.

### Deuxième lancement du Stage B — 6 courses certifiées, assertion temporelle à rejouer

Sur le commit `a63afea`, PostgreSQL applicatif a confirmé `UTC` et les sept scénarios
multiprocessus ont été exécutés. Six ont réussi intégralement. Le scénario de récurrence a lui-même
confirmé que les deux workers terminaient, que leurs compteurs étaient exactement `[0, 1]` et
qu'une seule occurrence existait ; sa dernière assertion a toutefois comparé le prochain passage
à l'objet Carbon d'avant persistance. La sérialisation Eloquent/PostgreSQL pouvant normaliser ses
microsecondes, cette référence n'était pas autoritative. Le test mesure désormais l'intervalle
quotidien depuis le timestamp relu en base avant la course. Aucun code moteur n'est modifié et le
Stage B reste non certifié jusqu'au rejeu intégral des sept scénarios.

### Passage concluant du Stage B — certification concurrente

Sur le commit `c03d379`, le Stage B a réussi intégralement sur le VPS isolé :

- session PostgreSQL applicative en UTC : PASS ;
- 60 migrations rejouées depuis une base vide : PASS ;
- sept scénarios multiprocessus, 72 assertions, zéro échec en 4,477 s : PASS ;
- code retour du runner : `0` ;
- verdict : `ENGINE-RUNTIME-001 STAGE B: PASS`.

Sont ainsi certifiés sous concurrence réelle : l'unicité du Wallet, l'absence de double dépense,
l'idempotence d'un crédit, la projection unique d'un paiement confirmé, l'unicité d'un reversal,
le non-dépassement d'une cible de financement et l'unicité/avancement d'une occurrence récurrente.
La preuve brute est conservée dans
`storage/logs/engine-runtime/20260827T224836Z-stage-b.log`. La production est restée au commit
`2a1847c`, sans modification de fichier suivi.

## Stage C — certification des frontières externes simulées

Le Stage C n'appelle aucun service réel. Son runner refuse de démarrer si une clé GeniusPay ou
DeepSeek est présente, si DeepSeek est actif, ou si les URL GAMAD Core, GeniusPay et DeepSeek ne
pointent pas vers les domaines `.test` dédiés. Les tests installent ensuite des réponses HTTP
simulées et interdisent toute requête non déclarée.

Le Stage C ferme quatre lacunes constatées par ENGINE-TRUTH-001 :

1. une preuve fédérée ne peut être remise qu'au callback HTTPS lié par configuration à sa
   `product_reference` exacte ; le registre administrable ne peut plus rediriger seul un token ;
2. les trois retours GeniusPay portent une URL signée et expirable ainsi qu'un secret opaque propre
   à la tentative, dont seul le hash est stocké ; un retour altéré, absent, inconnu ou rattaché à
   une autre identité est refusé, au lieu de sélectionner « le paiement le plus récent » ;
3. `payments:reconcile-pending-external` reprend toutes les cinq minutes les tentatives GeniusPay
   `PENDING/PROCESSING` suffisamment anciennes pour l'adhésion, les contributions et les
   acquisitions ZAHAB ; le lot est borné, anti-chevauchement, idempotent et signale un code d'échec
   si au moins un appel fournisseur échoue, sans bloquer les autres tentatives ;
4. DeepSeek est désactivé par défaut, exige une version de politique de données, sépare timeout de
   connexion et timeout total, et normalise les pannes de transport. La politique versionnée
   impose de le garder désactivé jusqu'à la preuve contractuelle de rétention et à l'information
   explicite dans la future interface.

La suite Stage C couvre les contrats GAMAD Core, la fédération, les trois familles de paiement,
le rattrapage tardif et rejoué, les incohérences fournisseur, les erreurs réseau, la gouvernance
DeepSeek, les réponses IA malformées et l'absence de mutation Core sans confirmation humaine.

Le runner remet uniquement la base isolée à zéro, vérifie PostgreSQL UTC, exécute
`phpunit.engine-runtime-stage-c.xml`, puis exige la présence de la commande de réconciliation dans
le scheduler.

Cette porte simulée ne prétend pas valider le contrat commercial ou le schéma réel d'une réponse
GeniusPay/DeepSeek. Leur activation future nécessitera un essai sandbox/preproduction autorisé,
avec identifiants dédiés et sans donnée réelle ; elle n'est pas requise pour conserver ces
fonctions désactivées pendant la reconstruction du frontend.

### Premier lancement du Stage C — contrat valide, assertion de test à rejouer

Sur le commit `856007d`, les 61 migrations ont réussi sur PostgreSQL en UTC et les 122 scénarios
ont tous été exécutés. 121 ont réussi avec 420 assertions ; l'unique échec supposait que la seconde
référence générée par le faux GeniusPay serait littéralement `ACQ-REF-2`. Cette recherche par
libellé fabriqué ne permettait pas de distinguer une numérotation différente d'une création
réellement absente. Le test exige désormais le succès des deux requêtes, capture les deux lignes
par leur identifiant, déclenche le retour signé de la première et vérifie que la première seule
devient `COMPLETED` tandis que la seconde reste `PENDING`.

Aucun code moteur n'est modifié par ce correctif. La production est restée sur `2a1847c`, sans
modification de fichier suivi.

### Passage concluant du Stage C — certification des frontières

Sur le commit `6f0040b`, le Stage C a réussi intégralement sur le VPS isolé :

- 61 migrations PostgreSQL depuis une base vide : PASS ;
- session PostgreSQL applicative en UTC : PASS ;
- 122 tests de contrats externes simulés, 424 assertions, zéro échec en 12,916 s : PASS ;
- commande `payments:reconcile-pending-external` enregistrée toutes les cinq minutes : PASS ;
- code retour du runner : `0` ;
- verdict : `ENGINE-RUNTIME-001 STAGE C: PASS`.

Sont ainsi certifiés sans requête réelle : les erreurs et incohérences GAMAD Core, l'audience et
la destination de fédération, la corrélation signée des retours GeniusPay, le rattrapage tardif et
idempotent des trois familles de paiement, l'indisponibilité fournisseur, l'arrêt explicite de
DeepSeek, les réponses IA invalides et l'absence de mutation Core avant confirmation humaine.
La preuve brute est conservée dans
`storage/logs/engine-runtime/20260828T112530Z-stage-c.log`. La production est restée sur
`2a1847c`, sans modification de fichier suivi.

## Stage D — exploitation et reprise

Le Stage D ne se contente pas de lire `schedule:list`. Il ajoute et prouve :

1. une séparation stricte entre `/up` (vie du processus) et `/ready` (PostgreSQL, deux Redis et
   heartbeat scheduler récent) ;
2. une commande de readiness exploitable par un moniteur, avec code de sortie non nul ;
3. un heartbeat durable planifié chaque minute ;
4. le déclenchement réel de Laravel par un timer systemd transitoire exécuté sous `www-data` ;
5. un dump PostgreSQL custom, son SHA-256, sa restauration dans une base distincte et la
   vérification de la sentinelle, des migrations et des tables ;
6. le rollback puis la réapplication de la dernière migration sur cette seule base restaurée ;
7. une bascule atomique de release protégée par l’état courant attendu, puis un retour effectif à
   la révision précédente ;
8. le rejeu intégral de la suite PostgreSQL du moteur après ajout de ces primitives.

Le runner refuse toute base, tout rôle, tout chemin ou toute configuration externe inattendus. Il
n’installe aucune unité persistante, ne publie aucun port et ne touche jamais `/var/www/dgafrique-core`.
Les procédures opérationnelles et les unités de référence sont documentées dans
`docs/production/OPERATIONS-RUNBOOK.md` et `deploy/systemd/`.

Une sauvegarde restaurable ne vaut pas encore sauvegarde de production : chiffrement, stockage hors
site, rétention et destinataire des alertes appartiennent au plan de déploiement final. Stage D
certifie que le moteur fournit les signaux et mécanismes nécessaires, sans inventer ces choix
d’infrastructure.

### Statut d’exécution

Le premier passage du 28 août 2026 sur `2255a2c` a validé les 61 migrations, puis exécuté les
1 111 tests du moteur. Il s’est arrêté avec 1 erreur et 1 échec, avant les preuves opérationnelles :

- le nouveau test de readiness passait trois arguments à `assertNotContains`, alors que PHPUnit 12
  réserve désormais le troisième à un message texte ;
- deux scénarios de rendu de l’ancienne page ZUMRA, alimentés par `ZumraWorldDemoSeeder`, n’étaient
  pas encore classés dans le groupe `legacy-frontend` déjà exclu de la certification moteur.

Le premier défaut est corrigé sans changer le contrat testé. Les deux scénarios de présentation
sont maintenant classés avec l’ancienne carrosserie ; le test séparé du seeder et tous les contrats
métier restent exécutés. Ce reclassement ne transforme donc aucun échec backend en succès.

Le runner a retourné `2`, supprimé sa base temporaire et n’a atteint aucune épreuve Stage D après
la régression complète. La production est restée sur `2a1847c`, sans modification de fichier suivi.
Stage D reste `À REJOUER SUR LE VPS ISOLÉ` et aucun PASS n’est déclaré avant une sortie `0` complète
de `scripts/engine-runtime/run-stage-d-vps.sh`.

Le deuxième passage sur `4eb2541` a franchi la régression complète avec 1 110 tests et
5 148 assertions, puis les quatre contrats opérationnels avec 31 assertions. Les transitions de
readiness sans puis avec heartbeat ont aussi produit les codes attendus. Le timer systemd
transitoire a bien démarré PHP sous `www-data`, mais ce compte ne pouvait pas traverser
`vendor/autoload.php` : Composer avait installé les dépendances sous `root` avec l’umask `027` sans
attribuer le groupe de lecture au runtime web.

Le bootstrap et le runner conservent désormais `root` comme propriétaire de `vendor/`, attribuent
le groupe `www-data` et ajoutent seulement `g+rX`. PHP peut donc lire l’application sans pouvoir
modifier ses dépendances. L’arrêt est survenu avant le dump et les rollbacks ; aucune sauvegarde
n’a été créée, la base temporaire était absente après nettoyage et la production est restée sur
`2a1847c`. Stage D demeure à rejouer intégralement.

### Passage concluant du Stage D — certification opérationnelle

Le passage final du 28 août 2026 sur `97890d0` a terminé avec le code `0` :

- validation Composer stricte : PASS ;
- 61 migrations PostgreSQL depuis une base vide : PASS ;
- régression complète : 1 110 tests, 5 148 assertions, zéro échec : PASS ;
- contrats opérationnels ciblés : 4 tests, 31 assertions, zéro échec : PASS ;
- readiness sans heartbeat `not_ready`, puis avec heartbeat `ready` : PASS ;
- timer systemd transitoire exécuté sous `www-data`, heartbeat écrit avec la source
  `laravel-scheduler` et réconciliation des paiements invoquée : PASS ;
- dump PostgreSQL custom de 570 717 octets, SHA-256
  `8e7300f1f02c5214e51a75603517cf1a36294bb4ed53ff5f7d25aefb9d9d0f52` : PASS ;
- restauration dans une base distincte, avec 61 migrations, 88 tables, sentinelle et readiness :
  PASS ;
- rollback puis réapplication de la migration opérationnelle sur la base restaurée : PASS ;
- bascule atomique vers la candidate, refus intentionnel d’un état concurrent, puis rollback vers
  la révision précédente `4eb2541` : PASS ;
- nettoyage de la base temporaire après le test : PASS ;
- dépôt de production maintenu sur `2a1847c`, sans modification de fichier suivi : PASS.

La preuve brute est conservée dans
`storage/logs/engine-runtime/20260828T122147Z-stage-d.log`. Le dump correspondant est
`storage/app/engine-runtime/backups/20260828T122147Z-stage-d.dump`. Le `REFUS` visible pendant la
bascule de release est une assertion négative attendue : il prouve que le garde-fou rejette une
release courante différente de l’état annoncé.

## Étape suivante

Les quatre stages dynamiques sont certifiés. La prochaine étape est `ENGINE-TRUTH-FINAL-001` :
réconcilier les preuves A–D avec le périmètre des 84 capacités, exécuter littéralement la décision
sur les seeders fictifs et prononcer séparément le verdict sur la suppression du frontend et le
verdict de lancement production.

Chaque Stage doit conserver sa sortie sous `storage/logs/engine-runtime/`. Un échec interrompt le
script et reste un résultat de certification, jamais une invitation à modifier la production.

## Constat de préflight VPS du 27 août 2026

- Ubuntu 24.04.4 ;
- PHP 8.4.24 et extensions Laravel/PostgreSQL/Redis disponibles ;
- Composer 2.10.2 ;
- PostgreSQL 16.15 actif ;
- Redis 7.0.15 actif ;
- Node 22.23.1 et npm 10.9.8 ;
- base et rôle runtime absents ;
- Redis 14 et 15 vides ;
- espace disque et mémoire suffisants ;
- déploiement actif sur `2a1847c`, sans modification de fichier suivi.

Le déploiement actif contient plusieurs fichiers non suivis qui ne sont ni lus ni supprimés par ce
lot. Il autorise aussi les paiements sandbox dans l'environnement `production`; cette configuration
est un constat séparé et n'est jamais copiée dans le runner.
