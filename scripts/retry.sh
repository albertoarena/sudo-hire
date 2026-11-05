#!/usr/bin/env bash
# Runs a command up to <count> times and stops at the first success.
# Usage: scripts/retry.sh <count> <command> [arguments...]
# The release workflow uses it for `xcrun stapler`, which sometimes fails on macOS runners
# with error 68 ("The network connection was lost") after Apple accepted the notarisation.
# It waits RETRY_PAUSE_SECONDS (default 15) between two attempts. It returns the exit code of the last attempt.
set -uo pipefail

count="${1:-}"
if ! [[ "$count" =~ ^[1-9][0-9]*$ ]] || [ "$#" -lt 2 ]; then
    echo "Usage: scripts/retry.sh <count> <command> [arguments...]" >&2
    exit 2
fi
shift

pause="${RETRY_PAUSE_SECONDS:-15}"
code=0

for ((attempt = 1; attempt <= count; attempt++)); do
    "$@"
    code=$?
    if [ "$code" -eq 0 ]; then
        exit 0
    fi
    echo "Attempt $attempt of $count failed (exit code $code): $*" >&2
    if [ "$attempt" -lt "$count" ]; then
        sleep "$pause"
    fi
done

exit "$code"
