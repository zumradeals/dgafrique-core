#!/usr/bin/env bash

set -Eeuo pipefail
set -o pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-engine-runtime-001"
readonly DB_NAME="dgafrique_engine_runtime_001"
readonly DB_ROLE="dgafrique_engine_runtime"
readonly RESTORE_DB="dgafrique_engine_runtime_001_restore_stage_d"
readonly RELEASE_TEST_PREFIX="${EXPECTED_ROOT}/storage/app/engine-runtime/stage-d-release-"

systemd_unit=""
restore_created=0
release_test_root=""

fail() {
    printf 'ENGINE-RUNTIME-001 STAGE D REFUS: %s\n' "$*" >&2
    exit 1
}

env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" .env | tail -n 1 | sed -e 's/^"//' -e 's/"$//'
}

cleanup() {
    local status="$?"
    set +e

    if test -n "$systemd_unit"; then
        systemctl stop "${systemd_unit}.timer" "${systemd_unit}.service" >/dev/null 2>&1 || true
        systemctl reset-failed "${systemd_unit}.service" >/dev/null 2>&1 || true
    fi

    if test "$restore_created" -eq 1; then
        runuser -u postgres -- psql -X -v ON_ERROR_STOP=1 -d postgres -c \
            "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${RESTORE_DB}' AND pid <> pg_backend_pid();" \
            >/dev/null 2>&1 || true
        runuser -u postgres -- dropdb --if-exists "$RESTORE_DB" >/dev/null 2>&1 || true
    fi

    if test -n "$release_test_root" && [[ "$release_test_root" == "${RELEASE_TEST_PREFIX}"* ]]; then
        rm -rf -- "$release_test_root"
    fi

    exit "$status"
}
trap cleanup EXIT

[[ "$(id -u)" == "0" ]] || fail "la certification doit être exécutée par root"
for command_name in git php composer psql pg_dump pg_restore createdb dropdb runuser tee grep sha256sum \
    systemd-run systemctl journalctl realpath awk sed seq sleep chown chmod; do
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
[[ "$(env_value REDIS_DB)" == "14" ]] || fail "Redis applicatif inattendu"
[[ "$(env_value REDIS_CACHE_DB)" == "15" ]] || fail "Redis cache inattendu"
[[ "$(env_value ZUMRA_PAYMENT_ENABLED)" == "false" ]] || fail "paiement externe actif"
[[ "$(env_value GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED)" == "false" ]] || fail "activation sandbox active"
deepseek_enabled="$(env_value DEEPSEEK_ENABLED)"
[[ -z "$deepseek_enabled" || "$deepseek_enabled" == "false" ]] || fail "DeepSeek actif"
[[ -z "$(env_value GENIUSPAY_API_KEY)" ]] || fail "clé GeniusPay présente"
[[ -z "$(env_value GENIUSPAY_API_SECRET)" ]] || fail "secret GeniusPay présent"
[[ -z "$(env_value DEEPSEEK_API_KEY)" ]] || fail "clé DeepSeek présente"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "fichiers suivis modifiés"

db_exists="$(runuser -u postgres -- psql -X -Atqc "SELECT count(*) FROM pg_database WHERE datname='${DB_NAME}'")"
role_exists="$(runuser -u postgres -- psql -X -Atqc "SELECT count(*) FROM pg_roles WHERE rolname='${DB_ROLE}'")"
restore_exists="$(runuser -u postgres -- psql -X -Atqc "SELECT count(*) FROM pg_database WHERE datname='${RESTORE_DB}'")"
[[ "$db_exists" == "1" ]] || fail "base runtime absente"
[[ "$role_exists" == "1" ]] || fail "rôle runtime absent"
[[ "$restore_exists" == "0" ]] || fail "base de restauration déjà présente: ${RESTORE_DB}"

mkdir -p storage/logs/engine-runtime storage/app/engine-runtime/backups
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
report="storage/logs/engine-runtime/${stamp}-stage-d.log"
dump_path="storage/app/engine-runtime/backups/${stamp}-stage-d.dump"
exec > >(tee -a "$report") 2>&1

printf '=== ENGINE-RUNTIME-001 / STAGE D ===\n'
printf 'date_utc=%s\n' "$(date -u +%FT%TZ)"
printf 'commit=%s\n' "$(git rev-parse HEAD)"
printf 'base_runtime=%s\n' "$DB_NAME"
printf 'base_restoration_temporaire=%s\n' "$RESTORE_DB"
printf 'production_touchee=NON\n'

