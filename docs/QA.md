# Manual QA

Location: `docs/QA.md` (committed).

Checks that tests cannot cover: menu bar, notifications, windows, timing, permissions, signing. A human runs them on a Mac before merging the pull request that added them.

Add entries in date order. Do not delete entries: mark them passed or failed, with the date.

## Template

```
## Mx: <short title>

- Added: YYYY-MM-DD, branch <branch>
- Why manual: <what tests cannot see>
- Setup: <state before the check, e.g. "fresh install, no settings">
- Steps:
  1. ...
  2. ...
- Expected: <what the human must see>
- Result: not run | passed YYYY-MM-DD | failed YYYY-MM-DD (<short note>)
```

## Before every merge (Mac)

1. Check out the branch.
2. `npm run build`, then `php artisan native:run`.
3. Run every entry for this milestone with result "not run".
4. Update each result.
5. If one fails, send the failure back to the session that made the change.

## Entries

## M1: App starts as a menu bar icon

- Added: 2026-10-28, branch m1/scaffold
- Why manual: tests only fake the NativePHP client. They cannot see the macOS menu bar, the dock or a window.
- Setup: fresh checkout, `composer install`, `npm ci`, `cp .env.example .env`, `php artisan key:generate`.
- Steps:
  1. `npm run build`
  2. `php artisan native:run`
  3. Look at the menu bar, the dock and the screen.
  4. Hover the new menu bar icon.
  5. Click the icon.
  6. Click somewhere else on the screen.
- Expected: no window opens on start. No Sudo Hire icon in the dock. A new icon in the menu bar shows the tooltip "Sudo Hire". The click opens a small panel (about 400 x 600) with the text "Sudo Hire". The panel closes when you click elsewhere.
- Result: passed 2026-10-28

## M1: Cloud setup script in a real cloud environment

- Added: 2026-10-28, branch m1/scaffold
- Why manual: the script was tested here in a scratch clone with a fake home folder, not in the real cloud container.
- Setup: a cloud environment for this repository with `scripts/cloud-setup.sh` pasted into its setup script.
- Steps:
  1. Start a new cloud session on this repository.
  2. Run `git config commit.gpgsign`, `echo $COMPOSER_ALLOW_SUPERUSER` and `ls vendor node_modules | head -2`.
  3. Run `grep -c COMPOSER_ALLOW_SUPERUSER ~/.bashrc`.
- Expected: `false`, `1`, both folders exist, and the count is `1` (the line is not added twice).
- Result: failed 2026-10-29 (script exits before the clone; replaced by an environment setup script)
