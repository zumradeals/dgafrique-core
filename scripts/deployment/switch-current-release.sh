#!/usr/bin/env bash

set -Eeuo pipefail

usage() {
    printf 'Usage: %s RELEASES_ROOT CURRENT_LINK TARGET_RELEASE EXPECTED_CURRENT\n' "$0" >&2
    printf 'EXPECTED_CURRENT vaut NONE lors de la toute première activation.\n' >&2
    exit 64
}

if test "$#" -ne 4; then
    usage
fi

releases_root="$1"
current_link="$2"
target_release="$3"
expected_current="$4"

case "$releases_root" in
    /*) ;;
    *) printf 'REFUS : RELEASES_ROOT doit être absolu.\n' >&2; exit 65 ;;
esac

case "$current_link" in
    /*) ;;
    *) printf 'REFUS : CURRENT_LINK doit être absolu.\n' >&2; exit 65 ;;
esac

valid_release_name() {
    test -n "$1" && test "${#1}" -le 120 && [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]]
}

if ! valid_release_name "$target_release"; then
    printf 'REFUS : nom de release cible invalide.\n' >&2
    exit 65
fi

if test "$expected_current" != 'NONE' && ! valid_release_name "$expected_current"; then
    printf 'REFUS : release courante attendue invalide.\n' >&2
    exit 65
fi

if test ! -d "$releases_root"; then
    printf 'REFUS : racine de releases absente.\n' >&2
    exit 66
fi

canonical_root="$(realpath "$releases_root")"
target_path="${canonical_root}/${target_release}"

if test ! -d "$target_path" || test ! -f "$target_path/REVISION"; then
    printf 'REFUS : release cible incomplète (répertoire ou REVISION absent).\n' >&2
    exit 66
fi

canonical_target="$(realpath "$target_path")"
if test "$canonical_target" != "$target_path"; then
    printf 'REFUS : la release cible sort de la racine autorisée.\n' >&2
    exit 65
fi

current_parent="$(dirname "$current_link")"
if test ! -d "$current_parent"; then
    printf 'REFUS : parent du lien courant absent.\n' >&2
    exit 66
fi

if test -e "$current_link" && test ! -L "$current_link"; then
    printf 'REFUS : CURRENT_LINK existe mais n’est pas un lien symbolique.\n' >&2
    exit 65
fi

if test "$expected_current" = 'NONE'; then
    if test -L "$current_link"; then
        printf 'REFUS : une release courante existe déjà.\n' >&2
        exit 65
    fi
else
    expected_path="${canonical_root}/${expected_current}"
    if test ! -L "$current_link" || test "$(realpath "$current_link")" != "$expected_path"; then
        printf 'REFUS : la release courante ne correspond pas à l’état attendu.\n' >&2
        exit 65
    fi
fi

temporary_link="${current_link}.next.$$"
cleanup() {
    if test -L "$temporary_link"; then
        unlink "$temporary_link"
    fi
}
trap cleanup EXIT

ln -s "$canonical_target" "$temporary_link"
mv -Tf "$temporary_link" "$current_link"

if test "$(realpath "$current_link")" != "$canonical_target"; then
    printf 'ERREUR : vérification de la bascule atomique impossible.\n' >&2
    exit 70
fi

printf 'release_active=%s\n' "$target_release"
printf 'revision=%s\n' "$(tr -d '\r\n' < "$canonical_target/REVISION")"
