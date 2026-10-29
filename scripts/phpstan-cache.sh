#!/bin/bash
# Puts the locked PHPStan release into Composer's cache, so `composer install`
# works where GitHub refuses zip downloads (cloud sessions).
#
# PHPStan has no git source in composer.lock, only a GitHub zip. This script
# clones the locked tag (one commit, no history) and zips it under the name
# Composer looks for: <cache-files-dir>/phpstan/phpstan/<sha1 of dist url>.zip
#
# Run it from anywhere, then run `composer install`.
# PHPSTAN_GIT_URL overrides the repository (used by the tests).

set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"

fail() {
    echo "phpstan-cache: $1" >&2
    exit 1
}

read -r version url < <(php -r '
    $lock = json_decode(file_get_contents($argv[1]), true);
    foreach ($lock["packages-dev"] ?? [] as $p) {
        if ($p["name"] === "phpstan/phpstan") { echo $p["version"], " ", $p["dist"]["url"], "\n"; exit; }
    }
' "$root/composer.lock") || true

[ -n "${version:-}" ] && [ -n "${url:-}" ] || fail "phpstan/phpstan not found in composer.lock"

cache_dir="$(cd "$root" && composer config cache-files-dir 2>/dev/null)" || fail "cannot read the Composer cache folder"
target="$cache_dir/phpstan/phpstan/$(printf '%s' "$url" | php -r 'echo sha1(stream_get_contents(STDIN));').zip"

if [ -s "$target" ]; then
    echo "phpstan-cache: PHPStan $version is already in the cache"
    exit 0
fi

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$version" "${PHPSTAN_GIT_URL:-https://github.com/phpstan/phpstan}" "$work/phpstan" \
    || fail "cannot clone the tag $version"
rm -rf "$work/phpstan/.git"

mkdir -p "$(dirname "$target")"
(cd "$work" && zip -qr "$target" phpstan) || fail "cannot write $target"

echo "phpstan-cache: PHPStan $version is in the cache. Run composer install."
