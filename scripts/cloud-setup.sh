#!/bin/bash
# Setup script for the Sudo Hire cloud environment.
# Paste it into the environment's setup script. It runs before the repository
# is cloned, so it must not depend on the repository.
#
# The environment also needs:
#   - variables GIT_AUTHOR_NAME, GIT_AUTHOR_EMAIL, GIT_COMMITTER_NAME,
#     GIT_COMMITTER_EMAIL and COMPOSER_ALLOW_SUPERUSER=1
#   - a GitHub network secret, so Composer can download packages
#
# CLOUD_SETUP_HOMES overrides the home folders (used by the tests).

for d in ${CLOUD_SETUP_HOMES:-/root /home/claude}; do
  [ -d "$d" ] || continue
  mkdir -p "$d/.claude"
  f="$d/.claude/settings.json"

  # No AI attribution in commits or pull requests (AGENTS.md hard rule 6).
  if [ -s "$f" ]; then
    echo "setup: $f already existed, merging"
    if command -v jq >/dev/null; then
      jq '.attribution={"commit":"","pr":"","commitTrailers":false,"sessionUrl":false}' "$f" > "$f.tmp" && mv "$f.tmp" "$f"
    else
      echo "setup: jq not available, leaving $f alone"
    fi
  else
    echo "setup: $f absent, creating"
    printf '%s\n' '{"attribution":{"commit":"","pr":"","commitTrailers":false,"sessionUrl":false}}' > "$f"
  fi

  # Commits in the container are not signed. The variables may be empty
  # here: git also reads them directly inside the session.
  git config --file "$d/.gitconfig" commit.gpgsign false
  if [ -n "${GIT_AUTHOR_NAME:-}" ]; then
    git config --file "$d/.gitconfig" user.name "$GIT_AUTHOR_NAME"
  fi
  if [ -n "${GIT_AUTHOR_EMAIL:-}" ]; then
    git config --file "$d/.gitconfig" user.email "$GIT_AUTHOR_EMAIL"
  fi
done

exit 0
