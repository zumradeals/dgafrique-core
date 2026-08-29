#!/usr/bin/env bash

set -Eeuo pipefail
set -o pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-engine-runtime-001"
readonly DB_NAME="dgafrique_engine_runtime_001"
readonly DB_ROLE="dgafrique_engine_runtime"

fail() {
    printf 'ENGINE-TRUTH-FINAL-001 REFUS: %s\n' "$*" >&2
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
for command_name in git php composer psql runuser tee find grep awk sort; do
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
[[ "$(env_value ZUMRA_PAYMENT_ENABLED)" == "false" ]] || fail "paiement externe actif"
[[ "$(env_value GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED)" == "false" ]] || fail "activation sandbox active"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "fichiers suivis modifiés"

db_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_database WHERE datname='${DB_NAME}'")"
role_exists="$(runuser -u postgres -- psql -Atqc "SELECT count(*) FROM pg_roles WHERE rolname='${DB_ROLE}'")"
[[ "$db_exists" == "1" ]] || fail "base runtime absente"
[[ "$role_exists" == "1" ]] || fail "rôle runtime absent"

mkdir -p storage/logs/engine-runtime
report="storage/logs/engine-runtime/$(date -u +%Y%m%dT%H%M%SZ)-engine-truth-final.log"
exec > >(tee -a "$report") 2>&1

printf '=== ENGINE-TRUTH-FINAL-001 ===\n'
printf 'date_utc=%s\n' "$(date -u +%FT%TZ)"
printf 'commit=%s\n' "$(git rev-parse HEAD)"
printf 'php=%s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'postgres=%s\n' "$(psql --version)"

printf '\n=== VERITE DES SEEDERS ===\n'
mapfile -t seeder_files < <(find database/seeders -maxdepth 1 -type f -name '*.php' -printf '%f\n' | sort)
[[ "${#seeder_files[@]}" == "1" ]] || fail "le répertoire des seeders doit contenir exactement un fichier"
[[ "${seeder_files[0]}" == "DatabaseSeeder.php" ]] || fail "seeder inattendu: ${seeder_files[0]}"
[[ -z "$(find database/seeders -maxdepth 1 -type f -name '*DemoSeeder.php' -print -quit)" ]] \
    || fail "un DemoSeeder existe encore"
printf 'seeders=DatabaseSeeder.php uniquement\n'

printf '\n=== PERIMETRE CAP V1 ===\n'
read -r total closed partial doc_only dependency_blocked not_implemented < <(
    awk -F'|' '
        /^\| CAP-[0-9][0-9][0-9] / {
            status=$4;
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", status);
            if (status == "CLOSED" || status == "PARTIAL" || status == "DOC_ONLY" || status == "DEPENDENCY_BLOCKED" || status == "NOT_IMPLEMENTED") {
                total++;
                counts[status]++;
            }
        }
        END {
            print total+0, counts["CLOSED"]+0, counts["PARTIAL"]+0, counts["DOC_ONLY"]+0, counts["DEPENDENCY_BLOCKED"]+0, counts["NOT_IMPLEMENTED"]+0;
        }
    ' docs/capacites/CAPABILITY-COVERAGE.md
)
[[ "$total" == "84" ]] || fail "registre CAP incomplet: $total"
[[ "$closed $partial $doc_only $dependency_blocked $not_implemented" == "60 5 15 2 2" ]] \
    || fail "distribution CAP inattendue: $closed/$partial/$doc_only/$dependency_blocked/$not_implemented"
awk -F'|' '
    /^\| CAP-/ {
        id=$2;
        gsub(/^[[:space:]]+|[[:space:]]+$/, "", id);
        count++;
        if (id != sprintf("CAP-%03d", count) || seen[id]++) bad=1;
    }
    END { if (count != 84) bad=1; exit bad }
' docs/capacites/CAPABILITY-COVERAGE.md || fail "séquence CAP invalide"
grep -Fq 'Total classé : **84 / 84**' docs/capacites/CAPABILITY-COVERAGE.md \
    || fail "contrat de périmètre V1 absent"
printf 'total=%s closed=%s partial=%s doc_only=%s dependency_blocked=%s not_implemented=%s\n' \
    "$total" "$closed" "$partial" "$doc_only" "$dependency_blocked" "$not_implemented"

printf '\n=== DEPENDANCES ===\n'
COMPOSER_ALLOW_SUPERUSER=1 composer validate --strict
COMPOSER_ALLOW_SUPERUSER=1 composer audit --locked
COMPOSER_ALLOW_SUPERUSER=1 composer check-platform-reqs

printf '\n=== MIGRATIONS POSTGRESQL PROPRES ===\n'
php artisan optimize:clear --no-ansi
php artisan migrate:fresh --force --no-ansi
php artisan migrate:status --no-ansi
php artisan db:seed --force --no-ansi

printf '\n=== PORTE PRODUCTION-TRUTH-002 ===\n'
./vendor/bin/phpunit --configuration phpunit.engine-runtime.xml --filter ProductionTruthTest

printf '\n=== SUITE MOTEUR COMPLETE ===\n'
./vendor/bin/phpunit --configuration phpunit.engine-runtime.xml

printf '\n=== RESULTAT ===\n'
printf 'ENGINE-TRUTH-FINAL-001: PASS\n'
printf 'frontend_deletion=GO\n'
printf 'new_frontend=GO\n'
printf 'production_launch=NO_GO\n'
printf 'rapport=%s\n' "$report"
