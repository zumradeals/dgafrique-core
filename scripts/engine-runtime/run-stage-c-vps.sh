#!/usr/bin/env bash

set -Eeuo pipefail
set -o pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-engine-runtime-001"
readonly DB_NAME="dgafrique_engine_runtime_001"
readonly DB_ROLE="dgafrique_engine_runtime"

fail() {
    printf 'ENGINE-RUNTIME-001 STAGE C REFUS: %s\n' "$*" >&2
    exit 1
}

env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" .env | tail -n 1 | sed -e 's/^"//' -e 's/"$//'
}

[[ "$(id -u)" == "0" ]] || fail "la certification doit être exécutée par root"
for command_name in git php psql runuser tee grep; do
    command -v "$command_name" >/dev/null 2>&1 || fail "commande absente: $command_name"
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
[[ "$(env_value ZUMRA_PAYMENT_ENABLED)" == "false" ]] || fail "paiement externe actif"
[[ "$(env_value GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED)" == "false" ]] || fail "activation sandbox active"
deepseek_enabled="$(env_value DEEPSEEK_ENABLED)"
[[ -z "$deepseek_enabled" || "$deepseek_enabled" == "false" ]] || fail "DeepSeek actif"
[[ -z "$(env_value GENIUSPAY_API_KEY)" ]] || fail "clé GeniusPay présente"
[[ -z "$(env_value GENIUSPAY_API_SECRET)" ]] || fail "secret GeniusPay présent"
[[ -z "$(env_value DEEPSEEK_API_KEY)" ]] || fail "clé DeepSeek présente"
[[ "$(env_value GAMAD_CORE_BASE_URL)" == "https://core.test/api/v1" ]] || fail "endpoint GAMAD Core non simulé"
[[ "$(env_value GENIUSPAY_BASE_URL)" == "https://geniuspay.test/api/v1/merchant" ]] || fail "endpoint GeniusPay non simulé"
[[ "$(env_value DEEPSEEK_BASE_URL)" == "https://deepseek.test" ]] || fail "endpoint DeepSeek non simulé"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "fichiers suivis modifiés"

db_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_database WHERE datname='${DB_NAME}'")"
role_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_roles WHERE rolname='${DB_ROLE}'")"
[[ "$db_exists" == "1" ]] || fail "base runtime absente"
[[ "$role_exists" == "1" ]] || fail "rôle runtime absent"

mkdir -p storage/logs/engine-runtime
report="storage/logs/engine-runtime/$(date -u +%Y%m%dT%H%M%SZ)-stage-c.log"
exec > >(tee -a "$report") 2>&1

printf '=== ENGINE-RUNTIME-001 / STAGE C ===\n'
printf 'date_utc=%s\n' "$(date -u +%FT%TZ)"
printf 'commit=%s\n' "$(git rev-parse HEAD)"
printf 'base=%s\n' "$DB_NAME"
printf 'reseau_externe=reponses HTTP simulees uniquement\n'
printf 'production_touchee=NON\n'

printf '\n=== REMISE A ZERO DE LA BASE ISOLEE ===\n'
php artisan optimize:clear --no-ansi
php artisan migrate:fresh --force --no-ansi

printf '\n=== GARDE POSTGRESQL ===\n'
php artisan tinker --execute='
$timezone = DB::connection()->getPdo()->query("SHOW TIMEZONE")->fetchColumn();
dump(["postgres_session_timezone" => $timezone]);
throw_unless($timezone === "UTC", RuntimeException::class, "La session PostgreSQL applicative doit être en UTC.");
'

printf '\n=== CONTRATS EXTERNES SIMULES ===\n'
./vendor/bin/phpunit --configuration phpunit.engine-runtime-stage-c.xml

printf '\n=== REPRISE AUTOMATIQUE DES PAIEMENTS ===\n'
schedule_output="$(php artisan schedule:list --no-ansi)"
printf '%s\n' "$schedule_output"
grep -Fq 'payments:reconcile-pending-external' <<< "$schedule_output" \
    || fail "commande de réconciliation absente du scheduler"

printf '\n=== RESULTAT ===\n'
printf 'ENGINE-RUNTIME-001 STAGE C: PASS\n'
printf 'rapport=%s\n' "$report"
