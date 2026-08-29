#!/usr/bin/env bash

set -Eeuo pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-engine-runtime-001"
readonly PRODUCTION_ROOT="/var/www/dgafrique-core"
readonly DB_NAME="dgafrique_engine_runtime_001"
readonly DB_ROLE="dgafrique_engine_runtime"
readonly REDIS_DEFAULT_DB="14"
readonly REDIS_CACHE_DB="15"

fail() {
    printf 'ENGINE-RUNTIME-001 REFUS: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "commande absente: $1"
}

[[ "$(id -u)" == "0" ]] || fail "ce bootstrap doit être exécuté par root"

for command_name in git php composer node npm openssl psql runuser redis-cli; do
    require_command "$command_name"
done

repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || fail "le répertoire courant n'est pas un dépôt Git"
[[ "$repo_root" == "$EXPECTED_ROOT" ]] || fail "chemin inattendu: $repo_root"
[[ "$repo_root" != "$PRODUCTION_ROOT" ]] || fail "le dépôt de production ne doit jamais être utilisé"
[[ -f "$repo_root/.env.engine-runtime.example" ]] || fail "template runtime absent"
[[ -f "$repo_root/phpunit.engine-runtime.xml" ]] || fail "configuration PHPUnit PostgreSQL absente"
[[ ! -e "$repo_root/.env" ]] || fail ".env existe déjà; bootstrap non rejouable automatiquement"
[[ -z "$(git status --porcelain)" ]] || fail "le clone runtime doit être propre avant bootstrap"

db_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_database WHERE datname='${DB_NAME}'")"
role_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_roles WHERE rolname='${DB_ROLE}'")"
[[ "$db_exists" == "0" ]] || fail "la base cible existe déjà: $DB_NAME"
[[ "$role_exists" == "0" ]] || fail "le rôle cible existe déjà: $DB_ROLE"

[[ "$(redis-cli -n "$REDIS_DEFAULT_DB" DBSIZE | tr -d '\r')" == "0" ]] \
    || fail "Redis DB $REDIS_DEFAULT_DB n'est pas vide"
[[ "$(redis-cli -n "$REDIS_CACHE_DB" DBSIZE | tr -d '\r')" == "0" ]] \
    || fail "Redis DB $REDIS_CACHE_DB n'est pas vide"

cd "$repo_root"

printf 'Installation des dépendances dans le clone isolé...\n'
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
npm ci --ignore-scripts

# Le code et les dépendances restent possédés par root et non modifiables par PHP-FPM. Le groupe
# www-data doit toutefois pouvoir traverser vendor/ et lire l'autoloader en exploitation.
chown -R root:www-data vendor
chmod -R g+rX vendor

db_password="$(openssl rand -hex 32)"

printf 'Création du rôle et de la base PostgreSQL isolés...\n'
runuser -u postgres -- psql --set=ON_ERROR_STOP=1 \
    --set=runtime_role="$DB_ROLE" \
    --set=runtime_password="$db_password" <<'SQL'
CREATE ROLE :"runtime_role"
    LOGIN
    PASSWORD :'runtime_password'
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE
    NOREPLICATION;
SQL

runuser -u postgres -- createdb \
    --owner="$DB_ROLE" \
    --encoding=UTF8 \
    "$DB_NAME"

install -m 0640 -o root -g www-data .env.engine-runtime.example .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${db_password}/" .env

php artisan key:generate --force --no-interaction

install -d -m 0775 -o www-data -g www-data \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

printf '\nENGINE-RUNTIME-001 bootstrap terminé.\n'
printf 'Clone     : %s\n' "$repo_root"
printf 'Commit    : %s\n' "$(git rev-parse HEAD)"
printf 'Base      : %s\n' "$DB_NAME"
printf 'Redis     : %s / %s\n' "$REDIS_DEFAULT_DB" "$REDIS_CACHE_DB"
printf 'Paiements : désactivés\n'
printf 'Étape suivante: scripts/engine-runtime/run-vps.sh\n'