printf '\n=== VALIDATION ET REGRESSION COMPLETE DU MOTEUR ===\n'
COMPOSER_ALLOW_SUPERUSER=1 composer validate --strict --no-check-publish
php artisan optimize:clear --no-ansi
php artisan migrate:fresh --force --no-ansi
./vendor/bin/phpunit --configuration phpunit.engine-runtime.xml

printf '\n=== CONTRATS OPERATIONNELS STAGE D ===\n'
./vendor/bin/phpunit --configuration phpunit.engine-runtime-stage-d.xml
php artisan migrate:fresh --force --no-ansi

printf '\n=== READINESS NEGATIVE SANS SCHEDULER ===\n'
if php artisan ops:readiness --json; then
    fail "la readiness accepte le trafic sans heartbeat scheduler"
fi

printf '\n=== READINESS POSITIVE APRES HEARTBEAT DIRECT ===\n'
php artisan ops:scheduler-heartbeat --source=stage-d-direct --no-ansi
php artisan ops:readiness --json

printf '\n=== PREUVE DU DECLENCHEUR SYSTEME REEL ===\n'
php artisan ops:scheduler-heartbeat --source=stage-d-awaiting-systemd --no-ansi
# Les étapes de test précédentes tournent volontairement sous root. La preuve systemd doit, elle,
# reproduire l’utilisateur PHP-FPM et disposer des mêmes droits minimaux sur les seuls chemins
# d’écriture Laravel.
chown -R www-data:www-data storage bootstrap/cache
# Composer a été installé avec umask 027 dans ce clone historique. On conserve root comme
# propriétaire, mais on donne au groupe PHP la lecture/traversée nécessaires à l'autoloader.
chown -R root:www-data vendor
chmod -R g+rX vendor
systemd_unit="dgafrique-engine-stage-d-${stamp,,}"
systemd_unit="${systemd_unit//:/-}"

systemd-run \
    --unit="$systemd_unit" \
    --on-active=3s \
    --collect \
    --uid=www-data \
    --gid=www-data \
    --working-directory="$EXPECTED_ROOT" \
    /usr/bin/php artisan schedule:run --no-ansi

scheduler_source=""
for _attempt in $(seq 1 30); do
    scheduler_source="$(runuser -u postgres -- psql -X -Atq -d "$DB_NAME" -c \
        "SELECT source FROM dg_operational_heartbeats WHERE name='scheduler';" 2>/dev/null || true)"
    if test "$scheduler_source" = 'laravel-scheduler'; then
        break
    fi
    sleep 1
done

journalctl -u "${systemd_unit}.service" --no-pager -n 80 || true
[[ "$scheduler_source" == 'laravel-scheduler' ]] || fail "le timer systemd n’a pas déclenché Laravel"
printf 'scheduler_source=%s\n' "$scheduler_source"
php artisan ops:readiness --json

printf '\n=== SAUVEGARDE POSTGRESQL ISOLEE ===\n'
sentinel="stage-d-backup-${stamp,,}"
php artisan ops:scheduler-heartbeat --source="$sentinel" --no-ansi
runuser -u postgres -- pg_dump --format=custom --no-owner --no-acl "$DB_NAME" > "$dump_path"
test -s "$dump_path" || fail "dump PostgreSQL vide"
dump_sha256="$(sha256sum "$dump_path" | awk '{print $1}')"
source_migrations="$(runuser -u postgres -- psql -X -Atq -d "$DB_NAME" -c 'SELECT count(*) FROM migrations;')"
source_tables="$(runuser -u postgres -- psql -X -Atq -d "$DB_NAME" -c \
    "SELECT count(*) FROM pg_tables WHERE schemaname='public';")"
printf 'dump=%s\n' "$dump_path"
printf 'dump_sha256=%s\n' "$dump_sha256"
printf 'migrations_source=%s\n' "$source_migrations"
printf 'tables_source=%s\n' "$source_tables"

printf '\n=== RESTAURATION ET INTEGRITE ===\n'
runuser -u postgres -- createdb --owner="$DB_ROLE" "$RESTORE_DB"
restore_created=1
runuser -u postgres -- pg_restore --exit-on-error --no-owner --no-acl --role="$DB_ROLE" \
    --dbname="$RESTORE_DB" < "$dump_path"

restored_migrations="$(runuser -u postgres -- psql -X -Atq -d "$RESTORE_DB" -c 'SELECT count(*) FROM migrations;')"
restored_tables="$(runuser -u postgres -- psql -X -Atq -d "$RESTORE_DB" -c \
    "SELECT count(*) FROM pg_tables WHERE schemaname='public';")"
