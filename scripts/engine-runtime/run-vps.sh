#!/usr/bin/env bash

set -Eeuo pipefail
set -o pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-engine-runtime-001"
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

env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" .env | tail -n 1 | sed -e 's/^"//' -e 's/"$//'
}

[[ "$(id -u)" == "0" ]] || fail "la certification doit être exécutée par root"
for command_name in git php composer psql redis-server redis-cli node npm runuser tee; do
    require_command "$command_name"
done
repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || fail "dépôt Git introuvable"
[[ "$repo_root" == "$EXPECTED_ROOT" ]] || fail "chemin inattendu: $repo_root"
cd "$repo_root"

[[ -f .env ]] || fail ".env runtime absent"
[[ "$(env_value APP_ENV)" == "testing" ]] || fail "APP_ENV doit valoir testing"
[[ "$(env_value APP_DEBUG)" == "false" ]] || fail "APP_DEBUG doit valoir false"
[[ "$(env_value DB_CONNECTION)" == "pgsql" ]] || fail "DB_CONNECTION doit valoir pgsql"
[[ "$(env_value DB_DATABASE)" == "$DB_NAME" ]] || fail "base PostgreSQL inattendue"
[[ "$(env_value DB_USERNAME)" == "$DB_ROLE" ]] || fail "rôle PostgreSQL inattendu"
[[ "$(env_value REDIS_DB)" == "$REDIS_DEFAULT_DB" ]] || fail "Redis default inattendu"
[[ "$(env_value REDIS_CACHE_DB)" == "$REDIS_CACHE_DB" ]] || fail "Redis cache inattendu"
[[ "$(env_value ZUMRA_PAYMENT_ENABLED)" == "false" ]] || fail "paiement externe actif"
[[ "$(env_value GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED)" == "false" ]] || fail "activation sandbox active"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "fichiers suivis modifiés"

db_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_database WHERE datname='${DB_NAME}'")"
role_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_roles WHERE rolname='${DB_ROLE}'")"
[[ "$db_exists" == "1" ]] || fail "base runtime absente"
[[ "$role_exists" == "1" ]] || fail "rôle runtime absent"

mkdir -p storage/logs/engine-runtime
report="storage/logs/engine-runtime/$(date -u +%Y%m%dT%H%M%SZ).log"

exec > >(tee -a "$report") 2>&1

printf '=== ENGINE-RUNTIME-001 ===\n'
printf 'date_utc=%s\n' "$(date -u +%FT%TZ)"
printf 'commit=%s\n' "$(git rev-parse HEAD)"
printf 'php=%s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'postgres=%s\n' "$(psql --version)"
printf 'redis=%s\n' "$(redis-server --version)"
printf 'node=%s\n' "$(node --version)"
printf 'npm=%s\n' "$(npm --version)"

printf '\n=== DEPENDANCES ===\n'
COMPOSER_ALLOW_SUPERUSER=1 composer validate --strict
COMPOSER_ALLOW_SUPERUSER=1 composer audit --locked
COMPOSER_ALLOW_SUPERUSER=1 composer check-platform-reqs
npm audit --omit=dev

printf '\n=== GARDE POSTGRESQL ===\n'
php artisan optimize:clear --no-ansi
php artisan about --no-ansi
php artisan tinker --execute='
$timezone = DB::selectOne("select current_setting('\''TIMEZONE'\'') as timezone")->timezone ?? null;
dump(["postgres_session_timezone" => $timezone]);
throw_unless($timezone === "UTC", RuntimeException::class, "La session PostgreSQL applicative doit être en UTC.");
'

printf '\n=== MIGRATIONS POSTGRESQL PROPRES ===\n'
php artisan migrate:fresh --force --no-ansi
php artisan migrate:status --no-ansi

printf '\n=== TESTS MOTEUR PHPUNIT SUR POSTGRESQL ===\n'
printf 'groupe_exclu=legacy-frontend (7 contrats résiduels de la carrosserie condamnée)\n'
./vendor/bin/phpunit --configuration phpunit.engine-runtime.xml

printf '\n=== REDIS ISOLE ===\n'
redis_key="dgafrique_engine_runtime_001:smoke:$(date +%s):$$"
[[ "$(redis-cli -n "$REDIS_DEFAULT_DB" SET "$redis_key" ok EX 30 NX)" == "OK" ]] \
    || fail "écriture Redis default refusée"
[[ "$(redis-cli -n "$REDIS_DEFAULT_DB" GET "$redis_key")" == "ok" ]] \
    || fail "lecture Redis default incohérente"
[[ "$(redis-cli -n "$REDIS_DEFAULT_DB" DEL "$redis_key")" == "1" ]] \
    || fail "nettoyage Redis default refusé"

cache_key="dgafrique_engine_runtime_001:cache-smoke:$(date +%s):$$"
[[ "$(redis-cli -n "$REDIS_CACHE_DB" SET "$cache_key" ok EX 30 NX)" == "OK" ]] \
    || fail "écriture Redis cache refusée"
[[ "$(redis-cli -n "$REDIS_CACHE_DB" DEL "$cache_key")" == "1" ]] \
    || fail "nettoyage Redis cache refusé"

printf '\n=== BUILD ET CACHES LARAVEL ===\n'
npm run build
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
php artisan schedule:list --no-ansi
php artisan about --no-ansi

printf '\n=== RESULTAT ===\n'
printf 'ENGINE-RUNTIME-001 STAGE A: PASS\n'
printf 'rapport=%s\n' "$report"
