#!/usr/bin/env bash
# Setup script for a Sudo Hire cloud environment.
# Paste it into the environment's setup script. It does nothing in other repositories.
set -uo pipefail

repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0
remote="$(git -C "$repo_root" remote get-url origin 2>/dev/null || true)"

case "$remote" in
    */sudo-hire|*/sudo-hire.git) ;;
    *) exit 0 ;;
esac

cd "$repo_root" || exit 0

# Commits in the container are not signed.
git config commit.gpgsign false

# The container runs as root. Keep the variable for later shells too.
export COMPOSER_ALLOW_SUPERUSER=1
line='export COMPOSER_ALLOW_SUPERUSER=1'
for rc in "$HOME/.bashrc" "$HOME/.profile"; do
    touch "$rc"
    grep -qxF "$line" "$rc" || printf '%s\n' "$line" >> "$rc"
done

# Warm the caches. The container may have PHP 8.3: CI proves 8.4.
if [ -f composer.json ]; then
    composer install --no-interaction --no-progress --ignore-platform-req=php \
        || echo "cloud-setup: composer install failed" >&2

    if [ -f package-lock.json ]; then
        npm ci || echo "cloud-setup: npm ci failed" >&2
    fi
fi

exit 0