restored_sentinel="$(runuser -u postgres -- psql -X -Atq -d "$RESTORE_DB" -c \
    "SELECT source FROM dg_operational_heartbeats WHERE name='scheduler';")"
[[ "$restored_migrations" == "$source_migrations" ]] || fail "nombre de migrations restauré différent"
[[ "$restored_tables" == "$source_tables" ]] || fail "nombre de tables restauré différent"
[[ "$restored_sentinel" == "$sentinel" ]] || fail "sentinelle absente de la restauration"
DB_DATABASE="$RESTORE_DB" php artisan ops:readiness --json
printf 'restauration_integrite=PASS\n'

printf '\n=== ROLLBACK DE MIGRATION SUR BASE RESTAUREE ===\n'
DB_DATABASE="$RESTORE_DB" php artisan migrate:rollback --step=1 --force --no-ansi
heartbeat_after_rollback="$(runuser -u postgres -- psql -X -Atq -d "$RESTORE_DB" -c \
    "SELECT to_regclass('public.dg_operational_heartbeats') IS NULL;")"
[[ "$heartbeat_after_rollback" == 't' ]] || fail "la dernière migration n’a pas été retirée"
DB_DATABASE="$RESTORE_DB" php artisan migrate --force --no-ansi
DB_DATABASE="$RESTORE_DB" php artisan ops:scheduler-heartbeat --source=stage-d-restored --no-ansi
DB_DATABASE="$RESTORE_DB" php artisan ops:readiness --json
reapplied_migrations="$(runuser -u postgres -- psql -X -Atq -d "$RESTORE_DB" -c 'SELECT count(*) FROM migrations;')"
[[ "$reapplied_migrations" == "$source_migrations" ]] || fail "migration non réappliquée après rollback"
printf 'rollback_migration=PASS\n'

printf '\n=== BASCULE ET ROLLBACK ATOMIQUES DE RELEASE ===\n'
release_test_root="${RELEASE_TEST_PREFIX}${stamp}"
releases_root="${release_test_root}/releases"
current_link="${release_test_root}/current"
previous_revision="$(git rev-parse HEAD^)"
candidate_revision="$(git rev-parse HEAD)"
mkdir -p "${releases_root}/previous" "${releases_root}/candidate"
printf '%s\n' "$previous_revision" > "${releases_root}/previous/REVISION"
printf '%s\n' "$candidate_revision" > "${releases_root}/candidate/REVISION"

scripts/deployment/switch-current-release.sh "$releases_root" "$current_link" previous NONE
scripts/deployment/switch-current-release.sh "$releases_root" "$current_link" candidate previous

if scripts/deployment/switch-current-release.sh "$releases_root" "$current_link" previous previous; then
    fail "la protection d’état attendu a accepté une bascule concurrente"
fi
[[ "$(realpath "$current_link")" == "$(realpath "${releases_root}/candidate")" ]] \
    || fail "le refus concurrent a altéré la release active"

scripts/deployment/switch-current-release.sh "$releases_root" "$current_link" previous candidate
active_revision="$(tr -d '\r\n' < "${current_link}/REVISION")"
[[ "$active_revision" == "$previous_revision" ]] || fail "rollback de release non vérifié"
printf 'rollback_release=PASS\n'
printf 'revision_restaurée=%s\n' "$active_revision"

printf '\n=== OBSERVABILITE FINALE ===\n'
php artisan ops:scheduler-heartbeat --source=stage-d-final --no-ansi
php artisan ops:readiness --json
schedule_output="$(php artisan schedule:list --no-ansi)"
printf '%s\n' "$schedule_output"
grep -Fq 'ops:scheduler-heartbeat' <<< "$schedule_output" || fail "heartbeat absent du scheduler"
grep -Fq 'payments:reconcile-pending-external' <<< "$schedule_output" || fail "réconciliation absente du scheduler"

printf '\n=== RESULTAT ===\n'
printf 'ENGINE-RUNTIME-001 STAGE D: PASS\n'
printf 'scheduler_systeme=PASS\n'
printf 'readiness=PASS\n'
printf 'backup_restore=PASS\n'
printf 'rollback_migration=PASS\n'
printf 'rollback_release=PASS\n'
printf 'dump=%s\n' "$dump_path"
printf 'dump_sha256=%s\n' "$dump_sha256"
printf 'rapport=%s\n' "$report"
