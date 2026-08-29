#!/usr/bin/env bash

set -Eeuo pipefail
set -o pipefail
umask 027

readonly EXPECTED_ROOT="/var/www/dgafrique-core"
readonly CERTIFIED_ENGINE_COMMIT="cdedc4c589f037fd4b272e1e3c6bfe36389bc9d1"

fail() {
    printf 'FRONTEND-EXCISION-001 REFUS: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "commande absente: $1"
}

[[ "$(id -u)" == "0" ]] || fail "la vérification doit être exécutée par root"
for command_name in git php composer find; do
    require_command "$command_name"
done

repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || fail "dépôt Git introuvable"
[[ "$repo_root" == "$EXPECTED_ROOT" ]] || fail "chemin inattendu: $repo_root"
cd "$repo_root"

[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "fichiers suivis modifiés"
git merge-base --is-ancestor "$CERTIFIED_ENGINE_COMMIT" HEAD \
    || fail "le moteur certifié n'est pas ancêtre du commit déployé"
git diff --quiet "$CERTIFIED_ENGINE_COMMIT" HEAD -- app bootstrap config database routes \
    || fail "un chemin protégé du moteur diffère du certificat"

for path in resources/views resources/css resources/js resources/design-reference public/images; do
    if [[ -d "$path" ]] && [[ -n "$(find "$path" -type f -print -quit)" ]]; then
        fail "présentation historique encore présente sous $path"
    fi
done

for path in public/favicon.ico package.json package-lock.json vite.config.js; do
    [[ ! -e "$path" ]] || fail "câblage frontend encore présent: $path"
done

[[ -f docs/brand/BRAND-DOCTRINE-001.md ]] || fail "doctrine de marque absente"
[[ -f docs/brand/tokens.json ]] || fail "tokens de marque absents"
[[ -f docs/brand/assets/gamad-logo-source.jpg ]] || fail "logo source absent"
[[ -f storage/framework/down ]] || fail "le site doit rester en maintenance"

printf '=== FRONTEND-EXCISION-001 ===\n'
printf 'commit=%s\n' "$(git rev-parse HEAD)"
printf 'certified_engine=%s\n' "$CERTIFIED_ENGINE_COMMIT"
printf 'frontend_files=0\n'
printf 'protected_engine_diff=0\n'
printf 'maintenance=on\n'

COMPOSER_ALLOW_SUPERUSER=1 composer validate --strict
php artisan about --no-ansi
php artisan route:list --no-ansi >/dev/null
php artisan migrate:status --no-ansi >/dev/null

printf 'FRONTEND-EXCISION-001: PASS\n'

