# DG Afrique — runbook d’exploitation du moteur

## Objet et autorité

Ce runbook décrit les primitives d’exploitation livrées par ENGINE-RUNTIME-001 / Stage D. Il ne
constitue pas une autorisation de déployer l’interface actuelle ni d’activer GeniusPay ou DeepSeek.
Les noms de domaine, secrets, chemins et politiques de rétention de production doivent être fixés
dans le plan de déploiement final.

## Vie, readiness et scheduler

- `GET /up` répond si le processus Laravel est vivant. Il ne garantit aucune dépendance.
- `GET /ready` répond `200` uniquement si PostgreSQL, Redis application, Redis cache et le
  heartbeat du scheduler sont disponibles. Il répond sinon `503`, sans message d’exception ni
  secret, et avec `Cache-Control: no-store`.
- `php artisan ops:readiness --json` expose le même contrat aux sondes locales et retourne un code
  non nul lorsque le moteur n’est pas prêt.
- `php artisan ops:scheduler-heartbeat` est planifié chaque minute. Par défaut, une absence de
  signal pendant plus de 180 secondes ferme la readiness.

Les unités de référence sont dans `deploy/systemd/`. Avant activation, le lien
`/var/www/dgafrique/current` doit viser une release complète et le `.env` doit être lisible par
`www-data` sans être public. Le code et `vendor/` restent possédés par `root`; le groupe
`www-data` reçoit uniquement la lecture et la traversée (`g+rX`). Les seuls chemins applicatifs
modifiables par PHP sont `storage/` et `bootstrap/cache/`. Après installation des unités :

```bash
systemctl daemon-reload
systemctl enable --now dgafrique-scheduler.timer dgafrique-readiness.timer
systemctl list-timers 'dgafrique-*'
journalctl -u dgafrique-scheduler.service -u dgafrique-readiness.service --since '-15 minutes'
```

Le code d’échec de `dgafrique-readiness.service` doit être relié au système d’alerte choisi avant
la bascule de production. L’absence de fournisseur d’alerte dans le dépôt est volontaire : aucun
destinataire ou canal ne doit être inventé par le code.

## Réaction à une readiness négative

1. Retirer l’instance du trafic ; ne pas redémarrer en boucle.
2. Exécuter `php artisan ops:readiness --json` pour identifier le composant en échec.
3. Pour `scheduler=false`, consulter les deux unités systemd et le journal Laravel, puis vérifier
   que le timer est actif.
4. Pour PostgreSQL ou Redis, vérifier le service et la connectivité locale sans afficher le `.env`.
5. Pour un incident paiement, garder les flux externes fermés ; la commande de réconciliation est
   idempotente et peut être rejouée après rétablissement.
6. Ne remettre l’instance dans le trafic qu’après plusieurs readiness `200` successives.

## Sauvegarde et restauration

Une sauvegarde acceptable est un dump PostgreSQL au format custom, créé avec `--no-owner --no-acl`,
un SHA-256 conservé séparément, une rétention définie et un chiffrement hors du serveur. La preuve
Stage D restaure réellement ce format dans une base distincte, vérifie la sentinelle, le nombre de
migrations et le nombre de tables, puis exécute la readiness sur la base restaurée.

Une restauration ne doit jamais viser la base active. Créer une nouvelle base, restaurer, vérifier,
puis décider explicitement de la promotion. Le nom temporaire Stage D est strictement
`dgafrique_engine_runtime_001_restore_stage_d` ; le runner la supprime à la fin et conserve le dump
ainsi que son empreinte dans le clone isolé.

Le chiffrement, l’envoi hors site et la politique de rétention dépendent de l’infrastructure finale
et restent une condition de déploiement. Stage D prouve la restaurabilité du moteur, pas l’existence
d’un coffre de sauvegarde externe.

## Release et retour arrière

Les releases sont des répertoires immuables contenant un fichier `REVISION`. Le lien `current` est
la seule cible stable de PHP-FPM/Nginx. La commande suivante exige de connaître l’état courant et
refuse une bascule concurrente :

```bash
scripts/deployment/switch-current-release.sh \
  /var/www/dgafrique/releases \
  /var/www/dgafrique/current \
  NOUVELLE_RELEASE \
  RELEASE_ACTUELLE
```

Le retour arrière utilise exactement la même commande en inversant cible et état attendu. Une
migration destructive ne doit jamais être couplée à une release. Utiliser d’abord des migrations
additives compatibles avec l’ancienne et la nouvelle version ; supprimer les colonnes seulement
dans une release ultérieure, après expiration de la fenêtre de rollback.

## Preuve Stage D

`scripts/engine-runtime/run-stage-d-vps.sh` s’exécute uniquement dans le clone isolé et :

1. rejoue la suite PostgreSQL complète du moteur ;
2. vérifie les contrats `/up`, `/ready` et le code de sortie CLI ;
3. déclenche un timer systemd transitoire sous `www-data` et attend le heartbeat écrit en base ;
4. crée un dump, le restaure dans une autre base et en vérifie l’intégrité ;
5. retire puis réapplique la dernière migration uniquement sur la base restaurée ;
6. active deux pointeurs de release, refuse un état concurrent, puis revient à la précédente ;
7. conserve un rapport daté et le dump avec leur commit et SHA-256.

Toute sortie non nulle maintient ENGINE-RUNTIME-001 en `HOLD`. Le runner n’ouvre aucun port, ne
modifie aucune unité persistante et ne lit, ne migre ni ne sauvegarde la base de production.
